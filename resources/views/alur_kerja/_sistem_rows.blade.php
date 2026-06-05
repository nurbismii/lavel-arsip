@php
    $rows = collect($rows ?? [])->values();
    $rows = $rows->isEmpty() ? collect([[]]) : $rows;
@endphp

<div data-structured-list="sistem" data-name-prefix="{{ $namePrefix }}">
    @foreach($rows as $rowIndex => $sistem)
        <div class="structured-row system-row draggable-structured-row border rounded-3 p-3 mb-2 bg-white" draggable="true">
            <input
                type="hidden"
                name="{{ $namePrefix }}[{{ $rowIndex }}][urutan]"
                value="{{ data_get($sistem, 'urutan', $rowIndex + 1) }}"
                data-order-input>
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border structured-drag-handle" title="Geser untuk mengubah urutan" aria-label="Geser Sistem / Aplikasi">Drag</button>
                    <strong><span data-order-label>Sistem {{ data_get($sistem, 'urutan', $rowIndex + 1) }}</span> / Aplikasi</strong>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-structured-row">Hapus</button>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Nama Sistem</label>
                    <input
                        type="text"
                        name="{{ $namePrefix }}[{{ $rowIndex }}][nama_sistem]"
                        class="form-control @error($errorPrefix . '.' . $rowIndex . '.nama_sistem') is-invalid @enderror"
                        value="{{ data_get($sistem, 'nama_sistem') }}"
                        placeholder="Contoh: HRIS, Email, Google Form">
                    @error($errorPrefix . '.' . $rowIndex . '.nama_sistem')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Akun yang Digunakan</label>
                    <input
                        type="text"
                        name="{{ $namePrefix }}[{{ $rowIndex }}][akun]"
                        class="form-control"
                        value="{{ data_get($sistem, 'akun') }}"
                        placeholder="Contoh: recruitment@company.com">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fungsi</label>
                    <textarea
                        name="{{ $namePrefix }}[{{ $rowIndex }}][fungsi]"
                        rows="2"
                        class="form-control"
                        placeholder="Dipakai untuk apa pada tahap ini">{{ data_get($sistem, 'fungsi') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">URL / Lokasi Akses</label>
                    <input
                        type="text"
                        name="{{ $namePrefix }}[{{ $rowIndex }}][url]"
                        class="form-control"
                        value="{{ data_get($sistem, 'url') }}"
                        placeholder="https://... atau lokasi aplikasi">
                </div>

                <div class="col-12">
                    <label class="form-label">Catatan Akses</label>
                    <textarea
                        name="{{ $namePrefix }}[{{ $rowIndex }}][catatan]"
                        rows="2"
                        class="form-control"
                        placeholder="Hak akses, batasan, atau prosedur login">{{ data_get($sistem, 'catatan') }}</textarea>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-end mt-2">
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-system-row>+ Tambah Sistem</button>
</div>
