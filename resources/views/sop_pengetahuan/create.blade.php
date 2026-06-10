@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Fase 2 - SOP</span>
            <h4 class="app-page-title">Tambah SOP</h4>
            <p class="app-page-subtitle">Dokumentasikan standar prosedur operasional agar proses kerja mudah dipahami dan ditinjau.</p>
        </div>
        <div class="app-page-actions">
            <a href="{{ route('sop-pengetahuan.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Gagal menyimpan SOP.</strong>
            <div class="mb-2">Periksa kembali judul, isi dokumen pada editor, relasi alur kerja, dan ukuran lampiran yang dipilih.</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="app-card">
        <div class="card-body">
            <form method="POST" action="{{ route('sop-pengetahuan.store') }}" enctype="multipart/form-data" data-loading-form>
                @csrf

                @include('sop_pengetahuan._form')

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('sop-pengetahuan.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" data-loading-text="Menyimpan...">Simpan SOP</button>
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
