@extends('layouts.app')

@push('styles')
<style>
    .dashboard-icon-box {
        width: 56px;
        height: 56px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .dashboard-icon-box svg {
        width: 24px;
        height: 24px;
    }

    .dashboard-icon-primary {
        background-color: rgba(13, 110, 253, 0.12);
        color: #0d6efd;
    }

    .dashboard-icon-success {
        background-color: rgba(25, 135, 84, 0.12);
        color: #198754;
    }

    .dashboard-icon-warning {
        background-color: rgba(255, 193, 7, 0.18);
        color: #b78103;
    }

    .deadline-card-warning {
        background-color: #fff8e1;
        border-color: #ffc107 !important;
    }

    .deadline-card-danger {
        background-color: #fff1f2;
        border-color: #dc3545 !important;
    }

    .deadline-status-warning {
        color: #946200;
    }

    .deadline-status-danger {
        color: #b02a37;
    }
</style>
@endpush

@section('content')
<div class="container py-4">

    {{-- Header --}}
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Dashboard</h4>
        <small class="text-muted">Selamat datang di V-Ops</small>
    </div>

    <div class="mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h6 class="fw-semibold mb-1 text-uppercase text-muted">Fondasi V-Ops</h6>
                <small class="text-muted">Ringkasan alur kerja, risiko, kesiapan dokumentasi, dan cadangan operasional.</small>
            </div>
            <a href="{{ route('alur-kerja.index') }}" class="btn btn-sm btn-outline-primary">
                Buka Registri
            </a>
        </div>

        <div class="row g-3">
            @foreach($opsStats as $stat)
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <span class="badge {{ $stat['class'] }} mb-2">{{ $stat['label'] }}</span>
                            <h4 class="fw-bold mb-0">{{ $stat['value'] }}</h4>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($opsAttentionItems->count())
            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h6 class="fw-semibold mb-0">Alur Kerja Perlu Perhatian</h6>
                        <a href="{{ route('alur-kerja.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="row g-2">
                        @foreach($opsAttentionItems as $alurKerja)
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <span class="badge {{ $alurKerja->risiko_badge_class }}">{{ $alurKerja->risiko_label }}</span>
                                        <span class="badge {{ $alurKerja->status_dokumentasi_badge_class }}">{{ $alurKerja->status_dokumentasi_label }}</span>
                                    </div>
                                    <a href="{{ route('alur-kerja.show', $alurKerja->id) }}" class="fw-semibold text-decoration-none">
                                        {{ $alurKerja->nama }}
                                    </a>
                                    <small class="text-muted d-block">
                                        Unit: {{ optional($alurKerja->team)->name ?: '-' }}
                                    </small>
                                    <small class="text-muted d-block">
                                        Cadangan: {{ optional($alurKerja->pemilikCadangan)->name ?: 'Belum ditetapkan' }}
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h6 class="fw-semibold mb-1 text-uppercase text-muted">Fase 2 - SOP dan Pengetahuan</h6>
                <small class="text-muted">Pantau kesiapan SOP, panduan, kebijakan, FAQ, dan pembelajaran operasional.</small>
            </div>
            <a href="{{ route('sop-pengetahuan.index') }}" class="btn btn-sm btn-outline-primary">
                Buka Pengetahuan
            </a>
        </div>

        <div class="row g-3">
            @foreach($knowledgeStats as $stat)
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <span class="badge {{ $stat['class'] }} mb-2">{{ $stat['label'] }}</span>
                            <h4 class="fw-bold mb-0">{{ $stat['value'] }}</h4>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($knowledgeAttentionItems->count())
            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h6 class="fw-semibold mb-0">SOP Perlu Tinjauan</h6>
                        <a href="{{ route('sop-pengetahuan.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="row g-2">
                        @foreach($knowledgeAttentionItems as $item)
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <span class="badge {{ $item->jenis_badge_class }}">{{ $item->jenis_label }}</span>
                                        <span class="badge {{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                                    </div>
                                    <a href="{{ route('sop-pengetahuan.show', $item->id) }}" class="fw-semibold text-decoration-none">
                                        {{ $item->judul }}
                                    </a>
                                    <small class="text-muted d-block">
                                        Pemilik: {{ optional($item->pemilik)->name ?: '-' }}
                                    </small>
                                    <small class="text-muted d-block">
                                        Tinjauan: {{ $item->tanggal_tinjauan_label }}
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($deadlineAlerts->count())
    <div class="alert {{ $hasCriticalDeadlineAlerts ? 'alert-danger' : 'alert-warning' }} border-0 shadow-sm rounded-4 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <h6 class="fw-semibold mb-1">Alert Penyelesaian Dokumen</h6>
                <div class="small">
                    H-3 ditandai kuning. Mulai H-2 sampai dokumen selesai ditandai merah.
                </div>
            </div>
            <a href="{{ route('pekerjaan.index') }}" class="btn btn-sm btn-outline-dark align-self-lg-start">
                Lihat Dokumen
            </a>
        </div>

        <div class="row g-2 mt-2">
            @foreach($deadlineAlerts as $doc)
                @php($pekerjaan = $doc->pekerjaan)
                @php($hariMenujuTarget = $pekerjaan ? $pekerjaan->hari_menuju_target : null)
                @php($isCriticalDeadline = $hariMenujuTarget !== null && $hariMenujuTarget <= 2)
                <div class="col-md-6">
                    <div class="{{ $isCriticalDeadline ? 'deadline-card-danger' : 'deadline-card-warning' }} rounded-3 border p-3 h-100">
                        <div class="d-flex justify-content-between gap-2">
                            <a href="{{ route('dokumen.lihat', $doc->id) }}" target="_blank" class="fw-semibold text-decoration-none">
                                {{ $doc->nama_file }}
                            </a>
                            <span class="badge {{ $doc->status_dokumen_badge_class }}">
                                {{ $doc->status_dokumen_label }}
                            </span>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Folder : {{ optional($pekerjaan)->judul ?: '-' }}
                        </small>
                        <small class="d-block fw-semibold {{ $isCriticalDeadline ? 'deadline-status-danger' : 'deadline-status-warning' }}">
                            Target : {{ optional($pekerjaan)->tanggal_target_penyelesaian_label ?: '-' }}
                            ({{ optional($pekerjaan)->status_target_penyelesaian ?: '-' }})
                        </small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- QUICK ACCESS --}}
    <div class="mb-4">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted">Quick Access</h6>

        <div class="row g-3">
            <div class="col-md-4">
                <a href="{{ route('profile.edit') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="transition: .2s;">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <div class="dashboard-icon-box dashboard-icon-primary rounded-3">
                                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3Zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">Profil Saya</h6>
                                <small class="text-muted">Kelola data akun Anda</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="{{ route('pekerjaan.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="transition: .2s;">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <div class="dashboard-icon-box dashboard-icon-success rounded-3">
                                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                        <path d="M9.828 4a3 3 0 0 0-5.656 0H2.5A1.5 1.5 0 0 0 1 5.5v7A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5v-7A1.5 1.5 0 0 0 13.5 4H9.828ZM8 3a2 2 0 0 1 1.732 1H6.268A2 2 0 0 1 8 3Zm5.5 2a.5.5 0 0 1 .5.5V6h-4V5H9v1H7V5H6v1H2V5.5a.5.5 0 0 1 .5-.5h11ZM2 12.5V7h4v1h1V7h2v1h1V7h4v5.5a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5Z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">Dokumen</h6>
                                <small class="text-muted">Lihat & tambah dokumen</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="{{ route('sop-pengetahuan.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="transition: .2s;">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <div class="dashboard-icon-box dashboard-icon-warning rounded-3">
                                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                        <path d="M2 2.75A1.75 1.75 0 0 1 3.75 1h8.5A1.75 1.75 0 0 1 14 2.75v10.5A1.75 1.75 0 0 1 12.25 15h-8.5A1.75 1.75 0 0 1 2 13.25V2.75ZM3.75 2a.75.75 0 0 0-.75.75v10.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75V2.75a.75.75 0 0 0-.75-.75h-8.5ZM5 4h6v1H5V4Zm0 2.5h6v1H5v-1ZM5 9h4v1H5V9Z"/>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">SOP dan Pengetahuan</h6>
                                <small class="text-muted">Buka panduan operasional</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- PAPAN INFORMASI STATUS --}}
    <div class="mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h6 class="fw-semibold mb-1 text-uppercase text-muted">Papan Informasi Status Dokumen</h6>
                <small class="text-muted">Pantau dokumen yang masih dalam proses, sedang digunakan, dan sudah selesai.</small>
            </div>
            <a href="{{ route('pekerjaan.index') }}" class="btn btn-sm btn-outline-primary">
                Kelola Status
            </a>
        </div>

        <div class="row g-3">
            @foreach($statusBoards as $board)
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <span class="badge {{ $board['badge_class'] }} mb-2">
                                        {{ $board['label'] }}
                                    </span>
                                    <h5 class="fw-bold mb-0">{{ $board['total'] }} dokumen</h5>
                                </div>
                                <a href="{{ route('pekerjaan.index', ['status_dokumen' => $board['status']]) }}" class="btn btn-sm btn-outline-primary">
                                    Lihat Semua
                                </a>
                            </div>

                            @if($board['documents']->count())
                                @foreach($board['documents'] as $doc)
                                    @php($pekerjaan = $doc->pekerjaan)
                                    <div class="{{ $loop->last ? '' : 'border-bottom pb-2 mb-2' }}">
                                        <a href="{{ route('dokumen.lihat', $doc->id) }}" target="_blank" class="fw-semibold text-decoration-none">
                                            {{ $doc->nama_file }}
                                        </a>
                                        <small class="text-muted d-block">
                                            Folder : {{ optional($pekerjaan)->judul ?: '-' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            Rentang : {{ optional($pekerjaan)->rentang_penyelesaian ?: '-' }}
                                        </small>
                                        @if($doc->status_dokumen === \App\Models\Dokumen::STATUS_AKTIF)
                                            <small class="text-primary fw-semibold d-block">
                                                Dipinjam oleh : {{ optional($doc->peminjam)->name ?: 'Belum dipilih' }}
                                            </small>
                                        @endif
                                        @if($doc->status_dokumen === \App\Models\Dokumen::STATUS_ARSIP)
                                            <small class="text-muted d-block">
                                                Selesai : {{ $doc->tanggal_diselesaikan }}
                                            </small>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-light border mb-0">
                                    Belum ada dokumen pada status ini.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Content --}}
    <div class="row g-3">

        {{-- Aktivitas --}}
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h6 class="fw-semibold mb-0">{{ $isAdmin ? 'Aktivitas Sistem Terbaru' : 'Aktivitas Anda Terbaru' }}</h6>

                        @if($isAdmin)
                            <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-primary">
                                Lihat Semua
                            </a>
                        @endif
                    </div>

                    @if($recentActivities->count())
                        @foreach($recentActivities as $activity)
                            <div class="{{ $loop->last ? '' : 'border-bottom pb-2 mb-2' }}">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="badge {{ $activity->action_badge_class }}">
                                        {{ $activity->action_label }}
                                    </span>

                                    @if($isAdmin)
                                        <span class="small text-muted">
                                            {{ $activity->actor_name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="fw-medium">{{ $activity->description }}</div>
                                <small class="text-muted">
                                    {{ $activity->tanggal_aktivitas }}
                                    @if($activity->subject_name)
                                        · {{ $activity->subject_name }}
                                    @endif
                                </small>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-light border mb-0">
                            Belum ada aktivitas yang tercatat.
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- Informasi --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Informasi</h6>

                    <div class="alert alert-light border rounded-3 mb-2">
                        V-Ops digunakan untuk menjaga keberlanjutan pekerjaan melalui alur kerja, dokumen operasional, dan catatan tanggung jawab.
                    </div>

                    <div class="alert alert-light border rounded-3 mb-0">
                        Pastikan data yang diinput sudah benar dan lengkap.
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection
