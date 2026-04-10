@php($autoExpand = $autoExpand ?? false)
@php($childrenCount = $item->sub_pekerjaans_count ?? 0)
@php($documentsCount = $item->dokumens_count ?? 0)
@php($hasNestedContent = $childrenCount > 0 || $documentsCount > 0)
@php($collapseId = 'folder-content-' . $item->id)
@php($canManage = auth()->check() && (auth()->user()->canAccessAllFiles() || (int) $item->user_id === (int) auth()->id()))

<li class="tree-item" data-tree-item-id="{{ $item->id }}">

    <div class="tree-node d-flex justify-content-between align-items-start p-3 rounded hover-bg">

        <div class="tree-folder-label">
            <div class="d-flex align-items-center gap-2">
                @if($hasNestedContent)
                    <button type="button"
                        class="btn btn-sm btn-link text-decoration-none text-secondary tree-collapse-toggle {{ $autoExpand ? '' : 'collapsed' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $collapseId }}"
                        aria-expanded="{{ $autoExpand ? 'true' : 'false' }}"
                        aria-controls="{{ $collapseId }}">
                    <span class="tree-chevron">></span>
                </button>
                @else
                <span class="tree-collapse-placeholder"></span>
                @endif

                <div class="d-flex align-items-center">
                    <span class="me-2">📁</span>
                    <strong>{{ $item->judul }}</strong>
                </div>
            </div>

            <small class="text-muted d-block ms-4 ps-1 tree-meta">
                Folder dibuat: {{ $item->tanggal_dibuat }}
            </small>

            <small class="text-muted d-block ms-4 ps-1 tree-meta">
                Lokasi dokumen: {{ optional($item->lokasi)->nama_lokasi ?: '-' }}
            </small>

            <small class="text-muted d-block ms-4 ps-1 tree-meta">
                Tim/divisi: {{ optional($item->team)->name ?: '-' }}
            </small>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
            @if($hasNestedContent)
            <span class="badge rounded-pill bg-light text-secondary border">
                {{ $documentsCount }} file / {{ $childrenCount }} folder
            </span>
            @endif

            @if($canManage)
                <a href="{{ route('pekerjaan.edit', $item->id) }}" class="btn btn-sm btn-outline-warning">
                    Edit
                </a>

                <form method="POST"
                    action="{{ route('pekerjaan.destroy', $item->id) }}"
                    onsubmit="return confirm('Hapus pekerjaan {{ addslashes($item->judul) }}? Semua sub pekerjaan dan file di dalamnya juga akan ikut dihapus.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        Hapus
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($hasNestedContent)
    <div id="{{ $collapseId }}"
        class="collapse tree-folder-collapse {{ $autoExpand ? 'show' : '' }}"
        data-tree-content
        data-tree-loaded="false"
        data-tree-url="{{ route('pekerjaan.tree-content', $item->id) }}">
        <div class="tree-loading px-3 py-3 small text-muted d-none">
            Memuat isi folder...
        </div>
    </div>
    @endif

</li>
