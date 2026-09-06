<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uraian Jabatan - {{ $jobdesc->jabatan }}{{ $jobdesc->job_code ? ' - '.$jobdesc->job_code : '' }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f5; color: #000; font: 12pt/1.15 'Times New Roman', serif; }
        .toolbar { max-width: 210mm; margin: 20px auto; padding: 16px; background: #fff; border: 1px solid #d8dee9; border-radius: 12px; font: 14px/1.5 Arial, sans-serif; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .toolbar a, .toolbar button { display: inline-block; padding: 12px 16px; border: 1px solid #2563eb; border-radius: 8px; font: inherit; text-decoration: none; cursor: pointer; color: #1d4ed8; background: white; }
        .toolbar button { background: #2563eb; color: white; }
        .toolbar button:disabled { opacity: .65; cursor: wait; }
        .toolbar button:hover { background: #1d4ed8; }
        :focus-visible { outline: 3px solid #f59e0b; outline-offset: 3px; }
        .toolbar p { margin: 10px 0 0; }
        .preview { overflow-x: auto; padding: 0 12px 24px; }
        .paper { width: 210mm; margin: auto; padding: 10mm; background: white; box-shadow: 0 4px 20px #0001; }
        table { border-collapse: collapse; width: calc(100% - 2px); table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 5px 7px; vertical-align: top; overflow-wrap: anywhere; }
        th { font-weight: bold; text-align: center; }
        thead { display: table-header-group; }
        .masthead { margin-bottom: 5mm; font-size: 10pt; }
        .masthead td { vertical-align: middle; }
        .masthead .title { width: 60%; text-align: center; }
        .masthead h1 { font-size: 15pt; margin: 5px 0; }
        .masthead p { margin: 4px 0; }
        h2 { font-size: 12pt; margin: 5mm 0 2mm; break-after: avoid; }
        h3, h4 { font-size: 12pt; margin: 3mm 0 2mm; break-after: avoid; }
        p { margin: 0 0 2mm; orphans: 3; widows: 3; overflow-wrap: anywhere; }
        .multiline { white-space: pre-line; overflow-wrap: anywhere; }
        .label { width: 30%; font-weight: bold; }
        .center { text-align: center; }
        .chart { display: block; max-width: 100%; max-height: 85mm; margin: 3mm auto; object-fit: contain; }
        .revision { font-size: 10pt; }
        .empty { font-style: italic; }
        @media (max-width: 600px) { .toolbar { margin: 12px; } .actions > * { flex: 1 1 100%; text-align: center; } }
        @media print {
            body { background: white; }
            .toolbar { display: none !important; }
            .preview { overflow: visible; padding: 0; }
            .paper { width: auto; margin: 0; padding: 0; box-shadow: none; }
            .print-header { break-inside: avoid; }
            .chart { break-inside: avoid; }
        }
    </style>
</head>
<body>
@php($spec = (array) $jobdesc->spesifikasi_pekerjaan)
<nav class="toolbar" aria-label="Aksi cetak dokumen">
    <div class="actions">
        <button type="button" id="print-button">Cetak / Simpan PDF</button>
        <a href="{{ route('jobdesc.show', $jobdesc) }}">Kembali ke detail</a>
    </div>
    <p>Pilih tujuan <strong>Simpan sebagai PDF / Save as PDF</strong> atau printer. Gunakan kertas A4, skala 100%, dan nonaktifkan header/footer bawaan browser.</p>
    <p id="print-status" role="status" aria-live="polite">Pratinjau siap dicetak. Pada layar kecil, geser dokumen ke samping.</p>
    <noscript><p>JavaScript tidak aktif. Gunakan menu Cetak pada browser atau Ctrl+P.</p></noscript>
</nav>
<main class="preview">
<div class="paper">

        <div class="print-header"><table class="masthead" aria-label="Kepala dokumen">
            <tr>
                <td class="title" rowspan="3">
                    <strong>{{ config('app.name') }}</strong>
                    <h1>URAIAN JABATAN</h1>
                    <p><strong>{{ mb_strtoupper($jobdesc->jabatan) }}</strong></p>
                </td>
                <td>Job Code: {{ $jobdesc->job_code ?: '-' }}</td>
            </tr>
            <tr><td>Departemen: {{ $jobdesc->departemen ?: '-' }}</td></tr>
            <tr><td>Status: {{ $jobdesc->status_label }}</td></tr>
        </table>

        </div>
        <h2>I. IDENTITAS JABATAN</h2>
        <table aria-label="Identitas jabatan">
            @foreach(['JABATAN / JOB CODE' => $jobdesc->jabatan.' / '.($jobdesc->job_code ?: '-'), 'GOLONGAN / LEVEL' => $jobdesc->golongan_level, 'DIVISI' => $jobdesc->divisi, 'DEPARTEMEN' => $jobdesc->departemen, 'AREA' => $jobdesc->area, 'ATASAN LANGSUNG' => $jobdesc->atasan_langsung, 'BAWAHAN LANGSUNG' => $jobdesc->bawahan_langsung, 'JUMLAH BAWAHAN' => $jobdesc->jumlah_bawahan] as $label => $value)
                <tr><td class="label">{{ $label }}</td><td class="multiline">{{ filled($value) ? $value : '-' }}</td></tr>
            @endforeach
        </table>
        <h2>II. RINGKASAN JABATAN</h2>
        <p class="multiline">{{ $jobdesc->ringkasan_jabatan ?: '-' }}</p>
        <h2>III. STRUKTUR ORGANISASI</h2>
        @if($jobdesc->bagan_struktur_path)
            <img class="chart" src="{{ asset('storage/'.$jobdesc->bagan_struktur_path) }}" alt="Bagan struktur {{ $jobdesc->jabatan }}">
        @endif
        <table aria-label="Struktur organisasi">
            <thead><tr><th>ATASAN</th><th style="width:15%">JUMLAH</th><th>BAWAHAN</th><th style="width:15%">JUMLAH</th></tr></thead>
            <tbody>
                @forelse((array) $jobdesc->struktur_organisasi as $row)
                    <tr><td>{{ data_get($row, 'atasan') ?: '-' }}</td><td class="center">{{ data_get($row, 'jumlah_atasan') ?? '-' }}</td><td>{{ data_get($row, 'bawahan') ?: '-' }}</td><td class="center">{{ data_get($row, 'jumlah_bawahan') ?? '-' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty">Data struktur organisasi belum diisi.</td></tr>
                @endforelse
            </tbody>
        </table>
        <h2>IV. TUGAS</h2>
        <h3>A. Tugas Pokok</h3>
        @forelse((array) $jobdesc->tugas_pokok as $row)
            <h4>{{ $loop->iteration }}. {{ data_get($row, 'nama') ?: 'Tugas Pokok' }}</h4>
            <p class="multiline">{{ data_get($row, 'rincian') ?: '-' }}</p>
        @empty
            <p class="empty">Tugas pokok belum diisi.</p>
        @endforelse
        <h3>B. Tugas Tambahan</h3>
        <p class="multiline">{{ $jobdesc->tugas_tambahan ?: '-' }}</p>
        <h3>C. Output Pekerjaan</h3>
        <p class="multiline">{{ $jobdesc->output_pekerjaan ?: '-' }}</p>
        @foreach(['V. HAK' => $jobdesc->hak, 'VI. KEWAJIBAN' => $jobdesc->kewajiban, 'VII. WEWENANG' => $jobdesc->wewenang] as $heading => $value)
            <h2>{{ $heading }}</h2><p class="multiline">{{ $value ?: '-' }}</p>
        @endforeach
        <h2>VIII. HUBUNGAN KERJA</h2>
        <table aria-label="Hubungan kerja">
            <thead><tr><th rowspan="2" style="width:22%">PIHAK</th><th colspan="2">FREKUENSI</th></tr><tr><th>JARANG</th><th>SERING</th></tr></thead>
            <tbody>
                @foreach(['internal' => 'INTERNAL', 'eksternal' => 'EKSTERNAL'] as $key => $label)
                    <tr><td><strong>{{ $label }}</strong></td><td class="multiline">{{ data_get($jobdesc->hubungan_kerja, $key.'_jarang') ?: '-' }}</td><td class="multiline">{{ data_get($jobdesc->hubungan_kerja, $key.'_sering') ?: '-' }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <h2>IX. LINGKUNGAN KERJA</h2>
        <p class="multiline">{{ $jobdesc->lingkungan_kerja ?: '-' }}</p>
        <h2>X. SPESIFIKASI PEKERJAAN</h2>
        <table aria-label="Spesifikasi pekerjaan">
            <tbody>
                <tr><td class="label">USIA</td><td colspan="2">{{ data_get($spec, 'umur') ?: '-' }}</td></tr>
                <tr><td class="label">JENIS KELAMIN</td><td colspan="2">{{ implode(', ', (array) data_get($spec, 'jenis_kelamin', [])) ?: '-' }}</td></tr>
                @forelse((array) data_get($spec, 'pendidikan', []) as $row)
                    <tr><td class="label">PENDIDIKAN</td><td>{{ data_get($row, 'jenjang') ?: '-' }}</td><td>Jurusan: {{ data_get($row, 'jurusan') ?: '-' }}</td></tr>
                @empty
                    <tr><td class="label">PENDIDIKAN</td><td colspan="2">-</td></tr>
                @endforelse
                <tr><td class="label">PENGALAMAN KERJA</td><td colspan="2">{{ implode(', ', (array) data_get($spec, 'pengalaman', [])) ?: '-' }}</td></tr>
                <tr><td class="label">KOMPETENSI TEKNIS</td><td colspan="2" class="multiline">{{ data_get($spec, 'kompetensi_teknis') ?: '-' }}</td></tr>
                <tr><td class="label">KOMPETENSI MANAJERIAL</td><td colspan="2" class="multiline">{{ data_get($spec, 'kompetensi_manajerial') ?: '-' }}</td></tr>
            </tbody>
        </table>
        <h2>XI. CATATAN REVISI</h2>
        <table class="revision" aria-label="Catatan revisi">
            <thead><tr><th style="width:9%">NO. REVISI</th><th style="width:15%">TANGGAL REVISI</th><th style="width:31%">DESKRIPSI PERUBAHAN</th><th style="width:26%">ALASAN REVISI</th><th style="width:19%">PIHAK YANG MEREVISI</th></tr></thead>
            <tbody>
                @forelse((array) $jobdesc->catatan_revisi as $row)
                    <tr>
                        @foreach(['nomor', 'tanggal', 'deskripsi', 'alasan', 'pihak'] as $field)
                            <td class="multiline">{{ data_get($row, $field) ?: '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Belum ada catatan revisi.</td></tr>
                @endforelse
            </tbody>
        </table>

</div>
</main>
<script>
    const button = document.getElementById('print-button');
    const status = document.getElementById('print-status');
    button.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'Menyiapkan cetak...';
        status.textContent = 'Menunggu gambar dan font dokumen siap.';
        try {
            await document.fonts.ready;
            await Promise.all(Array.from(document.images, image => image.decode()));
            status.textContent = 'Pilih Simpan sebagai PDF atau printer pada dialog cetak.';
            window.print();
        } catch (error) {
            status.textContent = 'Dokumen belum dapat dicetak. Gambar mungkin gagal dimuat. Muat ulang halaman dan periksa koneksi, lalu coba lagi.';
        } finally {
            button.disabled = false;
            button.textContent = 'Cetak / Simpan PDF';
        }
    });
</script>
</body>
</html>
