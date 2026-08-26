@extends('layouts.app')

@section('content')
<div class="container py-4">
    @php
        $hasFilledStructuredRows = function ($rows) {
            return collect($rows ?? [])->contains(function ($row) {
                return collect($row)
                    ->except(['urutan'])
                    ->filter(function ($value) {
                        return trim((string) $value) !== '';
                    })
                    ->isNotEmpty();
            });
        };
    @endphp

    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Fondasi V-Ops</span>
            <h4 class="app-page-title">Tambah Alur Kerja</h4>
            <p class="app-page-subtitle">Dokumentasikan proses operasional lengkap dengan tahapan, PIC, aplikasi, akun, dan template yang digunakan.</p>
        </div>
        <div class="app-page-actions">
            <a href="{{ route('alur-kerja.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Gagal menyimpan alur kerja.</strong>
            <div class="mb-2">Periksa kembali data wajib dan lampiran yang dipilih.</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="app-card">
        <div class="card-body">
            <form method="POST" action="{{ route('alur-kerja.store') }}" enctype="multipart/form-data" data-loading-form>
                @csrf

                @include('alur_kerja._form')

                @php
                    $tahapAwalRows = old('tahap', [
                        [
                            'urutan' => 1,
                            'nama' => '',
                            'deskripsi' => '',
                            'lokasi' => '',
                            'estimasi' => '',
                            'aplikasi_digunakan' => '',
                            'akun_digunakan' => '',
                            'pic_terkait' => '',
                            'kontak_pic' => '',
                            'sistem' => [[]],
                            'pic' => [[]],
                            'catatan' => '',
                        ],
                    ]);
                @endphp

                <hr class="my-4">

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h6 class="fw-semibold mb-1">Tahapan Proses</h6>
                        <small class="text-muted">Isi urutan kerja seperti permintaan tenaga kerja, open rekrutmen, verifikasi berkas, dan seterusnya.</small>
                    </div>
                </div>

                <div id="tahap-rows" class="d-grid gap-3">
                    @foreach($tahapAwalRows as $index => $tahap)
                        @php
                            $sistemRows = old('tahap.' . $index . '.sistem', data_get($tahap, 'sistem', [[]]));
                            $picRows = old('tahap.' . $index . '.pic', data_get($tahap, 'pic', [[]]));
                            $sistemEnabled = $hasFilledStructuredRows($sistemRows);
                            $picEnabled = $hasFilledStructuredRows($picRows);
                        @endphp

                        <div class="workflow-step-card tahap-row draggable-tahap-row" data-structured-scope>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-light border tahap-drag-handle" title="Geser untuk mengubah urutan tahap" aria-label="Geser Tahap" draggable="true">Drag</button>
                                    <strong data-tahap-order-label>Tahap {{ old('tahap.' . $index . '.urutan', data_get($tahap, 'urutan', $index + 1)) }}</strong>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-tahap-row">Hapus</button>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">No.</label>
                                    <input type="number" min="1" max="999" name="tahap[{{ $index }}][urutan]" class="form-control" value="{{ old('tahap.' . $index . '.urutan', data_get($tahap, 'urutan', $index + 1)) }}" data-tahap-order-input>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Tahap</label>
                                    <input type="text" name="tahap[{{ $index }}][nama]" class="form-control @error('tahap.' . $index . '.nama') is-invalid @enderror" value="{{ old('tahap.' . $index . '.nama', data_get($tahap, 'nama')) }}" placeholder="Contoh: Tahapan pertama dari alur kerja ini">
                                    @error('tahap.' . $index . '.nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Estimasi Pengerjaan</label>
                                    <input type="text" name="tahap[{{ $index }}][estimasi]" class="form-control @error('tahap.' . $index . '.estimasi') is-invalid @enderror" value="{{ old('tahap.' . $index . '.estimasi', data_get($tahap, 'estimasi')) }}" placeholder="Contoh: 2 jam / 1 hari kerja" data-stage-estimate-input>
                                    @error('tahap.' . $index . '.estimasi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-muted">Isi perkiraan durasi untuk menyelesaikan tahap ini.</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Deskripsi / Cara Kerja</label>
                                    <textarea name="tahap[{{ $index }}][deskripsi]" rows="2" class="form-control" placeholder="Jelaskan aktivitas utama pada tahap ini.">{{ old('tahap.' . $index . '.deskripsi', data_get($tahap, 'deskripsi')) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Lokasi Pelaksanaan</label>
                                    <input type="text" name="tahap[{{ $index }}][lokasi]" class="form-control @error('tahap.' . $index . '.lokasi') is-invalid @enderror" value="{{ old('tahap.' . $index . '.lokasi', data_get($tahap, 'lokasi')) }}" maxlength="255" placeholder="Contoh: Kantor Cabang Makassar, Gudang A, atau Remote">
                                    @error('tahap.' . $index . '.lokasi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-muted">Opsional. Isi tempat tahap ini dilakukan.</small>
                                    @enderror
                                </div>

                                <div class="col-12 workflow-optional-section" data-structured-scope data-optional-section data-optional-enabled="{{ $sistemEnabled ? '1' : '0' }}">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <div>
                                            <label class="form-label mb-0">Sistem / Aplikasi yang Digunakan</label>
                                            <small class="text-muted d-block">Tambahkan semua aplikasi, akun, dan fungsi yang dipakai pada tahap ini.</small>
                                        </div>
                                        <button type="button" class="workflow-toggle" data-optional-toggle aria-pressed="{{ $sistemEnabled ? 'true' : 'false' }}">
                                            <span class="workflow-toggle-dot"></span>
                                            <span data-optional-toggle-text>{{ $sistemEnabled ? 'ON' : 'OFF' }}</span>
                                        </button>
                                    </div>
                                    <div data-optional-body class="{{ $sistemEnabled ? '' : 'd-none' }}">
                                        @include('alur_kerja._sistem_rows', [
                                            'rows' => $sistemRows,
                                            'namePrefix' => 'tahap[' . $index . '][sistem]',
                                            'errorPrefix' => 'tahap.' . $index . '.sistem',
                                        ])
                                    </div>
                                </div>

                                <div class="col-12 workflow-optional-section" data-structured-scope data-optional-section data-optional-enabled="{{ $picEnabled ? '1' : '0' }}">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <div>
                                            <label class="form-label mb-0">PIC / Orang Terkait</label>
                                            <small class="text-muted d-block">Tambahkan semua pihak yang perlu dihubungi. Jika lebih dari satu, geser baris PIC untuk mengatur urutan.</small>
                                        </div>
                                        <button type="button" class="workflow-toggle" data-optional-toggle aria-pressed="{{ $picEnabled ? 'true' : 'false' }}">
                                            <span class="workflow-toggle-dot"></span>
                                            <span data-optional-toggle-text>{{ $picEnabled ? 'ON' : 'OFF' }}</span>
                                        </button>
                                    </div>
                                    <div data-optional-body class="{{ $picEnabled ? '' : 'd-none' }}">
                                        @include('alur_kerja._pic_rows', [
                                            'rows' => $picRows,
                                            'namePrefix' => 'tahap[' . $index . '][pic]',
                                            'errorPrefix' => 'tahap.' . $index . '.pic',
                                        ])
                                    </div>
                                </div>

                                <div class="col-md-6" data-file-picker>
                                    <label class="form-label">File / Template</label>
                                    <input type="file" name="tahap_lampiran[{{ $index }}][]" class="form-control" multiple data-file-input-preview>
                                    <small class="text-muted">Bisa lebih dari satu file. Maksimal 20 MB per file.</small>
                                    <div class="mt-2 d-none" data-selected-file-list></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catatan Tambahan</label>
                                    <textarea name="tahap[{{ $index }}][catatan]" rows="2" class="form-control" placeholder="Hal khusus, risiko, atau pengecualian.">{{ old('tahap.' . $index . '.catatan', data_get($tahap, 'catatan')) }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-outline-success" data-add-tahap-row>+ Tambah Tahap</button>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('alur-kerja.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" data-loading-text="Menyimpan...">Simpan Alur Kerja</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('alur_kerja._rich_text_editor_styles')
@endpush

@push('scripts')
@include('alur_kerja._rich_text_editor_script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('tahap-rows');
    const addButtons = document.querySelectorAll('[data-add-tahap-row]');
    const totalEstimateInput = document.querySelector('[data-workflow-total-estimate]');

    if (!rows || !addButtons.length) {
        return;
    }

    function nextIndex() {
        return rows.querySelectorAll('.tahap-row').length;
    }

    function updateTahapOrder() {
        rows.querySelectorAll('.tahap-row').forEach(function (row, index) {
            const order = index + 1;
            const input = row.querySelector('[data-tahap-order-input]');
            const label = row.querySelector('[data-tahap-order-label]');

            if (input) {
                input.value = order;
            }

            if (label) {
                label.textContent = 'Tahap ' + order;
            }
        });
    }

    function estimateUnitMultiplier(unit) {
        unit = String(unit || '').toLowerCase();

        if (['menit', 'mnt', 'min', 'm'].includes(unit)) {
            return 1;
        }

        if (unit === 'jam') {
            return 60;
        }

        if (['hari kerja', 'hk', 'hari', 'hr'].includes(unit)) {
            return 480;
        }

        if (['minggu', 'pekan'].includes(unit)) {
            return 2400;
        }

        if (unit === 'bulan') {
            return 9600;
        }

        return null;
    }

    function parseEstimateMinutes(value) {
        value = String(value || '').trim().toLowerCase().replace(/,/g, '.');

        if (!value) {
            return null;
        }

        const pattern = /(\d+(?:\.\d+)?)\s*(?:-|s\/d|sd|sampai)?\s*(\d+(?:\.\d+)?)?\s*(hari kerja|hk|menit|mnt|min|jam|hari|hr|minggu|pekan|bulan|m)\b/gu;
        let match;
        let total = 0;

        while ((match = pattern.exec(value)) !== null) {
            const amount = match[2] ? parseFloat(match[2]) : parseFloat(match[1]);
            const multiplier = estimateUnitMultiplier(match[3]);

            if (!Number.isFinite(amount) || !multiplier) {
                continue;
            }

            total += Math.round(amount * multiplier);
        }

        return total > 0 ? total : null;
    }

    function formatEstimateMinutes(minutes) {
        const dayMinutes = 480;
        const days = Math.floor(minutes / dayMinutes);
        let remaining = minutes % dayMinutes;
        const hours = Math.floor(remaining / 60);
        remaining = remaining % 60;
        const parts = [];

        if (days > 0) {
            parts.push(days + ' hari kerja');
        }

        if (hours > 0) {
            parts.push(hours + ' jam');
        }

        if (remaining > 0 || !parts.length) {
            parts.push(remaining + ' menit');
        }

        return parts.join(' ');
    }

    function updateWorkflowTotalEstimate() {
        if (!totalEstimateInput) {
            return;
        }

        const totalMinutes = Array.from(rows.querySelectorAll('[data-stage-estimate-input]'))
            .reduce(function (total, input) {
                const minutes = parseEstimateMinutes(input.value);

                return total + (minutes || 0);
            }, 0);

        totalEstimateInput.value = totalMinutes > 0 ? formatEstimateMinutes(totalMinutes) : '';
    }

    function bindRemoveButtons() {
        rows.querySelectorAll('.remove-tahap-row').forEach(function (button) {
            button.onclick = function () {
                if (rows.querySelectorAll('.tahap-row').length === 1) {
                    button.closest('.tahap-row').querySelectorAll('input, textarea').forEach(function (field) {
                        if (field.type === 'hidden') {
                            return;
                        }

                        if (field.matches('textarea') && window.VOpsRichTextEditor) {
                            window.VOpsRichTextEditor.clear(field);
                            return;
                        }

                        field.value = '';
                    });
                    updateTahapOrder();
                    updateWorkflowTotalEstimate();
                    return;
                }

                button.closest('.tahap-row').remove();
                updateTahapOrder();
                updateWorkflowTotalEstimate();
            };
        });
    }

    function addTahapRow() {
        const index = Date.now();
        const number = nextIndex() + 1;
        const wrapper = document.createElement('div');
        wrapper.className = 'workflow-step-card tahap-row draggable-tahap-row';
        wrapper.setAttribute('data-structured-scope', '');
        wrapper.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border tahap-drag-handle" title="Geser untuk mengubah urutan tahap" aria-label="Geser Tahap" draggable="true">Drag</button>
                    <strong data-tahap-order-label>Tahap ${number}</strong>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-tahap-row">Hapus</button>
            </div>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">No.</label>
                    <input type="number" min="1" max="999" name="tahap[${index}][urutan]" class="form-control" value="${number}" data-tahap-order-input>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Tahap</label>
                    <input type="text" name="tahap[${index}][nama]" class="form-control" placeholder="Contoh: Verifikasi berkas online">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estimasi Pengerjaan</label>
                    <input type="text" name="tahap[${index}][estimasi]" class="form-control" placeholder="Contoh: 2 jam / 1 hari kerja" data-stage-estimate-input>
                    <small class="text-muted">Isi perkiraan durasi untuk menyelesaikan tahap ini.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Deskripsi / Cara Kerja</label>
                    <textarea name="tahap[${index}][deskripsi]" rows="2" class="form-control" placeholder="Jelaskan aktivitas utama pada tahap ini."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lokasi Pelaksanaan</label>
                    <input type="text" name="tahap[${index}][lokasi]" class="form-control" maxlength="255" placeholder="Contoh: Kantor Cabang Makassar, Gudang A, atau Remote">
                    <small class="text-muted">Opsional. Isi tempat tahap ini dilakukan.</small>
                </div>
                <div class="col-12 workflow-optional-section" data-structured-scope data-optional-section data-optional-enabled="0">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <label class="form-label mb-0">Sistem / Aplikasi yang Digunakan</label>
                            <small class="text-muted d-block">Tambahkan semua aplikasi, akun, dan fungsi yang dipakai pada tahap ini.</small>
                        </div>
                        <button type="button" class="workflow-toggle" data-optional-toggle aria-pressed="false">
                            <span class="workflow-toggle-dot"></span>
                            <span data-optional-toggle-text>OFF</span>
                        </button>
                    </div>
                    <div data-optional-body class="d-none">
                        <div data-structured-list="sistem" data-name-prefix="tahap[${index}][sistem]">
                            <div class="structured-row system-row draggable-structured-row border rounded-3 p-3 mb-2 bg-white" draggable="true">
                                <input type="hidden" name="tahap[${index}][sistem][0][urutan]" value="1" data-order-input>
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-light border structured-drag-handle" title="Geser untuk mengubah urutan" aria-label="Geser Sistem / Aplikasi">Drag</button>
                                        <strong><span data-order-label>Sistem 1</span> / Aplikasi</strong>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-structured-row">Hapus</button>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Sistem</label>
                                        <input type="text" name="tahap[${index}][sistem][0][nama_sistem]" class="form-control" placeholder="Contoh: HRIS, Email, Google Form">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Akun yang Digunakan</label>
                                        <input type="text" name="tahap[${index}][sistem][0][akun]" class="form-control" placeholder="Contoh: recruitment@company.com">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fungsi</label>
                                        <textarea name="tahap[${index}][sistem][0][fungsi]" rows="2" class="form-control" placeholder="Dipakai untuk apa pada tahap ini"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">URL / Lokasi Akses</label>
                                        <input type="text" name="tahap[${index}][sistem][0][url]" class="form-control" placeholder="https://... atau lokasi aplikasi">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan Akses</label>
                                        <textarea name="tahap[${index}][sistem][0][catatan]" rows="2" class="form-control" placeholder="Hak akses, batasan, atau prosedur login"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-system-row>+ Tambah Sistem</button>
                        </div>
                    </div>
                </div>
                <div class="col-12 workflow-optional-section" data-structured-scope data-optional-section data-optional-enabled="0">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <label class="form-label mb-0">PIC / Orang Terkait</label>
                            <small class="text-muted d-block">Tambahkan semua pihak yang perlu dihubungi. Jika lebih dari satu, geser baris PIC untuk mengatur urutan.</small>
                        </div>
                        <button type="button" class="workflow-toggle" data-optional-toggle aria-pressed="false">
                            <span class="workflow-toggle-dot"></span>
                            <span data-optional-toggle-text>OFF</span>
                        </button>
                    </div>
                    <div data-optional-body class="d-none">
                        <div data-structured-list="pic" data-name-prefix="tahap[${index}][pic]">
                            <div class="structured-row pic-row draggable-structured-row border rounded-3 p-3 mb-2 bg-white" draggable="true">
                                <input type="hidden" name="tahap[${index}][pic][0][urutan]" value="1" data-order-input data-pic-order-input>
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-light border structured-drag-handle pic-drag-handle" title="Geser untuk mengubah urutan" aria-label="Geser PIC">Drag</button>
                                        <strong><span data-order-label data-pic-order-label>PIC 1</span> / Orang Terkait</strong>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-structured-row">Hapus</button>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama PIC</label>
                                        <input type="text" name="tahap[${index}][pic][0][nama]" class="form-control" placeholder="Nama orang atau tim">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Peran</label>
                                        <input type="text" name="tahap[${index}][pic][0][peran]" class="form-control" placeholder="Contoh: HR Recruitment, Kepala Departemen">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kontak</label>
                                        <input type="text" name="tahap[${index}][pic][0][kontak]" class="form-control" placeholder="Email, nomor HP, extension">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kapan Dihubungi</label>
                                        <input type="text" name="tahap[${index}][pic][0][waktu_dihubungi]" class="form-control" placeholder="Contoh: setelah verifikasi online selesai">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan</label>
                                        <textarea name="tahap[${index}][pic][0][catatan]" rows="2" class="form-control" placeholder="Hal khusus terkait PIC ini"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-pic-row>+ Tambah PIC</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" data-file-picker>
                    <label class="form-label">File / Template</label>
                    <input type="file" name="tahap_lampiran[${index}][]" class="form-control" multiple data-file-input-preview>
                    <small class="text-muted">Bisa lebih dari satu file. Maksimal 20 MB per file.</small>
                    <div class="mt-2 d-none" data-selected-file-list></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="tahap[${index}][catatan]" rows="2" class="form-control" placeholder="Hal khusus, risiko, atau pengecualian."></textarea>
                </div>
            </div>
        `;

        rows.appendChild(wrapper);
        if (window.VOpsOptionalSections) {
            window.VOpsOptionalSections.refresh(wrapper);
        }
        bindRemoveButtons();
        updateTahapOrder();
        updateWorkflowTotalEstimate();
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    let draggedTahapRow = null;

    function tahapAfterPointer(y) {
        const tahapRows = Array.from(rows.querySelectorAll('.tahap-row:not(.is-dragging)'));

        return tahapRows.reduce(function (closest, row) {
            const box = row.getBoundingClientRect();
            const offset = y - box.top - (box.height / 2);

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: row };
            }

            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    }

    document.addEventListener('dragstart', function (event) {
        const handle = event.target.closest('.tahap-drag-handle');

        if (!handle) {
            return;
        }

        draggedTahapRow = handle.closest('.tahap-row');

        if (!draggedTahapRow) {
            return;
        }

        draggedTahapRow.classList.add('is-dragging');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', 'tahap-row');
        }
    });

    document.addEventListener('dragover', function (event) {
        if (!draggedTahapRow || event.target.closest('#tahap-rows') !== rows) {
            return;
        }

        event.preventDefault();

        const nextRow = tahapAfterPointer(event.clientY);

        if (nextRow) {
            rows.insertBefore(draggedTahapRow, nextRow);
        } else {
            rows.appendChild(draggedTahapRow);
        }
    });

    document.addEventListener('drop', function (event) {
        if (!draggedTahapRow) {
            return;
        }

        event.preventDefault();
        updateTahapOrder();
    });

    document.addEventListener('dragend', function () {
        if (draggedTahapRow) {
            draggedTahapRow.classList.remove('is-dragging');
            updateTahapOrder();
        }

        draggedTahapRow = null;
    });

    addButtons.forEach(function (button) {
        button.addEventListener('click', addTahapRow);
    });

    rows.addEventListener('input', function (event) {
        if (event.target.matches('[data-stage-estimate-input]')) {
            updateWorkflowTotalEstimate();
        }
    });

    bindRemoveButtons();
    updateTahapOrder();
    updateWorkflowTotalEstimate();
});
</script>
@include('alur_kerja._selected_file_script')
@include('alur_kerja._structured_step_script')
@endpush
