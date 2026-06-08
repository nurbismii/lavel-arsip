<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function pekerjaans()
    {
        return $this->hasMany(Pekerjaan::class);
    }

    public function sopPengetahuans()
    {
        return $this->hasMany(SopPengetahuan::class);
    }
}
