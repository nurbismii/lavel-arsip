<?php

namespace App\Http\Controllers;

use App\Models\Pekerjaan;
use App\Models\AlurKerja;
use App\Models\AlurKerjaTahap;
use App\Models\Dokumen;
use App\Models\DokumenBuktiPenyelesaian;
use App\Models\Lokasi;
use App\Models\Team;
use App\Models\User;
use App\Rules\MeaningfulRichText;
use App\Services\ActivityLogService;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PekerjaanController extends Controller
{
    private const MAX_UPLOAD_SIZE_KB = 10240;

    public function lihatDokumen(Dokumen $dokumen)
    {
        $dokumen->load('pekerjaan');

        if (!$dokumen->pekerjaan) {
            abort(404);
        }

        $this->pastikanPekerjaanBisaDilihat($dokumen->pekerjaan);

        $disk = $dokumen->storage_disk;

        if (!$dokumen->path || !Storage::disk($disk)->exists($dokumen->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        if ($disk === 'r2') {
            return redirect()->away(Storage::disk('r2')->temporaryUrl(
                $dokumen->path,
                now()->addMinutes(5),
                [
                    'ResponseContentDisposition' => 'inline; filename="' . addslashes($dokumen->nama_file) . '"',
                ]
            ));
        }

        return response()->file(Storage::disk('local')->path($dokumen->path), [
            'Content-Disposition' => 'inline; filename="' . $dokumen->nama_file . '"',
        ]);
    }

    public function lihatBuktiPenyelesaian(Dokumen $dokumen, DokumenBuktiPenyelesaian $buktiPenyelesaian = null)
    {
        $dokumen->load('pekerjaan');

        if (!$dokumen->pekerjaan) {
            abort(404);
        }

        $this->pastikanPekerjaanBisaDilihat($dokumen->pekerjaan);

        if ($buktiPenyelesaian) {
            abort_unless((int) $buktiPenyelesaian->dokumen_id === (int) $dokumen->id, 404);

            $disk = $buktiPenyelesaian->storage_disk;

            if (!$buktiPenyelesaian->path || !Storage::disk($disk)->exists($buktiPenyelesaian->path)) {
                abort(404, 'Bukti penyelesaian tidak ditemukan.');
            }

            if ($disk === 'r2') {
                return redirect()->away(Storage::disk('r2')->temporaryUrl(
                    $buktiPenyelesaian->path,
                    now()->addMinutes(5),
                    [
                        'ResponseContentDisposition' => 'inline; filename="' . addslashes($buktiPenyelesaian->nama_file) . '"',
                    ]
                ));
            }

            return response()->file(Storage::disk('local')->path($buktiPenyelesaian->path), [
                'Content-Disposition' => 'inline; filename="' . $buktiPenyelesaian->nama_file . '"',
            ]);
        }

        $buktiPertama = $dokumen->buktiPenyelesaians()->first();

        if ($buktiPertama) {
            return $this->lihatBuktiPenyelesaian($dokumen, $buktiPertama);
        }

        $disk = $dokumen->bukti_penyelesaian_storage_disk;

        if (!$dokumen->bukti_penyelesaian_path || !Storage::disk($disk)->exists($dokumen->bukti_penyelesaian_path)) {
            abort(404, 'Bukti penyelesaian tidak ditemukan.');
        }

        if ($disk === 'r2') {
            return redirect()->away(Storage::disk('r2')->temporaryUrl(
                $dokumen->bukti_penyelesaian_path,
                now()->addMinutes(5),
                [
                    'ResponseContentDisposition' => 'inline; filename="' . addslashes($dokumen->bukti_penyelesaian_nama_file) . '"',
                ]
            ));
        }

        return response()->file(Storage::disk('local')->path($dokumen->bukti_penyelesaian_path), [
            'Content-Disposition' => 'inline; filename="' . $dokumen->bukti_penyelesaian_nama_file . '"',
        ]);
    }

    public function index()
    {
        $search = trim((string) request('search', ''));
        $statusDokumen = $this->resolveStatusDokumenFilter();
        $statusDokumenOptions = Dokumen::statusOptions();
        $perPage = 10;
        $relatedPekerjaanIdsByStatus = $statusDokumen !== ''
            ? $this->findRelatedPekerjaanIdsByStatus($statusDokumen)
            : [];

        $query = Pekerjaan::with(['lokasi', 'team', 'alurKerja', 'alurKerjaTahap'])
            ->withCount([
                'subPekerjaans' => function ($query) use ($statusDokumen, $relatedPekerjaanIdsByStatus) {
                    $this->applyVisiblePekerjaanScope($query);

                    if ($statusDokumen !== '') {
                        if (empty($relatedPekerjaanIdsByStatus)) {
                            $query->whereRaw('1 = 0');
                        } else {
                            $query->whereIn('id', $relatedPekerjaanIdsByStatus);
                        }
                    }
                },
                'dokumens' => function ($query) use ($statusDokumen) {
                    if ($statusDokumen !== '') {
                        $query->where('status_dokumen', $statusDokumen);
                    }
                },
            ])
            ->whereNull('parent_id');

        $this->applyVisiblePekerjaanScope($query);

        $query->orderBy('id');

        $rootIdFilters = [];

        if ($search !== '') {
            $rootIds = $this->findRelatedRootIds($search);

            $rootIdFilters[] = $rootIds;
        }

        if ($statusDokumen !== '') {
            $rootIdFilters[] = $this->findRootIdsFromRelatedPekerjaanIds($relatedPekerjaanIdsByStatus);
        }

        if (!empty($rootIdFilters)) {
            $filteredRootIds = array_shift($rootIdFilters);

            foreach ($rootIdFilters as $rootIds) {
                $filteredRootIds = array_values(array_intersect($filteredRootIds, $rootIds));
            }

            empty($filteredRootIds)
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('id', $filteredRootIds);
        }

        $pekerjaans = $query->paginate($perPage)->withQueryString();

        return view('pekerjaan.index', compact('pekerjaans', 'search', 'statusDokumen', 'statusDokumenOptions'));
    }

    public function treeContent(Pekerjaan $pekerjaan)
    {
        $this->pastikanPekerjaanBisaDilihat($pekerjaan);

        $statusDokumen = $this->resolveStatusDokumenFilter();
        $relatedPekerjaanIdsByStatus = $statusDokumen !== ''
            ? $this->findRelatedPekerjaanIdsByStatus($statusDokumen)
            : [];

        $pekerjaan->load([
            'lokasi',
            'team',
            'alurKerja',
            'alurKerjaTahap',
            'dokumens' => function ($query) use ($statusDokumen) {
                if ($statusDokumen !== '') {
                    $query->where('status_dokumen', $statusDokumen);
                }

                $query->with(['buktiPenyelesaians', 'peminjam'])->orderBy('id');
            },
            'subPekerjaans' => function ($query) use ($statusDokumen, $relatedPekerjaanIdsByStatus) {
                $this->applyVisiblePekerjaanScope($query);

                if ($statusDokumen !== '') {
                    if (empty($relatedPekerjaanIdsByStatus)) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('id', $relatedPekerjaanIdsByStatus);
                    }
                }

                $query->with(['lokasi', 'team', 'alurKerja', 'alurKerjaTahap'])
                    ->withCount([
                        'subPekerjaans' => function ($query) use ($statusDokumen, $relatedPekerjaanIdsByStatus) {
                            $this->applyVisiblePekerjaanScope($query);

                            if ($statusDokumen !== '') {
                                if (empty($relatedPekerjaanIdsByStatus)) {
                                    $query->whereRaw('1 = 0');
                                } else {
                                    $query->whereIn('id', $relatedPekerjaanIdsByStatus);
                                }
                            }
                        },
                        'dokumens' => function ($query) use ($statusDokumen) {
                            if ($statusDokumen !== '') {
                                $query->where('status_dokumen', $statusDokumen);
                            }
                        },
                    ])
                    ->orderBy('id');
            },
        ]);

        return response()->json([
            'html' => view('pekerjaan.partials.tree-content', [
                'item' => $pekerjaan,
                'statusDokumen' => $statusDokumen,
                'borrowerUsers' => $this->availableBorrowerUsers(),
            ])->render(),
        ]);
    }

    public function create()
    {
        $parents = Pekerjaan::query()
            ->manageableBy(auth()->user())
            ->with(['team', 'alurKerja', 'alurKerjaTahap'])
            ->orderBy('judul')
            ->get();
        $lokasis = Lokasi::orderBy('nama_lokasi')->get();
        $teams = $this->availableTeamsForCurrentUser();
        $alurKerjas = $this->availableAlurKerjasForCurrentUser();
        $statusDokumenOptions = Dokumen::statusOptionsForInput();

        return view('pekerjaan.create', compact('parents', 'lokasis', 'teams', 'alurKerjas', 'statusDokumenOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:pekerjaan,id'],
            'lokasi_id' => ['required', 'integer', 'exists:lokasi_dokumen,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'alur_kerja_id' => ['nullable', 'integer', 'exists:alur_kerja,id'],
            'alur_kerja_tahap_id' => ['nullable', 'integer', 'exists:alur_kerja_tahap,id'],
            'tanggal_mulai_penyelesaian' => ['required', 'date'],
            'tanggal_target_penyelesaian' => ['required', 'date', 'after_or_equal:tanggal_mulai_penyelesaian'],
            'status_dokumen' => ['nullable', Rule::in(array_keys(Dokumen::statusOptionsForInput()))],
            'dokumen.*' => ['nullable', 'file', 'max:' . self::MAX_UPLOAD_SIZE_KB],
            'sub_judul' => ['nullable', 'array'],
            'sub_judul.*' => ['nullable', 'string', 'max:255'],
            'sub_deskripsi' => ['nullable', 'array'],
            'sub_deskripsi.*' => ['nullable', 'string'],
            'sub_status_dokumen' => ['nullable', 'array'],
            'sub_status_dokumen.*' => ['nullable', Rule::in(array_keys(Dokumen::statusOptionsForInput()))],
            'sub_dokumen' => ['nullable', 'array'],
            'sub_dokumen.*' => ['nullable', 'array'],
            'sub_dokumen.*.*' => ['nullable', 'file', 'max:' . self::MAX_UPLOAD_SIZE_KB],
        ]);

        $data['deskripsi'] = RichText::sanitizeDocument($data['deskripsi'] ?? null);

        if ($this->requestHasUploads($request)) {
            try {
                $this->ensureR2Configured();
            } catch (RuntimeException $exception) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['r2' => $exception->getMessage()]);
            }
        }

        $parent = null;

        if (!empty($data['parent_id'])) {
            $parent = Pekerjaan::findOrFail($data['parent_id']);
            $this->pastikanPekerjaanBisaDiatur($parent);
        }

        $teamId = $parent
            ? $parent->team_id
            : $this->resolveAllowedTeamId($data['team_id'] ?? null);

        $alurKerjaId = $parent
            ? $parent->alur_kerja_id
            : $this->resolveAllowedAlurKerjaId($data['alur_kerja_id'] ?? null);

        $alurKerjaTahapId = $parent
            ? $parent->alur_kerja_tahap_id
            : $this->resolveAllowedAlurKerjaTahapId($data['alur_kerja_tahap_id'] ?? null, $alurKerjaId);

        $pekerjaan = Pekerjaan::create([
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'user_id' => auth()->id(),
            'lokasi_id' => $data['lokasi_id'] ?? null,
            'team_id' => $teamId,
            'alur_kerja_id' => $alurKerjaId,
            'alur_kerja_tahap_id' => $alurKerjaTahapId,
            'tanggal_mulai_penyelesaian' => $data['tanggal_mulai_penyelesaian'],
            'tanggal_target_penyelesaian' => $data['tanggal_target_penyelesaian'],
        ]);

        $this->simpanDokumen(
            $pekerjaan,
            $request->file('dokumen', []),
            $data['status_dokumen'] ?? Dokumen::STATUS_DRAFT
        );

        // sub pekerjaan
        if (!empty($data['sub_judul'])) {
            foreach ($data['sub_judul'] as $i => $judulSub) {

                if (!$judulSub) {
                    continue;
                }

                $sub = Pekerjaan::create([
                    'judul' => $judulSub,
                    'deskripsi' => RichText::sanitizeDocument($data['sub_deskripsi'][$i] ?? null),
                    'parent_id' => $pekerjaan->id,
                    'user_id' => auth()->id(),
                    'lokasi_id' => $data['lokasi_id'] ?? null,
                    'team_id' => $teamId,
                    'alur_kerja_id' => $alurKerjaId,
                    'alur_kerja_tahap_id' => $alurKerjaTahapId,
                    'tanggal_mulai_penyelesaian' => $data['tanggal_mulai_penyelesaian'],
                    'tanggal_target_penyelesaian' => $data['tanggal_target_penyelesaian'],
                ]);

                $this->simpanDokumen(
                    $sub,
                    $request->file("sub_dokumen.$i", []),
                    $data['sub_status_dokumen'][$i] ?? Dokumen::STATUS_DRAFT
                );
            }
        }

        ActivityLogService::log(
            'pekerjaan.create',
            'Menambahkan pekerjaan baru.',
            $pekerjaan
        );

        return redirect()->route('pekerjaan.index')->with('success', 'Data berhasil disimpan');
    }

    public function edit($id)
    {
        $pekerjaan = Pekerjaan::with(['dokumens', 'lokasi', 'team', 'alurKerja', 'alurKerjaTahap'])->findOrFail($id);
        $this->pastikanPekerjaanBisaDiatur($pekerjaan);

        $parents = Pekerjaan::query()
            ->manageableBy(auth()->user())
            ->where('id', '!=', $id)
            ->with(['team', 'alurKerja', 'alurKerjaTahap'])
            ->orderBy('judul')
            ->get();
        $lokasis = Lokasi::orderBy('nama_lokasi')->get();
        $teams = $this->availableTeamsForCurrentUser();
        $alurKerjas = $this->availableAlurKerjasForCurrentUser();
        $statusDokumenOptions = Dokumen::statusOptionsForInput();

        return view('pekerjaan.edit', compact('pekerjaan', 'parents', 'lokasis', 'teams', 'alurKerjas', 'statusDokumenOptions'));
    }

    public function update(Request $request, $id)
    {
        $pekerjaan = Pekerjaan::findOrFail($id);
        $this->pastikanPekerjaanBisaDiatur($pekerjaan);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:pekerjaan,id', Rule::notIn([$pekerjaan->id])],
            'lokasi_id' => ['nullable', 'integer', 'exists:lokasi_dokumen,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'alur_kerja_id' => ['nullable', 'integer', 'exists:alur_kerja,id'],
            'alur_kerja_tahap_id' => ['nullable', 'integer', 'exists:alur_kerja_tahap,id'],
            'tanggal_mulai_penyelesaian' => ['required', 'date'],
            'tanggal_target_penyelesaian' => ['required', 'date', 'after_or_equal:tanggal_mulai_penyelesaian'],
            'status_dokumen' => ['nullable', Rule::in(array_keys(Dokumen::statusOptionsForInput()))],
            'dokumen.*' => ['nullable', 'file', 'max:' . self::MAX_UPLOAD_SIZE_KB],
        ]);

        $data['deskripsi'] = RichText::sanitizeDocument($data['deskripsi'] ?? null);

        if ($this->requestHasUploads($request)) {
            try {
                $this->ensureR2Configured();
            } catch (RuntimeException $exception) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['r2' => $exception->getMessage()]);
            }
        }

        $parent = null;

        if (!empty($data['parent_id'])) {
            $parent = Pekerjaan::findOrFail($data['parent_id']);
            $this->pastikanPekerjaanBisaDiatur($parent);
        }

        $teamId = $parent
            ? $parent->team_id
            : $this->resolveAllowedTeamId($data['team_id'] ?? null);

        $alurKerjaId = $parent
            ? $parent->alur_kerja_id
            : $this->resolveAllowedAlurKerjaId($data['alur_kerja_id'] ?? null);

        $alurKerjaTahapId = $parent
            ? $parent->alur_kerja_tahap_id
            : $this->resolveAllowedAlurKerjaTahapId($data['alur_kerja_tahap_id'] ?? null, $alurKerjaId);

        $pekerjaan->update([
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'lokasi_id' => $data['lokasi_id'] ?? null,
            'team_id' => $teamId,
            'alur_kerja_id' => $alurKerjaId,
            'alur_kerja_tahap_id' => $alurKerjaTahapId,
            'tanggal_mulai_penyelesaian' => $data['tanggal_mulai_penyelesaian'],
            'tanggal_target_penyelesaian' => $data['tanggal_target_penyelesaian'],
        ]);

        $this->simpanDokumen(
            $pekerjaan,
            $request->file('dokumen', []),
            $data['status_dokumen'] ?? Dokumen::STATUS_DRAFT
        );

        ActivityLogService::log(
            'pekerjaan.update',
            'Memperbarui data pekerjaan.',
            $pekerjaan
        );

        return redirect()->route('pekerjaan.index')->with('success', 'Data berhasil diupdate');
    }

    public function hapusDokumen(Pekerjaan $pekerjaan, Dokumen $dokumen)
    {
        $this->pastikanPekerjaanBisaDiatur($pekerjaan);

        if ($dokumen->pekerjaan_id !== $pekerjaan->id) {
            abort(404);
        }

        $this->hapusFileDokumen($dokumen);
        $namaFile = $dokumen->nama_file;

        $dokumen->delete();

        ActivityLogService::log(
            'dokumen.delete',
            'Menghapus dokumen dari pekerjaan.',
            (object) ['id' => $dokumen->id, 'nama_file' => $namaFile]
        );

        return redirect()
            ->route('pekerjaan.edit', $pekerjaan->id)
            ->with('success', 'Dokumen berhasil dihapus');
    }

    public function updateStatusDokumen(Request $request, Pekerjaan $pekerjaan, Dokumen $dokumen)
    {
        $this->pastikanPekerjaanBisaDiatur($pekerjaan);

        if ((int) $dokumen->pekerjaan_id !== (int) $pekerjaan->id) {
            abort(404);
        }

        $status = (string) $request->input('status_dokumen');

        $hasExistingProof = $dokumen->buktiPenyelesaians()->exists() || filled($dokumen->bukti_penyelesaian_path);

        $data = $request->validate([
            'status_dokumen' => ['required', Rule::in(array_keys(Dokumen::statusOptions()))],
            'bukti_penyelesaian' => [
                $status === Dokumen::STATUS_ARSIP && !$hasExistingProof ? 'required' : 'nullable',
                'array',
            ],
            'bukti_penyelesaian.*' => [
                'file',
                'max:' . self::MAX_UPLOAD_SIZE_KB,
            ],
            'keterangan_penyelesaian' => [
                $status === Dokumen::STATUS_ARSIP ? 'required' : 'nullable',
                new MeaningfulRichText(1000, $status === Dokumen::STATUS_ARSIP),
            ],
            'peminjam_user_id' => [
                $status === Dokumen::STATUS_AKTIF ? 'required' : 'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('is_active', 1);
                }),
            ],
        ]);

        if ($this->requestHasCompletionProofUploads($request)) {
            try {
                $this->ensureR2Configured();
            } catch (RuntimeException $exception) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['bukti_penyelesaian' => $exception->getMessage()]);
            }
        }

        $updates = [
            'status_dokumen' => $data['status_dokumen'],
        ];

        if ($data['status_dokumen'] === Dokumen::STATUS_ARSIP) {
            $updates['keterangan_penyelesaian'] = RichText::sanitizeDocument($data['keterangan_penyelesaian']);
            $updates['diselesaikan_pada'] = $dokumen->diselesaikan_pada ?: now();
        } else {
            $updates['diselesaikan_pada'] = null;
        }

        if ($data['status_dokumen'] === Dokumen::STATUS_AKTIF) {
            $updates['peminjam_user_id'] = $data['peminjam_user_id'];
            $updates['dipinjam_pada'] = (int) $dokumen->peminjam_user_id === (int) $data['peminjam_user_id'] && $dokumen->dipinjam_pada
                ? $dokumen->dipinjam_pada
                : now();
        } else {
            $updates['peminjam_user_id'] = null;
            $updates['dipinjam_pada'] = null;
        }

        $dokumen->update($updates);

        if ($data['status_dokumen'] === Dokumen::STATUS_ARSIP) {
            $this->simpanBuktiPenyelesaian($dokumen, $request->file('bukti_penyelesaian', []));
        }

        $dokumen->load('peminjam');
        $description = 'Mengubah status dokumen menjadi ' . $dokumen->status_dokumen_label . '.';

        if ($dokumen->status_dokumen === Dokumen::STATUS_AKTIF && $dokumen->peminjam) {
            $description .= ' Dipinjam oleh ' . $dokumen->peminjam->name . '.';
        }

        ActivityLogService::log(
            'dokumen.status',
            $description,
            $dokumen
        );

        return redirect()
            ->back()
            ->with('success', 'Status dokumen berhasil diperbarui.');
    }

    public function destroy(Pekerjaan $pekerjaan)
    {
        $judulPekerjaan = $pekerjaan->judul;
        $this->pastikanKepemilikanRecursive($pekerjaan);
        $this->hapusPekerjaanRecursive($pekerjaan);

        ActivityLogService::log(
            'pekerjaan.delete',
            'Menghapus pekerjaan beserta seluruh data turunannya.',
            (object) ['id' => $pekerjaan->id, 'judul' => $judulPekerjaan]
        );

        return redirect()
            ->route('pekerjaan.index')
            ->with('success', 'Pekerjaan, sub pekerjaan, dan semua dokumen terkait berhasil dihapus.');
    }

    private function findRelatedRootIds(string $search): array
    {
        $matchingQuery = Pekerjaan::query();
        $this->applyVisiblePekerjaanScope($matchingQuery);

        $pendingIds = $matchingQuery
            ->where('judul', 'like', '%' . $search . '%')
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        if (empty($pendingIds)) {
            return [];
        }

        $rootIds = [];

        while (!empty($pendingIds)) {
            $itemsQuery = Pekerjaan::query()
                ->select('id', 'parent_id')
                ->whereIn('id', $pendingIds);

            $this->applyVisiblePekerjaanScope($itemsQuery);

            $items = $itemsQuery->get();

            $nextIds = [];

            foreach ($items as $item) {
                if ($item->parent_id === null) {
                    $rootIds[] = $item->id;
                    continue;
                }

                $nextIds[] = $item->parent_id;
            }

            $pendingIds = array_values(array_unique($nextIds));
        }

        return array_values(array_unique($rootIds));
    }

    private function resolveStatusDokumenFilter(): string
    {
        $statusDokumen = (string) request('status_dokumen', '');

        return array_key_exists($statusDokumen, Dokumen::statusOptions())
            ? $statusDokumen
            : '';
    }

    private function findRelatedPekerjaanIdsByStatus(string $statusDokumen): array
    {
        if (!array_key_exists($statusDokumen, Dokumen::statusOptions())) {
            return [];
        }

        $pendingIds = Dokumen::query()
            ->where('status_dokumen', $statusDokumen)
            ->whereHas('pekerjaan', function ($query) {
                $this->applyVisiblePekerjaanScope($query);
            })
            ->pluck('pekerjaan_id')
            ->unique()
            ->values()
            ->all();

        if (empty($pendingIds)) {
            return [];
        }

        $relatedIds = [];

        while (!empty($pendingIds)) {
            $itemsQuery = Pekerjaan::query()
                ->select('id', 'parent_id')
                ->whereIn('id', $pendingIds);

            $this->applyVisiblePekerjaanScope($itemsQuery);

            $items = $itemsQuery->get();
            $nextIds = [];

            foreach ($items as $item) {
                $relatedIds[] = $item->id;

                if ($item->parent_id !== null) {
                    $nextIds[] = $item->parent_id;
                }
            }

            $visitedIds = array_unique($relatedIds);
            $pendingIds = array_values(array_diff(array_unique($nextIds), $visitedIds));
        }

        return array_values(array_unique($relatedIds));
    }

    private function findRootIdsFromRelatedPekerjaanIds(array $relatedPekerjaanIds): array
    {
        if (empty($relatedPekerjaanIds)) {
            return [];
        }

        $query = Pekerjaan::query()
            ->whereIn('id', $relatedPekerjaanIds)
            ->whereNull('parent_id');

        $this->applyVisiblePekerjaanScope($query);

        return $query->pluck('id')->unique()->values()->all();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation
     */
    private function applyVisiblePekerjaanScope($query)
    {
        if (!auth()->check()) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        return $query->visibleTo(auth()->user());
    }

    private function currentUserCanAccessAllFiles(): bool
    {
        return auth()->check() && auth()->user()->canAccessAllFiles();
    }

    private function pastikanPekerjaanBisaDilihat(Pekerjaan $pekerjaan): void
    {
        if ($this->currentUserCanAccessAllFiles()) {
            return;
        }

        $user = auth()->user();

        abort_unless($user && $this->userCanViewPekerjaan($user, $pekerjaan), 403, 'Anda tidak memiliki izin untuk melihat pekerjaan ini.');
    }

    private function pastikanPekerjaanBisaDiatur(Pekerjaan $pekerjaan): void
    {
        $user = auth()->user();

        abort_unless($user && $this->userCanManagePekerjaan($user, $pekerjaan), 403, 'Anda tidak memiliki izin untuk mengubah pekerjaan ini.');
    }

    private function userCanViewPekerjaan($user, Pekerjaan $pekerjaan): bool
    {
        if ($user->canAccessAllFiles()) {
            return true;
        }

        if ((int) $pekerjaan->user_id === (int) $user->id) {
            return true;
        }

        if ($user->isSupervisor() && $pekerjaan->team_id) {
            return in_array((int) $pekerjaan->team_id, $user->assignedTeamIds(), true);
        }

        return false;
    }

    private function userCanManagePekerjaan($user, Pekerjaan $pekerjaan): bool
    {
        if ($user->canAccessAllFiles()) {
            return true;
        }

        return (int) $pekerjaan->user_id === (int) $user->id;
    }

    private function availableTeamsForCurrentUser()
    {
        $user = auth()->user();

        if (!$user) {
            return collect();
        }

        if ($user->canAccessAllFiles()) {
            return Team::orderBy('name')->get();
        }

        return $user->teams()
            ->orderBy('name')
            ->get();
    }

    private function availableBorrowerUsers()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function availableAlurKerjasForCurrentUser()
    {
        return AlurKerja::query()
            ->visibleTo(auth()->user())
            ->with(['tahaps' => function ($query) {
                $query->select('id', 'alur_kerja_id', 'urutan', 'nama')
                    ->orderBy('urutan')
                    ->orderBy('id');
            }])
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'team_id']);
    }

    private function resolveAllowedTeamId($teamId): ?int
    {
        if (!$teamId) {
            return null;
        }

        $teamId = (int) $teamId;
        $user = auth()->user();

        if ($user && $user->canAccessAllFiles()) {
            return $teamId;
        }

        abort_unless(
            $user && in_array($teamId, $user->assignedTeamIds(), true),
            403,
            'Anda tidak memiliki izin memakai tim/divisi ini.'
        );

        return $teamId;
    }

    private function resolveAllowedAlurKerjaId($alurKerjaId): ?int
    {
        if (!$alurKerjaId) {
            return null;
        }

        $alurKerjaId = (int) $alurKerjaId;

        abort_unless(
            AlurKerja::query()->visibleTo(auth()->user())->whereKey($alurKerjaId)->exists(),
            403,
            'Anda tidak memiliki izin memakai alur kerja ini.'
        );

        return $alurKerjaId;
    }

    private function resolveAllowedAlurKerjaTahapId($alurKerjaTahapId, ?int $alurKerjaId): ?int
    {
        if (!$alurKerjaTahapId) {
            return null;
        }

        if (!$alurKerjaId) {
            throw ValidationException::withMessages([
                'alur_kerja_tahap_id' => 'Pilih alur kerja terlebih dahulu sebelum memilih tahapan proses.',
            ]);
        }

        $alurKerjaTahapId = (int) $alurKerjaTahapId;

        $tahapAda = AlurKerjaTahap::query()
            ->whereKey($alurKerjaTahapId)
            ->where('alur_kerja_id', $alurKerjaId)
            ->whereHas('alurKerja', function ($query) {
                $query->visibleTo(auth()->user());
            })
            ->exists();

        if (!$tahapAda) {
            throw ValidationException::withMessages([
                'alur_kerja_tahap_id' => 'Tahapan proses yang dipilih tidak sesuai dengan alur kerja.',
            ]);
        }

        return $alurKerjaTahapId;
    }

    private function simpanDokumen(Pekerjaan $pekerjaan, $files, string $statusDokumen = Dokumen::STATUS_DRAFT): void
    {
        $files = is_array($files) ? $files : [$files];
        $files = array_values(array_filter($files));

        foreach ($files as $file) {
            $path = $file->store('dokumen/' . $pekerjaan->id, 'r2');

            Dokumen::create([
                'pekerjaan_id' => $pekerjaan->id,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $path,
                'status_dokumen' => $statusDokumen,
            ]);
        }
    }

    private function simpanBuktiPenyelesaian(Dokumen $dokumen, $files): void
    {
        $files = is_array($files) ? $files : [$files];
        $files = array_values(array_filter($files));

        if (empty($files)) {
            return;
        }

        $dokumen->loadMissing('pekerjaan');
        $pekerjaanId = $dokumen->pekerjaan_id;

        foreach ($files as $file) {
            $path = $file->store('dokumen/' . $pekerjaanId . '/bukti-penyelesaian', 'r2');

            DokumenBuktiPenyelesaian::create([
                'dokumen_id' => $dokumen->id,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $path,
            ]);

            if (!$dokumen->bukti_penyelesaian_path) {
                $dokumen->forceFill([
                    'bukti_penyelesaian_nama_file' => $file->getClientOriginalName(),
                    'bukti_penyelesaian_path' => $path,
                ])->save();
            }
        }
    }

    private function updateStatusDokumenExisting(Pekerjaan $pekerjaan, array $statusDokumen): void
    {
        if (empty($statusDokumen)) {
            return;
        }

        $dokumens = $pekerjaan->dokumens()
            ->whereIn('id', array_keys($statusDokumen))
            ->get();

        foreach ($dokumens as $dokumen) {
            $dokumen->update([
                'status_dokumen' => $statusDokumen[$dokumen->id] ?? Dokumen::STATUS_DRAFT,
            ]);
        }
    }

    private function hapusPekerjaanRecursive(Pekerjaan $pekerjaan): void
    {
        $pekerjaan->load([
            'dokumens',
            'subPekerjaans',
        ]);

        foreach ($pekerjaan->subPekerjaans as $subPekerjaan) {
            $this->hapusPekerjaanRecursive($subPekerjaan);
        }

        foreach ($pekerjaan->dokumens as $dokumen) {
            $this->hapusFileDokumen($dokumen);
            $dokumen->delete();
        }

        Storage::disk('local')->deleteDirectory('dokumen/' . $pekerjaan->id);

        if ($this->isR2Configured()) {
            Storage::disk('r2')->deleteDirectory('dokumen/' . $pekerjaan->id);
        }

        $pekerjaan->delete();
    }

    private function pastikanPekerjaanMilikUser(Pekerjaan $pekerjaan): void
    {
        $this->pastikanPekerjaanBisaDiatur($pekerjaan);
    }

    private function pastikanKepemilikanRecursive(Pekerjaan $pekerjaan): void
    {
        $this->pastikanPekerjaanMilikUser($pekerjaan);

        $pekerjaan->loadMissing('subPekerjaans');

        foreach ($pekerjaan->subPekerjaans as $subPekerjaan) {
            $this->pastikanKepemilikanRecursive($subPekerjaan);
        }
    }

    private function hapusFileDokumen(Dokumen $dokumen): void
    {
        $disk = $dokumen->storage_disk;

        if ($dokumen->path && Storage::disk($disk)->exists($dokumen->path)) {
            Storage::disk($disk)->delete($dokumen->path);
        }

        $this->hapusBuktiPenyelesaian($dokumen);
    }

    private function hapusBuktiPenyelesaian(Dokumen $dokumen): void
    {
        $dokumen->loadMissing('buktiPenyelesaians');

        foreach ($dokumen->buktiPenyelesaians as $buktiPenyelesaian) {
            $disk = $buktiPenyelesaian->storage_disk;

            if ($buktiPenyelesaian->path && Storage::disk($disk)->exists($buktiPenyelesaian->path)) {
                Storage::disk($disk)->delete($buktiPenyelesaian->path);
            }

            $buktiPenyelesaian->delete();
        }

        $disk = $dokumen->bukti_penyelesaian_storage_disk;

        if ($dokumen->bukti_penyelesaian_path && Storage::disk($disk)->exists($dokumen->bukti_penyelesaian_path)) {
            Storage::disk($disk)->delete($dokumen->bukti_penyelesaian_path);
        }
    }

    private function ensureR2Configured(): void
    {
        $requiredConfig = [
            'R2_ACCESS_KEY_ID' => config('filesystems.disks.r2.key'),
            'R2_SECRET_ACCESS_KEY' => config('filesystems.disks.r2.secret'),
            'R2_BUCKET' => config('filesystems.disks.r2.bucket'),
            'R2_ENDPOINT' => config('filesystems.disks.r2.endpoint'),
        ];

        foreach ($requiredConfig as $key => $value) {
            if (!filled($value)) {
                throw new RuntimeException($key . ' belum diisi. Lengkapi konfigurasi Cloudflare R2 di file .env.');
            }
        }
    }

    private function isR2Configured(): bool
    {
        return filled(config('filesystems.disks.r2.key'))
            && filled(config('filesystems.disks.r2.secret'))
            && filled(config('filesystems.disks.r2.bucket'))
            && filled(config('filesystems.disks.r2.endpoint'));
    }

    private function requestHasUploads(Request $request): bool
    {
        if ($request->hasFile('dokumen')) {
            return true;
        }

        foreach ((array) $request->file('sub_dokumen', []) as $subDokumen) {
            foreach ((array) $subDokumen as $file) {
                if ($file) {
                    return true;
                }
            }
        }

        return false;
    }

    private function requestHasCompletionProofUploads(Request $request): bool
    {
        foreach ((array) $request->file('bukti_penyelesaian', []) as $file) {
            if ($file) {
                return true;
            }
        }

        return false;
    }
}
