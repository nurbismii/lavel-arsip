# Upload Limit 10 MB Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membatasi setiap file dokumen operasional dan bukti penyelesaian menjadi maksimal 10 MB.

**Architecture:** Pertahankan validasi inline existing di `PekerjaanController`, tetapi gunakan satu konstanta ukuran agar seluruh endpoint konsisten. View hanya menampilkan bantuan ukuran dan tidak menjadi batas keamanan utama.

**Tech Stack:** Laravel 8.75, PHP 7.4, Blade, PHPUnit 9.5.

## Global Constraints

- Batas berlaku per file sebesar tepat 10.240 KB.
- Jangan mengubah konfigurasi PHP, R2, format file, atau jumlah file.
- Jangan menyertakan perubahan navbar existing.

---

### Task 1: Automated upload-limit coverage

**Files:**
- Modify: `tests/Feature/PekerjaanRichTextControllerTest.php`

- [ ] Tambahkan test dokumen utama 10.241 KB ditolak pada `dokumen.0`.
- [ ] Tambahkan test bukti penyelesaian 10.241 KB ditolak pada `bukti_penyelesaian.0`.
- [ ] Jalankan `php artisan test tests/Feature/PekerjaanRichTextControllerTest.php` dan pastikan kedua test baru gagal karena batas lama masih 20 MB.

### Task 2: Server validation and UI copy

**Files:**
- Modify: `app/Http/Controllers/PekerjaanController.php`
- Modify: `resources/views/pekerjaan/create.blade.php`
- Modify: `resources/views/pekerjaan/edit.blade.php`
- Modify: `resources/views/pekerjaan/partials/tree-content.blade.php`

- [ ] Tambahkan konstanta controller `MAX_UPLOAD_SIZE_KB = 10240`.
- [ ] Ganti empat rule upload agar memakai konstanta tersebut.
- [ ] Tambahkan bantuan “Maksimal 10 MB per file” pada seluruh input terkait.
- [ ] Jalankan focused test dan pastikan lulus.
- [ ] Jalankan seluruh test, `php artisan view:cache`, dan `git diff --check`.
