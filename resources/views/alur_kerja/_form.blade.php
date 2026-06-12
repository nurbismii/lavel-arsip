@php
    $alurKerja = isset($alurKerja) ? $alurKerja : null;
    $cadanganSubmitted = old('cadangan_user_ids_present') !== null;

    if ($cadanganSubmitted) {
        $selectedCadanganUserIds = old('pemilik_cadangan_user_ids', []);
    } elseif($alurKerja) {
        $selectedCadanganUserIds = $alurKerja->relationLoaded('pemilikCadangans')
            ? $alurKerja->pemilikCadangans->pluck('id')->all()
            : $alurKerja->pemilikCadangans()->pluck('users.id')->all();

        if (empty($selectedCadanganUserIds) && $alurKerja->pemilik_cadangan_user_id) {
            $selectedCadanganUserIds = [$alurKerja->pemilik_cadangan_user_id];
        }
    } else {
        $selectedCadanganUserIds = [];
    }

    $selectedCadanganUserIds = collect((array) $selectedCadanganUserIds)
        ->map(function ($userId) {
            return (int) $userId;
        })
        ->filter()
        ->values()
        ->all();

    $cadanganError = $errors->first('pemilik_cadangan_user_ids') ?: $errors->first('pemilik_cadangan_user_ids.*');
@endphp

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
        <select name="pemilik_utama_user_id" class="form-select @error('pemilik_utama_user_id') is-invalid @enderror" data-primary-owner-select {{ auth()->user()->canAccessAllFiles() ? '' : 'disabled' }}>
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
            <input type="hidden" name="pemilik_utama_user_id" value="{{ auth()->id() }}" data-primary-owner-hidden>
            <small class="text-muted">Untuk role Anda, PIC utama otomatis memakai akun sendiri.</small>
        @endif
    </div>

    <div class="col-md-4">
        <label class="form-label">Penanggung Jawab Cadangan</label>
        <input type="hidden" name="cadangan_user_ids_present" value="1">
        <div class="cadangan-owner-select-wrapper">
            <select
                name="pemilik_cadangan_user_ids[]"
                class="form-select cadangan-owner-select {{ $cadanganError ? 'is-invalid' : '' }}"
                multiple
                data-cadangan-owner-select
                data-placeholder="Cari dan pilih penanggung jawab cadangan">
            @foreach($users as $user)
                @php($isSelectedCadangan = in_array((int) $user->id, $selectedCadanganUserIds, true))
                <option value="{{ $user->id }}" {{ $isSelectedCadangan ? 'selected' : '' }}>
                    {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                </option>
            @endforeach
            </select>
        </div>
        @if($cadanganError)
            <div class="invalid-feedback d-block">{{ $cadanganError }}</div>
        @else
            <small class="text-muted">Ketik nama/email untuk mencari.</small>
        @endif
    </div>

    <div class="col-md-4">
        <label class="form-label">Prioritas <span class="required-mark">*</span></label>
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

    <div class="col-md-4">
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

    <div class="col-md-4">
        <label class="form-label">Estimasi Waktu Pengerjaan</label>
        <input
            type="text"
            name="estimasi"
            class="form-control @error('estimasi') is-invalid @enderror"
            value="{{ old('estimasi', optional($alurKerja)->estimasi) }}"
            maxlength="100"
            readonly
            data-workflow-total-estimate
            placeholder="Contoh: 2 hari atau 1-2 hari, 3 jam, dst.">
        @error('estimasi')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <small class="text-muted">Otomatis dihitung dari total estimasi setiap tahapan proses.</small>
        @enderror
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        const primarySelect = form.querySelector('[data-primary-owner-select]');
        const primaryHidden = form.querySelector('[data-primary-owner-hidden]');
        const backupSelect = form.querySelector('[data-cadangan-owner-select]');

        if (!backupSelect) {
            return;
        }

        const $backupSelect = window.jQuery ? window.jQuery(backupSelect) : null;

        function selectedPrimaryId() {
            if (primarySelect && !primarySelect.disabled) {
                return primarySelect.value;
            }

            if (primaryHidden) {
                return primaryHidden.value;
            }

            return primarySelect ? primarySelect.value : '';
        }

        function syncBackupOptions() {
            const primaryId = selectedPrimaryId();

            Array.from(backupSelect.options).forEach(function (option) {
                const isPrimary = option.value === primaryId;

                if (isPrimary) {
                    option.selected = false;
                }

                option.disabled = isPrimary;
            });

            if ($backupSelect && $backupSelect.data('select2')) {
                $backupSelect.trigger('change');
            }
        }

        if ($backupSelect && window.jQuery.fn && window.jQuery.fn.select2) {
            $backupSelect.select2({
                width: '100%',
                placeholder: backupSelect.dataset.placeholder || 'Pilih penanggung jawab cadangan',
                closeOnSelect: false,
                allowClear: true,
                language: {
                    noResults: function () {
                        return 'Data tidak ditemukan';
                    },
                    searching: function () {
                        return 'Mencari...';
                    }
                }
            });
        }

        if (primarySelect) {
            primarySelect.addEventListener('change', syncBackupOptions);
        }

        syncBackupOptions();
    });
});
</script>
@endpush
