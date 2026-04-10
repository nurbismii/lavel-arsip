@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">Tim / Divisi</h5>
            <small class="text-muted">Kelola daftar tim yang bisa dipakai untuk akses supervisor dan penempatan dokumen.</small>
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

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('teams.store') }}" class="row g-2 align-items-end">
                @csrf

                <div class="col-md-8 col-lg-9">
                    <label class="form-label">Nama Tim / Divisi</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Finance, Operasional, Legal" required>
                </div>

                <div class="col-md-4 col-lg-3">
                    <button type="submit" class="btn btn-primary w-100">
                        + Tambah Tim
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($teams->count())
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="80">No</th>
                        <th>Nama Tim / Divisi</th>
                        <th width="140">User</th>
                        <th width="160">Dokumen</th>
                        <th width="280">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teams as $team)
                    <tr>
                        <td>{{ $teams->firstItem() + $loop->index }}</td>
                        <td>
                            <form id="update-team-{{ $team->id }}" method="POST" action="{{ route('teams.update', $team->id) }}">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="name" class="form-control" value="{{ old('name', $team->name) }}" required>
                            </form>
                        </td>
                        <td>{{ $team->users_count }} user</td>
                        <td>{{ $team->pekerjaans_count }} dokumen</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button type="submit" form="update-team-{{ $team->id }}" class="btn btn-sm btn-outline-warning">
                                    Simpan
                                </button>

                                <form method="POST" action="{{ route('teams.destroy', $team->id) }}"
                                    onsubmit="return confirm('Hapus tim/divisi {{ addslashes($team->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $teams->links() }}
    </div>
    @else
    <div class="alert alert-light border mb-0">
        Belum ada tim/divisi.
    </div>
    @endif
</div>
@endsection
