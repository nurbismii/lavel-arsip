<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlurKerjaTahapPic extends Model
{
    protected $table = 'alur_kerja_tahap_pic';

    protected $fillable = [
        'alur_kerja_tahap_id',
        'urutan',
        'nama',
        'peran',
        'kontak',
        'waktu_dihubungi',
        'catatan',
    ];

    public function tahap()
    {
        return $this->belongsTo(AlurKerjaTahap::class, 'alur_kerja_tahap_id');
    }
}
