<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Dokumen extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_ARSIP = 'arsip';

    protected $table = 'dokumen';
    protected $fillable = ['pekerjaan_id', 'nama_file', 'path', 'status_dokumen'];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Dalam proses',
            self::STATUS_AKTIF => 'Sedang Digunakan',
            self::STATUS_ARSIP => 'Sudah Selesai',
        ];
    }

    public function getTanggalDisimpanAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M Y H:i') : '-';
    }

    public function getStatusDokumenLabelAttribute(): string
    {
        return static::statusOptions()[$this->status_dokumen] ?? static::statusOptions()[self::STATUS_DRAFT];
    }

    public function getStatusDokumenBadgeClassAttribute(): string
    {
        return [
            self::STATUS_DRAFT => 'bg-warning text-dark',
            self::STATUS_AKTIF => 'bg-primary text-white',
            self::STATUS_ARSIP => 'bg-success text-white',
        ][$this->status_dokumen] ?? 'bg-secondary text-white';
    }

    public function getUkuranFileAttribute(): string
    {
        $disk = $this->storage_disk;

        if (!$this->path || !Storage::disk($disk)->exists($this->path)) {
            return '-';
        }

        return $this->formatUkuranFile(Storage::disk($disk)->size($this->path));
    }

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

    private function formatUkuranFile(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, $unitIndex === 0 ? 0 : 2) . ' ' . $units[$unitIndex];
    }

    public function pekerjaan()
    {
        return $this->belongsTo(Pekerjaan::class);
    }
}
