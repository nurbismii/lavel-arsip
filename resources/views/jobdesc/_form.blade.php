@php($jobdesc = $jobdesc ?? null)
@php($spec = old('spesifikasi_pekerjaan', optional($jobdesc)->spesifikasi_pekerjaan ?? []))
@php($tugasPokok = old('tugas_pokok', optional($jobdesc)->tugas_pokok ?? [[]]))
@php($struktur = old('struktur_organisasi', optional($jobdesc)->struktur_organisasi ?? [[]]))
@php($pendidikan = old('spesifikasi_pekerjaan.pendidikan', data_get($spec, 'pendidikan', [[]])))
@php($revisi = old('catatan_revisi', optional($jobdesc)->catatan_revisi ?? [[]]))

<div class="app-page-header">
    <div><span class="app-page-eyebrow">HRIS</span>
        <h4 class="app-page-title mb-1">Form Uraian Jabatan</h4>
        <p class="app-page-subtitle">Lengkapi sesuai panduan HRIS. Field bertanda <span class="text-danger">*</span> wajib diisi.</p>
    </div>
</div>

<div class="app-card p-3 p-md-4 mb-4">
    <h5 class="mb-3">I. Identitas Jabatan</h5>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Jabatan <span class="text-danger">*</span></label><input name="jabatan" class="form-control @error('jabatan') is-invalid @enderror" value="{{ old('jabatan', optional($jobdesc)->jabatan) }}" maxlength="200" required placeholder="Contoh: HR Generalist">@error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label">Job Code</label><input name="job_code" class="form-control @error('job_code') is-invalid @enderror" value="{{ old('job_code', optional($jobdesc)->job_code) }}" maxlength="100" placeholder="VDNI-HRD-07">@error('job_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label">Golongan / Level</label><input name="golongan_level" class="form-control" value="{{ old('golongan_level', optional($jobdesc)->golongan_level) }}" placeholder="Non-Golongan / Staf"></div>
        <div class="col-md-4"><label class="form-label">Divisi</label><input name="divisi" class="form-control" value="{{ old('divisi', optional($jobdesc)->divisi) }}"></div>
        <div class="col-md-4"><label class="form-label">Departemen</label><input name="departemen" class="form-control" value="{{ old('departemen', optional($jobdesc)->departemen) }}"></div>
        <div class="col-md-4"><label class="form-label">Area</label><input name="area" class="form-control" value="{{ old('area', optional($jobdesc)->area) }}"></div>
        <div class="col-md-5"><label class="form-label">Atasan Langsung</label><input name="atasan_langsung" class="form-control" value="{{ old('atasan_langsung', optional($jobdesc)->atasan_langsung) }}" placeholder="Pisahkan dengan koma bila lebih dari satu"></div>
        <div class="col-md-5"><label class="form-label">Bawahan Langsung</label><input name="bawahan_langsung" class="form-control" value="{{ old('bawahan_langsung', optional($jobdesc)->bawahan_langsung) }}" placeholder="Pisahkan dengan koma bila lebih dari satu"></div>
        <div class="col-md-2"><label class="form-label">Jumlah Bawahan</label><input name="jumlah_bawahan" type="number" min="0" class="form-control" value="{{ old('jumlah_bawahan', optional($jobdesc)->jumlah_bawahan) }}"></div>
        <div class="col-md-4"><label class="form-label">Unit / Tim</label><select name="team_id" class="form-select">
                <option value="">Tidak ditetapkan</option>@foreach($teams as $team)<option value="{{ $team->id }}" {{ (int) old('team_id', optional($jobdesc)->team_id) === (int) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>@endforeach
            </select></div>
        <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" {{ old('status', optional($jobdesc)->status ?: \App\Models\Jobdesc::STATUS_DRAFT) === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Penanggung Jawab</label><select name="pemilik_user_id" class="form-select" {{ auth()->user()->canAccessAllFiles() ? '' : 'disabled' }}>@foreach($users as $user)<option value="{{ $user->id }}" {{ (int) old('pemilik_user_id', optional($jobdesc)->pemilik_user_id ?: auth()->id()) === (int) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select>@if(!auth()->user()->canAccessAllFiles())<input type="hidden" name="pemilik_user_id" value="{{ auth()->id() }}">@endif</div>
    </div>
</div>

<div class="app-card p-3 p-md-4 mb-4">
    <h5>II. Ringkasan Jabatan</h5>
    <p class="text-muted small">Ringkas tujuan dan ruang lingkup jabatan. Maksimal 2.000 karakter.</p><textarea name="ringkasan_jabatan" rows="5" maxlength="2000" class="form-control">{{ old('ringkasan_jabatan', optional($jobdesc)->ringkasan_jabatan) }}</textarea>
</div>

<div class="app-card p-3 p-md-4 mb-4">
    <h5>III. Struktur Organisasi</h5>
    <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Bagan Struktur Organisasi</label><input type="file" name="bagan_struktur" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control"><small class="text-muted">PNG, JPG, JPEG, WEBP, atau GIF. Maksimal 2 MB.</small>@if(optional($jobdesc)->bagan_struktur_path)<a class="d-block mt-2" target="_blank" href="{{ asset('storage/'.$jobdesc->bagan_struktur_path) }}">Lihat bagan saat ini</a>@endif</div>
    </div>
    <div data-rows="struktur">@foreach($struktur as $i => $row)<div class="row g-2 align-items-end mb-2 repeat-row">
            <div class="col-md-5"><label class="form-label small">Atasan</label><input class="form-control" name="struktur_organisasi[{{ $i }}][atasan]" value="{{ data_get($row, 'atasan') }}"></div>
            <div class="col-md-2"><label class="form-label small">Jumlah</label><input type="number" min="0" class="form-control" name="struktur_organisasi[{{ $i }}][jumlah_atasan]" value="{{ data_get($row, 'jumlah_atasan') }}"></div>
            <div class="col-md-3"><label class="form-label small">Bawahan</label><input class="form-control" name="struktur_organisasi[{{ $i }}][bawahan]" value="{{ data_get($row, 'bawahan') }}"></div>
            <div class="col-md-1"><label class="form-label small">Jumlah</label><input type="number" min="0" class="form-control" name="struktur_organisasi[{{ $i }}][jumlah_bawahan]" value="{{ data_get($row, 'jumlah_bawahan') }}"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" data-remove-row aria-label="Hapus baris">×</button></div>
        </div>@endforeach</div><button type="button" class="btn btn-outline-primary btn-sm" data-add-row="struktur">+ Tambah Baris</button>
</div>

<div class="app-card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-1">IV. Tugas</h5>
            <p class="text-muted small mb-0">Tulis rincian sebagai daftar berpoin, satu butir per baris.</p>
        </div><button type="button" class="btn btn-outline-primary btn-sm" data-add-row="tugas">+ Tambah Tugas Pokok</button>
    </div>
    <div data-rows="tugas">@foreach($tugasPokok as $i => $row)<div class="border rounded-3 p-3 mb-3 repeat-row">
            <div class="d-flex justify-content-between"><strong>Tugas Pokok</strong><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>Hapus</button></div>
            <div class="row g-2 mt-1">
                <div class="col-md-4"><label class="form-label small">Nama Pekerjaan</label><input class="form-control" name="tugas_pokok[{{ $i }}][nama]" value="{{ data_get($row, 'nama') }}"></div>
                <div class="col-md-8"><label class="form-label small">Rincian Pekerjaan</label><textarea class="form-control" rows="3" name="tugas_pokok[{{ $i }}][rincian]">{{ data_get($row, 'rincian') }}</textarea></div>
            </div>
        </div>@endforeach</div>
    <div class="row g-3 mt-1">
        <div class="col-md-6"><label class="form-label">Tugas Tambahan</label><textarea name="tugas_tambahan" rows="5" class="form-control">{{ old('tugas_tambahan', optional($jobdesc)->tugas_tambahan) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Output Pekerjaan</label><textarea name="output_pekerjaan" rows="5" class="form-control">{{ old('output_pekerjaan', optional($jobdesc)->output_pekerjaan) }}</textarea></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="app-card p-3 h-100">
            <h5>V. Hak</h5><textarea name="hak" rows="8" class="form-control">{{ old('hak', optional($jobdesc)->hak) }}</textarea>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="app-card p-3 h-100">
            <h5>VI. Kewajiban</h5><textarea name="kewajiban" rows="8" class="form-control">{{ old('kewajiban', optional($jobdesc)->kewajiban) }}</textarea>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="app-card p-3 h-100">
            <h5>VII. Wewenang</h5><textarea name="wewenang" rows="8" class="form-control">{{ old('wewenang', optional($jobdesc)->wewenang) }}</textarea>
        </div>
    </div>
</div>

<div class="app-card p-3 p-md-4 mb-4">
    <h5>VIII. Hubungan Kerja</h5>
    <div class="row g-3">@foreach(['internal_jarang' => 'Internal - Jarang', 'internal_sering' => 'Internal - Sering', 'eksternal_jarang' => 'Eksternal - Jarang', 'eksternal_sering' => 'Eksternal - Sering'] as $key => $label)<div class="col-md-6"><label class="form-label">{{ $label }}</label><textarea name="hubungan_kerja[{{ $key }}]" rows="3" class="form-control">{{ old('hubungan_kerja.'.$key, data_get(optional($jobdesc)->hubungan_kerja, $key)) }}</textarea></div>@endforeach</div>
</div>
<div class="app-card p-3 p-md-4 mb-4">
    <h5>IX. Lingkungan Kerja</h5><textarea name="lingkungan_kerja" rows="5" class="form-control">{{ old('lingkungan_kerja', optional($jobdesc)->lingkungan_kerja) }}</textarea>
</div>

<div class="app-card p-3 p-md-4 mb-4">
    <h5>X. Spesifikasi Pekerjaan</h5>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Umur</label><input name="spesifikasi_pekerjaan[umur]" class="form-control" value="{{ data_get($spec, 'umur') }}" placeholder="20 - 30"></div>
        <div class="col-md-5"><label class="form-label d-block">Jenis Kelamin</label>@foreach(['Laki-laki', 'Perempuan'] as $gender)<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="spesifikasi_pekerjaan[jenis_kelamin][]" value="{{ $gender }}" id="gender-{{ $loop->index }}" @checked(in_array($gender, (array) data_get($spec, 'jenis_kelamin' , [])))><label class="form-check-label" for="gender-{{ $loop->index }}">{{ $gender }}</label></div>@endforeach</div>
        <div class="col-md-4"><label class="form-label">Pengalaman Kerja</label><select name="spesifikasi_pekerjaan[pengalaman][]" multiple class="form-select" size="4">@foreach(['< 1 tahun','1 - 2 tahun','3 - 4 tahun','5 - 6 tahun','7 - 8 tahun','9 - 10 tahun','> 10 tahun'] as $item)<option value="{{ $item }}" @selected(in_array($item, (array) data_get($spec, 'pengalaman' , [])))>{{ $item }}</option>@endforeach</select><small class="text-muted">Tekan Ctrl untuk pilih lebih dari satu.</small></div>
    </div>
    <hr>
    <div class="d-flex justify-content-between">
        <h6>Pendidikan</h6><button type="button" class="btn btn-outline-primary btn-sm" data-add-row="pendidikan">+ Tambah Pendidikan</button>
    </div>
    <div data-rows="pendidikan">@foreach($pendidikan as $i => $row)<div class="row g-2 align-items-end mb-2 repeat-row">
            <div class="col-md-5"><label class="form-label small">Jenjang</label><select class="form-select" name="spesifikasi_pekerjaan[pendidikan][{{ $i }}][jenjang]">
                    <option value="">Pilih jenjang</option>@foreach(['SD/sederajat','SMP/sederajat','SMA/sederajat','D1','D2','D3','D4','S1','S2','S3','Profesi'] as $item)<option value="{{ $item }}" @selected(data_get($row, 'jenjang' )===$item)>{{ $item }}</option>@endforeach
                </select></div>
            <div class="col-md-6"><label class="form-label small">Jurusan</label><input class="form-control" name="spesifikasi_pekerjaan[pendidikan][{{ $i }}][jurusan]" value="{{ data_get($row, 'jurusan') }}" placeholder="Pisahkan dengan koma bila lebih dari satu"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>×</button></div>
        </div>@endforeach</div>
    <div class="row g-3 mt-1">
        <div class="col-md-6"><label class="form-label">Kompetensi Teknis</label><textarea name="spesifikasi_pekerjaan[kompetensi_teknis]" rows="5" class="form-control">{{ data_get($spec, 'kompetensi_teknis') }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Kompetensi Manajerial</label><textarea name="spesifikasi_pekerjaan[kompetensi_manajerial]" rows="5" class="form-control">{{ data_get($spec, 'kompetensi_manajerial') }}</textarea></div>
    </div>
</div>

<div class="app-card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">XI. Catatan Revisi</h5>
            <p class="text-muted small mb-0">Tambahkan riwayat apabila dokumen diperbarui.</p>
        </div><button type="button" class="btn btn-outline-primary btn-sm" data-add-row="revisi">+ Tambah Revisi</button>
    </div>
    <div data-rows="revisi" class="mt-3">@foreach($revisi as $i => $row)<div class="border rounded-3 p-3 mb-3 repeat-row">
            <div class="row g-2">
                <div class="col-md-2"><label class="form-label small">No. Revisi</label><input name="catatan_revisi[{{ $i }}][nomor]" maxlength="3" class="form-control" value="{{ data_get($row, 'nomor') }}"></div>
                <div class="col-md-3"><label class="form-label small">Tanggal</label><input type="date" name="catatan_revisi[{{ $i }}][tanggal]" class="form-control" value="{{ data_get($row, 'tanggal') }}"></div>
                <div class="col-md-5"><label class="form-label small">Pihak yang Merevisi</label><input name="catatan_revisi[{{ $i }}][pihak]" class="form-control" value="{{ data_get($row, 'pihak') }}"></div>
                <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>Hapus</button></div>
                <div class="col-md-6"><label class="form-label small">Deskripsi Perubahan</label><textarea name="catatan_revisi[{{ $i }}][deskripsi]" rows="3" class="form-control">{{ data_get($row, 'deskripsi') }}</textarea></div>
                <div class="col-md-6"><label class="form-label small">Alasan Revisi</label><textarea name="catatan_revisi[{{ $i }}][alasan]" rows="3" class="form-control">{{ data_get($row, 'alasan') }}</textarea></div>
            </div>
        </div>@endforeach</div>
</div>

<div class="app-card p-3 p-md-4 mb-4"><label class="form-label">Kata Kunci</label><input name="kata_kunci" class="form-control" value="{{ old('kata_kunci', optional($jobdesc)->kata_kunci) }}" placeholder="Contoh: HR, rekrutmen, payroll"><small class="text-muted">Pisahkan dengan koma agar mudah ditemukan.</small></div>

<div class="d-flex flex-column flex-md-row justify-content-end gap-2"><a href="{{ $jobdesc ? route('jobdesc.show', $jobdesc) : route('jobdesc.index') }}" class="btn btn-outline-secondary">Batal</a><button type="submit" class="btn btn-primary" data-loading-text="Menyimpan...">{{ $jobdesc ? 'Simpan Perubahan' : 'Simpan Uraian Jabatan' }}</button></div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const templates = {
            struktur: '<div class="row g-2 align-items-end mb-2 repeat-row"><div class="col-md-5"><label class="form-label small">Atasan</label><input class="form-control" name="struktur_organisasi[__i__][atasan]"></div><div class="col-md-2"><label class="form-label small">Jumlah</label><input type="number" min="0" class="form-control" name="struktur_organisasi[__i__][jumlah_atasan]"></div><div class="col-md-3"><label class="form-label small">Bawahan</label><input class="form-control" name="struktur_organisasi[__i__][bawahan]"></div><div class="col-md-1"><label class="form-label small">Jumlah</label><input type="number" min="0" class="form-control" name="struktur_organisasi[__i__][jumlah_bawahan]"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>×</button></div></div>',
            tugas: '<div class="border rounded-3 p-3 mb-3 repeat-row"><div class="d-flex justify-content-between"><strong>Tugas Pokok</strong><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>Hapus</button></div><div class="row g-2 mt-1"><div class="col-md-4"><label class="form-label small">Nama Pekerjaan</label><input class="form-control" name="tugas_pokok[__i__][nama]"></div><div class="col-md-8"><label class="form-label small">Rincian Pekerjaan</label><textarea class="form-control" rows="3" name="tugas_pokok[__i__][rincian]"></textarea></div></div></div>',
            pendidikan: '<div class="row g-2 align-items-end mb-2 repeat-row"><div class="col-md-5"><label class="form-label small">Jenjang</label><select class="form-select" name="spesifikasi_pekerjaan[pendidikan][__i__][jenjang]"><option value="">Pilih jenjang</option><option>SD/sederajat</option><option>SMP/sederajat</option><option>SMA/sederajat</option><option>D1</option><option>D2</option><option>D3</option><option>D4</option><option>S1</option><option>S2</option><option>S3</option><option>Profesi</option></select></div><div class="col-md-6"><label class="form-label small">Jurusan</label><input class="form-control" name="spesifikasi_pekerjaan[pendidikan][__i__][jurusan]"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>×</button></div></div>',
            revisi: '<div class="border rounded-3 p-3 mb-3 repeat-row"><div class="row g-2"><div class="col-md-2"><label class="form-label small">No. Revisi</label><input name="catatan_revisi[__i__][nomor]" maxlength="3" class="form-control"></div><div class="col-md-3"><label class="form-label small">Tanggal</label><input type="date" name="catatan_revisi[__i__][tanggal]" class="form-control"></div><div class="col-md-5"><label class="form-label small">Pihak yang Merevisi</label><input name="catatan_revisi[__i__][pihak]" class="form-control"></div><div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>Hapus</button></div><div class="col-md-6"><label class="form-label small">Deskripsi Perubahan</label><textarea name="catatan_revisi[__i__][deskripsi]" rows="3" class="form-control"></textarea></div><div class="col-md-6"><label class="form-label small">Alasan Revisi</label><textarea name="catatan_revisi[__i__][alasan]" rows="3" class="form-control"></textarea></div></div></div>'
        };
        document.addEventListener('click', function(event) {
            const add = event.target.closest('[data-add-row]');
            if (add) {
                const key = add.dataset.addRow,
                    container = document.querySelector('[data-rows="' + key + '"]');
                const index = container.querySelectorAll('.repeat-row').length + Date.now();
                container.insertAdjacentHTML('beforeend', templates[key].replaceAll('__i__', index));
            }
            const remove = event.target.closest('[data-remove-row]');
            if (remove) remove.closest('.repeat-row').remove();
        });
    });
</script>
@endpush