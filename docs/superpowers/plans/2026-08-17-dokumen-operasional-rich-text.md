# Dokumen Operasional Rich Text Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan editor Quill yang aman dan konsisten untuk deskripsi dokumen utama, deskripsi subdokumen, dan keterangan penyelesaian yang wajib saat status “Sudah Selesai”.

**Architecture:** Textarea tetap menjadi field form agar kontrak request existing tidak berubah, sementara partial JavaScript reusable membuat Quill dan menyinkronkan HTML ke textarea. `App\Support\RichText` menjadi batas keamanan server untuk sanitasi serta ekstraksi teks, sedangkan `MeaningfulRichText` memvalidasi bahwa keterangan wajib benar-benar berisi teks.

**Tech Stack:** Laravel 8.75, PHP 7.3/8.x compatible syntax, Blade, Bootstrap existing, Quill 2.0.3 lokal, PHPUnit 9.5.

## Global Constraints

- Jangan menambah package atau CDN baru; gunakan `public/vendor/quill`.
- Deskripsi utama dan subdokumen tetap opsional.
- Keterangan penyelesaian wajib hanya untuk status `arsip` dan maksimal 1.000 karakter teks.
- HTML harus disanitasi di server sebelum disimpan dan sebelum data lama dirender.
- Alur upload, bukti penyelesaian, peminjam, authorization, dan status existing tidak boleh berubah.
- Initializer editor wajib idempotent dan mendukung konten subdokumen serta tree yang dibuat dinamis.

---

### Task 1: Rich text security boundary

**Files:**
- Create: `app/Support/RichText.php`
- Create: `app/Rules/MeaningfulRichText.php`
- Create: `tests/Unit/RichTextTest.php`
- Create: `tests/Unit/MeaningfulRichTextTest.php`

**Interfaces:**
- Produces: `RichText::sanitize(?string $html): ?string`, `RichText::plainText(?string $html): string`, `RichText::hasMeaningfulText(?string $html): bool`, dan rule `new MeaningfulRichText(1000)`.

- [ ] **Step 1: Write failing sanitizer tests**

Test `RichText` dengan input format Quill yang diizinkan, `<script>`, atribut event, URL `javascript:`, teks biasa, markup kosong, dan entity HTML. Pastikan format aman dipertahankan, payload berbahaya hilang, teks biasa tidak rusak, dan `hasMeaningfulText('<p><br></p>')` bernilai false.

- [ ] **Step 2: Run sanitizer tests and verify RED**

Run: `php artisan test tests/Unit/RichTextTest.php`

Expected: FAIL karena class `App\Support\RichText` belum tersedia.

- [ ] **Step 3: Implement the sanitizer**

Implementasikan allowlist elemen `p`, `br`, `strong`, `b`, `em`, `i`, `u`, `blockquote`, `ol`, `ul`, `li`, `h2`, `h3`, dan `a` menggunakan `DOMDocument`. Hapus node aktif seperti `script`, `style`, `iframe`, `object`, dan `embed` beserta isinya; unwrap elemen lain yang tidak diizinkan; hapus semua atribut selain atribut tautan yang diizinkan; tolak protokol selain `http`, `https`, dan `mailto`; serta normalkan tautan menjadi `target="_blank" rel="noopener noreferrer"`.

`plainText()` harus menjalankan `html_entity_decode(strip_tags(...))`, mengganti non-breaking space, merapikan whitespace, lalu `trim`. `sanitize()` mengembalikan `null` untuk input null/kosong secara semantik.

- [ ] **Step 4: Run sanitizer tests and verify GREEN**

Run: `php artisan test tests/Unit/RichTextTest.php`

Expected: PASS.

- [ ] **Step 5: Write failing validation rule tests**

Uji bahwa `MeaningfulRichText(1000)` menolak `<p><br></p>`, menerima `<p>Pekerjaan selesai</p>`, dan menolak teks hasil ekstraksi sepanjang 1.001 karakter dengan pesan Bahasa Indonesia yang spesifik.

- [ ] **Step 6: Run rule tests and verify RED**

Run: `php artisan test tests/Unit/MeaningfulRichTextTest.php`

Expected: FAIL karena rule belum tersedia.

- [ ] **Step 7: Implement the validation rule**

Implementasikan kontrak `Illuminate\Contracts\Validation\Rule`. Method `passes($attribute, $value)` harus memanggil `RichText::plainText` dan menerima hanya panjang 1–1.000 karakter menggunakan `mb_strlen`; `message()` mengembalikan pesan wajib/limit yang mudah dipahami.

- [ ] **Step 8: Run unit tests**

Run: `php artisan test tests/Unit/RichTextTest.php tests/Unit/MeaningfulRichTextTest.php`

Expected: seluruh test PASS.

- [ ] **Step 9: Commit security boundary**

```bash
git add app/Support/RichText.php app/Rules/MeaningfulRichText.php tests/Unit/RichTextTest.php tests/Unit/MeaningfulRichTextTest.php
git commit -m "feat: add safe rich text support"
```

### Task 2: Controller validation and persistence

**Files:**
- Modify: `app/Http/Controllers/PekerjaanController.php`
- Modify: `tests/Unit/RichTextTest.php`

**Interfaces:**
- Consumes: `RichText::sanitize()` dan `MeaningfulRichText` dari Task 1.
- Produces: semua nilai `deskripsi`, `sub_deskripsi.*`, dan `keterangan_penyelesaian` yang disimpan sudah tersanitasi.

- [ ] **Step 1: Extend failing tests for persisted-input cases**

Tambahkan data provider ke `RichTextTest` untuk payload controller: deskripsi dengan heading/list/link aman, deskripsi subdokumen dengan event handler, dan keterangan penyelesaian dengan script. Expected value harus menunjukkan format aman bertahan dan payload aktif hilang.

- [ ] **Step 2: Run focused tests and verify RED**

Run: `php artisan test tests/Unit/RichTextTest.php`

Expected: minimal satu kasus baru FAIL terhadap keluaran sanitasi yang dibutuhkan controller.

- [ ] **Step 3: Integrate validation and sanitization in controller**

Tambahkan import:

```php
use App\Rules\MeaningfulRichText;
use App\Support\RichText;
```

Pada `store()` dan `update()`, sanitasi `deskripsi` setelah validasi dan sebelum `Pekerjaan::create/update`. Pada loop subdokumen, sanitasi setiap `sub_deskripsi[$i]`. Pada `updateStatusDokumen()`, gunakan rules `['required', new MeaningfulRichText(1000)]` untuk status `arsip` dan `['nullable', new MeaningfulRichText(1000)]` untuk status lain, kemudian sanitasi sebelum memasukkan nilai ke `$updates`.

Jangan mengosongkan `keterangan_penyelesaian` existing ketika status berubah ke selain `arsip`; hanya `diselesaikan_pada`, peminjam, dan tanggal pinjam yang tetap mengikuti behavior existing.

- [ ] **Step 4: Run unit and syntax tests**

Run: `php artisan test tests/Unit/RichTextTest.php tests/Unit/MeaningfulRichTextTest.php`

Run: `php -l app/Http/Controllers/PekerjaanController.php`

Expected: seluruh test PASS dan output `No syntax errors detected`.

- [ ] **Step 5: Commit controller integration**

```bash
git add app/Http/Controllers/PekerjaanController.php tests/Unit/RichTextTest.php
git commit -m "feat: validate document rich text"
```

### Task 3: Reusable Quill editor UI

**Files:**
- Create: `resources/views/pekerjaan/_rich_text_editor_styles.blade.php`
- Create: `resources/views/pekerjaan/_rich_text_editor_script.blade.php`
- Modify: `resources/views/pekerjaan/create.blade.php`
- Modify: `resources/views/pekerjaan/edit.blade.php`
- Modify: `resources/views/pekerjaan/index.blade.php`
- Modify: `resources/views/pekerjaan/partials/tree-content.blade.php`
- Modify: `resources/views/pekerjaan/partials/tree-item.blade.php`

**Interfaces:**
- Produces: global `window.PekerjaanRichText.init(container)` dan `window.PekerjaanRichText.destroy(container)`.
- Consumes: textarea dengan atribut `data-rich-text`, opsional `data-rich-text-required`, `data-rich-text-maxlength`, dan container dinamis dari create/index.

- [ ] **Step 1: Add editor styles with no-JS fallback**

Muat `vendor/quill/quill.snow.css`. Style wrapper, toolbar, tinggi minimum, focus ring, validation state, dan output `.rich-text-content`. Textarea hanya disembunyikan oleh JavaScript setelah instance Quill berhasil dibuat, bukan melalui CSS global.

- [ ] **Step 2: Implement idempotent editor initializer**

Muat `vendor/quill/quill.js`. Untuk setiap `textarea[data-rich-text]`, buat elemen editor setelah textarea, isi dari nilai textarea melalui clipboard Quill, lalu sinkronkan `quill.root.innerHTML` pada event `text-change` dan `submit`. Simpan instance pada elemen menggunakan property privat agar pemanggilan `init()` berulang tidak menggandakan editor.

Validasi browser memakai `quill.getText().trim()`: jika field bertanda wajib tetapi kosong, set custom validity dan fokuskan editor. Terapkan batas karakter terhadap plain text dan tampilkan `.invalid-feedback` kontekstual. Expose `init` dan `destroy` melalui `window.PekerjaanRichText`.

- [ ] **Step 3: Convert main and dynamic sub descriptions**

Pada create/edit, tambahkan `data-rich-text` pada textarea deskripsi utama dan include partial styles/scripts. Template subdokumen harus memberi `data-rich-text` pada `sub_deskripsi[index]`, lalu memanggil `PekerjaanRichText.init(newSubElement)` setelah `insertAdjacentHTML`. Sebelum menghapus subdokumen, panggil `destroy` pada elemennya.

- [ ] **Step 4: Convert completion note and status behavior**

Pada `tree-content`, ganti textarea keterangan menjadi textarea rich text dengan `data-rich-text`, `data-rich-text-required`, dan `data-rich-text-maxlength="1000"`. Atribut required native tetap disinkronkan oleh `syncCompletionFields` saat status berubah.

Setelah `loadTreeContent()` memasukkan HTML AJAX, panggil `PekerjaanRichText.init(collapseElement)`. Pastikan init juga dipanggil untuk tree yang dirender sejak awal dan field tersembunyi dapat diinisialisasi tanpa ukuran toolbar rusak ketika kemudian ditampilkan.

- [ ] **Step 5: Render sanitized formatted output**

Pada `tree-item` dan `tree-content`, render deskripsi/keterangan menggunakan `RichText::sanitize()` lalu `{!! !!}` di dalam wrapper `.rich-text-content`. Jangan merender nilai database mentah secara langsung.

- [ ] **Step 6: Validate Blade compilation and PHP syntax**

Run: `php artisan view:clear`

Run: `php artisan view:cache`

Run: `php -l app/Support/RichText.php`

Expected: Blade cache dibuat tanpa exception dan tidak ada syntax error.

- [ ] **Step 7: Commit editor UI**

```bash
git add resources/views/pekerjaan/_rich_text_editor_styles.blade.php resources/views/pekerjaan/_rich_text_editor_script.blade.php resources/views/pekerjaan/create.blade.php resources/views/pekerjaan/edit.blade.php resources/views/pekerjaan/index.blade.php resources/views/pekerjaan/partials/tree-content.blade.php resources/views/pekerjaan/partials/tree-item.blade.php
git commit -m "feat: add document rich text editors"
```

### Task 4: Final verification

**Files:**
- Verify only; no expected source changes.

**Interfaces:**
- Consumes: seluruh deliverable Task 1–3.
- Produces: bukti bahwa feature aman untuk dihandoff.

- [ ] **Step 1: Run focused automated tests**

Run: `php artisan test tests/Unit/RichTextTest.php tests/Unit/MeaningfulRichTextTest.php`

Expected: seluruh test PASS.

- [ ] **Step 2: Run project-safe verification**

Run: `php artisan route:list --name=pekerjaan`

Run: `php artisan view:cache`

Run: `git diff --check`

Expected: route pekerjaan tersedia, Blade terkompilasi, dan tidak ada whitespace error.

- [ ] **Step 3: Manual browser checklist**

- Tambah dokumen: format deskripsi utama tersimpan dan tampil kembali.
- Tambah dua subdokumen: masing-masing editor independen dan nilai tidak tertukar.
- Edit dokumen: data lama plain text dan rich text sama-sama tampil.
- Ubah status ke “Sudah Selesai”: field keterangan tampil dan submit kosong ditolak.
- Isi keterangan serta bukti: submit sukses dan format tampil pada tree.
- Ubah status ke status lain: field penyelesaian tersembunyi dan tidak menghalangi submit.
- Uji lebar 375px dan desktop: toolbar dapat membungkus tanpa overflow halaman.
- Tempel payload `<img src=x onerror=alert(1)>`: script tidak berjalan dan payload tidak tersimpan.

- [ ] **Step 4: Inspect repository status**

Run: `git status --short`

Expected: hanya perubahan user yang sudah ada sebelumnya (jika ada); jangan memasukkan `resources/views/_partials/navbar.blade.php` ke commit feature.
