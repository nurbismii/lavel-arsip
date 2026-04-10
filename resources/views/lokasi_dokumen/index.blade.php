@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">Lokasi Dokumen</h5>
            <small class="text-muted">Kelola daftar lokasi yang akan dipakai pada dropdown pekerjaan.</small>
        </div>

        <a href="{{ route('lokasi-dokumen.create') }}" class="btn btn-primary btn-sm">
            + Tambah Lokasi
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    @if($lokasis->count())
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="80">No</th>
                        <th>Nama Lokasi</th>
                        <th width="140">Dipakai</th>
                        <th width="220">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lokasis as $lokasi)
                    <tr>
                        <td>{{ $lokasis->firstItem() + $loop->index }}</td>
                        <td>{{ $lokasi->nama_lokasi }}</td>
                        <td>{{ $lokasi->pekerjaans_count }} pekerjaan</td>
                        <td>
                            @if(auth()->user()->isAdmin())
                            <div class="d-flex gap-2">
                                <a href="{{ route('lokasi-dokumen.edit', $lokasi->id) }}" class="btn btn-sm btn-outline-warning">
                                    Edit
                                </a>

                                <form method="POST" action="{{ route('lokasi-dokumen.destroy', $lokasi->id) }}"
                                    onsubmit="return confirm('Hapus lokasi {{ addslashes($lokasi->nama_lokasi) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                            @else
                            <span class="text-muted small">Hanya admin</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $lokasis->links() }}
    </div>
    @else
    <div class="alert alert-light border mb-0">
        Belum ada data lokasi dokumen.
    </div>
    @endif
</div>
@endsection
