<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'subject_name',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActorNameAttribute(): string
    {
        return optional($this->user)->name ?: 'Sistem';
    }

    public function getActionLabelAttribute(): string
    {
        $labels = [
            'auth.login' => 'Login',
            'auth.logout' => 'Logout',
            'profile.update' => 'Profil',
            'alur_kerja.create' => 'Tambah Alur Kerja',
            'alur_kerja.update' => 'Ubah Alur Kerja',
            'alur_kerja.delete' => 'Hapus Alur Kerja',
            'alur_kerja.tahap.create' => 'Tambah Tahap Alur Kerja',
            'alur_kerja.tahap.update' => 'Ubah Tahap Alur Kerja',
            'alur_kerja.tahap.delete' => 'Hapus Tahap Alur Kerja',
            'alur_kerja.tahap.lampiran.delete' => 'Hapus Lampiran Tahap',
            'sop_pengetahuan.create' => 'Tambah SOP',
            'sop_pengetahuan.update' => 'Ubah SOP',
            'sop_pengetahuan.delete' => 'Hapus SOP',
            'sop_pengetahuan.lampiran.delete' => 'Hapus Lampiran SOP',
            'pekerjaan.create' => 'Tambah Pekerjaan',
            'pekerjaan.update' => 'Ubah Pekerjaan',
            'pekerjaan.delete' => 'Hapus Pekerjaan',
            'dokumen.delete' => 'Hapus Dokumen',
            'dokumen.status' => 'Status Dokumen',
            'lokasi.create' => 'Tambah Lokasi',
            'lokasi.update' => 'Ubah Lokasi',
            'lokasi.delete' => 'Hapus Lokasi',
            'team.create' => 'Tambah Tim',
            'team.update' => 'Ubah Tim',
            'team.delete' => 'Hapus Tim',
            'user.update' => 'Kelola User',
        ];

        return $labels[$this->action] ?? $this->action;
    }

    public function getActionBadgeClassAttribute(): string
    {
        $classes = [
            'auth.login' => 'bg-success',
            'auth.logout' => 'bg-secondary',
            'profile.update' => 'bg-primary',
            'alur_kerja.create' => 'bg-success',
            'alur_kerja.update' => 'bg-primary',
            'alur_kerja.delete' => 'bg-danger',
            'alur_kerja.tahap.create' => 'bg-success',
            'alur_kerja.tahap.update' => 'bg-primary',
            'alur_kerja.tahap.delete' => 'bg-danger',
            'alur_kerja.tahap.lampiran.delete' => 'bg-danger',
            'sop_pengetahuan.create' => 'bg-success',
            'sop_pengetahuan.update' => 'bg-primary',
            'sop_pengetahuan.delete' => 'bg-danger',
            'sop_pengetahuan.lampiran.delete' => 'bg-danger',
            'pekerjaan.create' => 'bg-success',
            'pekerjaan.update' => 'bg-primary',
            'pekerjaan.delete' => 'bg-danger',
            'dokumen.delete' => 'bg-danger',
            'dokumen.status' => 'bg-warning text-dark',
            'lokasi.create' => 'bg-success',
            'lokasi.update' => 'bg-primary',
            'lokasi.delete' => 'bg-danger',
            'team.create' => 'bg-success',
            'team.update' => 'bg-primary',
            'team.delete' => 'bg-danger',
            'user.update' => 'bg-dark',
        ];

        return $classes[$this->action] ?? 'bg-secondary';
    }

    public function getTanggalAktivitasAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M Y H:i') : '-';
    }
}
