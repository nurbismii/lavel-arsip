@extends('layouts.app')

@section('content')
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Fondasi V-Ops</span>
            <h4 class="app-page-title">Registri Alur Kerja</h4>
            <p class="app-page-subtitle">Petakan proses, tahapan, PIC, prioritas, aplikasi, akun, dan kesiapan dokumentasi operasional.</p>
        </div>
        <div class="app-page-actions">
            <a href="{{ route('alur-kerja.create') }}" class="btn btn-primary">+ Tambah Alur Kerja</a>
        </div>
    </div>

    <form method="GET" action="{{ route('alur-kerja.index') }}" class="filter-panel" data-loading-form>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label">Pencarian</label>
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    class="form-control"
                    placeholder="Cari nama, kode, atau deskripsi...">
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Prioritas</label>
                <select name="risiko" class="form-select">
                    <option value="">Semua Prioritas</option>
                    @foreach($risikoOptions as $value => $label)
                        <option value="{{ $value }}" {{ $risiko === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Dokumentasi</label>
                <select name="status_dokumentasi" class="form-select">
                    <option value="">Semua Dokumentasi</option>
                    @foreach($statusDokumentasiOptions as $value => $label)
                        <option value="{{ $value }}" {{ $statusDokumentasi === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label">Operasional</label>
                <select name="status_operasional" class="form-select">
                    <option value="">Semua Operasional</option>
                    @foreach($statusOperasionalOptions as $value => $label)
                        <option value="{{ $value }}" {{ $statusOperasional === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <button type="submit" class="btn btn-primary w-100" data-loading-text="Memfilter...">Filter</button>
            </div>
            <div class="col-6 col-lg-1">
                <a href="{{ route('alur-kerja.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </div>
    </form>

    @if($alurKerjas->count())
        <div class="row g-3">
            @foreach($alurKerjas as $alurKerja)
                <div class="col-lg-4">
                    <div class="app-card app-card-hover h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        @if($alurKerja->kode)
                                            <span class="badge bg-light text-dark border">{{ $alurKerja->kode }}</span>
                                        @endif
                                        <span class="badge {{ $alurKerja->risiko_badge_class }}">{{ $alurKerja->risiko_label }}</span>
                                        <span class="badge {{ $alurKerja->status_dokumentasi_badge_class }}">{{ $alurKerja->status_dokumentasi_label }}</span>
                                        <span class="badge {{ $alurKerja->status_operasional_badge_class }}">{{ $alurKerja->status_operasional_label }}</span>
                                    </div>
                                    <h6 class="fw-bold mb-1">
                                        <a href="{{ route('alur-kerja.show', $alurKerja->id) }}" class="text-decoration-none">
                                            {{ $alurKerja->nama }}
                                        </a>
                                    </h6>
                                    <small class="text-muted d-block">Unit: {{ optional($alurKerja->team)->name ?: '-' }}</small>
                                    <small class="text-muted d-block">PIC utama: {{ optional($alurKerja->pemilikUtama)->name ?: '-' }}</small>
                                    <small class="text-muted d-block">Cadangan: {{ optional($alurKerja->pemilikCadangan)->name ?: 'Belum ditetapkan' }}</small>
                                </div>
                                @if($alurKerja->membutuhkan_perhatian)
                                    <span class="badge bg-danger">Perhatian</span>
                                @endif
                            </div>

                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-secondary border">{{ $alurKerja->tahaps_count }} tahap proses</span>
                                <span class="badge bg-light text-secondary border">{{ $alurKerja->sop_pengetahuans_count }} SOP</span>
                                <span class="badge bg-light text-secondary border">{{ $alurKerja->pekerjaans_count }} dokumen/folder terkait</span>
                                <span class="badge bg-light text-secondary border">Estimasi: {{ $alurKerja->estimasi_label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $alurKerjas->links() }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">0</div>
            <h5>Belum ada alur kerja</h5>
            <p>Data akan muncul setelah alur kerja dibuat atau filter pencarian diubah.</p>
        </div>
    @endif
</div>
@endsection
