@php
    $rows = collect($rows ?? [])->values();
    $rows = $rows->isEmpty() ? collect([[]]) : $rows;
@endphp

<div data-structured-list="pic" data-name-prefix="{{ $namePrefix }}">
    @foreach($rows as $rowIndex => $pic)
        <div class="structured-row pic-row draggable-structured-row border rounded-3 p-3 mb-2 bg-white" draggable="true">
            <input
                type="hidden"
                name="{{ $namePrefix }}[{{ $rowIndex }}][urutan]"
                value="{{ data_get($pic, 'urutan', $rowIndex + 1) }}"
                data-order-input
                data-pic-order-input>
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border structured-drag-handle pic-drag-handle" title="Geser untuk mengubah urutan" aria-label="Geser PIC">Drag</button>
                    <strong><span data-order-label data-pic-order-label>PIC {{ data_get($pic, 'urutan', $rowIndex + 1) }}</span> / Orang Terkait</strong>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-structured-row">Hapus</button>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Nama PIC</label>
                    <input
                        type="text"
                        name="{{ $namePrefix }}[{{ $rowIndex }}][nama]"
                        class="form-control @error($errorPrefix . '.' . $rowIndex . '.nama') is-invalid @enderror"
                        value="{{ data_get($pic, 'nama') }}"
                        placeholder="Nama orang atau tim">
                    @error($errorPrefix . '.' . $rowIndex . '.nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Peran</label>
                    <input
                        type="text"
                        name="{{ $namePrefix }}[{{ $rowIndex }}][peran]"
                        class="form-control"
                        value="{{ data_get($pic, 'peran') }}"
                        placeholder="Contoh: HR Recruitment, Kepala Departemen">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kontak</label>
                    <input
                        type="text"
                        name="{{ $namePrefix }}[{{ $rowIndex }}][kontak]"
                        class="form-control"
                        value="{{ data_get($pic, 'kontak') }}"
                        placeholder="Email, nomor HP, extension">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kapan Dihubungi</label>
                    <input
                        type="text"
                        name="{{ $namePrefix }}[{{ $rowIndex }}][waktu_dihubungi]"
                        class="form-control"
                        value="{{ data_get($pic, 'waktu_dihubungi') }}"
                        placeholder="Contoh: setelah verifikasi online selesai">
                </div>

                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea
                        name="{{ $namePrefix }}[{{ $rowIndex }}][catatan]"
                        rows="2"
                        class="form-control"
                        placeholder="Hal khusus terkait PIC ini">{{ data_get($pic, 'catatan') }}</textarea>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-end mt-2">
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-pic-row>+ Tambah PIC</button>
</div>
