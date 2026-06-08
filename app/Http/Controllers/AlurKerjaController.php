<?php

namespace App\Http\Controllers;

use App\Models\AlurKerja;
use App\Models\AlurKerjaTahap;
use App\Models\AlurKerjaTahapLampiran;
use App\Models\AlurKerjaTahapPic;
use App\Models\AlurKerjaTahapSistem;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AlurKerjaController extends Controller
{
    public function index()
    {
        $search = trim((string) request('search', ''));
        $risiko = $this->resolveFilter(request('risiko'), AlurKerja::risikoOptions());
        $statusDokumentasi = $this->resolveFilter(request('status_dokumentasi'), AlurKerja::statusDokumentasiOptions());
        $statusOperasional = $this->resolveFilter(request('status_operasional'), AlurKerja::statusOperasionalOptions());

        $query = AlurKerja::query()
            ->visibleTo(auth()->user())
            ->with(['team', 'pemilikUtama', 'pemilikCadangan'])
            ->withCount(['pekerjaans', 'tahaps', 'sopPengetahuans'])
            ->orderBy('nama');

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('kode', 'like', '%' . $search . '%')
                    ->orWhere('deskripsi', 'like', '%' . $search . '%');
            });
        }

        if ($risiko !== '') {
            $query->where('risiko', $risiko);
        }

        if ($statusDokumentasi !== '') {
            $query->where('status_dokumentasi', $statusDokumentasi);
        }

        if ($statusOperasional !== '') {
            $query->where('status_operasional', $statusOperasional);
        }

        $alurKerjas = $query->paginate(10)->withQueryString();

        return view('alur_kerja.index', [
            'alurKerjas' => $alurKerjas,
            'search' => $search,
            'risiko' => $risiko,
            'statusDokumentasi' => $statusDokumentasi,
            'statusOperasional' => $statusOperasional,
            'risikoOptions' => AlurKerja::risikoOptions(),
            'statusDokumentasiOptions' => AlurKerja::statusDokumentasiOptions(),
            'statusOperasionalOptions' => AlurKerja::statusOperasionalOptions(),
        ]);
    }

    public function create()
    {
        return view('alur_kerja.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $tahapAwal = $this->validatedTahapAwal($request);

        $alurKerja = AlurKerja::create($data);
        $this->simpanTahapAwal($alurKerja, $tahapAwal, $request->file('tahap_lampiran', []));

        ActivityLogService::log(
            'alur_kerja.create',
            'Menambahkan alur kerja baru.',
            $alurKerja
        );

        return redirect()
            ->route('alur-kerja.show', $alurKerja->id)
            ->with('success', 'Alur kerja berhasil ditambahkan.');
    }

    public function show(AlurKerja $alurKerja)
    {
        $this->pastikanAlurKerjaBisaDilihat($alurKerja);

        $alurKerja->load(['team', 'pemilikUtama', 'pemilikCadangan', 'tahaps.lampirans', 'tahaps.sistems', 'tahaps.pics']);

        $pekerjaans = $alurKerja->pekerjaans()
            ->visibleTo(auth()->user())
            ->with(['team', 'lokasi'])
            ->withCount('dokumens')
            ->latest()
            ->paginate(10);

        $sopPengetahuans = $alurKerja->sopPengetahuans()
            ->visibleTo(auth()->user())
            ->with(['tahap', 'pemilik'])
            ->withCount('lampirans')
            ->latest()
            ->limit(6)
            ->get();

        return view('alur_kerja.show', compact('alurKerja', 'pekerjaans', 'sopPengetahuans'));
    }

    public function edit(AlurKerja $alurKerja)
    {
        $this->pastikanAlurKerjaBisaDiatur($alurKerja);

        return view('alur_kerja.edit', array_merge($this->formData(), [
            'alurKerja' => $alurKerja,
        ]));
    }

    public function update(Request $request, AlurKerja $alurKerja)
    {
        $this->pastikanAlurKerjaBisaDiatur($alurKerja);

        $data = $this->validatedData($request, $alurKerja);

        $alurKerja->update($data);

        ActivityLogService::log(
            'alur_kerja.update',
            'Memperbarui alur kerja.',
            $alurKerja
        );

        return redirect()
            ->route('alur-kerja.show', $alurKerja->id)
            ->with('success', 'Alur kerja berhasil diperbarui.');
    }

    public function destroy(AlurKerja $alurKerja)
    {
        $this->pastikanAlurKerjaBisaDiatur($alurKerja);

        $alurKerja->load('tahaps.lampirans', 'tahaps.sistems', 'tahaps.pics');

        foreach ($alurKerja->tahaps as $tahap) {
            foreach ($tahap->lampirans as $lampiran) {
                $this->hapusFileLampiran($lampiran);
                $lampiran->delete();
            }

            $tahap->sistems()->delete();
            $tahap->pics()->delete();
            $tahap->delete();
        }

        $nama = $alurKerja->nama;
        $alurKerja->pekerjaans()->update(['alur_kerja_id' => null]);
        $alurKerja->delete();

        ActivityLogService::log(
            'alur_kerja.delete',
            'Menghapus alur kerja.',
            (object) ['id' => $alurKerja->id, 'nama' => $nama]
        );

        return redirect()
            ->route('alur-kerja.index')
            ->with('success', 'Alur kerja berhasil dihapus. Dokumen terkait tidak ikut dihapus.');
    }

    private function validatedData(Request $request, AlurKerja $alurKerja = null): array
    {
        $alurKerjaId = $alurKerja ? $alurKerja->id : null;

        $data = $request->validate([
            'kode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('alur_kerja', 'kode')->ignore($alurKerjaId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'pemilik_utama_user_id' => ['required', 'integer', 'exists:users,id'],
            'pemilik_cadangan_user_id' => ['nullable', 'integer', 'exists:users,id', 'different:pemilik_utama_user_id'],
            'risiko' => ['required', Rule::in(array_keys(AlurKerja::risikoOptions()))],
            'status_dokumentasi' => ['required', Rule::in(array_keys(AlurKerja::statusDokumentasiOptions()))],
            'status_operasional' => ['required', Rule::in(array_keys(AlurKerja::statusOperasionalOptions()))],
            'target_tinjauan_berikutnya' => ['nullable', 'date'],
        ]);

        $data = RichText::sanitizeFields($data, ['deskripsi']);

        if (!auth()->user()->canAccessAllFiles()) {
            $allowedTeamIds = auth()->user()->assignedTeamIds();

            if (!empty($data['team_id']) && !in_array((int) $data['team_id'], $allowedTeamIds, true)) {
                abort(403, 'Anda tidak memiliki izin memakai tim/divisi ini.');
            }

            $data['pemilik_utama_user_id'] = auth()->id();
        }

        return $data;
    }

    private function validatedTahapAwal(Request $request): array
    {
        $data = $request->validate([
            'tahap' => ['nullable', 'array'],
            'tahap.*.urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'tahap.*.nama' => ['nullable', 'string', 'max:255'],
            'tahap.*.deskripsi' => ['nullable', 'string'],
            'tahap.*.aplikasi_digunakan' => ['nullable', 'string'],
            'tahap.*.akun_digunakan' => ['nullable', 'string'],
            'tahap.*.pic_terkait' => ['nullable', 'string'],
            'tahap.*.kontak_pic' => ['nullable', 'string'],
            'tahap.*.sistem' => ['nullable', 'array'],
            'tahap.*.sistem.*.urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'tahap.*.sistem.*.nama_sistem' => ['nullable', 'string', 'max:255'],
            'tahap.*.sistem.*.fungsi' => ['nullable', 'string'],
            'tahap.*.sistem.*.akun' => ['nullable', 'string'],
            'tahap.*.sistem.*.url' => ['nullable', 'string', 'max:500'],
            'tahap.*.sistem.*.catatan' => ['nullable', 'string'],
            'tahap.*.pic' => ['nullable', 'array'],
            'tahap.*.pic.*.urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'tahap.*.pic.*.nama' => ['nullable', 'string', 'max:255'],
            'tahap.*.pic.*.peran' => ['nullable', 'string', 'max:255'],
            'tahap.*.pic.*.kontak' => ['nullable', 'string', 'max:500'],
            'tahap.*.pic.*.waktu_dihubungi' => ['nullable', 'string'],
            'tahap.*.pic.*.catatan' => ['nullable', 'string'],
            'tahap.*.catatan' => ['nullable', 'string'],
            'tahap_lampiran.*.*' => ['nullable', 'file', 'max:20480'],
        ]);

        $tahapRows = [];

        foreach ((array) ($data['tahap'] ?? []) as $index => $row) {
            $row = RichText::sanitizeFields($row, ['deskripsi', 'catatan']);
            $files = array_values(array_filter((array) $request->file('tahap_lampiran.' . $index, [])));
            $sistemRows = $this->validatedSistemRows((array) data_get($row, 'sistem', []), 'tahap.' . $index . '.sistem');
            $picRows = $this->validatedPicRows((array) data_get($row, 'pic', []), 'tahap.' . $index . '.pic');
            $hasText = collect($row)
                ->except(['urutan', 'sistem', 'pic'])
                ->filter(function ($value) {
                    return trim((string) $value) !== '';
                })
                ->isNotEmpty();

            if (!$hasText && empty($files) && empty($sistemRows) && empty($picRows)) {
                continue;
            }

            if (trim((string) data_get($row, 'nama')) === '') {
                throw ValidationException::withMessages([
                    'tahap.' . $index . '.nama' => 'Nama tahap wajib diisi jika tahap atau lampiran diisi.',
                ]);
            }

            $row['sistem'] = $sistemRows;
            $row['pic'] = $picRows;
            $tahapRows[$index] = $row;
        }

        return $tahapRows;
    }

    private function simpanTahapAwal(AlurKerja $alurKerja, array $tahapRows, $lampiranRows): void
    {
        if (empty($tahapRows)) {
            return;
        }

        $nomorFallback = 1;

        foreach ($tahapRows as $index => $row) {
            $tahap = AlurKerjaTahap::create([
                'alur_kerja_id' => $alurKerja->id,
                'urutan' => $row['urutan'] ?? $nomorFallback,
                'nama' => $row['nama'],
                'deskripsi' => $row['deskripsi'] ?? null,
                'aplikasi_digunakan' => $row['aplikasi_digunakan'] ?? null,
                'akun_digunakan' => $row['akun_digunakan'] ?? null,
                'pic_terkait' => $row['pic_terkait'] ?? null,
                'kontak_pic' => $row['kontak_pic'] ?? null,
                'catatan' => $row['catatan'] ?? null,
            ]);

            $this->simpanLampiranTahap($alurKerja, $tahap, data_get($lampiranRows, $index, []));
            $this->simpanSistemTahap($tahap, (array) data_get($row, 'sistem', []));
            $this->simpanPicTahap($tahap, (array) data_get($row, 'pic', []));
            $nomorFallback++;
        }
    }

    private function validatedSistemRows(array $rows, string $fieldPrefix): array
    {
        $validRows = [];

        foreach ($rows as $index => $row) {
            $row = RichText::sanitizeFields($row, ['fungsi', 'catatan']);
            $hasText = collect($row)
                ->except(['urutan'])
                ->filter(function ($value) {
                    return trim((string) $value) !== '';
                })
                ->isNotEmpty();

            if (!$hasText) {
                continue;
            }

            $validRows[$index] = $row;
        }

        return $validRows;
    }

    private function validatedPicRows(array $rows, string $fieldPrefix): array
    {
        $validRows = [];

        foreach ($rows as $index => $row) {
            $row = RichText::sanitizeFields($row, ['catatan']);
            $hasText = collect($row)
                ->except(['urutan'])
                ->filter(function ($value) {
                    return trim((string) $value) !== '';
                })
                ->isNotEmpty();

            if (!$hasText) {
                continue;
            }

            $validRows[$index] = $row;
        }

        return $validRows;
    }

    private function simpanSistemTahap(AlurKerjaTahap $tahap, array $rows): void
    {
        $nomorFallback = 1;

        foreach ($rows as $row) {
            AlurKerjaTahapSistem::create([
                'alur_kerja_tahap_id' => $tahap->id,
                'urutan' => $row['urutan'] ?? $nomorFallback,
                'nama_sistem' => $row['nama_sistem'] ?? null,
                'fungsi' => $row['fungsi'] ?? null,
                'akun' => $row['akun'] ?? null,
                'url' => $row['url'] ?? null,
                'catatan' => $row['catatan'] ?? null,
            ]);

            $nomorFallback++;
        }
    }

    private function simpanPicTahap(AlurKerjaTahap $tahap, array $rows): void
    {
        $nomorFallback = 1;

        foreach ($rows as $row) {
            AlurKerjaTahapPic::create([
                'alur_kerja_tahap_id' => $tahap->id,
                'urutan' => $row['urutan'] ?? $nomorFallback,
                'nama' => $row['nama'] ?? null,
                'peran' => $row['peran'] ?? null,
                'kontak' => $row['kontak'] ?? null,
                'waktu_dihubungi' => $row['waktu_dihubungi'] ?? null,
                'catatan' => $row['catatan'] ?? null,
            ]);

            $nomorFallback++;
        }
    }

    private function simpanLampiranTahap(AlurKerja $alurKerja, AlurKerjaTahap $tahap, $files): void
    {
        $files = is_array($files) ? $files : [$files];
        $files = array_values(array_filter($files));

        if (empty($files)) {
            return;
        }

        $disk = $this->storageDisk();

        foreach ($files as $file) {
            $path = $file->store('alur-kerja/' . $alurKerja->id . '/tahap/' . $tahap->id, $disk);

            AlurKerjaTahapLampiran::create([
                'alur_kerja_tahap_id' => $tahap->id,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $path,
                'storage_disk' => $disk,
                'ukuran_file' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }
    }

    private function hapusFileLampiran(AlurKerjaTahapLampiran $lampiran): void
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
        return [
            'teams' => $this->availableTeamsForCurrentUser(),
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'risikoOptions' => AlurKerja::risikoOptions(),
            'statusDokumentasiOptions' => AlurKerja::statusDokumentasiOptions(),
            'statusOperasionalOptions' => AlurKerja::statusOperasionalOptions(),
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

    private function pastikanAlurKerjaBisaDilihat(AlurKerja $alurKerja): void
    {
        abort_unless(
            AlurKerja::query()->visibleTo(auth()->user())->whereKey($alurKerja->id)->exists(),
            403,
            'Anda tidak memiliki izin melihat alur kerja ini.'
        );
    }

    private function pastikanAlurKerjaBisaDiatur(AlurKerja $alurKerja): void
    {
        abort_unless(
            AlurKerja::query()->manageableBy(auth()->user())->whereKey($alurKerja->id)->exists(),
            403,
            'Anda tidak memiliki izin mengubah alur kerja ini.'
        );
    }

    private function resolveFilter($value, array $options): string
    {
        $value = (string) $value;

        return array_key_exists($value, $options) ? $value : '';
    }
}
