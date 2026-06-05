@extends('layouts.guest')

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center auth-page-surface">
    <div class="row w-100 justify-content-center">
        <div class="col-md-4 col-lg-3">

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    {{-- Header --}}
                    <div class="text-center mb-4">
                        @include('_partials.brand-logo', [
                            'size' => 'lg',
                            'subtitle' => 'Buat akun V-Ops',
                            'align' => 'center',
                        ])
                    </div>

                    {{-- Form --}}
                    <form method="POST" action="{{ route('register') }}" data-loading-form>
                        @csrf

                        {{-- Name --}}
                        <div class="form-floating mb-3">
                            <input type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                placeholder="Nama lengkap"
                                value="{{ old('name') }}"
                                required autofocus>
                            <label for="name">Nama Lengkap</label>

                            @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-floating mb-3">
                            <input type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                placeholder="name@example.com"
                                value="{{ old('email') }}"
                                required>
                            <label for="email">Email</label>

                            @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="form-floating mb-3">
                            <input type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                placeholder="Password"
                                required>
                            <label for="password">Password</label>

                            @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="form-floating mb-3">
                            <input type="password"
                                class="form-control"
                                id="password-confirm"
                                name="password_confirmation"
                                placeholder="Konfirmasi Password"
                                required>
                            <label for="password-confirm">Konfirmasi Password</label>
                        </div>

                        {{-- Button --}}
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary rounded-3" data-loading-text="Membuat akun...">
                                Daftar
                            </button>
                        </div>

                        {{-- Link Login --}}
                        <div class="text-center">
                            <small class="text-muted">
                                Sudah punya akun?
                                <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">
                                    Masuk
                                </a>
                            </small>
                        </div>
                    </form>

                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-3">
                <small class="text-muted">
                    &copy; {{ date('Y') }} V-Ops
                </small>
            </div>

        </div>
    </div>
</div>
@endsection
