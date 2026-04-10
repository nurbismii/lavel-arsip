<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerjaan extends Model
{
    protected $table = 'pekerjaan';
    protected $fillable = ['judul', 'parent_id', 'user_id', 'lokasi_id', 'team_id'];

    public function getTanggalDibuatAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M Y H:i') : '-';
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
