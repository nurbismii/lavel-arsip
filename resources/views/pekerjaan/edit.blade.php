@extends('layouts.app')

@push('styles')
@include('pekerjaan._workflow_link_styles')
@endpush

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

    <form method="POST" action="{{ route('pekerjaan.update', $pekerjaan->id) }}" enctype="multipart/form-data" data-pekerjaan-form>
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Judul</label>
            <input type="text" name="judul" class="form-control" value="{{ old('judul', $pekerjaan->judul) }}">
        </div>

        <div class="mb-3">
            <label>Parent</label>
            <select name="parent_id" class="form-control" data-parent-select>
                <option value="">-- Utama --</option>
                @foreach($parents as $p)
                <option
                    value="{{ $p->id }}"
                    data-team-id="{{ $p->team_id }}"
                    data-alur-kerja-id="{{ $p->alur_kerja_id }}"
                    data-alur-kerja-tahap-id="{{ $p->alur_kerja_tahap_id }}"
                    {{ old('parent_id', $pekerjaan->parent_id) == $p->id ? 'selected' : '' }}>
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
            <select name="team_id" class="form-control" data-team-select>
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

        @php($hasOldWorkflowToggle = session()->hasOldInput('use_workflow_link'))
        @php($workflowToggleChecked = $hasOldWorkflowToggle ? old('use_workflow_link') === '1' : (bool) ($pekerjaan->alur_kerja_id || $pekerjaan->alur_kerja_tahap_id))
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
                        id="workflow-link-toggle-edit"
                        name="use_workflow_link"
                        value="1"
                        data-workflow-toggle
                        {{ $workflowToggleChecked ? 'checked' : '' }}>
                    <label class="form-check-label" for="workflow-link-toggle-edit" data-workflow-toggle-label>
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
                            <option value="{{ $alurKerja->id }}" {{ old('alur_kerja_id', $pekerjaan->alur_kerja_id) == $alurKerja->id ? 'selected' : '' }}>
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
                            data-selected-tahap="{{ old('alur_kerja_tahap_id', $pekerjaan->alur_kerja_tahap_id) }}">
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
                    Status selesai diisi dari utama dokumen beserta bukti penyelesaian.
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
@include('pekerjaan._workflow_stage_script')

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
