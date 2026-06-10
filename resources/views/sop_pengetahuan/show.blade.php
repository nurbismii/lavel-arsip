@extends('layouts.app')

@section('content')
@php
    $flowchart = data_get($sopPengetahuan->prosedur, 'flowchart', ['nodes' => [], 'connectors' => []]);
    $flowchartNodes = collect(data_get($flowchart, 'nodes', []));
    $flowchartConnectors = collect(data_get($flowchart, 'connectors', []));
    $hasEmbeddedFlowchart = str_contains((string) $sopPengetahuan->konten, 'sop-diagram-block');
    $structuredProsedur = collect($sopPengetahuan->prosedur ?? [])
        ->reject(function ($row, $key) {
            return $key === 'flowchart' || !is_array($row);
        })
        ->values();
@endphp
<div class="container py-4">
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Gagal memproses perubahan SOP.</strong>
            <div class="mb-2">Periksa kembali data atau lampiran yang dipilih.</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="app-page-header">
        <div>
            <span class="app-page-eyebrow">Detail SOP</span>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                @if($sopPengetahuan->kode)
                    <span class="badge bg-light text-dark border">No. Dokumen: {{ $sopPengetahuan->kode }}</span>
                @endif
                @if($sopPengetahuan->nomor_revisi)
                    <span class="badge bg-light text-dark border">Rev. {{ $sopPengetahuan->nomor_revisi }}</span>
                @endif
                <span class="badge {{ $sopPengetahuan->jenis_badge_class }}">{{ $sopPengetahuan->jenis_label }}</span>
                <span class="badge {{ $sopPengetahuan->status_badge_class }}">{{ $sopPengetahuan->status_label }}</span>
                <span class="badge {{ $sopPengetahuan->prioritas_badge_class }}">{{ $sopPengetahuan->prioritas_label }}</span>
                @if($sopPengetahuan->membutuhkan_tinjauan)
                    <span class="badge bg-danger">Perlu Tinjauan</span>
                @endif
            </div>
            <h4 class="app-page-title">{{ $sopPengetahuan->judul }}</h4>
            <p class="app-page-subtitle">Dokumen SOP dibuat dalam satu editor agar KOP, heading, paragraf, dan simbol SOP tetap terbaca sebagai satu dokumen utuh.</p>
        </div>
        <div class="app-page-actions">
            <a href="{{ route('sop-pengetahuan.index') }}" class="btn btn-outline-secondary">Kembali</a>
            @if($canManage)
                <a href="{{ route('sop-pengetahuan.edit', $sopPengetahuan->id) }}" class="btn btn-outline-warning">Edit</a>
                <form method="POST"
                    action="{{ route('sop-pengetahuan.destroy', $sopPengetahuan->id) }}"
                    data-loading-form
                    data-confirm-title="Hapus SOP?"
                    data-confirm-text="SOP {{ $sopPengetahuan->judul }} dan seluruh lampirannya akan dihapus.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" data-loading-text="Menghapus...">Hapus</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="app-card h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Ringkasan</h6>
                    @if($sopPengetahuan->ringkasan)
                        <div class="rich-text-content text-muted">{!! \App\Support\RichText::render($sopPengetahuan->ringkasan) !!}</div>
                    @else
                        <p class="mb-0 text-muted">Belum ada ringkasan.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="app-card h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Kepemilikan dan Tinjauan</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Penanggung jawab</small>
                        <div class="fw-semibold">{{ optional($sopPengetahuan->pemilik)->name ?: '-' }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Unit / Tim</small>
                        <div class="fw-semibold">{{ optional($sopPengetahuan->team)->name ?: 'Umum' }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Tanggal berlaku</small>
                        <div class="fw-semibold">{{ $sopPengetahuan->tanggal_berlaku_label }}</div>
                    </div>
                    <div>
                        <small class="text-muted d-block">Target tinjauan</small>
                        <div class="fw-semibold">{{ $sopPengetahuan->tanggal_tinjauan_label }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="d-grid gap-3">
                @if($sopPengetahuan->konten)
                    <div class="app-card">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Dokumen SOP</h6>
                            <div class="rich-text-content knowledge-content sop-document-preview">{!! \App\Support\RichText::render($sopPengetahuan->konten) !!}</div>
                        </div>
                    </div>

                @else
                <div class="app-card">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Tujuan</h6>
                        @if($sopPengetahuan->tujuan)
                            <div class="rich-text-content knowledge-content">{!! \App\Support\RichText::render($sopPengetahuan->tujuan) !!}</div>
                        @else
                            <div class="alert alert-light border mb-0">Tujuan belum diisi.</div>
                        @endif
                    </div>
                </div>

                <div class="app-card">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Ruang Lingkup</h6>
                        @if($sopPengetahuan->ruang_lingkup)
                            <div class="rich-text-content knowledge-content">{!! \App\Support\RichText::render($sopPengetahuan->ruang_lingkup) !!}</div>
                        @else
                            <div class="alert alert-light border mb-0">Ruang lingkup belum diisi.</div>
                        @endif
                    </div>
                </div>

                <div class="app-card">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Definisi</h6>
                        @if(!empty($sopPengetahuan->definisi))
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 28%;">Istilah</th>
                                            <th>Penjelasan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sopPengetahuan->definisi as $row)
                                            <tr>
                                                <td class="fw-semibold">{{ data_get($row, 'istilah') ?: '-' }}</td>
                                                <td>
                                                    @if(data_get($row, 'penjelasan'))
                                                        <div class="rich-text-content rich-text-content--compact">{!! \App\Support\RichText::render(data_get($row, 'penjelasan')) !!}</div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">Belum ada definisi khusus.</div>
                        @endif
                    </div>
                </div>

                <div class="app-card">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-1">Prosedur Pelaksanaan</h6>
                        <small class="text-muted d-block mb-3">Format mengikuti kolom Aktivitas, Dokumen, dan Keterangan pada ketentuan SOP.</small>

                        @if($structuredProsedur->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Simbol</th>
                                            <th>Pelaksana</th>
                                            <th>Aktivitas</th>
                                            <th>Dokumen</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($structuredProsedur as $row)
                                            <tr>
                                                <td class="fw-semibold">{{ data_get($row, 'urutan') ?: $loop->iteration }}</td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        {{ $simbolOptions[data_get($row, 'simbol')] ?? data_get($row, 'simbol', '-') }}
                                                    </span>
                                                </td>
                                                <td>{{ data_get($row, 'pelaksana') ?: '-' }}</td>
                                                <td>
                                                    @if(data_get($row, 'aktivitas'))
                                                        <div class="rich-text-content rich-text-content--compact">{!! \App\Support\RichText::render(data_get($row, 'aktivitas')) !!}</div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(data_get($row, 'dokumen'))
                                                        <div class="rich-text-content rich-text-content--compact">{!! \App\Support\RichText::render(data_get($row, 'dokumen')) !!}</div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(data_get($row, 'keterangan'))
                                                        <div class="rich-text-content rich-text-content--compact">{!! \App\Support\RichText::render(data_get($row, 'keterangan')) !!}</div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-state-icon">0</div>
                                <h5>Prosedur belum tersedia</h5>
                                <p>Tambahkan minimal satu aktivitas agar SOP dapat digunakan sebagai acuan kerja.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="app-card">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Lampiran dalam SOP</h6>
                        @if(!empty($sopPengetahuan->daftar_lampiran))
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nama Lampiran</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sopPengetahuan->daftar_lampiran as $row)
                                            <tr>
                                                <td class="fw-semibold">{{ data_get($row, 'nama') ?: '-' }}</td>
                                                <td>
                                                    @if(data_get($row, 'keterangan'))
                                                        <div class="rich-text-content rich-text-content--compact">{!! \App\Support\RichText::render(data_get($row, 'keterangan')) !!}</div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">Belum ada daftar lampiran.</div>
                        @endif
                    </div>
                </div>

                <div class="app-card">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Catatan Revisi</h6>
                        @if(!empty($sopPengetahuan->catatan_revisi))
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>No. Revisi</th>
                                            <th>Tanggal</th>
                                            <th>Deskripsi Perubahan</th>
                                            <th>Alasan</th>
                                            <th>Pihak Merevisi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sopPengetahuan->catatan_revisi as $row)
                                            <tr>
                                                <td class="fw-semibold">{{ data_get($row, 'no_revisi') ?: '-' }}</td>
                                                <td>{{ data_get($row, 'tanggal_revisi') ?: '-' }}</td>
                                                <td>
                                                    @if(data_get($row, 'deskripsi_perubahan'))
                                                        <div class="rich-text-content rich-text-content--compact">{!! \App\Support\RichText::render(data_get($row, 'deskripsi_perubahan')) !!}</div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(data_get($row, 'alasan_revisi'))
                                                        <div class="rich-text-content rich-text-content--compact">{!! \App\Support\RichText::render(data_get($row, 'alasan_revisi')) !!}</div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ data_get($row, 'pihak_merevisi') ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">Belum ada catatan revisi.</div>
                        @endif
                    </div>
                </div>

                @endif

                @if($flowchartNodes->isNotEmpty() && !$hasEmbeddedFlowchart)
                    @php($nodeMap = $flowchartNodes->keyBy('id'))
                    @php($canvasHeight = max(520, (float) $flowchartNodes->max('y') + 160))
                    <div class="app-card">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-2">Flowchart SOP</h6>
                            <small class="text-muted d-block mb-3">Simbol dan garis penghubung mengikuti susunan yang dibuat pada editor flowchart.</small>
                            <div class="sop-flow-canvas-wrap">
                                <div class="sop-flow-canvas sop-flow-canvas--readonly" style="height: {{ (int) $canvasHeight }}px;">
                                    <svg class="sop-flow-lines" aria-hidden="true">
                                        <defs>
                                            <marker id="sopFlowArrowView{{ $sopPengetahuan->id }}" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto" markerUnits="strokeWidth">
                                                <path d="M0,0 L0,6 L9,3 z"></path>
                                            </marker>
                                        </defs>
                                        @foreach($flowchartConnectors as $connector)
                                            @php($fromNode = $nodeMap->get(data_get($connector, 'from')))
                                            @php($toNode = $nodeMap->get(data_get($connector, 'to')))
                                            @if($fromNode && $toNode)
                                                @php($fromWidth = 168)
                                                @php($toWidth = 168)
                                                @php($fromHeight = data_get($fromNode, 'type') === 'decision' ? 92 : 72)
                                                @php($toHeight = data_get($toNode, 'type') === 'decision' ? 92 : 72)
                                                @php($fromCenterX = (float) data_get($fromNode, 'x', 0) + ($fromWidth / 2))
                                                @php($fromCenterY = (float) data_get($fromNode, 'y', 0) + ($fromHeight / 2))
                                                @php($toCenterX = (float) data_get($toNode, 'x', 0) + ($toWidth / 2))
                                                @php($toCenterY = (float) data_get($toNode, 'y', 0) + ($toHeight / 2))
                                                @php($deltaX = $toCenterX - $fromCenterX)
                                                @php($deltaY = $toCenterY - $fromCenterY)
                                                @php($fromScale = min(($fromWidth / 2) / max(abs($deltaX), 0.001), ($fromHeight / 2) / max(abs($deltaY), 0.001)))
                                                @php($toScale = min(($toWidth / 2) / max(abs($deltaX), 0.001), ($toHeight / 2) / max(abs($deltaY), 0.001)))
                                                @php($startX = $fromCenterX + ($deltaX * $fromScale))
                                                @php($startY = $fromCenterY + ($deltaY * $fromScale))
                                                @php($endX = $toCenterX - ($deltaX * $toScale))
                                                @php($endY = $toCenterY - ($deltaY * $toScale))
                                                @php($midX = $startX + (($endX - $startX) / 2))
                                                <path
                                                    class="sop-flow-connector"
                                                    marker-end="url(#sopFlowArrowView{{ $sopPengetahuan->id }})"
                                                    d="M {{ $startX }} {{ $startY }} C {{ $midX }} {{ $startY }}, {{ $midX }} {{ $endY }}, {{ $endX }} {{ $endY }}"></path>
                                            @endif
                                        @endforeach
                                    </svg>
                                    @foreach($flowchartNodes as $node)
                                        @php($symbol = data_get($node, 'type', \App\Models\SopPengetahuan::SIMBOL_AKTIVITAS))
                                        <div
                                            class="sop-flow-node sop-flow-node--{{ $symbol }} is-readonly"
                                            style="left: {{ (float) data_get($node, 'x', 0) }}px; top: {{ (float) data_get($node, 'y', 0) }}px;">
                                            <span class="sop-symbol-icon sop-symbol-icon--{{ $symbol }}" aria-hidden="true"></span>
                                            <div class="sop-flow-node__label">{{ data_get($node, 'label') ?: ($simbolOptions[$symbol] ?? 'Aktivitas') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($sopPengetahuan->kata_kunci)
                    <div class="app-card">
                        <div class="card-body">
                            <small class="text-muted d-block mb-2">Kata kunci</small>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(array_filter(array_map('trim', explode(',', $sopPengetahuan->kata_kunci))) as $tag)
                                    <span class="badge bg-light text-dark border">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="d-grid gap-3">
                <div class="app-card">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Relasi Operasional</h6>
                        <div class="mb-3">
                            <small class="text-muted d-block">Alur kerja</small>
                            @if($sopPengetahuan->alurKerja)
                                <a href="{{ route('alur-kerja.show', $sopPengetahuan->alurKerja->id) }}" class="fw-semibold text-decoration-none">
                                    {{ $sopPengetahuan->alurKerja->nama }}
                                </a>
                            @else
                                <div class="fw-semibold">Tidak ditautkan</div>
                            @endif
                        </div>
                        <div>
                            <small class="text-muted d-block">Tahap terkait</small>
                            <div class="fw-semibold">
                                @if($sopPengetahuan->tahap)
                                    Tahap {{ $sopPengetahuan->tahap->urutan }} - {{ $sopPengetahuan->tahap->nama }}
                                @else
                                    Tidak ada tahap khusus
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="fw-semibold mb-1">File Lampiran</h6>
                                <small class="text-muted">Template, contoh dokumen, atau file pendukung.</small>
                            </div>
                            <span class="badge bg-light text-dark border">{{ $sopPengetahuan->lampirans->count() }}</span>
                        </div>

                        @if($sopPengetahuan->lampirans->count())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <tbody>
                                        @foreach($sopPengetahuan->lampirans as $lampiran)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('sop-pengetahuan.lampiran.show', [$sopPengetahuan->id, $lampiran->id]) }}" target="_blank" class="text-decoration-none">
                                                        {{ $lampiran->nama_file }}
                                                    </a>
                                                    <small class="text-muted d-block">{{ $lampiran->ukuran_file_label }} - {{ $lampiran->tanggal_upload }}</small>
                                                </td>
                                                @if($canManage)
                                                    <td class="text-end">
                                                        <form method="POST"
                                                            action="{{ route('sop-pengetahuan.lampiran.destroy', [$sopPengetahuan->id, $lampiran->id]) }}"
                                                            data-loading-form
                                                            data-confirm-title="Hapus lampiran?"
                                                            data-confirm-text="File {{ $lampiran->nama_file }} akan dihapus dari SOP ini.">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-loading-text="Menghapus...">Hapus</button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">Belum ada file lampiran.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('alur_kerja._rich_text_editor_styles')
@endpush
