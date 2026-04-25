@php($isRoot = $isRoot ?? false)
@php($autoExpand = $autoExpand ?? false)
@php($statusDokumen = $statusDokumen ?? '')

<ul class="list-unstyled tree-list {{ $isRoot ? 'tree-root' : 'tree-branch' }}">
    @foreach($items as $item)
    @include('pekerjaan.partials.tree-item', ['item' => $item, 'autoExpand' => $autoExpand, 'statusDokumen' => $statusDokumen])
    @endforeach
</ul>
