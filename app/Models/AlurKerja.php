<?php

namespace App\Models;

use App\Support\EstimasiPengerjaan;
use Illuminate\Database\Eloquent\Model;

class AlurKerja extends Model
{
    public const RISIKO_RENDAH = 'rendah';
    public const RISIKO_SEDANG = 'sedang';
    public const RISIKO_TINGGI = 'tinggi';
    public const RISIKO_KRITIS = 'kritis';

    public const DOKUMENTASI_BELUM_LENGKAP = 'belum_lengkap';
    public const DOKUMENTASI_PERLU_UPDATE = 'perlu_update';
    public const DOKUMENTASI_LENGKAP = 'lengkap';

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_REVIEW = 'review';
    public const STATUS_NONAKTIF = 'nonaktif';

    protected $table = 'alur_kerja';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'team_id',
        'pemilik_utama_user_id',
        'pemilik_cadangan_user_id',
        'risiko',
        'status_dokumentasi',
        'status_operasional',
        'target_tinjauan_berikutnya',
        'estimasi',
    ];

    protected $casts = [
        'target_tinjauan_berikutnya' => 'date',
    ];

    public static function risikoOptions(): array
    {
        return [
            self::RISIKO_RENDAH => 'Rendah',
            self::RISIKO_SEDANG => 'Sedang',
            self::RISIKO_TINGGI => 'Tinggi',
            self::RISIKO_KRITIS => 'Kritis',
        ];
    }

    public static function statusDokumentasiOptions(): array
    {
        return [
            self::DOKUMENTASI_BELUM_LENGKAP => 'Belum Lengkap',
            self::DOKUMENTASI_PERLU_UPDATE => 'Perlu Update',
            self::DOKUMENTASI_LENGKAP => 'Lengkap',
        ];
    }

    public static function statusOperasionalOptions(): array
    {
        return [
            self::STATUS_AKTIF => 'Aktif',
            self::STATUS_REVIEW => 'Dalam Review',
            self::STATUS_NONAKTIF => 'Nonaktif',
        ];
    }

    public function getRisikoLabelAttribute(): string
    {
        return self::risikoOptions()[$this->risiko] ?? ucfirst((string) $this->risiko);
    }

    public function getRisikoBadgeClassAttribute(): string
    {
        return [
            self::RISIKO_RENDAH => 'bg-success',
            self::RISIKO_SEDANG => 'bg-primary',
            self::RISIKO_TINGGI => 'bg-warning text-dark',
            self::RISIKO_KRITIS => 'bg-danger',
        ][$this->risiko] ?? 'bg-secondary';
    }

    public function getStatusDokumentasiLabelAttribute(): string
    {
        return self::statusDokumentasiOptions()[$this->status_dokumentasi] ?? ucfirst((string) $this->status_dokumentasi);
    }

    public function getStatusDokumentasiBadgeClassAttribute(): string
    {
        return [
            self::DOKUMENTASI_BELUM_LENGKAP => 'bg-danger',
            self::DOKUMENTASI_PERLU_UPDATE => 'bg-warning text-dark',
            self::DOKUMENTASI_LENGKAP => 'bg-success',
        ][$this->status_dokumentasi] ?? 'bg-secondary';
    }

    public function getStatusOperasionalLabelAttribute(): string
    {
        return self::statusOperasionalOptions()[$this->status_operasional] ?? ucfirst((string) $this->status_operasional);
    }

    public function getStatusOperasionalBadgeClassAttribute(): string
    {
        return [
            self::STATUS_AKTIF => 'bg-success',
            self::STATUS_REVIEW => 'bg-warning text-dark',
            self::STATUS_NONAKTIF => 'bg-secondary',
        ][$this->status_operasional] ?? 'bg-secondary';
    }

    public function getTanggalTinjauanLabelAttribute(): string
    {
        return $this->target_tinjauan_berikutnya ? $this->target_tinjauan_berikutnya->format('d M Y') : '-';
    }

    public function getEstimasiLabelAttribute(): string
    {
        return $this->estimasi ?: '-';
    }

    public function getMemilikiPemilikCadanganAttribute(): bool
    {
        if (array_key_exists('pemilik_cadangans_count', $this->attributes)) {
            return (int) $this->attributes['pemilik_cadangans_count'] > 0 || filled($this->pemilik_cadangan_user_id);
        }

        if ($this->relationLoaded('pemilikCadangans')) {
            return $this->pemilikCadangans->isNotEmpty() || filled($this->pemilik_cadangan_user_id);
        }

        return $this->pemilikCadangans()->exists() || filled($this->pemilik_cadangan_user_id);
    }

    public function getPemilikCadanganLabelAttribute(): string
    {
        $pemilikCadangans = $this->relationLoaded('pemilikCadangans')
            ? $this->pemilikCadangans
            : $this->pemilikCadangans()->get(['users.id', 'users.name']);

        if ($pemilikCadangans->isNotEmpty()) {
            return $pemilikCadangans->pluck('name')->filter()->implode(', ');
        }

        return optional($this->pemilikCadangan)->name ?: 'Belum ditetapkan';
    }

    public function getMembutuhkanPerhatianAttribute(): bool
    {
        return in_array($this->risiko, [self::RISIKO_TINGGI, self::RISIKO_KRITIS], true)
            || $this->status_dokumentasi !== self::DOKUMENTASI_LENGKAP
            || !$this->memiliki_pemilik_cadangan;
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function pemilikUtama()
    {
        return $this->belongsTo(User::class, 'pemilik_utama_user_id');
    }

    public function pemilikCadangan()
    {
        return $this->belongsTo(User::class, 'pemilik_cadangan_user_id');
    }

    public function pemilikCadangans()
    {
        return $this->belongsToMany(User::class, 'alur_kerja_pemilik_cadangan', 'alur_kerja_id', 'user_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function pekerjaans()
    {
        return $this->hasMany(Pekerjaan::class);
    }

    public function tahaps()
    {
        return $this->hasMany(AlurKerjaTahap::class)->orderBy('urutan')->orderBy('id');
    }

    public function sopPengetahuans()
    {
        return $this->hasMany(SopPengetahuan::class)->latest();
    }

    public function sinkronEstimasiDariTahap(): void
    {
        $estimasi = EstimasiPengerjaan::fromTahaps(
            $this->tahaps()->get(['id', 'estimasi'])
        );

        $this->forceFill([
            'estimasi' => $estimasi,
        ])->save();
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canAccessAllFiles()) {
            return $query;
        }

        return $query->where(function ($query) use ($user) {
            $query->where('pemilik_utama_user_id', $user->id)
                ->orWhere('pemilik_cadangan_user_id', $user->id)
                ->orWhereHas('pemilikCadangans', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                });

            if ($user->isSupervisor()) {
                $teamIds = $user->assignedTeamIds();

                if (!empty($teamIds)) {
                    $query->orWhereIn('team_id', $teamIds);
                }
            }
        });
    }

    public function scopeManageableBy($query, User $user)
    {
        if ($user->canAccessAllFiles()) {
            return $query;
        }

        return $query->where('pemilik_utama_user_id', $user->id);
    }
}
