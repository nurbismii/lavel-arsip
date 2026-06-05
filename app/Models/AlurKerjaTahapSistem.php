<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlurKerjaTahapSistem extends Model
{
    protected $table = 'alur_kerja_tahap_sistem';

    protected $fillable = [
        'alur_kerja_tahap_id',
        'urutan',
        'nama_sistem',
        'fungsi',
        'akun',
        'url',
        'catatan',
    ];

    public function tahap()
    {
        return $this->belongsTo(AlurKerjaTahap::class, 'alur_kerja_tahap_id');
    }
}
