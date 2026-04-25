@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h5 class="fw-bold mb-3">Tambah Dokumen</h5>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('pekerjaan.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Judul --}}
        <div class="mb-3">
            <label>Judul</label>
            <input type="text" name="judul" class="form-control" value="{{ old('judul') }}" required>
        </div>

        {{-- Parent --}}
        <div class="mb-3">
            <label>Induk</label>
            <select name="parent_id" class="form-control">
                <option value="">-- Utama --</option>
                @foreach($parents as $p)
                <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>
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
            <select name="team_id" class="form-control">
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
                    Status selesai diisi dari halaman index dokumen beserta bukti penyelesaian.
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
