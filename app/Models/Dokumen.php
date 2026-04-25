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
    protected $fillable = [
        'pekerjaan_id',
        'nama_file',
        'path',
        'status_dokumen',
        'peminjam_user_id',
        'dipinjam_pada',
        'bukti_penyelesaian_nama_file',
        'bukti_penyelesaian_path',
        'keterangan_penyelesaian',
        'diselesaikan_pada',
    ];

    protected $casts = [
        'dipinjam_pada' => 'datetime',
        'diselesaikan_pada' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Dalam proses',
            self::STATUS_AKTIF => 'Sedang Digunakan',
            self::STATUS_ARSIP => 'Sudah Selesai',
        ];
    }

    public static function statusOptionsForInput(): array
    {
        return [
            self::STATUS_DRAFT => static::statusOptions()[self::STATUS_DRAFT],
            self::STATUS_AKTIF => static::statusOptions()[self::STATUS_AKTIF],
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
        return $this->resolveStorageDisk($this->path);
    }

    public function getBuktiPenyelesaianStorageDiskAttribute(): string
    {
        return $this->resolveStorageDisk($this->bukti_penyelesaian_path);
    }

    public function getTanggalDiselesaikanAttribute(): string
    {
        return $this->diselesaikan_pada ? $this->diselesaikan_pada->format('d M Y H:i') : '-';
    }

    public function getTanggalDipinjamAttribute(): string
    {
        return $this->dipinjam_pada ? $this->dipinjam_pada->format('d M Y H:i') : '-';
    }

    private function resolveStorageDisk(?string $path): string
    {
        if (!$path) {
            return 'local';
        }

        if (Storage::disk('local')->exists($path)) {
            return 'local';
        }

        if (!config('filesystems.disks.r2.bucket') || !config('filesystems.disks.r2.key') || !config('filesystems.disks.r2.secret') || !config('filesystems.disks.r2.endpoint')) {
            return 'local';
        }

        if (Storage::disk('r2')->exists($path)) {
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

    public function peminjam()
    {
        return $this->belongsTo(User::class, 'peminjam_user_id');
    }

    public function buktiPenyelesaians()
    {
        return $this->hasMany(DokumenBuktiPenyelesaian::class)->orderBy('id');
    }
}
