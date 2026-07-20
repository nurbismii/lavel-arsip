<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jobdesc extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_ARSIP = 'arsip';

    protected $fillable = [
        'jabatan', 'job_code', 'golongan_level', 'divisi', 'departemen', 'area',
        'atasan_langsung', 'bawahan_langsung', 'jumlah_bawahan', 'ringkasan_jabatan',
        'bagan_struktur_path', 'struktur_organisasi', 'tugas_pokok', 'tugas_tambahan',
        'output_pekerjaan', 'hak', 'kewajiban', 'wewenang', 'hubungan_kerja',
        'lingkungan_kerja', 'spesifikasi_pekerjaan', 'catatan_revisi', 'team_id',
        'pemilik_user_id', 'status', 'kata_kunci',
    ];

    protected $casts = [
        'struktur_organisasi' => 'array',
        'tugas_pokok' => 'array',
        'hubungan_kerja' => 'array',
        'spesifikasi_pekerjaan' => 'array',
        'catatan_revisi' => 'array',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_REVIEW => 'Dalam Review',
            self::STATUS_AKTIF => 'Aktif',
            self::STATUS_ARSIP => 'Arsip',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return [self::STATUS_DRAFT => 'bg-secondary', self::STATUS_REVIEW => 'bg-warning text-dark', self::STATUS_AKTIF => 'bg-success', self::STATUS_ARSIP => 'bg-dark'][$this->status] ?? 'bg-secondary';
    }

    public function team() { return $this->belongsTo(Team::class); }
    public function pemilik() { return $this->belongsTo(User::class, 'pemilik_user_id'); }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canAccessAllFiles()) return $query;
        $teamIds = $user->assignedTeamIds();
        return $query->where(function ($query) use ($user, $teamIds) {
            $query->where('pemilik_user_id', $user->id);
            if ($teamIds) $query->orWhereIn('team_id', $teamIds);
        });
    }

    public function scopeManageableBy($query, User $user)
    {
        return $user->canAccessAllFiles() ? $query : $query->where('pemilik_user_id', $user->id);
    }
}
