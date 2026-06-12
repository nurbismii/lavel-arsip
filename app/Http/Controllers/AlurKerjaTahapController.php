<?php

namespace App\Http\Controllers;

use App\Models\AlurKerja;
use App\Models\AlurKerjaTahap;
use App\Models\AlurKerjaTahapLampiran;
use App\Models\AlurKerjaTahapPic;
use App\Models\AlurKerjaTahapSistem;
use App\Services\ActivityLogService;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlurKerjaTahapController extends Controller
{
    public function store(Request $request, AlurKerja $alurKerja)
    {
        $this->pastikanAlurKerjaBisaDiatur($alurKerja);

        $data = $this->validatedData($request);
        $sistemRows = $this->validatedSistemRows((array) data_get($data, 'sistem', []), 'sistem');
        $picRows = $this->validatedPicRows((array) data_get($data, 'pic', []), 'pic');

        $tahap = AlurKerjaTahap::create($this->stageData($data, $alurKerja));
        $this->simpanSistemTahap($tahap, $sistemRows);
        $this->simpanPicTahap($tahap, $picRows);
        $this->simpanLampiran($alurKerja, $tahap, $request->file('lampiran', []));

        ActivityLogService::log(
            'alur_kerja.tahap.create',
            'Menambahkan tahap alur kerja.',
            $tahap
        );

        return redirect()
            ->route('alur-kerja.show', $alurKerja->id)
            ->with('success', 'Tahap alur kerja berhasil ditambahkan.');
    }

    public function update(Request $request, AlurKerja $alurKerja, AlurKerjaTahap $tahap)
    {
        $this->pastikanTahapMilikAlurKerja($alurKerja, $tahap);
        $this->pastikanAlurKerjaBisaDiatur($alurKerja);

        $data = $this->validatedData($request);
        $sistemRows = $this->validatedSistemRows((array) data_get($data, 'sistem', []), 'sistem');
        $picRows = $this->validatedPicRows((array) data_get($data, 'pic', []), 'pic');

        $tahap->update($this->stageData($data, $alurKerja, $tahap));
        $tahap->sistems()->delete();
        $tahap->pics()->delete();
        $this->simpanSistemTahap($tahap, $sistemRows);
        $this->simpanPicTahap($tahap, $picRows);
        $this->simpanLampiran($alurKerja, $tahap, $request->file('lampiran', []));

        ActivityLogService::log(
            'alur_kerja.tahap.update',
            'Memperbarui tahap alur kerja.',
            $tahap
        );

        return redirect()
            ->route('alur-kerja.show', $alurKerja->id)
            ->with('success', 'Tahap alur kerja berhasil diperbarui.');
    }

    public function destroy(AlurKerja $alurKerja, AlurKerjaTahap $tahap)
    {
        $this->pastikanTahapMilikAlurKerja($alurKerja, $tahap);
        $this->pastikanAlurKerjaBisaDiatur($alurKerja);

        $tahap->load('lampirans');

        foreach ($tahap->lampirans as $lampiran) {
            $this->hapusFileLampiran($lampiran);
            $lampiran->delete();
        }

        $tahap->sistems()->delete();
        $tahap->pics()->delete();
        $tahap->delete();

        ActivityLogService::log(
            'alur_kerja.tahap.delete',
            'Menghapus tahap alur kerja.',
            $tahap
        );

        return redirect()
            ->route('alur-kerja.show', $alurKerja->id)
            ->with('success', 'Tahap alur kerja berhasil dihapus.');
    }

    public function showLampiran(AlurKerja $alurKerja, AlurKerjaTahap $tahap, AlurKerjaTahapLampiran $lampiran)
    {
        $this->pastikanTahapMilikAlurKerja($alurKerja, $tahap);
        $this->pastikanLampiranMilikTahap($tahap, $lampiran);

        abort_unless(
            AlurKerja::query()->visibleTo(auth()->user())->whereKey($alurKerja->id)->exists(),
            403,
            'Anda tidak memiliki izin melihat lampiran tahap ini.'
        );

        if (!$lampiran->path || !Storage::disk($lampiran->storage_disk)->exists($lampiran->path)) {
            abort(404, 'Lampiran tahap tidak ditemukan.');
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

    public function destroyLampiran(AlurKerja $alurKerja, AlurKerjaTahap $tahap, AlurKerjaTahapLampiran $lampiran)
    {
        $this->pastikanTahapMilikAlurKerja($alurKerja, $tahap);
        $this->pastikanLampiranMilikTahap($tahap, $lampiran);
        $this->pastikanAlurKerjaBisaDiatur($alurKerja);

        $this->hapusFileLampiran($lampiran);
        $lampiran->delete();

        ActivityLogService::log(
            'alur_kerja.tahap.lampiran.delete',
            'Menghapus lampiran tahap alur kerja.',
            $lampiran
        );

        return redirect()
            ->route('alur-kerja.show', $alurKerja->id)
            ->with('success', 'Lampiran tahap berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'estimasi' => ['nullable', 'string', 'max:100'],
            'aplikasi_digunakan' => ['nullable', 'string'],
            'akun_digunakan' => ['nullable', 'string'],
            'pic_terkait' => ['nullable', 'string'],
            'kontak_pic' => ['nullable', 'string'],
            'sistem' => ['nullable', 'array'],
            'sistem.*.urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'sistem.*.nama_sistem' => ['nullable', 'string', 'max:255'],
            'sistem.*.fungsi' => ['nullable', 'string'],
            'sistem.*.akun' => ['nullable', 'string'],
            'sistem.*.url' => ['nullable', 'string', 'max:500'],
            'sistem.*.catatan' => ['nullable', 'string'],
            'pic' => ['nullable', 'array'],
            'pic.*.urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'pic.*.nama' => ['nullable', 'string', 'max:255'],
            'pic.*.peran' => ['nullable', 'string', 'max:255'],
            'pic.*.kontak' => ['nullable', 'string', 'max:500'],
            'pic.*.waktu_dihubungi' => ['nullable', 'string'],
            'pic.*.catatan' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
            'lampiran.*' => ['nullable', 'file', 'max:20480'],
        ]);

        return RichText::sanitizeFields($data, ['deskripsi', 'catatan']);
    }

    private function stageData(array $data, AlurKerja $alurKerja, AlurKerjaTahap $tahap = null): array
    {
        return [
            'alur_kerja_id' => $alurKerja->id,
            'urutan' => ($data['urutan'] ?? null) ?: ($tahap ? $tahap->urutan : $this->urutanBerikutnya($alurKerja)),
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'estimasi' => $this->nullableTrimmedString($data['estimasi'] ?? null),
            'aplikasi_digunakan' => $data['aplikasi_digunakan'] ?? null,
            'akun_digunakan' => $data['akun_digunakan'] ?? null,
            'pic_terkait' => $data['pic_terkait'] ?? null,
            'kontak_pic' => $data['kontak_pic'] ?? null,
            'catatan' => $data['catatan'] ?? null,
        ];
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

    private function simpanLampiran(AlurKerja $alurKerja, AlurKerjaTahap $tahap, $files): void
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

    private function nullableTrimmedString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function urutanBerikutnya(AlurKerja $alurKerja): int
    {
        return ((int) $alurKerja->tahaps()->max('urutan')) + 1;
    }

    private function pastikanAlurKerjaBisaDiatur(AlurKerja $alurKerja): void
    {
        abort_unless(
            AlurKerja::query()->manageableBy(auth()->user())->whereKey($alurKerja->id)->exists(),
            403,
            'Anda tidak memiliki izin mengubah alur kerja ini.'
        );
    }

    private function pastikanTahapMilikAlurKerja(AlurKerja $alurKerja, AlurKerjaTahap $tahap): void
    {
        abort_unless((int) $tahap->alur_kerja_id === (int) $alurKerja->id, 404);
    }

    private function pastikanLampiranMilikTahap(AlurKerjaTahap $tahap, AlurKerjaTahapLampiran $lampiran): void
    {
        abort_unless((int) $lampiran->alur_kerja_tahap_id === (int) $tahap->id, 404);
    }
}
