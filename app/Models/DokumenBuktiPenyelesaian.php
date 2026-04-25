<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DokumenBuktiPenyelesaian extends Model
{
    protected $table = 'dokumen_bukti_penyelesaian';

    protected $fillable = [
        'dokumen_id',
        'nama_file',
        'path',
    ];

    public function getStorageDiskAttribute(): string
    {
        if (!$this->path) {
            return 'local';
        }

        if (Storage::disk('local')->exists($this->path)) {
            return 'local';
        }

        if (!config('filesystems.disks.r2.bucket') || !config('filesystems.disks.r2.key') || !config('filesystems.disks.r2.secret') || !config('filesystems.disks.r2.endpoint')) {
            return 'local';
        }

        if (Storage::disk('r2')->exists($this->path)) {
            return 'r2';
        }

        return 'local';
    }

    public function getTanggalUploadAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M Y H:i') : '-';
    }

    public function dokumen()
    {
        return $this->belongsTo(Dokumen::class);
    }
}
