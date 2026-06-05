@php($alurKerja = $alurKerja ?? null)

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Kode</label>
        <input
            type="text"
            name="kode"
            class="form-control @error('kode') is-invalid @enderror"
            value="{{ old('kode', optional($alurKerja)->kode) }}"
            placeholder="OPS-001">
        @error('kode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label class="form-label">Nama Alur Kerja <span class="required-mark">*</span></label>
        <input
            type="text"
            name="nama"
            class="form-control @error('nama') is-invalid @enderror"
            value="{{ old('nama', optional($alurKerja)->nama) }}"
            required>
        @error('nama')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Deskripsi</label>
        <textarea
            name="deskripsi"
            rows="4"
            class="form-control @error('deskripsi') is-invalid @enderror"
            placeholder="Ringkas proses, batasan, dan konteks operasional.">{{ old('deskripsi', optional($alurKerja)->deskripsi) }}</textarea>
        @error('deskripsi')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Unit / Tim</label>
        <select name="team_id" class="form-select @error('team_id') is-invalid @enderror">
            <option value="">-- Tanpa Unit --</option>
            @foreach($teams as $team)
                <option value="{{ $team->id }}" {{ (int) old('team_id', optional($alurKerja)->team_id) === (int) $team->id ? 'selected' : '' }}>
                    {{ $team->name }}
                </option>
            @endforeach
        </select>
        @error('team_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Penanggung Jawab Utama <span class="required-mark">*</span></label>
        <select name="pemilik_utama_user_id" class="form-select @error('pemilik_utama_user_id') is-invalid @enderror" {{ auth()->user()->canAccessAllFiles() ? '' : 'disabled' }}>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ (int) old('pemilik_utama_user_id', optional($alurKerja)->pemilik_utama_user_id ?: auth()->id()) === (int) $user->id ? 'selected' : '' }}>
                    {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                </option>
            @endforeach
        </select>
        @error('pemilik_utama_user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if(!auth()->user()->canAccessAllFiles())
            <input type="hidden" name="pemilik_utama_user_id" value="{{ auth()->id() }}">
            <small class="text-muted">Untuk role Anda, PIC utama otomatis memakai akun sendiri.</small>
        @endif
    </div>

    <div class="col-md-4">
        <label class="form-label">Penanggung Jawab Cadangan</label>
        <select name="pemilik_cadangan_user_id" class="form-select @error('pemilik_cadangan_user_id') is-invalid @enderror">
            <option value="">-- Belum ditetapkan --</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ (int) old('pemilik_cadangan_user_id', optional($alurKerja)->pemilik_cadangan_user_id) === (int) $user->id ? 'selected' : '' }}>
                    {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                </option>
            @endforeach
        </select>
        @error('pemilik_cadangan_user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Risiko <span class="required-mark">*</span></label>
        <select name="risiko" class="form-select @error('risiko') is-invalid @enderror" required>
            @foreach($risikoOptions as $value => $label)
                <option value="{{ $value }}" {{ old('risiko', optional($alurKerja)->risiko ?: \App\Models\AlurKerja::RISIKO_SEDANG) === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('risiko')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Status Dokumentasi <span class="required-mark">*</span></label>
        <select name="status_dokumentasi" class="form-select @error('status_dokumentasi') is-invalid @enderror" required>
            @foreach($statusDokumentasiOptions as $value => $label)
                <option value="{{ $value }}" {{ old('status_dokumentasi', optional($alurKerja)->status_dokumentasi ?: \App\Models\AlurKerja::DOKUMENTASI_BELUM_LENGKAP) === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status_dokumentasi')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Status Operasional <span class="required-mark">*</span></label>
        <select name="status_operasional" class="form-select @error('status_operasional') is-invalid @enderror" required>
            @foreach($statusOperasionalOptions as $value => $label)
                <option value="{{ $value }}" {{ old('status_operasional', optional($alurKerja)->status_operasional ?: \App\Models\AlurKerja::STATUS_AKTIF) === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status_operasional')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Target Tinjauan</label>
        <input
            type="date"
            name="target_tinjauan_berikutnya"
            class="form-control @error('target_tinjauan_berikutnya') is-invalid @enderror"
            value="{{ old('target_tinjauan_berikutnya', optional(optional($alurKerja)->target_tinjauan_berikutnya)->format('Y-m-d')) }}">
        @error('target_tinjauan_berikutnya')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
