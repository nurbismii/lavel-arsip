@php($statusDokumen = $statusDokumen ?? '')
@php($borrowerUsers = $borrowerUsers ?? collect())
@if($item->dokumens->count())
@php($canManageDocument = auth()->check() && (auth()->user()->canAccessAllFiles() || (int) $item->user_id === (int) auth()->id()))
@php($statusOptions = \App\Models\Dokumen::statusOptions())
<ul class="list-unstyled tree-list tree-branch tree-documents">
    @foreach($item->dokumens as $doc)
    <li class="tree-item">
        <div class="tree-document small">
            <div>
                <span class="me-1">File:</span>
                <a href="{{ route('dokumen.lihat', $doc->id) }}" target="_blank" class="text-decoration-none">{{ $doc->nama_file }}</a>
                <span class="badge {{ $doc->status_dokumen_badge_class }} ms-2">{{ $doc->status_dokumen_label }}</span>
            </div>
            <small class="text-muted d-block ms-4 tree-meta">
                Disimpan: {{ $doc->tanggal_disimpan }} | Ukuran: {{ $doc->ukuran_file }}
            </small>
            <small class="text-muted d-block ms-4 tree-meta">
                Rentang penyelesaian: {{ $item->rentang_penyelesaian }}
            </small>

            @if($doc->status_dokumen === \App\Models\Dokumen::STATUS_AKTIF)
                <div class="mt-2 ms-4">
                    <span class="badge bg-primary text-white">
                        Dipinjam oleh: {{ optional($doc->peminjam)->name ?: 'Belum dipilih' }}
                    </span>
                    <small class="text-muted d-block mt-1">
                        Tanggal pinjam: {{ $doc->tanggal_dipinjam }}
                    </small>
                </div>
            @endif

            @if($doc->status_dokumen === \App\Models\Dokumen::STATUS_ARSIP)
                <div class="mt-2 ms-4">
                    <small class="text-muted d-block">
                        Selesai: {{ $doc->tanggal_diselesaikan }}
                    </small>
                    @if($doc->buktiPenyelesaians->count())
                        <div class="mt-1">
                            <small class="text-muted d-block">Bukti penyelesaian:</small>
                            @foreach($doc->buktiPenyelesaians as $bukti)
                                <small class="d-block">
                                    <a href="{{ route('dokumen.bukti-penyelesaian.file', [$doc->id, $bukti->id]) }}" target="_blank" class="text-decoration-none">
                                        {{ $bukti->nama_file }}
                                    </a>
                                </small>
                            @endforeach
                        </div>
                    @elseif($doc->bukti_penyelesaian_path)
                        <small class="d-block">
                            <a href="{{ route('dokumen.bukti-penyelesaian', $doc->id) }}" target="_blank" class="text-decoration-none">
                                {{ $doc->bukti_penyelesaian_nama_file ?: 'Lihat bukti penyelesaian' }}
                            </a>
                        </small>
                    @endif
                    @if($doc->keterangan_penyelesaian)
                        <div class="text-muted mt-2">
                            <small class="d-block fw-semibold mb-1">Keterangan penyelesaian:</small>
                            <div class="rich-text-content small">
                                {{ \App\Support\RichText::renderDocument($doc->keterangan_penyelesaian) }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if($canManageDocument)
                <form
                    method="POST"
                    action="{{ route('pekerjaan.dokumen.status', [$item->id, $doc->id]) }}"
                    enctype="multipart/form-data"
                    class="document-status-form mt-3 ms-4">
                    @csrf
                    @method('PATCH')

                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select
                                name="status_dokumen"
                                class="form-control form-control-sm document-status-select"
                                data-active-status="{{ \App\Models\Dokumen::STATUS_AKTIF }}"
                                data-complete-status="{{ \App\Models\Dokumen::STATUS_ARSIP }}">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $doc->status_dokumen === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 loan-fields {{ $doc->status_dokumen === \App\Models\Dokumen::STATUS_AKTIF ? '' : 'd-none' }}">
                            <label class="form-label small text-muted mb-1">Dipinjam oleh</label>
                            <select
                                name="peminjam_user_id"
                                class="form-control form-control-sm borrower-select"
                                {{ $doc->status_dokumen === \App\Models\Dokumen::STATUS_AKTIF ? 'required' : '' }}>
                                <option value="">-- Pilih User --</option>
                                @foreach($borrowerUsers as $borrowerUser)
                                    <option value="{{ $borrowerUser->id }}" {{ (int) old('peminjam_user_id', $doc->peminjam_user_id) === (int) $borrowerUser->id ? 'selected' : '' }}>
                                        {{ $borrowerUser->name }}{{ $borrowerUser->email ? ' - ' . $borrowerUser->email : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div
                            class="col-md-8 completion-fields {{ $doc->status_dokumen === \App\Models\Dokumen::STATUS_ARSIP ? '' : 'd-none' }}"
                            data-has-proof="{{ $doc->buktiPenyelesaians->count() || $doc->bukti_penyelesaian_path ? 'true' : 'false' }}">
                            <label class="form-label small text-muted mb-1">Bukti penyelesaian</label>
                            <input
                                type="file"
                                name="bukti_penyelesaian[]"
                                class="form-control form-control-sm completion-proof-input"
                                multiple
                                {{ $doc->status_dokumen === \App\Models\Dokumen::STATUS_ARSIP && !$doc->buktiPenyelesaians->count() && !$doc->bukti_penyelesaian_path ? 'required' : '' }}>
                            <small class="text-muted d-block mt-1">
                                Bisa pilih lebih dari satu file. Maksimal 10 MB per file. Upload baru akan ditambahkan ke daftar bukti.
                            </small>
                            @if($doc->buktiPenyelesaians->count())
                                <div class="mt-1">
                                    <small class="text-muted d-block">Bukti saat ini:</small>
                                    @foreach($doc->buktiPenyelesaians as $bukti)
                                        <small class="d-block">
                                            <a href="{{ route('dokumen.bukti-penyelesaian.file', [$doc->id, $bukti->id]) }}" target="_blank" class="text-decoration-none">
                                                {{ $bukti->nama_file }}
                                            </a>
                                        </small>
                                    @endforeach
                                </div>
                            @elseif($doc->bukti_penyelesaian_path)
                                <small class="d-block mt-1">
                                    <a href="{{ route('dokumen.bukti-penyelesaian', $doc->id) }}" target="_blank" class="text-decoration-none">
                                        Bukti saat ini: {{ $doc->bukti_penyelesaian_nama_file }}
                                    </a>
                                </small>
                            @endif

                            <label for="completion-note-{{ $doc->id }}" class="form-label small text-muted mt-2 mb-1">
                                Keterangan penyelesaian <span class="text-danger">*</span>
                            </label>
                            <textarea
                                id="completion-note-{{ $doc->id }}"
                                name="keterangan_penyelesaian"
                                rows="2"
                                class="form-control form-control-sm completion-note-input"
                                placeholder="Jelaskan hasil akhir dan tindak lanjut penyelesaian dokumen."
                                data-rich-text
                                data-rich-text-compact="true"
                                data-rich-text-maxlength="1000"
                                data-rich-text-required="{{ $doc->status_dokumen === \App\Models\Dokumen::STATUS_ARSIP ? 'true' : 'false' }}"
                                {{ $doc->status_dokumen === \App\Models\Dokumen::STATUS_ARSIP ? 'required' : '' }}>{{ \App\Support\RichText::sanitizeDocument(old('keterangan_penyelesaian', $doc->keterangan_penyelesaian)) }}</textarea>
                            <small class="text-muted d-block mt-1">
                                Wajib diisi saat status dokumen sudah selesai. Maksimal 1.000 karakter.
                            </small>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                Update Status
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </li>
    @endforeach
</ul>
@endif

@if($item->subPekerjaans->count())
@include('pekerjaan.tree', ['items' => $item->subPekerjaans, 'isRoot' => false, 'autoExpand' => false, 'statusDokumen' => $statusDokumen ?? ''])
@endif
