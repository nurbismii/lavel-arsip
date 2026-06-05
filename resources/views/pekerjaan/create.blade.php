@extends('layouts.app')

@push('styles')
@include('pekerjaan._workflow_link_styles')
@endpush

@section('content')
<div class="container py-4">

    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Kelola Dokumen</span>
            <h4 class="app-page-title">Tambah Dokumen</h4>
            <p class="app-page-subtitle">
                Lengkapi informasi dokumen, alur kerja, tanggal penyelesaian, dan file pendukung.
            </p>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="app-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('pekerjaan.store') }}" enctype="multipart/form-data" data-pekerjaan-form>
                @csrf

                {{-- Judul --}}
                <div class="mb-3">
                    <label>Judul</label>
                    <input type="text" name="judul" class="form-control" value="{{ old('judul') }}" required>
                </div>

                {{-- Parent --}}
                <div class="mb-3">
                    <label>Induk</label>
                    <select name="parent_id" class="form-control" data-parent-select>
                        <option value="">-- Utama --</option>
                        @foreach($parents as $p)
                        <option
                            value="{{ $p->id }}"
                            data-team-id="{{ $p->team_id }}"
                            data-alur-kerja-id="{{ $p->alur_kerja_id }}"
                            data-alur-kerja-tahap-id="{{ $p->alur_kerja_tahap_id }}"
                            {{ old('parent_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->judul }}{{ $p->team ? ' - ' . $p->team->name : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Lokasi Dokumen</label>
                    <select name="lokasi_id" class="form-control" required>
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach($lokasis as $lokasi)
                        <option value="{{ $lokasi->id }}" {{ old('lokasi_id') == $lokasi->id ? 'selected' : '' }}>
                            {{ $lokasi->nama_lokasi }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Tim / Divisi</label>
                    <select name="team_id" class="form-control" data-team-select>
                        <option value="">-- Tanpa Tim --</option>
                        @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                            {{ $team->name }}
                        </option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        Jika memilih parent, tim/divisi akan mengikuti parent tersebut saat disimpan.
                    </small>
                    @if($teams->isEmpty())
                    <small class="text-muted d-block">
                        Belum ada tim/divisi yang bisa dipilih. Admin dapat menambah atau menetapkan tim dari menu Tim / Divisi dan Kelola User.
                    </small>
                    @endif
                </div>

                @php($workflowToggleChecked = old('use_workflow_link') === '1' || old('alur_kerja_id') || old('alur_kerja_tahap_id'))
                <div class="workflow-link-card mb-3" data-workflow-card>
                    <div class="workflow-link-header">
                        <div>
                            <div class="workflow-link-title">Kaitkan ke Alur Kerja</div>
                            <p class="workflow-link-subtitle" data-workflow-toggle-help>
                                Aktifkan jika dokumen perlu dipetakan ke alur kerja dan tahapan proses.
                            </p>
                        </div>

                        <div class="form-check form-switch workflow-switch">
                            <input type="hidden" name="use_workflow_link" value="0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="workflow-link-toggle-create"
                                name="use_workflow_link"
                                value="1"
                                data-workflow-toggle
                                {{ $workflowToggleChecked ? 'checked' : '' }}>
                            <label class="form-check-label" for="workflow-link-toggle-create" data-workflow-toggle-label>
                                {{ $workflowToggleChecked ? 'Aktif' : 'Nonaktif' }}
                            </label>
                        </div>
                    </div>

                    <div class="workflow-link-fields {{ $workflowToggleChecked ? '' : 'is-hidden' }}" data-workflow-fields>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label>Alur Kerja V-Ops <span class="text-muted small">(opsional)</span></label>
                                <select name="alur_kerja_id" class="form-control" data-alur-kerja-select>
                                    <option value="">-- Tidak ditautkan --</option>
                                    @foreach($alurKerjas as $alurKerja)
                                        <option value="{{ $alurKerja->id }}" {{ old('alur_kerja_id') == $alurKerja->id ? 'selected' : '' }}>
                                            {{ $alurKerja->kode ? $alurKerja->kode . ' - ' : '' }}{{ $alurKerja->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    Boleh dikosongkan jika dokumen belum perlu dikaitkan ke alur kerja.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label>Tahapan Proses <span class="text-muted small">(opsional)</span></label>
                                <select
                                    name="alur_kerja_tahap_id"
                                    class="form-control"
                                    data-tahap-select
                                    data-selected-tahap="{{ old('alur_kerja_tahap_id') }}">
                                    <option value="">-- Tidak dikaitkan ke tahapan --</option>
                                </select>
                                <small class="text-muted" data-tahap-help>
                                    Boleh dikosongkan. Pilih tahapan hanya jika dokumen sudah perlu dipetakan ke proses tertentu.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label>Mulai Penyelesaian</label>
                        <input
                            type="date"
                            name="tanggal_mulai_penyelesaian"
                            class="form-control"
                            value="{{ old('tanggal_mulai_penyelesaian') }}"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label>Target Selesai</label>
                        <input
                            type="date"
                            name="tanggal_target_penyelesaian"
                            class="form-control"
                            value="{{ old('tanggal_target_penyelesaian') }}"
                            required>
                    </div>
                </div>

                {{-- Dokumen Utama --}}
                <div class="mb-3">
                    <label>Dokumen</label>
                    <input type="file" id="dokumen-input" multiple class="form-control">

                    <div class="mt-2">
                        <label class="form-label">Status Awal Dokumen Utama</label>
                        <select name="status_dokumen" class="form-control">
                            @foreach($statusDokumenOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('status_dokumen', 'draft') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            Status selesai diisi dari utama dokumen beserta bukti penyelesaian.
                        </small>
                    </div>

                    <div id="hidden-inputs"></div>

                    <ul id="file-list" class="list-group mt-2"></ul>
                </div>

                <hr>

                <h6>Sub Dokumen</h6>

                <div id="sub-wrapper"></div>

                <button type="button" id="add-sub" class="btn btn-secondary btn-sm">
                    + Tambah Sub
                </button>

                <br><br>

                <button class="btn btn-primary">Simpan</button>

            </form>
        </div>
    </div>

</div>

@include('pekerjaan._workflow_stage_script')

<script>
    // ================= MAIN FILE =================
    let selectedFiles = [];

    document.getElementById('dokumen-input').addEventListener('change', function(e) {
        let files = Array.from(e.target.files);
        selectedFiles = [...selectedFiles, ...files];

        renderMainFiles();
        updateMainInputs();
    });

    function renderMainFiles() {
        let list = document.getElementById('file-list');
        list.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            list.insertAdjacentHTML('beforeend', `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>📄 ${file.name}</span>
                <div>
                    <span class="badge bg-secondary me-2">${formatSize(file.size)}</span>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeMainFile(${index})">✕</button>
                </div>
            </li>
        `);
        });
    }

    function removeMainFile(index) {
        selectedFiles.splice(index, 1);
        renderMainFiles();
        updateMainInputs();
    }

    function updateMainInputs() {
        let container = document.getElementById('hidden-inputs');
        container.innerHTML = '';

        selectedFiles.forEach((file) => {
            let dt = new DataTransfer();
            dt.items.add(file);

            let input = document.createElement('input');
            input.type = 'file';
            input.name = 'dokumen[]';
            input.files = dt.files;

            container.appendChild(input);
        });
    }

    // ================= SUB PEKERJAAN =================
    let subIndex = 0;
    let subFiles = {};

    document.getElementById('add-sub').onclick = function() {

        subFiles[subIndex] = [];

        let html = `
        <div class="border p-3 mb-3 rounded position-relative" id="sub-${subIndex}">

            <button type="button"
                class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                onclick="removeSub(${subIndex})">
                ✕
            </button>

            <input type="text" name="sub_judul[]" class="form-control mb-2" placeholder="Judul Sub">

            <small class="text-muted d-block mb-2">
                Lokasi sub pekerjaan akan mengikuti lokasi pekerjaan utama saat disimpan.
            </small>

            <label class="form-label small text-muted mb-1">Status Awal Sub Dokumen</label>
            <select name="sub_status_dokumen[${subIndex}]" class="form-control mb-2">
                @foreach($statusDokumenOptions as $value => $label)
                <option value="{{ $value }}" {{ $value === 'draft' ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>

            <input type="file" multiple class="form-control mb-2"
                onchange="handleSubFile(this, ${subIndex})">

            <div id="sub-hidden-${subIndex}"></div>

            <ul id="sub-list-${subIndex}" class="list-group"></ul>
        </div>
        `;

        document.getElementById('sub-wrapper').insertAdjacentHTML('beforeend', html);

        subIndex++;
    };

    function removeSub(index) {
        // hapus dari object file
        delete subFiles[index];

        // hapus tampilan
        let el = document.getElementById(`sub-${index}`);
        if (el) el.remove();
    }

    function handleSubFile(input, index) {
        let files = Array.from(input.files);
        subFiles[index] = [...subFiles[index], ...files];

        renderSubFiles(index);
        updateSubInputs(index);
    }

    function renderSubFiles(index) {
        let list = document.getElementById(`sub-list-${index}`);
        list.innerHTML = '';

        subFiles[index].forEach((file, i) => {
            list.insertAdjacentHTML('beforeend', `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>📄 ${file.name}</span>
                <div>
                    <span class="badge bg-secondary me-2">${formatSize(file.size)}</span>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeSubFile(${index}, ${i})">✕</button>
                </div>
            </li>
        `);
        });
    }

    function removeSubFile(index, i) {
        subFiles[index].splice(i, 1);
        renderSubFiles(index);
        updateSubInputs(index);
    }

    function updateSubInputs(index) {
        let container = document.getElementById(`sub-hidden-${index}`);
        container.innerHTML = '';

        subFiles[index].forEach(file => {
            let dt = new DataTransfer();
            dt.items.add(file);

            let input = document.createElement('input');
            input.type = 'file';
            input.name = `sub_dokumen[${index}][]`;
            input.files = dt.files;

            container.appendChild(input);
        });
    }

    // ================= HELPER =================
    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
</script>
@endsection
