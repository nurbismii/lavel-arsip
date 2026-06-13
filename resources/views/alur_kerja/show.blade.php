@extends('layouts.app')

@section('content')
<div class="container py-4">
    @php
        $canManage = auth()->user()->canAccessAllFiles() || (int) $alurKerja->pemilik_utama_user_id === (int) auth()->id();
        $hasFilledStructuredRows = function ($rows) {
            return collect($rows ?? [])->contains(function ($row) {
                return collect($row)
                    ->except(['urutan'])
                    ->filter(function ($value) {
                        return trim((string) $value) !== '';
                    })
                    ->isNotEmpty();
            });
        };
    @endphp

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Gagal memproses perubahan alur kerja.</strong>
            <div class="mb-2">Periksa kembali data wajib dan ukuran lampiran yang dipilih.</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Detail Alur Kerja</span>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                @if($alurKerja->kode)
                    <span class="badge bg-light text-dark border">{{ $alurKerja->kode }}</span>
                @endif
                <span class="badge {{ $alurKerja->risiko_badge_class }}">{{ $alurKerja->risiko_label }}</span>
                <span class="badge {{ $alurKerja->status_dokumentasi_badge_class }}">{{ $alurKerja->status_dokumentasi_label }}</span>
            </div>
            <h4 class="app-page-title">{{ $alurKerja->nama }}</h4>
            <p class="app-page-subtitle">Dokumentasi operasional berisi tahapan, PIC, aplikasi, akun, file/template, dan dokumen terkait.</p>
        </div>
        <div class="app-page-actions">
            <a href="{{ route('alur-kerja.index') }}" class="btn btn-outline-secondary">Kembali</a>
            @if($canManage)
                <a href="{{ route('alur-kerja.edit', $alurKerja->id) }}" class="btn btn-outline-warning">Edit</a>
                <form method="POST"
                    action="{{ route('alur-kerja.destroy', $alurKerja->id) }}"
                    data-loading-form
                    data-confirm-title="Hapus alur kerja?"
                    data-confirm-text="Alur kerja {{ $alurKerja->nama }} akan dihapus. Dokumen terkait tidak ikut dihapus.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" data-loading-text="Menghapus...">Hapus</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="app-card h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Informasi Proses</h6>
                    @if($alurKerja->deskripsi)
                        <div class="rich-text-content text-muted">{!! \App\Support\RichText::render($alurKerja->deskripsi) !!}</div>
                    @else
                        <p class="mb-0 text-muted">Belum ada deskripsi.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="app-card h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Kepemilikan</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Unit / Tim</small>
                        <div class="fw-semibold">{{ optional($alurKerja->team)->name ?: '-' }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Penanggung jawab utama</small>
                        <div class="fw-semibold">{{ optional($alurKerja->pemilikUtama)->name ?: '-' }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Penanggung jawab cadangan</small>
                        <div class="fw-semibold">{{ $alurKerja->pemilik_cadangan_label }}</div>
                    </div>
                    <div>
                        <small class="text-muted d-block">Estimasi pengerjaan</small>
                        <div class="fw-semibold">{{ $alurKerja->estimasi_label }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-semibold mb-1">Tahapan Proses</h6>
                    <small class="text-muted">Urutan kerja, file/template, aplikasi, akun, dan PIC yang diperlukan di setiap tahap.</small>
                </div>
                @if($canManage)
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#formTambahTahap">
                        + Tambah Tahap
                    </button>
                @endif
            </div>

            @if($canManage)
                <div class="collapse {{ $errors->any() ? 'show' : '' }} mb-4" id="formTambahTahap">
                    <div class="border rounded-3 p-3 bg-light">
                        <form method="POST" action="{{ route('alur-kerja.tahap.store', $alurKerja->id) }}" enctype="multipart/form-data" data-loading-form>
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">No.</label>
                                    <input type="number" min="1" max="999" name="urutan" class="form-control" value="{{ old('urutan', $alurKerja->tahaps->count() + 1) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Tahap <span class="required-mark">*</span></label>
                                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" required placeholder="Contoh: Pemanggilan verifikasi berkas offline - email">
                                    @error('nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Estimasi Pengerjaan</label>
                                    <input type="text" name="estimasi" class="form-control @error('estimasi') is-invalid @enderror" value="{{ old('estimasi') }}" placeholder="Contoh: 2 jam / 1 hari kerja">
                                    @error('estimasi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-muted">Isi perkiraan durasi untuk menyelesaikan tahap ini.</small>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Deskripsi / Cara Kerja</label>
                                    <textarea name="deskripsi" rows="2" class="form-control">{{ old('deskripsi') }}</textarea>
                                </div>
                                @php
                                    $sistemTambahRows = old('sistem', [[]]);
                                    $picTambahRows = old('pic', [[]]);
                                    $sistemTambahEnabled = $hasFilledStructuredRows($sistemTambahRows);
                                    $picTambahEnabled = $hasFilledStructuredRows($picTambahRows);
                                @endphp
                                <div class="col-12 workflow-optional-section" data-structured-scope data-optional-section data-optional-enabled="{{ $sistemTambahEnabled ? '1' : '0' }}">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <div>
                                            <label class="form-label mb-0">Sistem / Aplikasi yang Digunakan</label>
                                            <small class="text-muted d-block">Tambahkan semua aplikasi, akun, dan fungsi yang dipakai pada tahap ini.</small>
                                        </div>
                                        <button type="button" class="workflow-toggle" data-optional-toggle aria-pressed="{{ $sistemTambahEnabled ? 'true' : 'false' }}">
                                            <span class="workflow-toggle-dot"></span>
                                            <span data-optional-toggle-text>{{ $sistemTambahEnabled ? 'ON' : 'OFF' }}</span>
                                        </button>
                                    </div>
                                    <div data-optional-body class="{{ $sistemTambahEnabled ? '' : 'd-none' }}">
                                        <div class="d-flex justify-content-end mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-system-row>+ Tambah Sistem</button>
                                        </div>
                                        @include('alur_kerja._sistem_rows', [
                                            'rows' => $sistemTambahRows,
                                            'namePrefix' => 'sistem',
                                            'errorPrefix' => 'sistem',
                                        ])
                                    </div>
                                </div>

                                <div class="col-12 workflow-optional-section" data-structured-scope data-optional-section data-optional-enabled="{{ $picTambahEnabled ? '1' : '0' }}">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <div>
                                            <label class="form-label mb-0">PIC / Orang Terkait</label>
                                            <small class="text-muted d-block">Tambahkan semua pihak yang perlu dihubungi. Jika lebih dari satu, geser baris PIC untuk mengatur urutan.</small>
                                        </div>
                                        <button type="button" class="workflow-toggle" data-optional-toggle aria-pressed="{{ $picTambahEnabled ? 'true' : 'false' }}">
                                            <span class="workflow-toggle-dot"></span>
                                            <span data-optional-toggle-text>{{ $picTambahEnabled ? 'ON' : 'OFF' }}</span>
                                        </button>
                                    </div>
                                    <div data-optional-body class="{{ $picTambahEnabled ? '' : 'd-none' }}">
                                        <div class="d-flex justify-content-end mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-pic-row>+ Tambah PIC</button>
                                        </div>
                                        @include('alur_kerja._pic_rows', [
                                            'rows' => $picTambahRows,
                                            'namePrefix' => 'pic',
                                            'errorPrefix' => 'pic',
                                        ])
                                    </div>
                                </div>
                                <div class="col-md-6" data-file-picker>
                                    <label class="form-label">File / Template</label>
                                    <input type="file" name="lampiran[]" class="form-control" multiple data-file-input-preview>
                                    <small class="text-muted">Bisa lebih dari satu file. Maksimal 20 MB per file.</small>
                                    <div class="mt-2 d-none" data-selected-file-list></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catatan Tambahan</label>
                                    <textarea name="catatan" rows="2" class="form-control">{{ old('catatan') }}</textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary" data-loading-text="Menyimpan tahap...">Simpan Tahap</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if($alurKerja->tahaps->count())
                <div class="d-grid gap-3">
                    @foreach($alurKerja->tahaps as $tahap)
                        <div class="workflow-step-card app-card-hover">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-2">
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <span class="badge bg-primary">Tahap {{ $tahap->urutan }}</span>
                                        <span class="badge bg-light text-secondary border">Estimasi: {{ $tahap->estimasi_label }}</span>
                                        @if($tahap->lampirans->count())
                                            <span class="badge bg-light text-dark border">{{ $tahap->lampirans->count() }} file/template</span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold mb-1">{{ $tahap->nama }}</h6>
                                    @if($tahap->deskripsi)
                                        <div class="rich-text-content text-muted mb-2">{!! \App\Support\RichText::render($tahap->deskripsi) !!}</div>
                                    @endif
                                </div>
                                @if($canManage)
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-warning" type="button" data-bs-toggle="collapse" data-bs-target="#editTahap{{ $tahap->id }}">
                                            Edit
                                        </button>
                                        <form method="POST"
                                            action="{{ route('alur-kerja.tahap.destroy', [$alurKerja->id, $tahap->id]) }}"
                                            data-loading-form
                                            data-confirm-title="Hapus tahap?"
                                            data-confirm-text="Tahap {{ $tahap->nama }} dan seluruh lampirannya akan dihapus.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-loading-text="Menghapus...">Hapus</button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-lg-6">
                                    <small class="text-muted d-block mb-2">Sistem / aplikasi tahap</small>
                                    @if($tahap->sistems->count())
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Nama</th>
                                                        <th>Fungsi</th>
                                                        <th>Akun</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($tahap->sistems as $sistem)
                                                        <tr>
                                                            <td class="fw-semibold">
                                                                {{ $sistem->nama_sistem ?: 'Sistem/aplikasi belum diberi nama' }}
                                                                @if($sistem->url)
                                                                    <a href="{{ $sistem->url }}" target="_blank" class="d-block small text-decoration-none">Buka akses</a>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($sistem->fungsi)
                                                                    <div class="rich-text-content rich-text-content--compact">{!! \App\Support\RichText::render($sistem->fungsi) !!}</div>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td>{{ $sistem->akun ?: '-' }}</td>
                                                        </tr>
                                                        @if($sistem->catatan)
                                                            <tr>
                                                                <td colspan="3" class="text-muted small">
                                                                    <span class="fw-semibold">Catatan:</span>
                                                                    <div class="rich-text-content rich-text-content--compact d-inline-block align-top">{!! \App\Support\RichText::render($sistem->catatan) !!}</div>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @elseif($tahap->aplikasi_digunakan || $tahap->akun_digunakan)
                                        <div class="alert alert-light border mb-0">
                                            <div><strong>Aplikasi:</strong> {{ $tahap->aplikasi_digunakan ?: '-' }}</div>
                                            <div><strong>Akun:</strong> {{ $tahap->akun_digunakan ?: '-' }}</div>
                                        </div>
                                    @else
                                        <div class="alert alert-light border mb-0">Belum ada sistem/aplikasi untuk tahap ini.</div>
                                    @endif
                                </div>

                                <div class="col-lg-6">
                                    <small class="text-muted d-block mb-2">PIC / orang terkait</small>
                                    @if($tahap->pics->count())
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Nama</th>
                                                        <th>Peran</th>
                                                        <th>Kontak</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($tahap->pics as $pic)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $pic->nama ?: 'PIC belum diberi nama' }}</td>
                                                            <td>{{ $pic->peran ?: '-' }}</td>
                                                            <td>{{ $pic->kontak ?: '-' }}</td>
                                                        </tr>
                                                        @if($pic->waktu_dihubungi || $pic->catatan)
                                                            <tr>
                                                                <td colspan="3" class="text-muted small">
                                                                    @if($pic->waktu_dihubungi)
                                                                        Dihubungi: {{ $pic->waktu_dihubungi }}
                                                                    @endif
                                                                    @if($pic->catatan)
                                                                        <div class="rich-text-content rich-text-content--compact {{ $pic->waktu_dihubungi ? 'mt-1' : '' }}">
                                                                            <span class="fw-semibold">Catatan:</span>
                                                                            {!! \App\Support\RichText::render($pic->catatan) !!}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @elseif($tahap->pic_terkait || $tahap->kontak_pic)
                                        <div class="alert alert-light border mb-0">
                                            <div><strong>PIC:</strong> {{ $tahap->pic_terkait ?: '-' }}</div>
                                            <div><strong>Kontak:</strong> {{ $tahap->kontak_pic ?: '-' }}</div>
                                        </div>
                                    @else
                                        <div class="alert alert-light border mb-0">Belum ada PIC untuk tahap ini.</div>
                                    @endif
                                </div>

                                @if($tahap->catatan)
                                    <div class="col-12">
                                        <small class="text-muted d-block">Catatan</small>
                                        <div class="rich-text-content">{!! \App\Support\RichText::render($tahap->catatan) !!}</div>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-3">
                                <small class="text-muted d-block mb-2">File / template tahap</small>
                                @if($tahap->lampirans->count())
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <tbody>
                                                @foreach($tahap->lampirans as $lampiran)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('alur-kerja.tahap.lampiran.show', [$alurKerja->id, $tahap->id, $lampiran->id]) }}" target="_blank" class="text-decoration-none">
                                                                {{ $lampiran->nama_file }}
                                                            </a>
                                                            <small class="text-muted d-block">{{ $lampiran->ukuran_file_label }} - {{ $lampiran->tanggal_upload }}</small>
                                                        </td>
                                                        @if($canManage)
                                                            <td class="text-end">
                                                                <form method="POST"
                                                                    action="{{ route('alur-kerja.tahap.lampiran.destroy', [$alurKerja->id, $tahap->id, $lampiran->id]) }}"
                                                                    data-loading-form
                                                                    data-confirm-title="Hapus lampiran?"
                                                                    data-confirm-text="File {{ $lampiran->nama_file }} akan dihapus dari tahap ini.">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-loading-text="Menghapus...">Hapus</button>
                                                                </form>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-light border mb-0">Belum ada file/template untuk tahap ini.</div>
                                @endif
                            </div>

                            @if($canManage)
                                <div class="collapse mt-3" id="editTahap{{ $tahap->id }}">
                                    <div class="border rounded-3 p-3 bg-light">
                                        <form method="POST" action="{{ route('alur-kerja.tahap.update', [$alurKerja->id, $tahap->id]) }}" enctype="multipart/form-data" data-loading-form>
                                            @csrf
                                            @method('PATCH')

                                            <div class="row g-3">
                                                <div class="col-md-2">
                                                    <label class="form-label">No.</label>
                                                    <input type="number" min="1" max="999" name="urutan" class="form-control" value="{{ old('urutan', $tahap->urutan) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Nama Tahap <span class="required-mark">*</span></label>
                                                    <input type="text" name="nama" class="form-control" value="{{ old('nama', $tahap->nama) }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Estimasi Pengerjaan</label>
                                                    <input type="text" name="estimasi" class="form-control @error('estimasi') is-invalid @enderror" value="{{ old('estimasi', $tahap->estimasi) }}" placeholder="Contoh: 2 jam / 1 hari kerja">
                                                    @error('estimasi')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @else
                                                        <small class="text-muted">Isi perkiraan durasi untuk menyelesaikan tahap ini.</small>
                                                    @enderror
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Deskripsi / Cara Kerja</label>
                                                    <textarea name="deskripsi" rows="2" class="form-control">{{ old('deskripsi', $tahap->deskripsi) }}</textarea>
                                                </div>
                                                @php
                                                    $sistemEditRows = old('sistem', $tahap->sistems->map(function ($sistem) {
                                                        return [
                                                            'urutan' => $sistem->urutan,
                                                            'nama_sistem' => $sistem->nama_sistem,
                                                            'fungsi' => $sistem->fungsi,
                                                            'akun' => $sistem->akun,
                                                            'url' => $sistem->url,
                                                            'catatan' => $sistem->catatan,
                                                        ];
                                                    })->values()->all());
                                                    $picEditRows = old('pic', $tahap->pics->map(function ($pic) {
                                                        return [
                                                            'urutan' => $pic->urutan,
                                                            'nama' => $pic->nama,
                                                            'peran' => $pic->peran,
                                                            'kontak' => $pic->kontak,
                                                            'waktu_dihubungi' => $pic->waktu_dihubungi,
                                                            'catatan' => $pic->catatan,
                                                        ];
                                                    })->values()->all());
                                                    $sistemEditEnabled = $tahap->sistems->count() || $hasFilledStructuredRows($sistemEditRows);
                                                    $picEditEnabled = $tahap->pics->count() || $hasFilledStructuredRows($picEditRows);
                                                @endphp
                                                <div class="col-12 workflow-optional-section" data-structured-scope data-optional-section data-optional-enabled="{{ $sistemEditEnabled ? '1' : '0' }}">
                                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                                        <div>
                                                            <label class="form-label mb-0">Sistem / Aplikasi yang Digunakan</label>
                                                            <small class="text-muted d-block">File lama tidak berubah; sistem di bawah ini akan menggantikan daftar sistem tahap.</small>
                                                        </div>
                                                        <button type="button" class="workflow-toggle" data-optional-toggle aria-pressed="{{ $sistemEditEnabled ? 'true' : 'false' }}">
                                                            <span class="workflow-toggle-dot"></span>
                                                            <span data-optional-toggle-text>{{ $sistemEditEnabled ? 'ON' : 'OFF' }}</span>
                                                        </button>
                                                    </div>
                                                    <div data-optional-body class="{{ $sistemEditEnabled ? '' : 'd-none' }}">
                                                        <div class="d-flex justify-content-end mb-2">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-system-row>+ Tambah Sistem</button>
                                                        </div>
                                                        @include('alur_kerja._sistem_rows', [
                                                            'rows' => $sistemEditRows,
                                                            'namePrefix' => 'sistem',
                                                            'errorPrefix' => 'sistem',
                                                        ])
                                                    </div>
                                                </div>

                                                <div class="col-12 workflow-optional-section" data-structured-scope data-optional-section data-optional-enabled="{{ $picEditEnabled ? '1' : '0' }}">
                                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                                        <div>
                                                            <label class="form-label mb-0">PIC / Orang Terkait</label>
                                                            <small class="text-muted d-block">Daftar ini akan menggantikan daftar PIC tahap. Geser baris PIC untuk mengatur urutan.</small>
                                                        </div>
                                                        <button type="button" class="workflow-toggle" data-optional-toggle aria-pressed="{{ $picEditEnabled ? 'true' : 'false' }}">
                                                            <span class="workflow-toggle-dot"></span>
                                                            <span data-optional-toggle-text>{{ $picEditEnabled ? 'ON' : 'OFF' }}</span>
                                                        </button>
                                                    </div>
                                                    <div data-optional-body class="{{ $picEditEnabled ? '' : 'd-none' }}">
                                                        <div class="d-flex justify-content-end mb-2">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-pic-row>+ Tambah PIC</button>
                                                        </div>
                                                        @include('alur_kerja._pic_rows', [
                                                            'rows' => $picEditRows,
                                                            'namePrefix' => 'pic',
                                                            'errorPrefix' => 'pic',
                                                        ])
                                                    </div>
                                                </div>
                                                <div class="col-md-6" data-file-picker>
                                                    <label class="form-label">Tambah File / Template</label>
                                                    <input type="file" name="lampiran[]" class="form-control" multiple data-file-input-preview>
                                                    <small class="text-muted">File baru akan ditambahkan tanpa menghapus file lama.</small>
                                                    <div class="mt-2 d-none" data-selected-file-list></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Catatan Tambahan</label>
                                                    <textarea name="catatan" rows="2" class="form-control">{{ old('catatan', $tahap->catatan) }}</textarea>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-end mt-3">
                                                <button type="submit" class="btn btn-primary" data-loading-text="Menyimpan...">Simpan Perubahan Tahap</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">0</div>
                    <h5>Belum ada tahapan proses</h5>
                    <p>Tambahkan tahap agar alur kerja dapat dipakai sebagai acuan operasional.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="app-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-semibold mb-1">SOP Terkait</h6>
                    <small class="text-muted">Standar prosedur operasional yang ditautkan ke alur kerja ini.</small>
                </div>
                <a href="{{ route('sop-pengetahuan.create', ['alur_kerja_id' => $alurKerja->id]) }}" class="btn btn-sm btn-outline-primary">Tambah SOP</a>
            </div>

            @if($sopPengetahuans->count())
                <div class="row g-3">
                    @foreach($sopPengetahuans as $item)
                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    @if($item->kode)
                                        <span class="badge bg-white text-dark border">{{ $item->kode }}</span>
                                    @endif
                                    <span class="badge {{ $item->jenis_badge_class }}">{{ $item->jenis_label }}</span>
                                    <span class="badge {{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                                    @if($item->tahap)
                                        <span class="badge bg-white text-secondary border">Tahap {{ $item->tahap->urutan }}</span>
                                    @endif
                                </div>
                                <h6 class="fw-bold mb-1">
                                    <a href="{{ route('sop-pengetahuan.show', $item->id) }}" class="text-decoration-none">
                                        {{ $item->judul }}
                                    </a>
                                </h6>
                                <small class="text-muted d-block">Pemilik: {{ optional($item->pemilik)->name ?: '-' }}</small>
                                <small class="text-muted d-block">{{ $item->lampirans_count }} lampiran - Tinjauan: {{ $item->tanggal_tinjauan_label }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <a href="{{ route('sop-pengetahuan.index', ['alur_kerja_id' => $alurKerja->id]) }}" class="btn btn-sm btn-outline-secondary">
                        Lihat Semua SOP Terkait
                    </a>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">0</div>
                    <h5>Belum ada SOP terkait</h5>
                    <p>Tambahkan SOP agar alur kerja memiliki acuan operasional yang jelas.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="app-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-semibold mb-1">Dokumen / Folder Terkait</h6>
                    <small class="text-muted">Folder dokumen yang ditautkan ke alur kerja ini.</small>
                </div>
                <a href="{{ route('pekerjaan.create') }}" class="btn btn-sm btn-outline-primary">Tambah Dokumen</a>
            </div>

            @if($pekerjaans->count())
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Unit</th>
                                <th>Lokasi</th>
                                <th>File</th>
                                <th>Target</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pekerjaans as $pekerjaan)
                                <tr>
                                    <td class="fw-semibold">{{ $pekerjaan->judul }}</td>
                                    <td>{{ optional($pekerjaan->team)->name ?: '-' }}</td>
                                    <td>{{ optional($pekerjaan->lokasi)->nama_lokasi ?: '-' }}</td>
                                    <td>{{ $pekerjaan->dokumens_count }}</td>
                                    <td>{{ $pekerjaan->tanggal_target_penyelesaian_label }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('pekerjaan.index', ['search' => $pekerjaan->judul]) }}" class="btn btn-sm btn-outline-primary">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $pekerjaans->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">0</div>
                    <h5>Belum ada dokumen terkait</h5>
                    <p>Dokumen atau folder akan muncul setelah ditautkan ke alur kerja ini.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('alur_kerja._rich_text_editor_styles')
@endpush

@push('scripts')
@include('alur_kerja._rich_text_editor_script')
@include('alur_kerja._selected_file_script')
@include('alur_kerja._structured_step_script')
@endpush
