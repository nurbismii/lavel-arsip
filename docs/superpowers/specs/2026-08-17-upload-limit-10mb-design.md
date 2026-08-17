# Desain Batas Upload Dokumen 10 MB

## Tujuan

Membatasi setiap file pada fitur kelola dokumen operasional menjadi maksimal 10 MB secara konsisten dan memberikan informasi batas tersebut dekat input upload.

## Cakupan

- Dokumen utama saat tambah.
- Dokumen sub saat tambah.
- Dokumen baru saat edit.
- Bukti penyelesaian saat status “Sudah Selesai”.

## Implementasi

Semua rule Laravel `max:20480` pada upload dokumen diganti menjadi `max:10240`. Nilai Laravel dihitung dalam kilobyte, sehingga `10240 KB` setara dengan `10 MB`.

Setiap input file terkait menampilkan bantuan “Maksimal 10 MB per file”. Konfigurasi PHP `upload_max_filesize=512M` dan `post_max_size=512M` tidak diubah karena limit aplikasi yang lebih kecil menjadi batas efektif.

## Validasi

- File berukuran tepat 10 MB diterima oleh rule ukuran file.
- File di atas 10 MB ditolak dengan error pada field terkait.
- Pemilihan banyak file tetap diperbolehkan; batas berlaku untuk setiap file, bukan total seluruh file.
- Alur R2, format file, dan jumlah file tidak diubah.

## Pengujian

Tambahkan feature test menggunakan file palsu untuk memastikan dokumen utama serta bukti penyelesaian di atas 10 MB ditolak sebelum proses penyimpanan. Jalankan seluruh test, kompilasi Blade, dan pemeriksaan diff.
