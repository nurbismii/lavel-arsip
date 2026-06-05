<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlurKerjaTahap extends Model
{
    protected $table = 'alur_kerja_tahap';

    protected $fillable = [
        'alur_kerja_id',
        'urutan',
        'nama',
        'deskripsi',
        'aplikasi_digunakan',
        'akun_digunakan',
        'pic_terkait',
        'kontak_pic',
        'catatan',
    ];

    public function alurKerja()
    {
        return $this->belongsTo(AlurKerja::class);
    }

    public function lampirans()
    {
        return $this->hasMany(AlurKerjaTahapLampiran::class)->orderBy('id');
    }

    public function sistems()
    {
        return $this->hasMany(AlurKerjaTahapSistem::class)->orderBy('urutan')->orderBy('id');
    }

    public function pics()
    {
        return $this->hasMany(AlurKerjaTahapPic::class)->orderBy('urutan')->orderBy('id');
    }
}
