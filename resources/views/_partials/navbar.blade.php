<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container py-2">

        {{-- Brand aplikasi --}}
        <a class="navbar-brand d-flex align-items-center gap-3 me-0" href="{{ auth()->check() ? route('home') : url('/') }}">
            @include('_partials.brand-logo', ['size' => 'md'])
        </a>

        {{-- Toggle Mobile --}}
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">

            @auth
            <div class="ms-lg-4 mt-3 mt-lg-0 me-auto">
                @php
                    $kelolaDokumenActive = request()->is('pekerjaan*') || request()->is('lokasi-dokumen*');
                    $prosesKerjaActive = request()->is('alur-kerja*') || request()->is('sop-pengetahuan*') || request()->is('jobdesc*');
                @endphp

                <ul class="navbar-nav flex-column flex-lg-row gap-2">
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill {{ request()->is('home') ? 'active fw-semibold bg-primary text-white' : 'text-dark bg-light' }}"
                            href="{{ route('home') }}">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle px-3 py-2 rounded-pill {{ $prosesKerjaActive ? 'active fw-semibold bg-primary text-white' : 'text-dark bg-light' }}"
                            href="#"
                            id="prosesKerjaDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Proses Kerja
                        </a>
                        <div class="dropdown-menu shadow-sm border-0 rounded-3 mt-2" aria-labelledby="prosesKerjaDropdown">
                            <a class="dropdown-item {{ request()->is('alur-kerja*') ? 'active' : '' }}"
                                href="{{ route('alur-kerja.index') }}">
                                Alur Kerja
                            </a>
                            <a class="dropdown-item {{ request()->is('sop-pengetahuan*') ? 'active' : '' }}"
                                href="{{ route('sop-pengetahuan.index') }}">
                                SOP
                            </a>
                            <a class="dropdown-item {{ request()->is('jobdesc*') ? 'active' : '' }}"
                                href="{{ route('jobdesc.index') }}">
                                Jobdesc
                            </a>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle px-3 py-2 rounded-pill {{ $kelolaDokumenActive ? 'active fw-semibold bg-primary text-white' : 'text-dark bg-light' }}"
                            href="#"
                            id="kelolaDokumenDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Kelola Dokumen
                        </a>
                        <div class="dropdown-menu shadow-sm border-0 rounded-3 mt-2" aria-labelledby="kelolaDokumenDropdown">
                            <a class="dropdown-item {{ request()->is('pekerjaan*') ? 'active' : '' }}"
                                href="{{ route('pekerjaan.index') }}">
                                Dokumen Operasional
                            </a>
                            <a class="dropdown-item {{ request()->is('lokasi-dokumen*') ? 'active' : '' }}"
                                href="{{ route('lokasi-dokumen.index') }}">
                                Lokasi Dokumen
                            </a>
                        </div>
                    </li>

                    @if(auth()->user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill {{ request()->is('log-aktivitas*') ? 'active fw-semibold bg-primary text-white' : 'text-dark bg-light' }}"
                            href="{{ route('activity-logs.index') }}">
                            Log Aktivitas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill {{ request()->is('kelola-user*') ? 'active fw-semibold bg-primary text-white' : 'text-dark bg-light' }}"
                            href="{{ route('users.index') }}">
                            Kelola User
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill {{ request()->is('tim-divisi*') ? 'active fw-semibold bg-primary text-white' : 'text-dark bg-light' }}"
                            href="{{ route('teams.index') }}">
                            Tim / Divisi
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            @endauth

            <ul class="navbar-nav ms-lg-auto mt-3 mt-lg-0">
                @guest
                <li class="nav-item d-flex align-items-lg-center">
                    <span class="small text-uppercase text-muted fw-semibold me-lg-3 mb-2 mb-lg-0" style="letter-spacing: .12em;">
                        Akses
                    </span>
                </li>

                @if (Route::has('login'))
                <li class="nav-item mb-2 mb-lg-0 me-lg-2">
                    <a class="nav-link px-3 py-2 rounded-pill text-dark bg-light" href="{{ route('login') }}">
                        Login
                    </a>
                </li>
                @endif

                @if (Route::has('register'))
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-pill text-primary border border-primary" href="{{ route('register') }}">
                        Daftar
                    </a>
                </li>
                @endif
                @else

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-3 py-2 rounded-pill border bg-white"
                        href="#"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-primary fw-bold"
                            style="width: 34px; height: 34px;">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        <span class="d-flex flex-column lh-sm text-start">
                            <span class="small text-muted">{{ Auth::user()->role_label }}</span>
                            <span class="fw-semibold text-dark">{{ Auth::user()->name }}</span>
                        </span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                        <div class="dropdown-header text-uppercase small text-muted fw-semibold">
                            Akun
                        </div>

                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            Profil
                        </a>

                        @if(auth()->user()->isAdmin())
                        <a class="dropdown-item" href="{{ route('activity-logs.index') }}">
                            Log Aktivitas
                        </a>
                        <a class="dropdown-item" href="{{ route('users.index') }}">
                            Kelola User
                        </a>
                        <a class="dropdown-item" href="{{ route('teams.index') }}">
                            Tim / Divisi
                        </a>
                        @endif

                        <div class="dropdown-divider"></div>

                        <a class="dropdown-item text-danger"
                            href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Logout
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </li>

                @endguest
            </ul>

        </div>
    </div>
</nav>
