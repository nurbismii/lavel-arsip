<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerjaan extends Model
{
    protected $table = 'pekerjaan';
    protected $fillable = [
        'judul',
        'parent_id',
        'user_id',
        'lokasi_id',
        'team_id',
        'alur_kerja_id',
        'tanggal_mulai_penyelesaian',
        'tanggal_target_penyelesaian',
    ];

    protected $casts = [
        'tanggal_mulai_penyelesaian' => 'date',
        'tanggal_target_penyelesaian' => 'date',
    ];

    public function getTanggalDibuatAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M Y H:i') : '-';
    }

    public function getRentangPenyelesaianAttribute(): string
    {
        if (!$this->tanggal_mulai_penyelesaian && !$this->tanggal_target_penyelesaian) {
            return '-';
        }

        $mulai = $this->tanggal_mulai_penyelesaian
            ? $this->tanggal_mulai_penyelesaian->format('d M Y')
            : '-';

        $target = $this->tanggal_target_penyelesaian
            ? $this->tanggal_target_penyelesaian->format('d M Y')
            : '-';

        return $mulai . ' s/d ' . $target;
    }

    public function getTanggalTargetPenyelesaianLabelAttribute(): string
    {
        return $this->tanggal_target_penyelesaian
            ? $this->tanggal_target_penyelesaian->format('d M Y')
            : '-';
    }

    public function getHariMenujuTargetAttribute(): ?int
    {
        if (!$this->tanggal_target_penyelesaian) {
            return null;
        }

        return now()->startOfDay()->diffInDays(
            $this->tanggal_target_penyelesaian->copy()->startOfDay(),
            false
        );
    }

    public function getStatusTargetPenyelesaianAttribute(): string
    {
        $hari = $this->hari_menuju_target;

        if ($hari === null) {
            return 'Belum ada target';
        }

        if ($hari < 0) {
            return 'Terlambat ' . abs($hari) . ' hari';
        }

        if ($hari === 0) {
            return 'Jatuh tempo hari ini';
        }

        return 'H-' . $hari;
    }

    // dokumen
    public function dokumens()
    {
        return $this->hasMany(Dokumen::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function alurKerja()
    {
        return $this->belongsTo(AlurKerja::class, 'alur_kerja_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // sub pekerjaan (level 1)
    public function subPekerjaans()
    {
        return $this->hasMany(Pekerjaan::class, 'parent_id');
    }

    // parent
    public function parent()
    {
        return $this->belongsTo(Pekerjaan::class, 'parent_id');
    }

    // recursive (ini yang kamu belum punya)
    public function childrenRecursive()
    {
        return $this->subPekerjaans()->with('childrenRecursive', 'dokumens', 'lokasi', 'team');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canAccessAllFiles()) {
            return $query;
        }

        if ($user->isSupervisor()) {
            $teamIds = $user->assignedTeamIds();

            return $query->where(function ($query) use ($user, $teamIds) {
                $query->where('user_id', $user->id);

                if (!empty($teamIds)) {
                    $query->orWhereIn('team_id', $teamIds);
                }
            });
        }

        return $query->where('user_id', $user->id);
    }

    public function scopeManageableBy($query, User $user)
    {
        if ($user->canAccessAllFiles()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
