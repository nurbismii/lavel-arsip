@extends('layouts.guest')

@php
    $selectedLoginSource = old('login_source', 'hris');

    if (! in_array($selectedLoginSource, ['hris', 'local'], true)) {
        $selectedLoginSource = 'hris';
    }
@endphp

@push('styles')
<style>
    .auth-login-card {
        overflow: hidden;
    }

    .auth-login-card .card-body {
        position: relative;
        z-index: 1;
    }

    .login-method-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
    }

    .login-method-option {
        position: relative;
        display: block;
        min-width: 0;
        height: 100%;
    }

    .login-method-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .login-method-card {
        display: flex;
        flex-direction: column;
        gap: .48rem;
        height: 100%;
        min-height: 110px;
        padding: .72rem;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 14px;
        background: rgba(255, 255, 255, .34);
        color: var(--app-dark);
        cursor: pointer;
        box-shadow: var(--neu-shadow-inset);
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease, background .18s ease;
    }

    .login-method-card:hover {
        transform: translateY(-1px);
        border-color: rgba(37, 99, 235, .34);
        background: rgba(255, 255, 255, .54);
        box-shadow: var(--neu-shadow-sm);
    }

    .login-method-input:focus-visible + .login-method-card {
        box-shadow: var(--neu-shadow-sm), 0 0 0 .22rem rgba(37, 99, 235, .16);
    }

    .login-method-input:checked + .login-method-card {
        border-color: rgba(37, 99, 235, .58);
        background:
            linear-gradient(135deg, rgba(37, 99, 235, .11), rgba(15, 118, 110, .09)),
            rgba(255, 255, 255, .72);
        box-shadow: var(--neu-shadow-sm);
    }

    .login-method-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    .login-method-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 24px;
        padding: 0 .48rem;
        border-radius: 999px;
        background: var(--app-primary-soft);
        color: var(--app-primary);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .04em;
    }

    .login-method-check {
        width: 14px;
        height: 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 999px;
        background: #fff;
        box-shadow: inset 0 0 0 2.5px #fff;
        transition: border-color .18s ease, background-color .18s ease;
    }

    .login-method-input:checked + .login-method-card .login-method-check {
        border-color: var(--app-primary);
        background: var(--app-primary);
    }

    .login-method-card strong {
        display: block;
        font-size: .98rem;
        line-height: 1.2;
    }

    .login-method-card small {
        display: block;
        color: var(--app-muted);
        line-height: 1.35;
    }

    .login-context-note {
        border: 1px solid rgba(37, 99, 235, .14);
        border-radius: 14px;
        background: rgba(238, 244, 255, .72);
        color: #1e3a8a;
        padding: .72rem .85rem;
        font-size: .86rem;
        font-weight: 600;
    }

    .auth-secondary-copy {
        color: var(--app-muted);
        font-size: .86rem;
        line-height: 1.45;
    }

    @media (max-height: 760px) and (min-width: 576px) {
        .auth-page-surface {
            padding-top: .85rem;
            padding-bottom: .85rem;
        }

        .auth-login-card .card-body {
            padding: 1.15rem !important;
        }

        .auth-login-card .brand-logo-mark--lg {
            --brand-logo-size: 56px;
            border-radius: 18px;
        }

        .login-method-card {
            min-height: 100px;
            padding: .68rem;
        }

        .login-context-note {
            padding: .62rem .75rem;
        }
    }

    @media (max-width: 575.98px) {
        .auth-page-surface {
            padding: 1rem .75rem;
        }

        .auth-login-card .card-body {
            padding: 1.15rem !important;
        }

        .auth-login-card .brand-logo-mark--lg {
            --brand-logo-size: 54px;
            border-radius: 17px;
        }

        .auth-login-card .brand-logo-subtitle,
        .auth-login-card .text-muted.small {
            font-size: .8rem;
        }

        .login-method-grid {
            grid-template-columns: 1fr;
            gap: .6rem;
        }

        .login-method-card {
            min-height: auto;
            padding: .68rem;
        }

        .login-method-card strong {
            font-size: .94rem;
        }

        .login-method-card small,
        .login-context-note {
            font-size: .82rem;
        }

        .login-context-note {
            padding: .62rem .75rem;
        }

        .auth-login-card .form-floating.mb-3 {
            margin-bottom: .75rem !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center auth-page-surface">
    <div class="row w-100 justify-content-center">
        <div class="col-md-7 col-lg-5 col-xl-4">

            <div class="card border-0 shadow-sm rounded-4 auth-login-card">
                <div class="card-body p-4">

                    {{-- Header --}}
                    <div class="text-center mb-3">
                        @include('_partials.brand-logo', [
                            'size' => 'lg',
                            'subtitle' => 'Sistem Keberlanjutan Operasional',
                            'align' => 'center',
                        ])
                        <p class="text-muted small mb-0 mt-2">
                            Pilih cara login yang sesuai dengan akun Anda.
                        </p>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger py-2" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Form --}}
                    <form method="POST" action="{{ route('login') }}" data-loading-form data-login-form>
                        @csrf

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <label class="form-label fw-semibold mb-0">
                                    Pilih cara login <span class="text-danger">*</span>
                                </label>
                            </div>

                            <div class="login-method-grid" role="radiogroup" aria-label="Pilih cara login">
                                <label class="login-method-option" for="loginSourceHris">
                                    <input class="login-method-input @error('login_source') is-invalid @enderror"
                                        type="radio"
                                        id="loginSourceHris"
                                        name="login_source"
                                        value="hris"
                                        data-login-method="hris"
                                        {{ $selectedLoginSource === 'hris' ? 'checked' : '' }}>
                                    <span class="login-method-card">
                                        <span class="login-method-top">
                                            <span class="login-method-badge">V-People</span>
                                            <span class="login-method-check" aria-hidden="true"></span>
                                        </span>
                                        <span>
                                            <strong>Akun V-People</strong>
                                            <small>Email dan password dari V-People aktif.</small>
                                        </span>
                                    </span>
                                </label>

                                <label class="login-method-option" for="loginSourceLocal">
                                    <input class="login-method-input @error('login_source') is-invalid @enderror"
                                        type="radio"
                                        id="loginSourceLocal"
                                        name="login_source"
                                        value="local"
                                        data-login-method="local"
                                        {{ $selectedLoginSource === 'local' ? 'checked' : '' }}>
                                    <span class="login-method-card">
                                        <span class="login-method-top">
                                            <span class="login-method-badge">V-Ops</span>
                                            <span class="login-method-check" aria-hidden="true"></span>
                                        </span>
                                        <span>
                                            <strong>Akun V-Ops</strong>
                                            <small>Jika tidak memiliki akun V-People</small>
                                        </span>
                                    </span>
                                </label>
                            </div>

                            @error('login_source')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="login-context-note mt-3" data-login-method-help>
                                @if ($selectedLoginSource === 'local')
                                    Memakai akun lokal V-Ops. Untuk HRIS, pilih HRIS V-People.
                                @else
                                    Gunakan akun HRIS V-People tanpa membuat akun V-Ops baru.
                                @endif
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="form-floating mb-3">
                            <input type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                placeholder="name@example.com"
                                value="{{ old('email') }}"
                                required autofocus>
                            <label for="email" data-login-email-label>
                                {{ $selectedLoginSource === 'local' ? 'Email Akun V-Ops' : 'Email HRIS V-People' }}
                            </label>

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
                            <label for="password" data-login-password-label>
                                {{ $selectedLoginSource === 'local' ? 'Password Akun V-Ops' : 'Password HRIS V-People' }}
                            </label>

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

                        </div>

                        {{-- Button --}}
                        <div class="d-grid mb-3">
                            <button type="submit"
                                class="btn btn-primary rounded-3"
                                data-login-submit
                                data-loading-text="{{ $selectedLoginSource === 'local' ? 'Memeriksa akun V-Ops...' : 'Memeriksa akun HRIS...' }}">
                                Masuk
                            </button>
                        </div>

                        {{-- Link Register --}}
                        @if (Route::has('register'))
                        <div class="text-center">
                            <small class="auth-secondary-copy">
                                Perlu akun lokal V-Ops?
                                <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">
                                    Daftar akun
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
                    &copy; {{ date('Y') }} V-Ops
                </small>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.querySelector('[data-login-form]');

    if (!loginForm) {
        return;
    }

    const emailLabel = loginForm.querySelector('[data-login-email-label]');
    const passwordLabel = loginForm.querySelector('[data-login-password-label]');
    const methodHelp = loginForm.querySelector('[data-login-method-help]');
    const submitButton = loginForm.querySelector('[data-login-submit]');
    const methodInputs = loginForm.querySelectorAll('input[name="login_source"]');

    const copy = {
        hris: {
            email: 'Email HRIS V-People',
            password: 'Password HRIS V-People',
            help: 'Gunakan akun HRIS V-People tanpa membuat akun V-Ops baru.',
            loading: 'Memeriksa akun HRIS...'
        },
        local: {
            email: 'Email Akun V-Ops',
            password: 'Password Akun V-Ops',
            help: 'Memakai akun lokal V-Ops. Untuk HRIS, pilih HRIS V-People.',
            loading: 'Memeriksa akun V-Ops...'
        }
    };

    function getSelectedLoginSource() {
        const selectedInput = loginForm.querySelector('input[name="login_source"]:checked');

        return selectedInput ? selectedInput.value : 'hris';
    }

    function refreshLoginCopy() {
        const selectedCopy = copy[getSelectedLoginSource()] || copy.hris;

        if (emailLabel) {
            emailLabel.textContent = selectedCopy.email;
        }

        if (passwordLabel) {
            passwordLabel.textContent = selectedCopy.password;
        }

        if (methodHelp) {
            methodHelp.textContent = selectedCopy.help;
        }

        if (submitButton) {
            submitButton.dataset.loadingText = selectedCopy.loading;
        }
    }

    methodInputs.forEach(function (input) {
        input.addEventListener('change', refreshLoginCopy);
    });

    refreshLoginCopy();
});
</script>
@endpush
