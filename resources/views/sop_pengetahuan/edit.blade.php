@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Fase 2 - SOP</span>
            <h4 class="app-page-title">Edit SOP</h4>
            <p class="app-page-subtitle">{{ $sopPengetahuan->judul }}</p>
        </div>
        <div class="app-page-actions">
            <a href="{{ route('sop-pengetahuan.show', $sopPengetahuan->id) }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Gagal memperbarui SOP.</strong>
            <div class="mb-2">Periksa kembali isi dokumen pada editor, status, relasi alur kerja, dan lampiran baru.</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="app-card">
        <div class="card-body">
            <form method="POST" action="{{ route('sop-pengetahuan.update', $sopPengetahuan->id) }}" enctype="multipart/form-data" data-loading-form>
                @csrf
                @method('PUT')

                @include('sop_pengetahuan._form', ['sopPengetahuan' => $sopPengetahuan])

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('sop-pengetahuan.show', $sopPengetahuan->id) }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" data-loading-text="Memperbarui...">Update SOP</button>
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
@include('alur_kerja._selected_file_script')
@include('sop_pengetahuan._stage_script')
@include('sop_pengetahuan._sop_structure_script')
@endpush
