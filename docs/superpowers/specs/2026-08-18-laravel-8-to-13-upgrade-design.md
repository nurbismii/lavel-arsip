# Desain Upgrade Laravel 8 ke Laravel 13

**Tanggal:** 18 Agustus 2026  
**Status:** Disetujui untuk penyusunan implementation plan  
**Target akhir:** Laravel 13 pada PHP 8.5

## 1. Latar Belakang

Aplikasi Arsipin saat ini berjalan di production menggunakan Laravel 8.83.29 dan PHP 7.4.33. Upgrade diperlukan karena Laravel 8 dan PHP 7.4 sudah tidak menerima dukungan keamanan. Target akhir adalah Laravel 13 dengan PHP 8.5, tanpa mengganggu aplikasi production selama proses pengembangan.

Audit awal menghasilkan kondisi berikut:

- Composer memakai PHP 7.4 dari `C:\xampp\php\php.exe`.
- PHP 8.5.9 tersedia di `C:\xampp\php85\php.exe`.
- Baseline Laravel 8 memiliki 18 automated test yang lulus pada PHP 7.4.
- PHP 8.5 dapat memuat Laravel 8, tetapi menghasilkan banyak peringatan deprecation.
- Tujuh feature test gagal pada PHP 8.5 karena extension `pdo_sqlite` dan `sqlite3` belum aktif.
- Fresh migration pada SQLite berhenti pada dua migration `ALTER TABLE ... MODIFY` khusus MySQL, walaupun migration pembuat tabelnya sudah mendefinisikan kedua kolom sebagai nullable. Keduanya perlu dibuat no-op pada SQLite tanpa mengubah perilaku MariaDB.
- Route `/` dan `/home` menggunakan nama `home` yang sama sehingga `php artisan route:cache` gagal pada baseline. Nama `home` perlu dipertahankan hanya untuk `/home`; route `/` tetap tersedia tanpa nama.
- Project memiliki 18 controller, 15 model, 29 migration, 55 Blade view, dan menggunakan Laravel Mix.
- Dependency yang memerlukan perhatian khusus meliputi `fruitcake/laravel-cors`, `facade/ignition`, `laravel/sanctum`, `laravel/ui`, Flysystem AWS v1, Google API Client 2.15, Collision, dan PHPUnit 9.
- Aplikasi menggunakan local storage, Cloudflare R2/S3, Google Drive, autentikasi, role-based access, dan activity log.

## 2. Tujuan

1. Menghasilkan aplikasi Laravel 13 yang berjalan pada PHP 8.5.
2. Menjaga perilaku bisnis, route, autentikasi, authorization, upload, dan storage yang sudah ada.
3. Menjaga folder, database, file, dan layanan production tetap terisolasi selama upgrade.
4. Menyediakan checkpoint yang dapat diaudit dan di-rollback pada setiap major version.
5. Memastikan schema dapat dibangun dari nol dan alur utama tervalidasi sebelum deployment.

## 3. Di Luar Cakupan

- Migrasi Laravel Mix ke Vite, kecuali Laravel Mix terbukti tidak dapat digunakan pada target akhir.
- Refactor UI/UX atau perubahan fitur bisnis.
- Perubahan skeleton aplikasi ke struktur minimal Laravel 11–13.
- Penyalinan data atau dokumen production ke staging.
- Upgrade MariaDB, kecuali ditemukan masalah kompatibilitas yang dapat dibuktikan.

## 4. Pendekatan yang Dipilih

Upgrade dikerjakan pada Git worktree terpisah di bawah `.worktrees/upgrade-laravel13`. Folder production `C:\xampp\htdocs\lavel-arsipin` tidak digunakan sebagai tempat perubahan dependency atau pengujian upgrade.

Setiap major version ditangani secara berurutan:

1. Laravel 8 ke Laravel 9.
2. Laravel 9 ke Laravel 10.
3. Laravel 10 ke Laravel 11.
4. Laravel 11 ke Laravel 12.
5. Laravel 12 ke Laravel 13.

Setiap tahap menghasilkan commit/checkpoint tersendiri. Upgrade tahap berikutnya hanya dimulai setelah quality gate tahap berjalan lulus.

Pendekatan upgrade langsung Laravel 8 ke 13 ditolak karena menyulitkan isolasi root cause dependency dan breaking change. Penyalinan folder manual juga tidak digunakan karena berisiko membawa `.env`, cache, session, log, storage, dan dependency production.

## 5. Runtime PHP

Runtime digunakan secara eksplisit agar tidak bergantung pada nilai `PATH` global:

| Tahap | Runtime |
| --- | --- |
| Baseline Laravel 8 | PHP 7.4.33 (`C:\xampp\php\php.exe`) |
| Laravel 9–12 | PHP 8.2 portable |
| Laravel 13 dan target akhir | PHP 8.5.9 (`C:\xampp\php85\php.exe`) |

PHP 8.2 portable diperlukan karena Laravel 9 secara resmi mendukung PHP 8.0–8.2. PHP 8.5 hanya digunakan setelah dependency aplikasi mendukungnya. Composer stable terbaru disimpan di `.tools/composer.phar` dalam worktree dan dijalankan melalui executable PHP checkpoint. Pendekatan ini menghindari perubahan Composer global serta menghindari deprecation Composer 2.7 pada PHP 8.5.

`pdo_sqlite` dan `sqlite3` harus aktif pada runtime yang menjalankan automated test. Extension wajib Laravel dan driver MariaDB juga diverifikasi sebelum dependency diubah.

## 6. Isolasi Environment dan Data

Worktree memiliki `.env` staging yang tidak disimpan ke Git dan tidak menyalin `.env` production secara mentah. Konfigurasi berikut wajib berbeda dari production:

- `APP_NAME`, `APP_ENV`, `APP_URL`, dan `APP_DEBUG`.
- Nama database staging.
- Session cookie, cache prefix, dan Redis prefix jika Redis digunakan.
- Direktori storage dan log.
- Kredensial atau endpoint layanan eksternal.

Database staging dibuat kosong dengan nama khusus yang berakhiran `_staging`. Schema dibangun melalui `php artisan migrate:fresh`. Tidak ada data atau file `storage/app` production yang disalin.

Google Drive, R2/S3, email, dan notifikasi dinonaktifkan atau di-fake selama automated test. Upload dan penghapusan file hanya memakai storage staging/fake. Migration dan command berisiko hanya boleh dijalankan setelah koneksi database aktif diverifikasi menunjuk database staging.

`DatabaseSeeder` saat ini kosong. Automated test tetap membangun data melalui factory dan fixture. Untuk smoke test UI, akun lokal staging dibuat tanpa password yang di-hardcode atau di-commit.

## 7. Tahapan Upgrade

### 7.1 Persiapan Baseline

- Membuat worktree dan branch upgrade.
- Menyiapkan PHP 8.2 portable dan konfigurasi PHP testing.
- Menyiapkan database staging kosong.
- Menjalankan baseline test Laravel 8 pada PHP 7.4.
- Menambah test yang diperlukan untuk route, autentikasi, authorization, dan alur bisnis kritis sebelum dependency diubah.
- Mencatat hasil `composer validate`, route list, migration status, dan build frontend.

### 7.2 Laravel 8 ke Laravel 9

- Memperbarui constraint PHP dan Laravel Framework.
- Mengganti `facade/ignition` dengan paket yang kompatibel.
- Menghapus `fruitcake/laravel-cors` setelah middleware CORS bawaan framework diterapkan.
- Memigrasikan Flysystem dan adapter S3 dari v1 ke v3.
- Memperbarui Sanctum, Laravel UI, Collision, dan dependency terkait.
- Memeriksa perubahan Symfony Mailer, filesystem, route, dan model.

### 7.3 Laravel 9 ke Laravel 10

- Memperbarui framework dan dependency first-party/dev ke major yang kompatibel.
- Menyesuaikan signature method, Monolog, validation, database expressions, dan deprecated APIs yang digunakan aplikasi.
- Memvalidasi autentikasi, route model binding, upload, local storage, dan fake cloud storage.

### 7.4 Laravel 10 ke Laravel 11

- Memperbarui framework dan testing tools dengan PHP 8.2.
- Mempertahankan struktur `Kernel`, service provider, dan exception handler aplikasi yang ada.
- Menyesuaikan breaking change yang benar-benar menyentuh aplikasi tanpa memindahkan aplikasi ke skeleton baru.
- Memvalidasi middleware custom `check.login` dan `admin` serta seluruh role aplikasi.

### 7.5 Laravel 11 ke Laravel 12

- Memperbarui Laravel Framework dan Carbon 3.
- Memeriksa perubahan UUID, filesystem local root, nested request merge, image validation, dan container resolution.
- Memperbarui PHPUnit/dependency dev sesuai panduan resmi.

### 7.6 Laravel 12 ke Laravel 13

- Beralih ke PHP 8.5.9.
- Memperbarui Laravel Framework ke `^13.0`, Tinker ke major yang kompatibel, dan PHPUnit ke versi target Laravel 13.
- Memeriksa request forgery protection, cache/session prefix, MySQL/MariaDB `upsert`, joined delete, pagination view, model serialization, dan konflik helper PHP 8.5.
- Menghilangkan seluruh deprecation yang berasal dari dependency atau kode aplikasi pada runtime target.

Referensi utama adalah panduan resmi Laravel untuk setiap lompatan major:

- <https://laravel.com/docs/9.x/upgrade>
- <https://laravel.com/docs/10.x/upgrade>
- <https://laravel.com/docs/11.x/upgrade>
- <https://laravel.com/docs/12.x/upgrade>
- <https://laravel.com/docs/13.x/upgrade>

## 8. Quality Gate per Checkpoint

Sebuah checkpoint hanya dinyatakan lulus jika:

1. Dependency dapat di-resolve tanpa `--ignore-platform-reqs`.
2. `composer validate` lulus dan `composer audit` tidak menemukan kerentanan yang belum ditangani.
3. `php artisan migrate:fresh` berhasil pada database staging.
4. Seluruh automated test lulus pada runtime checkpoint.
5. `route:list`, `config:cache`, `route:cache`, `event:cache`, dan `view:cache` berhasil, sejauh command tersedia pada versi tersebut.
6. Build production Laravel Mix berhasil.
7. Tidak ada error atau deprecation pada target runtime checkpoint.
8. Smoke test authorization berhasil untuk admin, manager, supervisor, dan staff.
9. Alur login, pekerjaan, dokumen, upload, status penyelesaian, alur kerja, SOP, job description, lokasi dokumen, user management, dan activity log berhasil.
10. Integrasi eksternal tidak mengirim request atau notifikasi nyata selama test.

Kegagalan pada suatu tahap diperbaiki pada tahap tersebut. Constraint major berikutnya tidak boleh diterapkan untuk menutupi kegagalan checkpoint sebelumnya.

## 9. Error Handling dan Observability

- Output Composer, test, migration, dan build disimpan atau diringkas pada catatan checkpoint.
- Error dikelompokkan menjadi platform/runtime, dependency resolution, breaking change framework, database/migration, aplikasi, frontend, dan integrasi.
- Secret tidak boleh dicetak pada log atau command output.
- `APP_DEBUG` hanya aktif pada staging lokal dan wajib mati pada production.
- Cache selalu dibersihkan sebelum diagnosis agar error konfigurasi lama tidak disalahartikan sebagai regresi kode.

## 10. Rollback

Checkpoint konseptual yang dipertahankan:

- `baseline-laravel-8`
- `upgrade-laravel-9`
- `upgrade-laravel-10`
- `upgrade-laravel-11`
- `upgrade-laravel-12`
- `upgrade-laravel-13`

Rollback selama pengembangan dilakukan dengan kembali ke commit terakhir yang lulus, tanpa mengubah folder production. Tidak ada destructive reset terhadap worktree utama.

Untuk deployment production, backup kode, database, dan storage dibuat sebelum maintenance. Jika deployment gagal, kode dan runtime dikembalikan ke Laravel 8/PHP 7.4, dan database dipulihkan hanya jika migration deployment telah mengubah schema atau data.

## 11. Deployment Production

1. Menjalankan audit read-only terhadap schema dan pola data production untuk mendeteksi nilai lama yang tidak terwakili oleh database staging kosong.
2. Menetapkan maintenance window dan prosedur rollback.
3. Membuat backup kode, database, dan storage serta memverifikasi backup dapat dibaca.
4. Mengaktifkan maintenance mode.
5. Men-deploy kode dan lock file Laravel 13.
6. Menginstal dependency production menggunakan PHP 8.5.
7. Memindahkan runtime Apache ke PHP 8.5 dan memverifikasi extension.
8. Menjalankan migration, cache rebuild, dan smoke test production.
9. Membuka maintenance mode setelah health check lulus.
10. Memantau log, login, error rate, upload, download, dan alur utama selama periode observasi.

Deployment tidak diklaim zero-downtime karena perubahan runtime PHP dan lima major version framework memerlukan validasi setelah cutover.

## 12. Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| PHP 8.5 tidak kompatibel dengan dependency lama | Gunakan PHP 8.2 untuk Laravel 9–12 dan update dependency bertahap. |
| Database staging kosong tidak mewakili data lama | Jalankan audit read-only production sebelum deployment dan backup penuh. |
| R2/Google Drive terpicu saat test | Nonaktifkan kredensial eksternal dan gunakan fake storage/client. |
| Perubahan Flysystem merusak upload/download | Tambah test local dan fake S3/R2 pada checkpoint Laravel 9. |
| Perubahan auth/middleware merusak hak akses | Tambah matrix test empat role dan route terproteksi. |
| Composer menyelesaikan dependency secara semu | Larang `--ignore-platform-reqs` dan validasi dengan runtime checkpoint. |
| Laravel Mix menjadi tidak kompatibel | Pertahankan Mix selama masih lulus build; migrasi Vite menjadi pekerjaan terpisah jika diperlukan. |
| Rollback database tidak aman | Backup terverifikasi dan review migration sebelum production. |

## 13. Kriteria Selesai

Pekerjaan dinyatakan selesai ketika:

- Aplikasi melaporkan Laravel 13 dan berjalan pada PHP 8.5.9.
- Semua quality gate final lulus.
- Database dapat dibangun dari nol melalui migration.
- Tidak ada koneksi staging ke database, storage, email, atau integrasi production.
- Alur dan authorization kritis lulus automated test dan smoke test.
- Build frontend production berhasil.
- Prosedur deployment dan rollback production telah diverifikasi.
- Tidak ada secret atau file production yang masuk ke Git.
