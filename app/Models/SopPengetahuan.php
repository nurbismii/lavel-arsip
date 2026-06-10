<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SopPengetahuan extends Model
{
    public const JENIS_SOP = 'sop';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_TERBIT = 'terbit';
    public const STATUS_ARSIP = 'arsip';

    public const PRIORITAS_NORMAL = 'normal';
    public const PRIORITAS_PENTING = 'penting';
    public const PRIORITAS_KRITIS = 'kritis';

    public const SIMBOL_TERMINATOR = 'terminator';
    public const SIMBOL_AKTIVITAS = 'aktivitas';
    public const SIMBOL_DECISION = 'decision';
    public const SIMBOL_DOKUMEN = 'dokumen';
    public const SIMBOL_CONNECTOR_HALAMAN = 'connector_halaman';
    public const SIMBOL_CONNECTOR_INTERNAL = 'connector_internal';

    protected $table = 'sop_pengetahuan';

    protected $fillable = [
        'kode',
        'nomor_revisi',
        'judul',
        'jenis',
        'ringkasan',
        'tujuan',
        'ruang_lingkup',
        'definisi',
        'prosedur',
        'daftar_lampiran',
        'catatan_revisi',
        'konten',
        'team_id',
        'alur_kerja_id',
        'alur_kerja_tahap_id',
        'pemilik_user_id',
        'status',
        'tingkat_kepentingan',
        'tanggal_berlaku',
        'target_tinjauan_berikutnya',
        'kata_kunci',
    ];

    protected $casts = [
        'tanggal_berlaku' => 'date',
        'target_tinjauan_berikutnya' => 'date',
        'definisi' => 'array',
        'prosedur' => 'array',
        'daftar_lampiran' => 'array',
        'catatan_revisi' => 'array',
    ];

    public static function jenisOptions(): array
    {
        return [
            self::JENIS_SOP => 'SOP',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_REVIEW => 'Dalam Review',
            self::STATUS_TERBIT => 'Terbit',
            self::STATUS_ARSIP => 'Arsip',
        ];
    }

    public static function prioritasOptions(): array
    {
        return [
            self::PRIORITAS_NORMAL => 'Normal',
            self::PRIORITAS_PENTING => 'Penting',
            self::PRIORITAS_KRITIS => 'Kritis',
        ];
    }

    public static function simbolOptions(): array
    {
        return [
            self::SIMBOL_TERMINATOR => 'Terminator - Mulai/Selesai',
            self::SIMBOL_AKTIVITAS => 'Aktivitas - Langkah Kerja',
            self::SIMBOL_DECISION => 'Decision - Keputusan Ya/Tidak',
            self::SIMBOL_DOKUMEN => 'Document - Dokumen Digunakan',
            self::SIMBOL_CONNECTOR_HALAMAN => 'Off Page Connector',
            self::SIMBOL_CONNECTOR_INTERNAL => 'On Page Connector',
        ];
    }

    public function getJenisLabelAttribute(): string
    {
        return self::jenisOptions()[$this->jenis] ?? 'SOP';
    }

    public function getJenisBadgeClassAttribute(): string
    {
        return [
            self::JENIS_SOP => 'bg-primary',
        ][$this->jenis] ?? 'bg-primary';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return [
            self::STATUS_DRAFT => 'bg-secondary',
            self::STATUS_REVIEW => 'bg-warning text-dark',
            self::STATUS_TERBIT => 'bg-success',
            self::STATUS_ARSIP => 'bg-dark',
        ][$this->status] ?? 'bg-secondary';
    }

    public function getPrioritasLabelAttribute(): string
    {
        return self::prioritasOptions()[$this->tingkat_kepentingan] ?? ucfirst((string) $this->tingkat_kepentingan);
    }

    public function getPrioritasBadgeClassAttribute(): string
    {
        return [
            self::PRIORITAS_NORMAL => 'bg-light text-dark border',
            self::PRIORITAS_PENTING => 'bg-warning text-dark',
            self::PRIORITAS_KRITIS => 'bg-danger',
        ][$this->tingkat_kepentingan] ?? 'bg-secondary';
    }

    public function getTanggalBerlakuLabelAttribute(): string
    {
        return $this->tanggal_berlaku ? $this->tanggal_berlaku->format('d M Y') : '-';
    }

    public function getTanggalTinjauanLabelAttribute(): string
    {
        return $this->target_tinjauan_berikutnya ? $this->target_tinjauan_berikutnya->format('d M Y') : '-';
    }

    public function getMembutuhkanTinjauanAttribute(): bool
    {
        return $this->status !== self::STATUS_TERBIT
            || ($this->target_tinjauan_berikutnya && $this->target_tinjauan_berikutnya->isPast());
    }

    public function getMemilikiStrukturSopAttribute(): bool
    {
        return filled($this->konten)
            || filled($this->tujuan)
            || filled($this->ruang_lingkup)
            || !empty($this->definisi)
            || !empty($this->prosedur)
            || !empty($this->daftar_lampiran)
            || !empty($this->catatan_revisi);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function alurKerja()
    {
        return $this->belongsTo(AlurKerja::class);
    }

    public function tahap()
    {
        return $this->belongsTo(AlurKerjaTahap::class, 'alur_kerja_tahap_id');
    }

    public function pemilik()
    {
        return $this->belongsTo(User::class, 'pemilik_user_id');
    }

    public function lampirans()
    {
        return $this->hasMany(SopPengetahuanLampiran::class)->orderBy('id');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canAccessAllFiles()) {
            return $query;
        }

        $teamIds = $user->assignedTeamIds();

        return $query->where(function ($query) use ($user, $teamIds) {
            $query->where('pemilik_user_id', $user->id)
                ->orWhere(function ($query) {
                    $query->where('status', self::STATUS_TERBIT)
                        ->whereNull('team_id');
                });

            if (!empty($teamIds)) {
                $query->orWhereIn('team_id', $teamIds);
            }
        });
    }

    public function scopeManageableBy($query, User $user)
    {
        if ($user->canAccessAllFiles()) {
            return $query;
        }

        return $query->where('pemilik_user_id', $user->id);
    }
}
