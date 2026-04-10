@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kelola User</h4>
            <small class="text-muted">Atur jabatan, tim/divisi, dan status aktif akun dari halaman ini.</small>
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

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="py-3">Email</th>
                            <th class="py-3">Jabatan, Tim, Status</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="px-4">
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    @if((int) $user->id === (int) auth()->id())
                                        <small class="text-muted">Akun Anda</small>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td colspan="3">
                                    <form method="POST" action="{{ route('users.update', $user->id) }}" class="row g-2 align-items-center">
                                        @csrf
                                        @method('PATCH')

                                        @php
                                            $selectedRole = old('role', $user->role === 'user' ? 'staff' : $user->role);
                                            $selectedTeamIds = array_map('intval', (array) old('team_ids', $user->teams->pluck('id')->all()));
                                        @endphp

                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Jabatan</label>
                                            <select
                                                name="role"
                                                class="form-select"
                                                {{ (int) $user->id === (int) auth()->id() ? 'disabled' : '' }}>
                                                @foreach($roleOptions as $value => $label)
                                                <option value="{{ $value }}" {{ $selectedRole === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1">Tim / Divisi</label>
                                            <select
                                                name="team_ids[]"
                                                class="form-select"
                                                multiple
                                                size="3"
                                                {{ (int) $user->id === (int) auth()->id() || $teams->isEmpty() ? 'disabled' : '' }}>
                                                @foreach($teams as $team)
                                                <option value="{{ $team->id }}" {{ in_array((int) $team->id, $selectedTeamIds, true) ? 'selected' : '' }}>
                                                    {{ $team->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                            @if($teams->isEmpty())
                                            <small class="text-muted">Belum ada tim/divisi.</small>
                                            @else
                                            <small class="text-muted">Tahan Ctrl untuk memilih lebih dari satu.</small>
                                            @endif
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label small text-muted mb-1">Status Akun</label>
                                            <select
                                                name="is_active"
                                                class="form-select"
                                                {{ (int) $user->id === (int) auth()->id() ? 'disabled' : '' }}>
                                                <option value="1" {{ $user->is_active ? 'selected' : '' }}>Aktif</option>
                                                <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Nonaktif</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <span class="badge bg-primary">{{ $user->role_label }}</span>

                                            @if($user->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Nonaktif</span>
                                            @endif

                                            <div class="small text-muted mt-2">
                                                {{ $user->teams->pluck('name')->implode(', ') ?: 'Belum ada tim' }}
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            @if((int) $user->id === (int) auth()->id())
                                                <button type="button" class="btn btn-outline-secondary w-100" disabled>
                                                    Tidak Bisa Diubah
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-primary w-100">
                                                    Simpan
                                                </button>
                                            @endif
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada data user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $users->links() }}
    </div>
</div>
@endsection
