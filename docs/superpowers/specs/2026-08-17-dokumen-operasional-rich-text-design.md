# Desain Rich Text Dokumen Operasional

## Tujuan

Menyediakan text editor yang konsisten untuk deskripsi/kronologi dokumen utama, deskripsi/kronologi subdokumen, dan keterangan penyelesaian tanpa mengubah alur penyimpanan dokumen yang sudah berjalan.

## Cakupan

- Deskripsi/kronologi dokumen utama pada halaman tambah dan edit menggunakan Quill.
- Deskripsi/kronologi setiap subdokumen yang ditambahkan secara dinamis menggunakan Quill.
- Keterangan penyelesaian menggunakan Quill.
- Keterangan penyelesaian hanya ditampilkan saat status dokumen `arsip` (label “Sudah Selesai”) dan wajib berisi teks bermakna.
- Bukti penyelesaian, peminjam dokumen, status lain, upload dokumen, serta hubungan pekerjaan tidak diubah.

## Pendekatan

Gunakan Quill 2.0.3 yang sudah tersedia di `public/vendor/quill`. Buat komponen rich text ringan dan reusable khusus dokumen operasional, bukan memakai editor Alur Kerja karena editor tersebut memiliki fitur SOP, kop, simbol, dan diagram yang tidak dibutuhkan.

Textarea asli tetap menjadi sumber nilai form dan disembunyikan secara visual setelah Quill berhasil dibuat. Setiap perubahan isi Quill disinkronkan ke textarea, termasuk sebelum form disubmit. Jika JavaScript atau Quill gagal dimuat, textarea tetap terlihat sehingga form masih dapat digunakan.

Toolbar dibatasi pada heading, bold, italic, underline, blockquote, ordered list, bullet list, link, dan clear formatting.

## Keamanan dan Penyimpanan

Konten disimpan sebagai HTML yang sudah disanitasi di server. Allowlist HTML:

- Elemen: `p`, `br`, `strong`, `b`, `em`, `i`, `u`, `blockquote`, `ol`, `ul`, `li`, `h2`, `h3`, dan `a`.
- Atribut: hanya `href`, `target`, dan `rel` pada tautan.
- Protokol tautan: `http`, `https`, dan `mailto`.
- Tautan eksternal dipaksa memakai `target="_blank"` serta `rel="noopener noreferrer"`.
- Elemen, atribut, event handler, URL JavaScript, dan markup lain di luar allowlist dihapus.

Data lama berupa teks biasa tetap valid. Sanitizer mengubah teks biasa menjadi paragraf aman ketika diperlukan dan tidak memerlukan migrasi database.

## Validasi

- Deskripsi dokumen utama dan subdokumen tetap opsional.
- Keterangan penyelesaian wajib ketika status `arsip`, baik di browser maupun server.
- Markup kosong seperti `<p><br></p>`, whitespace, dan HTML tanpa teks bermakna dianggap kosong.
- Batas keterangan penyelesaian dihitung berdasarkan teks hasil ekstraksi, maksimal 1.000 karakter, sehingga markup editor tidak menghabiskan batas karakter.
- Pesan validasi menjelaskan bahwa keterangan penyelesaian wajib diisi saat dokumen dinyatakan selesai.

## Perilaku UI

- Field penyelesaian langsung tampil ketika status berubah ke “Sudah Selesai”.
- Field disembunyikan kembali untuk status lain dan atribut wajib dilepas.
- Editor yang berada dalam konten tree hasil AJAX diinisialisasi setelah HTML dimasukkan ke halaman.
- Editor subdokumen diinisialisasi saat baris subdokumen dibuat dan instance dibersihkan saat baris dihapus.
- Tombol update status tetap mengikuti alur submit yang ada.
- Konten tersimpan ditampilkan sebagai HTML terformat yang sudah disanitasi, bukan sebagai tag mentah.

## Struktur Kode

- `app/Support/RichText.php`: sanitasi HTML, ekstraksi teks bermakna, dan pemeriksaan konten kosong.
- `app/Rules/MeaningfulRichText.php`: aturan validasi reusable untuk konten editor.
- `resources/views/pekerjaan/_rich_text_editor_styles.blade.php`: aset dan gaya Quill untuk dokumen operasional.
- `resources/views/pekerjaan/_rich_text_editor_script.blade.php`: inisialisasi, sinkronisasi textarea, dukungan field dinamis, dan validasi browser.
- View create, edit, index, dan tree-content memakai komponen tersebut.
- `PekerjaanController` melakukan validasi dan sanitasi sebelum menyimpan.

## Pengujian

- Store dan update menyimpan format yang diizinkan serta menghapus markup berbahaya.
- Store menyimpan rich text pada deskripsi subdokumen.
- Update status selesai menolak keterangan kosong secara semantik.
- Update status selesai menyimpan keterangan yang sudah disanitasi.
- Status selain selesai tidak mewajibkan keterangan penyelesaian.
- Tampilan tree merender rich text yang aman dan terformat.

## Risiko dan Mitigasi

- Merender HTML tanpa sanitasi dapat menimbulkan XSS; semua input disanitasi sebelum disimpan dan keluaran lama disanitasi lagi saat dirender.
- Field subdokumen dibuat dinamis; initializer dibuat idempotent agar editor tidak dibuat dua kali.
- Panjang HTML lebih besar dari teks; validasi batas karakter menggunakan teks hasil ekstraksi.
- Data lama tidak dimigrasikan; fallback teks biasa menjaga kompatibilitas.
