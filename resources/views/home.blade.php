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
</style>
@endpush

@section('content')
<div class="container py-4">

    {{-- Header --}}
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Dashboard</h4>
        <small class="text-muted">Selamat datang di Sistem Arsipin</small>
    </div>

    {{-- QUICK ACCESS --}}
    <div class="mb-4">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted">Quick Access</h6>

        <div class="row g-3">
            <div class="col-md-6">
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

            <div class="col-md-6">
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
        </div>
    </div>

    {{-- DASHBOARD / STATISTIK --}}
    <div class="mb-4">
        <h6 class="fw-semibold mb-3 text-uppercase text-muted">Ringkasan Data</h6>

        <div class="mb-3">
            <small class="text-muted">
                {{ $canAccessAllFiles ? 'Menampilkan total keseluruhan dokumen berdasarkan status.' : 'Menampilkan total dokumen yang dapat Anda akses berdasarkan jabatan dan tim.' }}
            </small>
        </div>

        <div class="row g-3">
            @foreach($dashboardStats as $stat)
                <div class="col-md-4">
                    <div class="card border-0 {{ $stat['card_class'] }} rounded-4">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <div class="dashboard-icon-box {{ $stat['icon_wrapper_class'] }} rounded-3">
                                    @if($stat['icon'] === 'archive')
                                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                            <path d="M1.5 3.5A1.5 1.5 0 0 1 3 2h10a1.5 1.5 0 0 1 1.5 1.5V5H1.5V3.5ZM1 6h14v6.5A1.5 1.5 0 0 1 13.5 14h-11A1.5 1.5 0 0 1 1 12.5V6Zm4.5 2a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5Z"/>
                                        </svg>
                                    @elseif($stat['icon'] === 'draft')
                                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5L3 14l.646-3.354 9.5-9.5ZM11.207 2 4.5 8.707V10.5h1.793L13 3.793 11.207 2ZM1 13.5A1.5 1.5 0 0 0 2.5 15h11a.5.5 0 0 0 0-1h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 0-1 0v11Z"/>
                                        </svg>
                                    @elseif($stat['icon'] === 'active')
                                        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                            <path d="M8 1a7 7 0 1 0 4.95 11.95.5.5 0 1 0-.707-.707A6 6 0 1 1 14 8a.5.5 0 0 0 1 0A7 7 0 0 0 8 1Z"/>
                                            <path d="M7.5 4.5a.5.5 0 0 1 1 0v3.793l2.354 2.353a.5.5 0 0 1-.708.708l-2.5-2.5A.5.5 0 0 1 7.5 8.5v-4Z"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <small class="text-muted">{{ $stat['label'] }}</small>
                                <h4 class="fw-bold mb-0">{{ $stat['value'] }}</h4>
                            </div>
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
                        Sistem Arsipin digunakan untuk mengelola dokumen secara digital.
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
