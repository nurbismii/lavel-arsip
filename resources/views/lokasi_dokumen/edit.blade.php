@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h5 class="fw-bold mb-4">Edit Lokasi Dokumen</h5>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('lokasi-dokumen.update', $lokasi->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama Lokasi</label>
                    <input type="text" name="nama_lokasi" class="form-control" value="{{ old('nama_lokasi', $lokasi->nama_lokasi) }}" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('lokasi-dokumen.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
