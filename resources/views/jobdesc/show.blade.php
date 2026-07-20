@extends('layouts.app')
@section('content')
<div class="container">
    <div class="app-page-header"><div><span class="app-page-eyebrow">Uraian Jabatan</span><h4 class="app-page-title">{{ $jobdesc->jabatan }}</h4><p class="app-page-subtitle">{{ $jobdesc->job_code ?: 'Job code belum ditentukan' }}</p></div><div class="app-page-actions d-flex gap-2">@if($canManage)<a href="{{ route('jobdesc.edit', $jobdesc) }}" class="btn btn-primary">Ubah</a>@endif<a href="{{ route('jobdesc.index') }}" class="btn btn-outline-secondary">Kembali</a></div></div>
    <div class="row g-4"><div class="col-lg-8">
        <div class="app-card p-3 p-md-4 mb-4"><h5>I. Identitas Jabatan</h5><div class="row g-3"><div class="col-md-6"><small class="text-muted">Golongan / Level</small><div>{{ $jobdesc->golongan_level ?: '-' }}</div></div><div class="col-md-6"><small class="text-muted">Area</small><div>{{ $jobdesc->area ?: '-' }}</div></div><div class="col-md-6"><small class="text-muted">Divisi / Departemen</small><div>{{ $jobdesc->divisi ?: '-' }} / {{ $jobdesc->departemen ?: '-' }}</div></div><div class="col-md-6"><small class="text-muted">Atasan / Bawahan Langsung</small><div>{{ $jobdesc->atasan_langsung ?: '-' }} / {{ $jobdesc->bawahan_langsung ?: '-' }}</div></div></div></div>
        @foreach(['II. Ringkasan Jabatan' => $jobdesc->ringkasan_jabatan] as $heading => $content)
            @if(filled($content))
                <div class="app-card p-3 p-md-4 mb-4"><h5>{{ $heading }}</h5><div class="text-pre-line">{!! nl2br(e($content)) !!}</div></div>
            @endif
        @endforeach
        <div class="app-card p-3 p-md-4 mb-4">
            <h5>III. Struktur Organisasi</h5>
            @if($jobdesc->bagan_struktur_path)
                <img src="{{ asset('storage/'.$jobdesc->bagan_struktur_path) }}" class="img-fluid rounded border mb-3" alt="Bagan struktur {{ $jobdesc->jabatan }}">
            @endif
            @if(!empty($jobdesc->struktur_organisasi))
                <div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead><tr><th>Atasan</th><th class="text-center">Jumlah</th><th>Bawahan</th><th class="text-center">Jumlah</th></tr></thead><tbody>
                    @foreach($jobdesc->struktur_organisasi as $row)
                        <tr><td>{{ data_get($row, 'atasan') ?: '-' }}</td><td class="text-center">{{ data_get($row, 'jumlah_atasan') ?? '-' }}</td><td>{{ data_get($row, 'bawahan') ?: '-' }}</td><td class="text-center">{{ data_get($row, 'jumlah_bawahan') ?? '-' }}</td></tr>
                    @endforeach
                </tbody></table></div>
            @elseif(!$jobdesc->bagan_struktur_path)
                <p class="text-muted mb-0">Struktur organisasi belum diisi.</p>
            @endif
        </div>

        <div class="app-card p-3 p-md-4 mb-4">
            <h5>IV. Tugas</h5>
            <h6 class="mt-3">Tugas Pokok</h6>
            @forelse((array) $jobdesc->tugas_pokok as $row)
                <div class="border-start border-3 border-primary ps-3 mb-3"><strong>{{ data_get($row, 'nama') ?: 'Tugas Pokok' }}</strong><div class="text-pre-line mt-1">{!! nl2br(e(data_get($row, 'rincian'))) !!}</div></div>
            @empty
                <p class="text-muted">Belum ada tugas pokok yang diisi.</p>
            @endforelse
            <div class="row g-4 mt-1"><div class="col-md-6"><h6>Tugas Tambahan</h6><div class="text-pre-line">{!! nl2br(e($jobdesc->tugas_tambahan ?: '-')) !!}</div></div><div class="col-md-6"><h6>Output Pekerjaan</h6><div class="text-pre-line">{!! nl2br(e($jobdesc->output_pekerjaan ?: '-')) !!}</div></div></div>
        </div>
        @foreach(['V. Hak' => $jobdesc->hak, 'VI. Kewajiban' => $jobdesc->kewajiban, 'VII. Wewenang' => $jobdesc->wewenang] as $heading => $content)
            @if(filled($content))
                <div class="app-card p-3 p-md-4 mb-4"><h5>{{ $heading }}</h5><div class="text-pre-line">{!! nl2br(e($content)) !!}</div></div>
            @endif
        @endforeach
        @if(!empty($jobdesc->hubungan_kerja))
            <div class="app-card p-3 p-md-4 mb-4"><h5>VIII. Hubungan Kerja</h5><div class="row g-3">
                @foreach(['internal_jarang'=>'Internal - Jarang','internal_sering'=>'Internal - Sering','eksternal_jarang'=>'Eksternal - Jarang','eksternal_sering'=>'Eksternal - Sering'] as $key=>$label)
                    @if(filled(data_get($jobdesc->hubungan_kerja,$key)))
                        <div class="col-md-6"><strong>{{ $label }}</strong><div class="text-pre-line">{!! nl2br(e(data_get($jobdesc->hubungan_kerja,$key))) !!}</div></div>
                    @endif
                @endforeach
            </div></div>
        @endif
        @if(filled($jobdesc->lingkungan_kerja))
            <div class="app-card p-3 p-md-4 mb-4"><h5>IX. Lingkungan Kerja</h5><div class="text-pre-line">{!! nl2br(e($jobdesc->lingkungan_kerja)) !!}</div></div>
        @endif
        @if(!empty($jobdesc->spesifikasi_pekerjaan))<div class="app-card p-3 p-md-4 mb-4"><h5>X. Spesifikasi Pekerjaan</h5><p><strong>Umur:</strong> {{ data_get($jobdesc->spesifikasi_pekerjaan,'umur') ?: '-' }} &nbsp; <strong>Jenis kelamin:</strong> {{ implode(', ', (array) data_get($jobdesc->spesifikasi_pekerjaan,'jenis_kelamin', [])) ?: '-' }}</p>@if(!empty(data_get($jobdesc->spesifikasi_pekerjaan,'pendidikan')))<strong>Pendidikan</strong><ul>@foreach(data_get($jobdesc->spesifikasi_pekerjaan,'pendidikan') as $item)<li>{{ data_get($item,'jenjang') }}{{ data_get($item,'jurusan') ? ' - '.data_get($item,'jurusan') : '' }}</li>@endforeach</ul>@endif</div>@endif
        @if(!empty($jobdesc->catatan_revisi))
            <div class="app-card p-3 p-md-4 mb-4"><h5>XI. Catatan Revisi</h5><div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead><tr><th>No.</th><th>Tanggal</th><th>Deskripsi Perubahan</th><th>Alasan Revisi</th><th>Pihak yang Merevisi</th></tr></thead><tbody>
                @foreach($jobdesc->catatan_revisi as $revisi)
                    <tr><td>{{ data_get($revisi, 'nomor') ?: '-' }}</td><td>{{ data_get($revisi, 'tanggal') ?: '-' }}</td><td class="text-pre-line">{{ data_get($revisi, 'deskripsi') ?: '-' }}</td><td class="text-pre-line">{{ data_get($revisi, 'alasan') ?: '-' }}</td><td>{{ data_get($revisi, 'pihak') ?: '-' }}</td></tr>
                @endforeach
            </tbody></table></div></div>
        @endif
    </div><div class="col-lg-4"><div class="app-card p-3 mb-4"><h6>Status Dokumen</h6><span class="badge {{ $jobdesc->status_badge_class }}">{{ $jobdesc->status_label }}</span><hr><small class="text-muted d-block">Penanggung jawab</small><div>{{ $jobdesc->pemilik->name ?? '-' }}</div><small class="text-muted d-block mt-3">Unit / Tim</small><div>{{ $jobdesc->team->name ?? '-' }}</div><small class="text-muted d-block mt-3">Dibuat</small><div>{{ $jobdesc->created_at->format('d M Y H:i') }}</div></div></div></div>
    @if($canManage)<form method="POST" action="{{ route('jobdesc.destroy', $jobdesc) }}" class="mt-3" data-loading-form data-confirm-title="Hapus uraian jabatan?" data-confirm-text="Data uraian jabatan ini akan dihapus permanen.">@csrf @method('DELETE')<button class="btn btn-outline-danger">Hapus Uraian Jabatan</button></form>@endif
</div>
@endsection
