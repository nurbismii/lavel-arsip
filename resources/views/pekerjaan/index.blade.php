@extends('layouts.app')

@push('styles')
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

    .tree-root > .tree-item {
        margin-bottom: 1rem;
    }

    .tree-branch {
        margin-top: 0.75rem;
        margin-left: 1rem;
        padding-left: 1.5rem;
    }

    .tree-branch > .tree-item {
        position: relative;
        margin-bottom: 0.75rem;
    }

    .tree-branch > .tree-item::before {
        content: "";
        position: absolute;
        top: 1rem;
        left: -1rem;
        width: 1rem;
        height: 1px;
        background: #cfd7e3;
    }

    .tree-branch > .tree-item::after {
        content: "";
        position: absolute;
        top: -0.75rem;
        left: -1rem;
        width: 1px;
        height: calc(100% + 0.75rem);
        background: #cfd7e3;
    }

    .tree-branch > .tree-item:last-child::after {
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

    .tree-loading {
        margin-left: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container py-4">

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

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <div>
            <h5 class="fw-bold mb-1">Dokumen</h5>
            <small class="text-muted">Cari judul utama atau sub judul tanpa memuat semua data sekaligus.</small>
        </div>
        <a href="{{ route('pekerjaan.create') }}" class="btn btn-primary btn-sm">
            + Tambah
        </a>
    </div>

    <form method="GET" action="{{ route('pekerjaan.index') }}" class="row g-2 mb-4">
        <div class="col-12 col-md-8 col-lg-12">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Cari judul utama atau sub judul...">
        </div>
        <div class="col-6 col-md-auto">
            <button type="submit" class="btn btn-primary w-100">Cari</button>
        </div>
        <div class="col-6 col-md-auto">
            <a href="{{ route('pekerjaan.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>

    @if($search !== '')
    <div class="alert alert-light border small">
        Hasil pencarian untuk <strong>{{ $search }}</strong>.
        Jika yang cocok adalah sub pekerjaan, seluruh struktur parent dan child terkait tetap ditampilkan.
    </div>
    @endif

    @if($pekerjaans->count())
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <small class="text-muted">
            Gunakan kontrol ini untuk membuka atau menutup semua struktur folder sekaligus.
        </small>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="expand-all-tree">
                Expand All
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="collapse-all-tree">
                Collapse All
            </button>
        </div>
    </div>

    <div class="tree-wrapper">
        @include('pekerjaan.tree', ['items' => $pekerjaans, 'isRoot' => true, 'autoExpand' => $search !== ''])
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $pekerjaans->links() }}
    </div>
    @else
    <div class="alert alert-warning mb-0">
        Data pekerjaan tidak ditemukan.
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const expandButton = document.getElementById('expand-all-tree');
        const collapseButton = document.getElementById('collapse-all-tree');

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

                    window.location.href = '{{ route('login') }}';
                    return;
                }

                if (!response.ok) {
                    throw new Error('Gagal memuat isi folder');
                }

                const result = await response.json();
                collapseElement.innerHTML = result.html || '';
                collapseElement.dataset.treeLoaded = 'true';
            } catch (error) {
                collapseElement.innerHTML = `
                    <div class="alert alert-warning small ms-3 mt-2 mb-0">
                        Gagal memuat isi folder. Silakan coba lagi.
                    </div>
                `;
            } finally {
                collapseElement.dataset.treeLoading = 'false';
            }
        }

        async function expandAllTree() {
            const queue = [...getCollapseElements()];
            const processed = new Set();

            while (queue.length) {
                const collapseElement = queue.shift();

                if (!collapseElement || processed.has(collapseElement)) {
                    continue;
                }

                processed.add(collapseElement);

                bootstrap.Collapse.getOrCreateInstance(collapseElement, {
                    toggle: false
                }).show();

                await loadTreeContent(collapseElement);

                getCollapseElements(collapseElement).forEach((nested) => {
                    if (!processed.has(nested)) {
                        queue.push(nested);
                    }
                });
            }
        }

        document.addEventListener('show.bs.collapse', function(event) {
            if (event.target.classList.contains('tree-folder-collapse')) {
                loadTreeContent(event.target);
            }
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
            expandButton.disabled = true;
            collapseButton.disabled = true;

            expandAllTree().finally(() => {
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
