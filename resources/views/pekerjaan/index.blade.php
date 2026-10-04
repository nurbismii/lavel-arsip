@extends('layouts.app')

@push('styles')
@include('pekerjaan._rich_text_editor_styles')
<style>
    .hover-bg:hover {
        background-color: #f8fbff;
        transition: 0.2s;
    }

    .tree-wrapper {
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        border: 1px solid #e6ebf2;
        border-radius: 1rem;
        padding: 1rem 1rem 0.25rem;
    }

    .tree-list {
        margin: 0;
        padding-left: 0;
    }

    .tree-root>.tree-item {
        margin-bottom: 1rem;
    }

    .tree-branch {
        margin-top: 0.75rem;
        margin-left: 1rem;
        padding-left: 1.5rem;
    }

    .tree-branch>.tree-item {
        position: relative;
        margin-bottom: 0.75rem;
    }

    .tree-branch>.tree-item::before {
        content: "";
        position: absolute;
        top: 1rem;
        left: -1rem;
        width: 1rem;
        height: 1px;
        background: #cfd7e3;
    }

    .tree-branch>.tree-item::after {
        content: "";
        position: absolute;
        top: -0.75rem;
        left: -1rem;
        width: 1px;
        height: calc(100% + 0.75rem);
        background: #cfd7e3;
    }

    .tree-branch>.tree-item:last-child::after {
        height: 1rem;
    }

    .tree-node {
        background: #ffffff;
        border: 1px solid #e8edf3;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
    }

    .tree-folder-label {
        min-width: 0;
    }

    .tree-collapse-toggle {
        width: 1.6rem;
        height: 1.6rem;
        padding: 0;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #5f6b7a;
        background: #f6f8fb;
        border: 1px solid #e1e7ef;
    }

    .tree-collapse-toggle:hover {
        background: #edf3fb;
        color: #355070;
    }

    .tree-chevron {
        line-height: 1;
        transition: transform 0.2s ease;
        font-weight: 700;
    }

    .tree-collapse-toggle.collapsed .tree-chevron {
        transform: rotate(0deg);
    }

    .tree-collapse-toggle:not(.collapsed) .tree-chevron {
        transform: rotate(90deg);
    }

    .tree-collapse-placeholder {
        width: 1.6rem;
        height: 1.6rem;
        display: inline-block;
    }

    .tree-document {
        background: #f8fafc;
        border: 1px solid #e8edf3;
        border-radius: 0.85rem;
        padding: 0.7rem 0.9rem;
    }

    .tree-meta {
        font-size: 0.82rem;
    }

    .tree-description-toggle {
        margin: 0.45rem 0 0.2rem 2.45rem;
        padding: 0;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .tree-description-toggle:hover,
    .tree-description-toggle:focus {
        color: #1d4ed8;
    }

    .tree-description-toggle .tree-description-hide-label,
    .tree-description-toggle:not(.collapsed) .tree-description-show-label {
        display: none;
    }

    .tree-description-toggle:not(.collapsed) .tree-description-hide-label {
        display: inline;
    }

    .tree-description {
        margin: 0.25rem 0 0.35rem 2.45rem;
        max-width: 56rem;
        color: #475569;
        font-size: 0.875rem;
        line-height: 1.55;
        white-space: normal;
    }

    .tree-description-label {
        color: #334155;
        font-weight: 600;
    }

    .tree-loading {
        margin-left: 1rem;
    }

    .document-page .tree-node { gap: 1rem; border-radius: 14px !important; }
    .document-page .tree-folder-label { flex: 1; overflow-wrap: anywhere; }
    .document-page .tree-folder-actions { flex-shrink: 0; max-width: 16rem; }
    .document-page .tree-meta { margin-top: .25rem; line-height: 1.5; }
    .document-page .tree-collapse-toggle { width: 2rem; height: 2rem; flex-shrink: 0; }
    .document-page .tree-wrapper { padding: 1rem; }
    .document-page .tree-root > .tree-item:last-child { margin-bottom: 0; }
    .document-page .btn:focus-visible { outline: 3px solid var(--app-primary); outline-offset: 3px; }
    @media (max-width: 767.98px) {
        .document-page .tree-node { flex-direction: column; }
        .document-page .tree-folder-actions { max-width: none; width: 100%; justify-content: flex-start !important; border-top: 1px solid var(--app-border); padding-top: .75rem; }
        .document-page .tree-branch { margin-left: 0; padding-left: .75rem; border-left: 1px solid var(--app-border); }
        .document-page .tree-branch > .tree-item::before,
        .document-page .tree-branch > .tree-item::after { display: none; }
        .document-page .tree-wrapper { padding: .75rem; }
        .document-page .tree-description { margin-left: 0; }
        .document-page .tree-document { overflow-wrap: anywhere; }
        .document-page .tree-toolbar { align-items: stretch !important; }
        .document-page .tree-toolbar-actions { width: 100%; }
        .document-page .tree-toolbar-actions .btn { flex: 1; }
    }
</style>
@endpush

@section('content')
<div class="container document-page">

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

    <div class="app-page-header mb-4">
        <div>
            <span class="app-page-eyebrow">Kelola Dokumen</span>
            <h1 class="app-page-title h3">Dokumen Pekerjaan</h1>
            <p class="app-page-subtitle">Temukan dokumen, pantau status, dan kelola folder pekerjaan dalam satu tempat.</p>
        </div>
        <div class="app-page-actions">
        <a href="{{ route('pekerjaan.create') }}" class="btn btn-primary">
            + Tambah Dokumen
        </a>
        </div>
    </div>

    <form method="GET" action="{{ route('pekerjaan.index') }}" class="filter-panel mb-4" data-loading-form>
        <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-6">
            <label for="document-search" class="form-label fw-semibold">Cari dokumen atau folder</label>
            <input
                id="document-search"
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Cari judul utama atau sub judul..." data-character-limit="none">
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label for="document-status" class="form-label fw-semibold">Status dokumen</label>
            <select id="document-status" name="status_dokumen" class="form-select">
                <option value="">Semua Status</option>
                @foreach($statusDokumenOptions as $value => $label)
                <option value="{{ $value }}" {{ $statusDokumen === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill" data-loading-text="Mencari...">Cari</button>
            <a href="{{ route('pekerjaan.index') }}" class="btn btn-outline-secondary flex-fill">Reset</a>
        </div>
        </div>
    </form>

    @if($search !== '' || $statusDokumen !== '')
    <div class="alert alert-light border small">
        Filter aktif:
        @if($search !== '')
        kata kunci <strong>{{ $search }}</strong>
        @endif
        @if($search !== '' && $statusDokumen !== '')
        dan
        @endif
        @if($statusDokumen !== '')
        status <strong>{{ $statusDokumenOptions[$statusDokumen] ?? $statusDokumen }}</strong>
        @endif
        .
        Folder yang tampil adalah struktur yang memiliki dokumen sesuai filter.
    </div>
    @endif

    @if($pekerjaans->count())
    <div class="tree-toolbar d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="h6 fw-bold mb-1">Folder pekerjaan <span class="badge bg-primary rounded-pill ms-1">{{ $pekerjaans->total() }}</span></h2>
            <small class="text-muted">Buka folder untuk melihat subfolder dan dokumen di dalamnya.</small>
        </div>
        <div class="tree-toolbar-actions d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="expand-all-tree">
                Buka Semua
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="collapse-all-tree">
                Tutup Semua
            </button>
        </div>
    </div>

    <div class="tree-wrapper">
        @include('pekerjaan.tree', ['items' => $pekerjaans, 'isRoot' => true, 'autoExpand' => $search !== '' || $statusDokumen !== '', 'statusDokumen' => $statusDokumen])
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $pekerjaans->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">📁</div>
        <h5>{{ $search !== '' || $statusDokumen !== '' ? 'Dokumen tidak ditemukan' : 'Belum ada dokumen pekerjaan' }}</h5>
        <p>{{ $search !== '' || $statusDokumen !== '' ? 'Coba kata kunci lain atau reset filter untuk melihat semua folder.' : 'Mulai dengan menambahkan folder pekerjaan beserta dokumen pendukungnya.' }}</p>
        <a href="{{ $search !== '' || $statusDokumen !== '' ? route('pekerjaan.index') : route('pekerjaan.create') }}" class="btn btn-primary mt-3">
            {{ $search !== '' || $statusDokumen !== '' ? 'Reset Filter' : '+ Tambah Dokumen' }}
        </a>
    </div>
    @endif

</div>
@endsection

@push('scripts')
@include('pekerjaan._rich_text_editor_script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const expandButton = document.getElementById('expand-all-tree');
        const collapseButton = document.getElementById('collapse-all-tree');

        function syncCompletionFields(form) {
            if (!form) {
                return;
            }

            const select = form.querySelector('.document-status-select');
            const loanFields = form.querySelector('.loan-fields');
            const borrowerSelect = form.querySelector('.borrower-select');
            const fields = form.querySelector('.completion-fields');
            const proofFields = form.querySelector('.completion-proof-fields');
            const proofInput = form.querySelector('.completion-proof-input');
            const noteInput = form.querySelector('.completion-note-input');

            if (!select || !fields) {
                return;
            }

            const isComplete = select.value === select.dataset.completeStatus;
            const isCompletionNoteStatus = isComplete || select.value === 'tidak_selesai' || select.value === 'tidak_dihadiri';
            const isActive = select.value === select.dataset.activeStatus;

            if (loanFields) {
                loanFields.classList.toggle('d-none', !isActive);
            }

            if (borrowerSelect) {
                borrowerSelect.required = isActive;
            }

            fields.classList.toggle('d-none', !isCompletionNoteStatus);

            if (proofFields) {
                proofFields.classList.toggle('d-none', !isComplete);
            }

            if (proofInput) {
                proofInput.required = isComplete && fields.dataset.hasProof !== 'true';
            }

            if (noteInput) {
                noteInput.dataset.richTextRequired = isCompletionNoteStatus ? 'true' : 'false';
                noteInput.required = noteInput.dataset.richTextReady === 'true' ? false : isCompletionNoteStatus;

                if (window.PekerjaanRichText && noteInput.dataset.richTextReady === 'true') {
                    window.PekerjaanRichText.validate(noteInput, false);
                }
            }
        }

        document.addEventListener('change', function(event) {
            if (!event.target.classList.contains('document-status-select')) {
                return;
            }

            syncCompletionFields(event.target.closest('.document-status-form'));
        });

        if (typeof bootstrap === 'undefined') {
            return;
        }

        function getCollapseElements(container = document) {
            return Array.from(container.querySelectorAll('.tree-folder-collapse'));
        }

        async function loadTreeContent(collapseElement) {
            if (!collapseElement || collapseElement.dataset.treeLoaded === 'true') {
                return;
            }

            if (collapseElement.dataset.treeLoading === 'true') {
                while (collapseElement.dataset.treeLoading === 'true') {
                    await new Promise((resolve) => setTimeout(resolve, 50));
                }
                return;
            }

            const url = collapseElement.dataset.treeUrl;

            if (!url) {
                collapseElement.dataset.treeLoaded = 'true';
                return;
            }

            collapseElement.dataset.treeLoading = 'true';

            const loadingElement = collapseElement.querySelector('.tree-loading');

            if (loadingElement) {
                loadingElement.classList.remove('d-none');
            }

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (response.status === 401) {
                    const result = await response.json().catch(() => ({}));

                    if (result.redirect) {
                        window.location.href = result.redirect;
                        return;
                    }

                    window.location.href = '{{route('login')}}';
                    return;
                }

                if (!response.ok) {
                    throw new Error('Gagal memuat isi folder');
                }

                const result = await response.json();
                collapseElement.innerHTML = result.html || '';
                collapseElement.dataset.treeLoaded = 'true';

                if (window.PekerjaanRichText) {
                    window.PekerjaanRichText.init(collapseElement);
                }

                collapseElement.querySelectorAll('.document-status-form').forEach(syncCompletionFields);
            } catch (error) {
                collapseElement.innerHTML = `
                    <div class="alert alert-warning small ms-3 mt-2 mb-0">
                        <strong>Isi folder belum dapat dimuat.</strong>
                        <div>Periksa koneksi Anda, lalu coba muat kembali.</div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-tree-retry>Coba Lagi</button>
                    </div>
                `;
            } finally {
                collapseElement.dataset.treeLoading = 'false';
                collapseElement.dataset.treeRendered = 'true';
            }
        }

        async function expandAllTree() {
            const queue = [...getCollapseElements()];
            const processed = new Set();

            while (queue.length) {
                const collapseElement = queue.shift();

                if (!collapseElement || !collapseElement.isConnected || processed.has(collapseElement)) {
                    continue;
                }

                processed.add(collapseElement);

                await loadTreeContent(collapseElement);

                bootstrap.Collapse.getOrCreateInstance(collapseElement, {
                    toggle: false
                }).show();

                getCollapseElements(collapseElement).forEach((nested) => {
                    if (!processed.has(nested)) {
                        queue.push(nested);
                    }
                });
            }
        }

        document.addEventListener('show.bs.collapse', function(event) {
            const folder = event.target;
            if (!folder.classList.contains('tree-folder-collapse') || folder.dataset.treeLoaded === 'true' || folder.dataset.treeRendered === 'true') return;

            event.preventDefault();
            loadTreeContent(folder).then(() => {
                if (!folder.isConnected) return;
                bootstrap.Collapse.getOrCreateInstance(folder, { toggle: false }).show();
            });
        });

        document.addEventListener('click', function(event) {
            const retryButton = event.target.closest('[data-tree-retry]');
            if (!retryButton) return;
            retryButton.disabled = true;
            retryButton.textContent = 'Memuat...';
            loadTreeContent(retryButton.closest('.tree-folder-collapse'));
        });

        getCollapseElements().forEach((collapseElement) => {
            if (collapseElement.classList.contains('show')) {
                loadTreeContent(collapseElement);
            }
        });

        if (!expandButton || !collapseButton) {
            return;
        }

        expandButton.addEventListener('click', function() {
            const originalText = expandButton.innerHTML;
            expandButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Membuka...';
            expandButton.disabled = true;
            collapseButton.disabled = true;

            expandAllTree().finally(() => {
                expandButton.innerHTML = originalText;
                expandButton.disabled = false;
                collapseButton.disabled = false;
            });
        });

        collapseButton.addEventListener('click', function() {
            getCollapseElements().forEach((element) => {
                bootstrap.Collapse.getOrCreateInstance(element, {
                    toggle: false
                }).hide();
            });
        });
    });
</script>
@endpush
