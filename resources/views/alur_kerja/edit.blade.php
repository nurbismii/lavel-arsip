@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Fondasi V-Ops</span>
            <h4 class="app-page-title">Edit Alur Kerja</h4>
            <p class="app-page-subtitle">{{ $alurKerja->nama }}</p>
        </div>
        <div class="app-page-actions">
            <a href="{{ route('alur-kerja.show', $alurKerja->id) }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Gagal memperbarui alur kerja.</strong>
            <div class="mb-2">Periksa kembali data wajib, status, dan pemilik proses.</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="app-card">
        <div class="card-body">
            <form method="POST" action="{{ route('alur-kerja.update', $alurKerja->id) }}" data-loading-form>
                @csrf
                @method('PUT')

                @include('alur_kerja._form', ['alurKerja' => $alurKerja])

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('alur-kerja.show', $alurKerja->id) }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" data-loading-text="Memperbarui...">Update Alur Kerja</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('alur_kerja._rich_text_editor_styles')
@endpush

@push('scripts')
@include('alur_kerja._rich_text_editor_script')
@endpush
