<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SopPengetahuanLampiran extends Model
{
    protected $table = 'sop_pengetahuan_lampiran';

    protected $fillable = [
        'sop_pengetahuan_id',
        'nama_file',
        'path',
        'storage_disk',
        'ukuran_file',
        'mime_type',
    ];

    public function sopPengetahuan()
    {
        return $this->belongsTo(SopPengetahuan::class);
    }

    public function getUkuranFileLabelAttribute(): string
    {
        if (!$this->ukuran_file) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->ukuran_file;
        $index = 0;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size = $size / 1024;
            $index++;
        }

        return ($index === 0 ? (int) $size : number_format($size, 2)) . ' ' . $units[$index];
    }

    public function getTanggalUploadAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M Y H:i') : '-';
    }
}
