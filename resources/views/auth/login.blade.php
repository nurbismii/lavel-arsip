@extends('layouts.guest')

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="row w-100 justify-content-center">
        <div class="col-md-4 col-lg-3">

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    {{-- Header --}}
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-primary mb-1">ARSIPIN</h4>
                        <small class="text-muted">Sistem Manajemen Arsip</small>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger py-2" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Form --}}
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        {{-- Email --}}
                        <div class="form-floating mb-3">
                            <input type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                placeholder="name@example.com"
                                value="{{ old('email') }}"
                                required autofocus>
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

                        {{-- Remember --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    name="remember" id="remember"
                                    {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label small" for="remember">
                                    Ingat saya
                                </label>
                            </div>

                            @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="small text-decoration-none">
                                Lupa?
                            </a>
                            @endif
                        </div>

                        {{-- Button --}}
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary rounded-3">
                                Masuk
                            </button>
                        </div>

                        {{-- Link Register --}}
                        @if (Route::has('register'))
                        <div class="text-center">
                            <small class="text-muted">
                                Belum punya akun?
                                <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">
                                    Daftar
                                </a>
                            </small>
                        </div>
                        @endif
                    </form>

                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-3">
                <small class="text-muted">
                    © {{ date('Y') }} Arsipin
                </small>
            </div>

        </div>
    </div>
</div>
@endsection
