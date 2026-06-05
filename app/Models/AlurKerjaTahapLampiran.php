<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlurKerjaTahapLampiran extends Model
{
    protected $table = 'alur_kerja_tahap_lampiran';

    protected $fillable = [
        'alur_kerja_tahap_id',
        'nama_file',
        'path',
        'storage_disk',
        'ukuran_file',
        'mime_type',
    ];

    public function getUkuranFileLabelAttribute(): string
    {
        if (!$this->ukuran_file) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->ukuran_file;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, $unitIndex === 0 ? 0 : 2) . ' ' . $units[$unitIndex];
    }

    public function getTanggalUploadAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M Y H:i') : '-';
    }

    public function tahap()
    {
        return $this->belongsTo(AlurKerjaTahap::class, 'alur_kerja_tahap_id');
    }
}
