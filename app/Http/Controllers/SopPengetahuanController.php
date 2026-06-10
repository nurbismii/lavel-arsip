<?php

namespace App\Http\Controllers;

use App\Models\AlurKerja;
use App\Models\AlurKerjaTahap;
use App\Models\SopPengetahuan;
use App\Models\SopPengetahuanLampiran;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SopPengetahuanController extends Controller
{
    public function index()
    {
        $search = trim((string) request('search', ''));
        $status = $this->resolveFilter(request('status'), SopPengetahuan::statusOptions());
        $alurKerjaId = $this->resolveAlurKerjaFilter(request('alur_kerja_id'));

        $query = SopPengetahuan::query()
            ->visibleTo(auth()->user())
            ->with(['team', 'alurKerja', 'tahap', 'pemilik'])
            ->withCount('lampirans')
            ->latest();

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('judul', 'like', '%' . $search . '%')
                    ->orWhere('kode', 'like', '%' . $search . '%')
                    ->orWhere('nomor_revisi', 'like', '%' . $search . '%')
                    ->orWhere('ringkasan', 'like', '%' . $search . '%')
                    ->orWhere('tujuan', 'like', '%' . $search . '%')
                    ->orWhere('ruang_lingkup', 'like', '%' . $search . '%')
                    ->orWhere('konten', 'like', '%' . $search . '%')
                    ->orWhere('kata_kunci', 'like', '%' . $search . '%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($alurKerjaId) {
            $query->where('alur_kerja_id', $alurKerjaId);
        }

        $sopPengetahuans = $query->paginate(10)->withQueryString();

        return view('sop_pengetahuan.index', [
            'sopPengetahuans' => $sopPengetahuans,
            'search' => $search,
            'status' => $status,
            'alurKerjaId' => $alurKerjaId,
            'statusOptions' => SopPengetahuan::statusOptions(),
            'alurKerjas' => $this->availableAlurKerjasForCurrentUser(),
        ]);
    }

    public function create()
    {
        return view('sop_pengetahuan.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $sopPengetahuan = SopPengetahuan::create($data);
        $this->simpanLampiran($sopPengetahuan, $request->file('lampiran', []));

        ActivityLogService::log(
            'sop_pengetahuan.create',
            'Menambahkan SOP.',
            $sopPengetahuan
        );

        return redirect()
            ->route('sop-pengetahuan.show', $sopPengetahuan->id)
            ->with('success', 'SOP berhasil ditambahkan.');
    }

    public function show(SopPengetahuan $sopPengetahuan)
    {
        $this->pastikanBisaDilihat($sopPengetahuan);

        $sopPengetahuan->load(['team', 'alurKerja', 'tahap', 'pemilik', 'lampirans']);

        return view('sop_pengetahuan.show', [
            'sopPengetahuan' => $sopPengetahuan,
            'canManage' => $this->canManage($sopPengetahuan),
            'simbolOptions' => SopPengetahuan::simbolOptions(),
        ]);
    }

    public function edit(SopPengetahuan $sopPengetahuan)
    {
        $this->pastikanBisaDiatur($sopPengetahuan);

        return view('sop_pengetahuan.edit', array_merge($this->formData(), [
            'sopPengetahuan' => $sopPengetahuan,
        ]));
    }

    public function update(Request $request, SopPengetahuan $sopPengetahuan)
    {
        $this->pastikanBisaDiatur($sopPengetahuan);

        $data = $this->validatedData($request, $sopPengetahuan);

        $sopPengetahuan->update($data);
        $this->simpanLampiran($sopPengetahuan, $request->file('lampiran', []));

        ActivityLogService::log(
            'sop_pengetahuan.update',
            'Memperbarui SOP.',
            $sopPengetahuan
        );

        return redirect()
            ->route('sop-pengetahuan.show', $sopPengetahuan->id)
            ->with('success', 'SOP berhasil diperbarui.');
    }

    public function destroy(SopPengetahuan $sopPengetahuan)
    {
        $this->pastikanBisaDiatur($sopPengetahuan);

        $sopPengetahuan->load('lampirans');

        foreach ($sopPengetahuan->lampirans as $lampiran) {
            $this->hapusFileLampiran($lampiran);
            $lampiran->delete();
        }

        $judul = $sopPengetahuan->judul;
        $sopPengetahuan->delete();

        ActivityLogService::log(
            'sop_pengetahuan.delete',
            'Menghapus SOP.',
            (object) ['id' => $sopPengetahuan->id, 'judul' => $judul]
        );

        return redirect()
            ->route('sop-pengetahuan.index')
            ->with('success', 'SOP berhasil dihapus.');
    }

    public function showLampiran(SopPengetahuan $sopPengetahuan, SopPengetahuanLampiran $lampiran)
    {
        $this->pastikanLampiranMilikSop($sopPengetahuan, $lampiran);
        $this->pastikanBisaDilihat($sopPengetahuan);

        if (!$lampiran->path || !Storage::disk($lampiran->storage_disk)->exists($lampiran->path)) {
            abort(404, 'Lampiran SOP tidak ditemukan.');
        }

        if ($lampiran->storage_disk === 'r2') {
            return redirect()->away(Storage::disk('r2')->temporaryUrl(
                $lampiran->path,
                now()->addMinutes(5),
                [
                    'ResponseContentDisposition' => 'inline; filename="' . addslashes($lampiran->nama_file) . '"',
                ]
            ));
        }

        return response()->file(Storage::disk('local')->path($lampiran->path), [
            'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
        ]);
    }

    public function destroyLampiran(SopPengetahuan $sopPengetahuan, SopPengetahuanLampiran $lampiran)
    {
        $this->pastikanLampiranMilikSop($sopPengetahuan, $lampiran);
        $this->pastikanBisaDiatur($sopPengetahuan);

        $this->hapusFileLampiran($lampiran);
        $lampiran->delete();

        ActivityLogService::log(
            'sop_pengetahuan.lampiran.delete',
            'Menghapus lampiran SOP.',
            $lampiran
        );

        return redirect()
            ->route('sop-pengetahuan.show', $sopPengetahuan->id)
            ->with('success', 'Lampiran berhasil dihapus.');
    }

    public function uploadEditorImage(Request $request)
    {
        $data = $request->validate([
            'diagram_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $data['diagram_image'];
        $directory = public_path('uploads/sop-diagrams');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'diagram-sop';
        $fileName = now()->format('YmdHis') . '-' . Str::random(8) . '-' . $baseName . '.' . $file->getClientOriginalExtension();

        $file->move($directory, $fileName);

        return response()->json([
            'url' => asset('uploads/sop-diagrams/' . $fileName),
            'name' => $file->getClientOriginalName(),
        ]);
    }

    private function validatedData(Request $request, SopPengetahuan $sopPengetahuan = null): array
    {
        $sopPengetahuanId = $sopPengetahuan ? $sopPengetahuan->id : null;

        $data = $request->validate([
            'kode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('sop_pengetahuan', 'kode')->ignore($sopPengetahuanId),
            ],
            'nomor_revisi' => ['nullable', 'string', 'max:50'],
            'judul' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'tujuan' => ['nullable', 'string'],
            'ruang_lingkup' => ['nullable', 'string'],
            'definisi' => ['nullable', 'array'],
            'definisi.*.istilah' => ['nullable', 'string', 'max:255'],
            'definisi.*.penjelasan' => ['nullable', 'string'],
            'prosedur' => ['nullable', 'array'],
            'prosedur.*.urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'prosedur.*.simbol' => ['nullable', Rule::in(array_keys(SopPengetahuan::simbolOptions()))],
            'prosedur.*.pelaksana' => ['nullable', 'string', 'max:255'],
            'prosedur.*.aktivitas' => ['nullable', 'string'],
            'prosedur.*.dokumen' => ['nullable', 'string'],
            'prosedur.*.keterangan' => ['nullable', 'string'],
            'prosedur_flowchart' => ['nullable', 'string'],
            'daftar_lampiran' => ['nullable', 'array'],
            'daftar_lampiran.*.nama' => ['nullable', 'string', 'max:255'],
            'daftar_lampiran.*.keterangan' => ['nullable', 'string'],
            'catatan_revisi' => ['nullable', 'array'],
            'catatan_revisi.*.no_revisi' => ['nullable', 'string', 'max:50'],
            'catatan_revisi.*.tanggal_revisi' => ['nullable', 'date'],
            'catatan_revisi.*.deskripsi_perubahan' => ['nullable', 'string'],
            'catatan_revisi.*.alasan_revisi' => ['nullable', 'string'],
            'catatan_revisi.*.pihak_merevisi' => ['nullable', 'string', 'max:255'],
            'konten' => ['nullable', 'string'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'alur_kerja_id' => ['nullable', 'integer', 'exists:alur_kerja,id'],
            'alur_kerja_tahap_id' => ['nullable', 'integer', 'exists:alur_kerja_tahap,id'],
            'pemilik_user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(array_keys(SopPengetahuan::statusOptions()))],
            'tingkat_kepentingan' => ['required', Rule::in(array_keys(SopPengetahuan::prioritasOptions()))],
            'tanggal_berlaku' => ['nullable', 'date'],
            'target_tinjauan_berikutnya' => ['nullable', 'date'],
            'kata_kunci' => ['nullable', 'string', 'max:500'],
            'lampiran.*' => ['nullable', 'file', 'max:20480'],
        ]);

        $data = RichText::sanitizeFields($data, ['ringkasan', 'tujuan', 'ruang_lingkup', 'konten']);
        $data['definisi'] = $this->normalizedDefinisiRows((array) ($data['definisi'] ?? []));
        $data['prosedur'] = $this->normalizedProsedurRows((array) ($data['prosedur'] ?? []));
        $flowchart = $this->normalizedFlowchartPayload((string) ($data['prosedur_flowchart'] ?? ''));

        if (!empty($flowchart['nodes'])) {
            $data['prosedur'] = ['flowchart' => $flowchart];
        }

        unset($data['prosedur_flowchart']);

        $data['daftar_lampiran'] = $this->normalizedLampiranRows((array) ($data['daftar_lampiran'] ?? []));
        $data['catatan_revisi'] = $this->normalizedRevisiRows((array) ($data['catatan_revisi'] ?? []));
        $data['jenis'] = SopPengetahuan::JENIS_SOP;
        $data['alur_kerja_id'] = $this->resolveAllowedAlurKerjaId($data['alur_kerja_id'] ?? null);
        $data['alur_kerja_tahap_id'] = $this->resolveAllowedAlurKerjaTahapId($data['alur_kerja_tahap_id'] ?? null, $data['alur_kerja_id']);

        $messages = [];
        $hasStructuredSop = filled($data['tujuan'] ?? null)
            && filled($data['ruang_lingkup'] ?? null)
            && !empty($data['prosedur']);

        if (!filled($data['konten'] ?? null) && !$hasStructuredSop) {
            $messages['konten'] = 'Isi dokumen SOP wajib diisi pada editor utama.';
        }

        if (!empty($messages)) {
            throw ValidationException::withMessages($messages);
        }

        if (!auth()->user()->canAccessAllFiles()) {
            $allowedTeamIds = auth()->user()->assignedTeamIds();

            if (!empty($data['team_id']) && !in_array((int) $data['team_id'], $allowedTeamIds, true)) {
                abort(403, 'Anda tidak memiliki izin memakai tim/divisi ini.');
            }

            $data['pemilik_user_id'] = auth()->id();
        }

        return $data;
    }

    private function normalizedDefinisiRows(array $rows): array
    {
        $validRows = [];

        foreach ($rows as $row) {
            $row = RichText::sanitizeFields((array) $row, ['penjelasan']);
            $istilah = trim((string) ($row['istilah'] ?? ''));
            $penjelasan = trim((string) ($row['penjelasan'] ?? ''));

            if ($istilah === '' && $penjelasan === '') {
                continue;
            }

            $validRows[] = [
                'istilah' => $istilah,
                'penjelasan' => $penjelasan,
            ];
        }

        return $validRows;
    }

    private function normalizedProsedurRows(array $rows): array
    {
        $validRows = [];
        $fallbackOrder = 1;

        foreach ($rows as $row) {
            $row = RichText::sanitizeFields((array) $row, ['aktivitas', 'dokumen', 'keterangan']);
            $hasText = collect($row)
                ->except(['urutan', 'simbol'])
                ->filter(function ($value) {
                    return trim((string) $value) !== '';
                })
                ->isNotEmpty();

            if (!$hasText) {
                continue;
            }

            $validRows[] = [
                'urutan' => (int) (($row['urutan'] ?? null) ?: $fallbackOrder),
                'simbol' => array_key_exists((string) ($row['simbol'] ?? ''), SopPengetahuan::simbolOptions())
                    ? (string) $row['simbol']
                    : SopPengetahuan::SIMBOL_AKTIVITAS,
                'pelaksana' => trim((string) ($row['pelaksana'] ?? '')),
                'aktivitas' => trim((string) ($row['aktivitas'] ?? '')),
                'dokumen' => trim((string) ($row['dokumen'] ?? '')),
                'keterangan' => trim((string) ($row['keterangan'] ?? '')),
            ];

            $fallbackOrder++;
        }

        return $validRows;
    }

    private function normalizedFlowchartPayload(string $payload): array
    {
        $payload = trim($payload);

        if ($payload === '') {
            return [
                'nodes' => [],
                'connectors' => [],
            ];
        }

        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'prosedur_flowchart' => 'Data flowchart SOP tidak valid. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        $allowedSymbols = array_keys(SopPengetahuan::simbolOptions());
        $nodes = [];
        $nodeIds = [];

        foreach (array_slice((array) ($decoded['nodes'] ?? []), 0, 120) as $node) {
            $node = (array) $node;
            $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($node['id'] ?? ''));
            $type = (string) ($node['type'] ?? SopPengetahuan::SIMBOL_AKTIVITAS);

            if ($id === '' || !in_array($type, $allowedSymbols, true)) {
                continue;
            }

            $label = mb_substr(trim(strip_tags((string) ($node['label'] ?? SopPengetahuan::simbolOptions()[$type]))), 0, 160);
            $x = max(0, min(1800, (float) ($node['x'] ?? 0)));
            $y = max(0, min(1200, (float) ($node['y'] ?? 0)));

            $nodes[] = [
                'id' => $id,
                'type' => $type,
                'label' => $label !== '' ? $label : SopPengetahuan::simbolOptions()[$type],
                'x' => round($x, 2),
                'y' => round($y, 2),
            ];
            $nodeIds[$id] = true;
        }

        $connectors = [];

        foreach (array_slice((array) ($decoded['connectors'] ?? []), 0, 180) as $connector) {
            $connector = (array) $connector;
            $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($connector['id'] ?? ''));
            $from = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($connector['from'] ?? ''));
            $to = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($connector['to'] ?? ''));

            if ($id === '' || $from === '' || $to === '' || $from === $to || !isset($nodeIds[$from], $nodeIds[$to])) {
                continue;
            }

            $connectors[] = [
                'id' => $id,
                'from' => $from,
                'to' => $to,
            ];
        }

        return [
            'nodes' => $nodes,
            'connectors' => $connectors,
        ];
    }

    private function normalizedLampiranRows(array $rows): array
    {
        $validRows = [];

        foreach ($rows as $row) {
            $row = RichText::sanitizeFields((array) $row, ['keterangan']);
            $nama = trim((string) ($row['nama'] ?? ''));
            $keterangan = trim((string) ($row['keterangan'] ?? ''));

            if ($nama === '' && $keterangan === '') {
                continue;
            }

            $validRows[] = [
                'nama' => $nama,
                'keterangan' => $keterangan,
            ];
        }

        return $validRows;
    }

    private function normalizedRevisiRows(array $rows): array
    {
        $validRows = [];

        foreach ($rows as $row) {
            $row = RichText::sanitizeFields((array) $row, ['deskripsi_perubahan', 'alasan_revisi']);
            $hasText = collect($row)->filter(function ($value) {
                return trim((string) $value) !== '';
            })->isNotEmpty();

            if (!$hasText) {
                continue;
            }

            $validRows[] = [
                'no_revisi' => trim((string) ($row['no_revisi'] ?? '')),
                'tanggal_revisi' => $row['tanggal_revisi'] ?? null,
                'deskripsi_perubahan' => trim((string) ($row['deskripsi_perubahan'] ?? '')),
                'alasan_revisi' => trim((string) ($row['alasan_revisi'] ?? '')),
                'pihak_merevisi' => trim((string) ($row['pihak_merevisi'] ?? '')),
            ];
        }

        return $validRows;
    }

    private function resolveAllowedAlurKerjaId($alurKerjaId): ?int
    {
        if (empty($alurKerjaId)) {
            return null;
        }

        $alurKerja = AlurKerja::query()
            ->visibleTo(auth()->user())
            ->whereKey($alurKerjaId)
            ->first();

        if (!$alurKerja) {
            throw ValidationException::withMessages([
                'alur_kerja_id' => 'Anda tidak memiliki izin memakai alur kerja ini.',
            ]);
        }

        return (int) $alurKerja->id;
    }

    private function resolveAllowedAlurKerjaTahapId($alurKerjaTahapId, ?int $alurKerjaId): ?int
    {
        if (empty($alurKerjaTahapId)) {
            return null;
        }

        if (!$alurKerjaId) {
            throw ValidationException::withMessages([
                'alur_kerja_tahap_id' => 'Pilih alur kerja terlebih dahulu sebelum memilih tahap SOP.',
            ]);
        }

        $tahap = AlurKerjaTahap::query()
            ->whereKey($alurKerjaTahapId)
            ->where('alur_kerja_id', $alurKerjaId)
            ->first();

        if (!$tahap) {
            throw ValidationException::withMessages([
                'alur_kerja_tahap_id' => 'Tahap yang dipilih tidak sesuai dengan alur kerja.',
            ]);
        }

        return (int) $tahap->id;
    }

    private function simpanLampiran(SopPengetahuan $sopPengetahuan, $files): void
    {
        $files = is_array($files) ? $files : [$files];
        $files = array_values(array_filter($files));

        if (empty($files)) {
            return;
        }

        $disk = $this->storageDisk();

        foreach ($files as $file) {
            $path = $file->store('sop-pengetahuan/' . $sopPengetahuan->id, $disk);

            SopPengetahuanLampiran::create([
                'sop_pengetahuan_id' => $sopPengetahuan->id,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $path,
                'storage_disk' => $disk,
                'ukuran_file' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }
    }

    private function hapusFileLampiran(SopPengetahuanLampiran $lampiran): void
    {
        if ($lampiran->path && Storage::disk($lampiran->storage_disk)->exists($lampiran->path)) {
            Storage::disk($lampiran->storage_disk)->delete($lampiran->path);
        }
    }

    private function storageDisk(): string
    {
        return filled(config('filesystems.disks.r2.key'))
            && filled(config('filesystems.disks.r2.secret'))
            && filled(config('filesystems.disks.r2.bucket'))
            && filled(config('filesystems.disks.r2.endpoint'))
                ? 'r2'
                : 'local';
    }

    private function formData(): array
    {
        $alurKerjas = $this->availableAlurKerjasForCurrentUser(true);

        $alurKerjaStageMap = $alurKerjas->mapWithKeys(function ($alurKerja) {
            return [
                $alurKerja->id => $alurKerja->tahaps->map(function ($tahap) {
                    return [
                        'id' => $tahap->id,
                        'label' => 'Tahap ' . $tahap->urutan . ' - ' . $tahap->nama,
                    ];
                })->values(),
            ];
        });

        return [
            'teams' => $this->availableTeamsForCurrentUser(),
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'alurKerjas' => $alurKerjas,
            'alurKerjaStageMap' => $alurKerjaStageMap,
            'statusOptions' => SopPengetahuan::statusOptions(),
            'prioritasOptions' => SopPengetahuan::prioritasOptions(),
            'simbolOptions' => SopPengetahuan::simbolOptions(),
        ];
    }

    private function availableTeamsForCurrentUser()
    {
        $user = auth()->user();

        if ($user->canAccessAllFiles()) {
            return Team::orderBy('name')->get();
        }

        return $user->teams()->orderBy('name')->get();
    }

    private function availableAlurKerjasForCurrentUser(bool $withTahaps = false)
    {
        $query = AlurKerja::query()
            ->visibleTo(auth()->user())
            ->orderBy('nama');

        if ($withTahaps) {
            $query->with(['tahaps' => function ($query) {
                $query->select('id', 'alur_kerja_id', 'urutan', 'nama')
                    ->orderBy('urutan')
                    ->orderBy('id');
            }]);
        }

        return $query->get(['id', 'kode', 'nama']);
    }

    private function pastikanBisaDilihat(SopPengetahuan $sopPengetahuan): void
    {
        abort_unless(
            SopPengetahuan::query()->visibleTo(auth()->user())->whereKey($sopPengetahuan->id)->exists(),
            403,
            'Anda tidak memiliki izin melihat SOP ini.'
        );
    }

    private function pastikanBisaDiatur(SopPengetahuan $sopPengetahuan): void
    {
        abort_unless(
            SopPengetahuan::query()->manageableBy(auth()->user())->whereKey($sopPengetahuan->id)->exists(),
            403,
            'Anda tidak memiliki izin mengubah SOP ini.'
        );
    }

    private function pastikanLampiranMilikSop(SopPengetahuan $sopPengetahuan, SopPengetahuanLampiran $lampiran): void
    {
        abort_unless((int) $lampiran->sop_pengetahuan_id === (int) $sopPengetahuan->id, 404);
    }

    private function canManage(SopPengetahuan $sopPengetahuan): bool
    {
        return SopPengetahuan::query()
            ->manageableBy(auth()->user())
            ->whereKey($sopPengetahuan->id)
            ->exists();
    }

    private function resolveFilter($value, array $options): string
    {
        $value = (string) $value;

        return array_key_exists($value, $options) ? $value : '';
    }

    private function resolveAlurKerjaFilter($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        return AlurKerja::query()
            ->visibleTo(auth()->user())
            ->whereKey($value)
            ->value('id');
    }
}
