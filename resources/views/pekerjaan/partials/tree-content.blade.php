@if($item->dokumens->count())
<ul class="list-unstyled tree-list tree-branch tree-documents">
    @foreach($item->dokumens as $doc)
    <li class="tree-item">
        <div class="tree-document small">
            <div>
                📄 <a href="{{ route('dokumen.lihat', $doc->id) }}" target="_blank" class="text-decoration-none">{{ $doc->nama_file }}</a>
                <span class="badge {{ $doc->status_dokumen_badge_class }} ms-2">{{ $doc->status_dokumen_label }}</span>
            </div>
            <small class="text-muted d-block ms-4 tree-meta">
                Disimpan: {{ $doc->tanggal_disimpan }} | Ukuran: {{ $doc->ukuran_file }}
            </small>
        </div>
    </li>
    @endforeach
</ul>
@endif

@if($item->subPekerjaans->count())
@include('pekerjaan.tree', ['items' => $item->subPekerjaans, 'isRoot' => false, 'autoExpand' => false])
@endif
