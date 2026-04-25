@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h5 class="fw-bold mb-1">Edit Pekerjaan</h5>
    <div class="text-muted small mb-4">
        Folder dibuat: {{ $pekerjaan->tanggal_dibuat }}
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

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('pekerjaan.update', $pekerjaan->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Judul</label>
            <input type="text" name="judul" class="form-control" value="{{ old('judul', $pekerjaan->judul) }}">
        </div>

        <div class="mb-3">
            <label>Parent</label>
            <select name="parent_id" class="form-control">
                <option value="">-- Utama --</option>
                @foreach($parents as $p)
                <option value="{{ $p->id }}" {{ old('parent_id', $pekerjaan->parent_id) == $p->id ? 'selected' : '' }}>
                    {{ $p->judul }}{{ $p->team ? ' - ' . $p->team->name : '' }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Lokasi Dokumen</label>
            <select name="lokasi_id" class="form-control">
                <option value="">-- Pilih Lokasi --</option>
                @foreach($lokasis as $lokasi)
                <option value="{{ $lokasi->id }}" {{ old('lokasi_id', $pekerjaan->lokasi_id) == $lokasi->id ? 'selected' : '' }}>
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
                <option value="{{ $team->id }}" {{ old('team_id', $pekerjaan->team_id) == $team->id ? 'selected' : '' }}>
                    {{ $team->name }}
                </option>
                @endforeach
            </select>
            <small class="text-muted">
                Jika memilih parent, tim/divisi akan mengikuti parent tersebut saat disimpan.
            </small>
            @if($teams->isEmpty())
            <small class="text-muted d-block">
                Belum ada tim/divisi yang bisa dipilih untuk akun ini.
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
                    value="{{ old('tanggal_mulai_penyelesaian', optional($pekerjaan->tanggal_mulai_penyelesaian)->format('Y-m-d')) }}"
                    required>
            </div>
            <div class="col-md-6">
                <label>Target Selesai</label>
                <input
                    type="date"
                    name="tanggal_target_penyelesaian"
                    class="form-control"
                    value="{{ old('tanggal_target_penyelesaian', optional($pekerjaan->tanggal_target_penyelesaian)->format('Y-m-d')) }}"
                    required>
            </div>
        </div>

        <div class="mb-4">
            <label>Tambah Dokumen</label>
            <input type="file" id="dokumen-input" multiple class="form-control">
            <small class="text-muted d-block mt-1">
                Anda bisa pilih file lebih dari sekali sebelum klik update.
            </small>

            <div class="mt-2">
                <label class="form-label">Status Awal Dokumen Baru</label>
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

        <hr class="my-4">

        <h6>Dokumen Saat Ini</h6>

    @if($pekerjaan->dokumens->count())
    <ul class="list-unstyled mb-0">
        @foreach($pekerjaan->dokumens as $doc)
        <li class="mb-3 border rounded p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                <div>
                    <div>
                        📄 <a href="{{ route('dokumen.lihat', $doc->id) }}" target="_blank" class="text-decoration-none">{{ $doc->nama_file }}</a>
                        <span class="badge {{ $doc->status_dokumen_badge_class }} ms-2">{{ $doc->status_dokumen_label }}</span>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Disimpan: {{ $doc->tanggal_disimpan }} | Ukuran: {{ $doc->ukuran_file }}
                    </small>
                </div>

                <button type="submit"
                    form="delete-dokumen-{{ $doc->id }}"
                    class="btn btn-sm btn-outline-danger">
                    Hapus File
                </button>
            </div>
        </li>
        @endforeach
    </ul>
    @else
    <div class="alert alert-light border mb-0">
        Belum ada dokumen pada pekerjaan ini.
    </div>
    @endif

        <div class="mt-4">
            <button class="btn btn-primary">Update</button>
        </div>
    </form>

    @foreach($pekerjaan->dokumens as $doc)
    <form id="delete-dokumen-{{ $doc->id }}"
        method="POST"
        action="{{ route('pekerjaan.dokumen.destroy', [$pekerjaan->id, $doc->id]) }}"
        class="d-none"
        onsubmit="return confirm('Hapus dokumen {{ addslashes($doc->nama_file) }}?')">
        @csrf
        @method('DELETE')
    </form>
    @endforeach

</div>
@endsection

@push('scripts')
<script>
    let selectedFiles = [];

    document.getElementById('dokumen-input').addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        selectedFiles = [...selectedFiles, ...files];

        renderFiles();
        updateInputs();
        e.target.value = '';
    });

    function renderFiles() {
        const list = document.getElementById('file-list');
        list.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            list.insertAdjacentHTML('beforeend', `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>📄 ${file.name}</span>
                    <div>
                        <span class="badge bg-secondary me-2">${formatSize(file.size)}</span>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeFile(${index})">×</button>
                    </div>
                </li>
            `);
        });
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        renderFiles();
        updateInputs();
    }

    function updateInputs() {
        const container = document.getElementById('hidden-inputs');
        container.innerHTML = '';

        selectedFiles.forEach((file) => {
            const dt = new DataTransfer();
            dt.items.add(file);

            const input = document.createElement('input');
            input.type = 'file';
            input.name = 'dokumen[]';
            input.files = dt.files;

            container.appendChild(input);
        });
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
</script>
@endpush
