<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $table = 'lokasi_dokumen';
    protected $fillable = ['nama_lokasi'];

    public function pekerjaans()
    {
        return $this->hasMany(Pekerjaan::class, 'lokasi_id');
    }
}
