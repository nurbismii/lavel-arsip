<?php

namespace App\Http\Controllers;

use App\Models\Pekerjaan;
use App\Models\Dokumen;
use App\Models\Lokasi;
use App\Models\Team;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class PekerjaanController extends Controller
{
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

    public function index()
    {
        $search = trim((string) request('search', ''));
        $perPage = 10;

        $query = Pekerjaan::with(['lokasi', 'team'])
            ->withCount([
                'subPekerjaans' => function ($query) {
                    $this->applyVisiblePekerjaanScope($query);
                },
                'dokumens',
            ])
            ->whereNull('parent_id');

        $this->applyVisiblePekerjaanScope($query);

        $query->orderBy('id');

        if ($search !== '') {
            $rootIds = $this->findRelatedRootIds($search);

            if (empty($rootIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $rootIds);
            }
        }

        $pekerjaans = $query->paginate($perPage)->withQueryString();

        return view('pekerjaan.index', compact('pekerjaans', 'search'));
    }

    public function treeContent(Pekerjaan $pekerjaan)
    {
        $this->pastikanPekerjaanBisaDilihat($pekerjaan);

        $pekerjaan->load([
            'lokasi',
            'team',
            'dokumens' => function ($query) {
                $query->orderBy('id');
            },
            'subPekerjaans' => function ($query) {
                $this->applyVisiblePekerjaanScope($query);

                $query->with(['lokasi', 'team'])
                    ->withCount([
                        'subPekerjaans' => function ($query) {
                            $this->applyVisiblePekerjaanScope($query);
                        },
                        'dokumens',
                    ])
                    ->orderBy('id');
            },
        ]);

        return response()->json([
            'html' => view('pekerjaan.partials.tree-content', [
                'item' => $pekerjaan,
            ])->render(),
        ]);
    }

    public function create()
    {
        $parents = Pekerjaan::query()
            ->manageableBy(auth()->user())
            ->with('team')
            ->orderBy('judul')
            ->get();
        $lokasis = Lokasi::orderBy('nama_lokasi')->get();
        $teams = $this->availableTeamsForCurrentUser();
        $statusDokumenOptions = Dokumen::statusOptions();

        return view('pekerjaan.create', compact('parents', 'lokasis', 'teams', 'statusDokumenOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:pekerjaan,id'],
            'lokasi_id' => ['required', 'integer', 'exists:lokasi_dokumen,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'status_dokumen' => ['nullable', Rule::in(array_keys(Dokumen::statusOptions()))],
            'dokumen.*' => ['nullable', 'file', 'max:20480'],
            'sub_judul' => ['nullable', 'array'],
            'sub_judul.*' => ['nullable', 'string', 'max:255'],
            'sub_status_dokumen' => ['nullable', 'array'],
            'sub_status_dokumen.*' => ['nullable', Rule::in(array_keys(Dokumen::statusOptions()))],
            'sub_dokumen' => ['nullable', 'array'],
            'sub_dokumen.*' => ['nullable', 'array'],
            'sub_dokumen.*.*' => ['nullable', 'file', 'max:20480'],
        ]);

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

        $pekerjaan = Pekerjaan::create([
            'judul' => $data['judul'],
            'parent_id' => $data['parent_id'] ?? null,
            'user_id' => auth()->id(),
            'lokasi_id' => $data['lokasi_id'] ?? null,
            'team_id' => $teamId,
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
                    'parent_id' => $pekerjaan->id,
                    'user_id' => auth()->id(),
                    'lokasi_id' => $data['lokasi_id'] ?? null,
                    'team_id' => $teamId,
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
        $pekerjaan = Pekerjaan::with(['dokumens', 'lokasi', 'team'])->findOrFail($id);
        $this->pastikanPekerjaanBisaDiatur($pekerjaan);

        $parents = Pekerjaan::query()
            ->manageableBy(auth()->user())
            ->where('id', '!=', $id)
            ->with('team')
            ->orderBy('judul')
            ->get();
        $lokasis = Lokasi::orderBy('nama_lokasi')->get();
        $teams = $this->availableTeamsForCurrentUser();
        $statusDokumenOptions = Dokumen::statusOptions();

        return view('pekerjaan.edit', compact('pekerjaan', 'parents', 'lokasis', 'teams', 'statusDokumenOptions'));
    }

    public function update(Request $request, $id)
    {
        $pekerjaan = Pekerjaan::findOrFail($id);
        $this->pastikanPekerjaanBisaDiatur($pekerjaan);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:pekerjaan,id', Rule::notIn([$pekerjaan->id])],
            'lokasi_id' => ['nullable', 'integer', 'exists:lokasi_dokumen,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'status_dokumen' => ['nullable', Rule::in(array_keys(Dokumen::statusOptions()))],
            'existing_status_dokumen' => ['nullable', 'array'],
            'existing_status_dokumen.*' => ['nullable', Rule::in(array_keys(Dokumen::statusOptions()))],
            'dokumen.*' => ['nullable', 'file', 'max:20480'],
        ]);

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

        $pekerjaan->update([
            'judul' => $data['judul'],
            'parent_id' => $data['parent_id'] ?? null,
            'lokasi_id' => $data['lokasi_id'] ?? null,
            'team_id' => $teamId,
        ]);

        $this->simpanDokumen(
            $pekerjaan,
            $request->file('dokumen', []),
            $data['status_dokumen'] ?? Dokumen::STATUS_DRAFT
        );

        $this->updateStatusDokumenExisting(
            $pekerjaan,
            $data['existing_status_dokumen'] ?? []
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

        if ($dokumen->pekerjaan_id !== $pekerjaan->id) {
            abort(404);
        }

        $data = $request->validate([
            'status_dokumen' => ['required', Rule::in(array_keys(Dokumen::statusOptions()))],
        ]);

        $dokumen->update([
            'status_dokumen' => $data['status_dokumen'],
        ]);

        ActivityLogService::log(
            'dokumen.status',
            'Mengubah status dokumen menjadi ' . $dokumen->status_dokumen_label . '.',
            $dokumen
        );

        return redirect()
            ->route('pekerjaan.edit', $pekerjaan->id)
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
}
