@extends('layouts.app')
@section('content')
<div class="container">
    <div class="app-page-header">
        <div><span class="app-page-eyebrow">HRIS</span>
            <h4 class="app-page-title">Uraian Jabatan</h4>
            <p class="app-page-subtitle">Kelola identitas jabatan, tugas, wewenang, dan spesifikasi pekerjaan.</p>
        </div>
        <div class="app-page-actions"><a href="{{ route('jobdesc.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Tambah Uraian Jabatan</a></div>
    </div>
    <div class="filter-panel">
        <form class="row g-3 align-items-end">
            <div class="col-md-6"><label class="form-label">Pencarian</label><input name="search" value="{{ $search }}" class="form-control" placeholder="Cari jabatan, job code, divisi, atau departemen"></div>
            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select">
                    <option value="">Semua status</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>@endforeach
                </select></div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary"><i class="fas fa-search me-1"></i>Terapkan</button></div>
        </form>
    </div>
    @if($jobdescs->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-id-card"></i></div>
        <h5>Belum ada uraian jabatan</h5>
        <p>Tambahkan uraian jabatan pertama untuk mendokumentasikan peran dan tanggung jawab pekerjaan.</p><a href="{{ route('jobdesc.create') }}" class="btn btn-primary mt-2">Tambah Uraian Jabatan</a>
    </div>
    @else
    <div class="app-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Jabatan</th>
                        <th>Unit</th>
                        <th>Atasan</th>
                        <th>Status</th>
                        <th>Pemilik</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>@foreach($jobdescs as $item)<tr>
                        <td><a class="fw-semibold text-decoration-none" href="{{ route('jobdesc.show', $item) }}">{{ $item->jabatan }}</a><small class="d-block text-muted">{{ $item->job_code ?: 'Belum ada job code' }}</small></td>
                        <td>{{ $item->departemen ?: '-' }}<small class="d-block text-muted">{{ $item->divisi ?: ($item->team->name ?? '-') }}</small></td>
                        <td>{{ \Illuminate\Support\Str::limit($item->atasan_langsung, 45) ?: '-' }}</td>
                        <td><span class="badge {{ $item->status_badge_class }}">{{ $item->status_label }}</span></td>
                        <td>{{ $item->pemilik->name ?? '-' }}</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2"><a href="{{ route('jobdesc.show', $item) }}" class="btn btn-sm btn-outline-primary">Lihat</a>@if(auth()->user()->canAccessAllFiles() || $item->pemilik_user_id === auth()->id())<a href="{{ route('jobdesc.edit', $item) }}" class="btn btn-sm btn-outline-warning">Ubah</a>@endif</div>
                        </td>
                    </tr>@endforeach</tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $jobdescs->links() }}</div>
    @endif
</div>
@endsection