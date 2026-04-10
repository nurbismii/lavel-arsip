@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">Log Aktivitas</h5>
            <small class="text-muted">Pantau aktivitas terbaru yang terjadi di sistem. Refresh halaman untuk melihat log terbaru.</small>
        </div>
    </div>

    <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-2 mb-4">
        <div class="col-12 col-md-8">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Cari deskripsi, target, atau nama user...">
        </div>
        <div class="col-6 col-md-auto">
            <button type="submit" class="btn btn-primary w-100">Cari</button>
        </div>
        <div class="col-6 col-md-auto">
            <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>

    @if($activities->count())
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="py-3">Pengguna</th>
                            <th class="py-3">Aksi</th>
                            <th class="py-3">Deskripsi</th>
                            <th class="py-3">Target</th>
                            <th class="py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td class="px-4">{{ $activity->tanggal_aktivitas }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $activity->actor_name }}</div>
                                    @if(optional($activity->user)->email)
                                        <small class="text-muted">{{ $activity->user->email }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $activity->action_badge_class }}">
                                        {{ $activity->action_label }}
                                    </span>
                                </td>
                                <td>{{ $activity->description }}</td>
                                <td>{{ $activity->subject_name ?: '-' }}</td>
                                <td>{{ $activity->ip_address ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $activities->links() }}
        </div>
    @else
        <div class="alert alert-light border mb-0">
            Belum ada log aktivitas.
        </div>
    @endif
</div>
@endsection
