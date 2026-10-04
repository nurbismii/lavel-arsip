@extends('layouts.app')

@section('content')
<div class="container">
    <div class="app-page-header mb-4">
        <div>
            <span class="app-page-eyebrow">Kelola Dokumen</span>
            <h1 class="app-page-title h3">Lokasi Dokumen</h1>
            <p class="app-page-subtitle">Atur lokasi penyimpanan agar dokumen pekerjaan mudah ditemukan.</p>
        </div>

        <div class="app-page-actions">
        <a href="{{ route('lokasi-dokumen.create') }}" class="btn btn-primary">
            + Tambah Lokasi
        </a>
        </div>
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
    <div class="app-card overflow-hidden">
        <div class="p-3 border-bottom d-flex flex-wrap justify-content-between gap-2">
            <h2 class="h6 fw-bold mb-0">Daftar lokasi</h2>
            <span class="text-muted small">{{ $lokasis->total() }} lokasi tersedia</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">Daftar lokasi penyimpanan dokumen dan jumlah pekerjaan yang menggunakannya.</caption>
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
                        <td class="fw-semibold">{{ $lokasi->nama_lokasi }}</td>
                        <td><span class="badge rounded-pill bg-light text-dark border">{{ $lokasi->pekerjaans_count }} pekerjaan</span></td>
                        <td>
                            @if(auth()->user()->isAdmin())
                            <div class="d-flex gap-2">
                                <a href="{{ route('lokasi-dokumen.edit', $lokasi->id) }}" class="btn btn-sm btn-outline-warning">
                                    Edit
                                </a>

                                <form method="POST" action="{{ route('lokasi-dokumen.destroy', $lokasi->id) }}"
                                    data-loading-form data-confirm-title="Hapus lokasi dokumen?"
                                    data-confirm-text="Hapus lokasi {{ $lokasi->nama_lokasi }}? Pastikan lokasi sudah tidak digunakan."
                                    data-confirm-icon="warning" data-confirm-button="Ya, hapus">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-loading-text="Menghapus...">
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
    <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">📁</div>
        <h5>Belum ada lokasi dokumen</h5>
        <p>Tambahkan lokasi penyimpanan untuk digunakan pada folder pekerjaan.</p>
        <a href="{{ route('lokasi-dokumen.create') }}" class="btn btn-primary mt-3">+ Tambah Lokasi</a>
    </div>
    @endif
</div>
@endsection
