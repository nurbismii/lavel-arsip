<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_STAFF = 'staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF || $this->role === 'user';
    }

    public function canAccessAllFiles(): bool
    {
        return $this->isAdmin() || $this->isManager();
    }

    public function getRoleLabelAttribute(): string
    {
        return self::roleOptions()[$this->role] ?? ($this->role === 'user' ? 'Staff' : ucfirst((string) $this->role));
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_STAFF => 'Staff',
        ];
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function assignedTeamIds(): array
    {
        $teams = $this->relationLoaded('teams')
            ? $this->teams
            : $this->teams()->get(['teams.id']);

        return $teams
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function sopPengetahuans()
    {
        return $this->hasMany(SopPengetahuan::class, 'pemilik_user_id');
    }

    public function alurKerjaCadangans()
    {
        return $this->belongsToMany(AlurKerja::class, 'alur_kerja_pemilik_cadangan', 'user_id', 'alur_kerja_id')
            ->withTimestamps();
    }
}
