@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Fase 2 - SOP</span>
            <h4 class="app-page-title">SOP</h4>
            <p class="app-page-subtitle">Kelola standar prosedur operasional agar proses kerja terdokumentasi, mudah diikuti, dan siap ditinjau.</p>
        </div>
        <div class="app-page-actions">
            <a href="{{ route('sop-pengetahuan.create') }}" class="btn btn-primary">+ Tambah SOP</a>
        </div>
    </div>

    <form method="GET" action="{{ route('sop-pengetahuan.index') }}" class="filter-panel" data-loading-form>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label">Pencarian</label>
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    class="form-control"
                    placeholder="Cari judul, kode, isi, atau kata kunci...">
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label">Alur Kerja</label>
                <select name="alur_kerja_id" class="form-select">
                    <option value="">Semua Alur</option>
                    @foreach($alurKerjas as $alurKerja)
                        <option value="{{ $alurKerja->id }}" {{ (int) $alurKerjaId === (int) $alurKerja->id ? 'selected' : '' }}>
                            {{ $alurKerja->kode ? $alurKerja->kode . ' - ' : '' }}{{ $alurKerja->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <button type="submit" class="btn btn-primary w-100" data-loading-text="Memfilter...">Filter</button>
            </div>
            <div class="col-6 col-lg-1">
                <a href="{{ route('sop-pengetahuan.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </div>
    </form>

    @if($sopPengetahuans->count())
        <div class="row g-3">
            @foreach($sopPengetahuans as $item)
                <div class="col-lg-6">
                    <div class="app-card app-card-hover h-100">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        @if($item->kode)
                                            <span class="badge bg-light text-dark border">{{ $item->kode }}</span>
                                        @endif
                                        @if($item->nomor_revisi)
                                            <span class="badge bg-light text-dark border">Rev. {{ $item->nomor_revisi }}</span>
                                        @endif
                                        <span class="badge {{ $item->jenis_badge_class }}">{{ $item->jenis_label }}</span>
                                        <span class="badge {{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                                        <span class="badge {{ $item->prioritas_badge_class }}">{{ $item->prioritas_label }}</span>
                                        @if($item->membutuhkan_tinjauan)
                                            <span class="badge bg-danger">Perlu Tinjauan</span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold mb-1">
                                        <a href="{{ route('sop-pengetahuan.show', $item->id) }}" class="text-decoration-none">
                                            {{ $item->judul }}
                                        </a>
                                    </h6>
                                    <small class="text-muted d-block">Pemilik: {{ optional($item->pemilik)->name ?: '-' }}</small>
                                    <small class="text-muted d-block">Unit: {{ optional($item->team)->name ?: 'Umum' }}</small>
                                </div>
                                <a href="{{ route('sop-pengetahuan.show', $item->id) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                            </div>

                            @if($item->ringkasan)
                                <div class="rich-text-content text-muted mt-3">{!! \App\Support\RichText::render($item->ringkasan) !!}</div>
                            @elseif($item->tujuan)
                                <div class="rich-text-content text-muted mt-3">{!! \App\Support\RichText::render($item->tujuan) !!}</div>
                            @elseif($item->konten)
                                <p class="text-muted mt-3 mb-0">{{ \Illuminate\Support\Str::limit(trim(strip_tags($item->konten)), 220) }}</p>
                            @endif

                            <div class="mt-3 d-flex flex-wrap gap-2">
                                @if($item->alurKerja)
                                    <span class="badge bg-light text-secondary border">{{ $item->alurKerja->nama }}</span>
                                @endif
                                @if($item->tahap)
                                    <span class="badge bg-light text-secondary border">Tahap {{ $item->tahap->urutan }}</span>
                                @endif
                                <span class="badge bg-light text-secondary border">{{ $item->lampirans_count }} lampiran</span>
                                <span class="badge bg-light text-secondary border">Tinjauan: {{ $item->tanggal_tinjauan_label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $sopPengetahuans->links() }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">0</div>
            <h5>Belum ada SOP</h5>
            <p>Data akan muncul setelah SOP dibuat atau filter pencarian diubah.</p>
        </div>
    @endif
</div>
@endsection

@push('styles')
@include('alur_kerja._rich_text_editor_styles')
@endpush
