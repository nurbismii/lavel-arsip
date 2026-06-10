@php($sopPengetahuan = $sopPengetahuan ?? null)
@php($selectedAlurKerjaId = old('alur_kerja_id', optional($sopPengetahuan)->alur_kerja_id ?: request('alur_kerja_id')))
@php($selectedTahapId = old('alur_kerja_tahap_id', optional($sopPengetahuan)->alur_kerja_tahap_id ?: request('alur_kerja_tahap_id')))
@php($documentContent = old('konten', optional($sopPengetahuan)->konten))

<div data-knowledge-form data-rich-text-scope="explicit">
    <input type="hidden" name="jenis" value="{{ \App\Models\SopPengetahuan::JENIS_SOP }}">

    <div class="sop-form-guide mb-3">
        <div class="sop-form-guide__item">
            <span>1</span>
            <div>
                <strong>Identitas</strong>
                <small>Isi judul, unit, dan status dokumen.</small>
            </div>
        </div>
        <div class="sop-form-guide__item">
            <span>2</span>
            <div>
                <strong>Editor SOP</strong>
                <small>Buat KOP, heading, paragraf, dan simbol.</small>
            </div>
        </div>
        <div class="sop-form-guide__item">
            <span>3</span>
            <div>
                <strong>Diagram</strong>
                <small>Upload gambar prosedur dari draw.io.</small>
            </div>
        </div>
        <div class="sop-form-guide__item">
            <span>4</span>
            <div>
                <strong>Lampiran</strong>
                <small>Tambahkan file pendukung bila ada.</small>
            </div>
        </div>
    </div>

    <div class="sop-form-section">
        <div class="sop-section-heading">
            <span class="sop-step-number">1</span>
            <div>
                <h6>Informasi Dokumen</h6>
                <p>Bagian ini dipakai untuk pencarian, kontrol status, dan relasi ke alur kerja.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <label class="form-label">Nama SOP <span class="required-mark">*</span></label>
                <input
                    type="text"
                    name="judul"
                    class="form-control form-control-lg @error('judul') is-invalid @enderror"
                    value="{{ old('judul', optional($sopPengetahuan)->judul) }}"
                    placeholder="Contoh: Rekrutmen Karyawan"
                    data-sop-title-input
                    required>
                @error('judul')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Gunakan nama proses, bukan kalimat panjang.</small>
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">Unit / Tim</label>
                <select name="team_id" class="form-select @error('team_id') is-invalid @enderror" data-sop-team-select>
                    <option value="">Semua Unit</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ (int) old('team_id', optional($sopPengetahuan)->team_id) === (int) $team->id ? 'selected' : '' }}>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
                @error('team_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 col-lg-2">
                <label class="form-label">Tanggal Berlaku</label>
                <input
                    type="date"
                    name="tanggal_berlaku"
                    class="form-control @error('tanggal_berlaku') is-invalid @enderror"
                    value="{{ old('tanggal_berlaku', optional(optional($sopPengetahuan)->tanggal_berlaku)->format('Y-m-d')) }}"
                    data-sop-effective-date-input>
                @error('tanggal_berlaku')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Status Dokumen</label>
                <div class="row g-2">
                    <div class="col-md-6">
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('status', optional($sopPengetahuan)->status ?: \App\Models\SopPengetahuan::STATUS_DRAFT) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <select name="tingkat_kepentingan" class="form-select @error('tingkat_kepentingan') is-invalid @enderror">
                            @foreach($prioritasOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('tingkat_kepentingan', optional($sopPengetahuan)->tingkat_kepentingan ?: \App\Models\SopPengetahuan::PRIORITAS_NORMAL) === $value ? 'selected' : '' }}>
                                    Prioritas {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('tingkat_kepentingan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <small class="text-muted">Gunakan Draft saat SOP masih disusun.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">Kata Kunci</label>
                <input
                    type="text"
                    name="kata_kunci"
                    class="form-control @error('kata_kunci') is-invalid @enderror"
                    value="{{ old('kata_kunci', optional($sopPengetahuan)->kata_kunci) }}"
                    placeholder="Contoh: arsip, verifikasi, rekrutmen">
                @error('kata_kunci')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Pisahkan dengan koma agar dokumen mudah dicari.</small>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#sopAdvancedSettings" aria-expanded="false" aria-controls="sopAdvancedSettings">
                Pengaturan lanjutan
            </button>
        </div>

        <div class="collapse mt-3" id="sopAdvancedSettings">
            <div class="sop-advanced-panel">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">No. Dokumen</label>
                        <input
                            type="text"
                            name="kode"
                            class="form-control @error('kode') is-invalid @enderror"
                            value="{{ old('kode', optional($sopPengetahuan)->kode) }}"
                            placeholder="02-0001/SOP/HRD-VDNI/VIII/2025"
                            data-sop-code-input>
                        @error('kode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">No. Revisi</label>
                        <input
                            type="text"
                            name="nomor_revisi"
                            class="form-control @error('nomor_revisi') is-invalid @enderror"
                            value="{{ old('nomor_revisi', optional($sopPengetahuan)->nomor_revisi ?: '000') }}"
                            placeholder="000"
                            data-sop-revision-input>
                        @error('nomor_revisi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Penanggung Jawab</label>
                        <select name="pemilik_user_id" class="form-select @error('pemilik_user_id') is-invalid @enderror" {{ auth()->user()->canAccessAllFiles() ? '' : 'disabled' }}>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ (int) old('pemilik_user_id', optional($sopPengetahuan)->pemilik_user_id ?: auth()->id()) === (int) $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('pemilik_user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if(!auth()->user()->canAccessAllFiles())
                            <input type="hidden" name="pemilik_user_id" value="{{ auth()->id() }}">
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Target Tinjauan</label>
                        <input
                            type="date"
                            name="target_tinjauan_berikutnya"
                            class="form-control @error('target_tinjauan_berikutnya') is-invalid @enderror"
                            value="{{ old('target_tinjauan_berikutnya', optional(optional($sopPengetahuan)->target_tinjauan_berikutnya)->format('Y-m-d')) }}">
                        @error('target_tinjauan_berikutnya')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tautkan ke Alur Kerja</label>
                        <select name="alur_kerja_id" class="form-select @error('alur_kerja_id') is-invalid @enderror" data-knowledge-workflow-select>
                            <option value="">Tidak ditautkan</option>
                            @foreach($alurKerjas as $alurKerja)
                                <option value="{{ $alurKerja->id }}" {{ (int) $selectedAlurKerjaId === (int) $alurKerja->id ? 'selected' : '' }}>
                                    {{ $alurKerja->kode ? $alurKerja->kode . ' - ' : '' }}{{ $alurKerja->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('alur_kerja_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tahap Terkait</label>
                        <select
                            name="alur_kerja_tahap_id"
                            class="form-select @error('alur_kerja_tahap_id') is-invalid @enderror"
                            data-knowledge-stage-select
                            data-selected-stage="{{ $selectedTahapId }}">
                            <option value="">Pilih alur kerja terlebih dahulu</option>
                        </select>
                        @error('alur_kerja_tahap_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted" data-knowledge-stage-help>Tahap bersifat opsional.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sop-form-section sop-document-workspace">
        <div class="sop-section-heading">
            <span class="sop-step-number">2</span>
            <div>
                <h6>Editor Dokumen SOP</h6>
                <p>Gunakan satu editor ini untuk membuat KOP, heading, paragraf, dan menyisipkan gambar diagram prosedur.</p>
            </div>
        </div>

        @error('konten')
            <div class="alert alert-danger py-2">{{ $message }}</div>
        @enderror

        <div class="sop-editor-commandbar">
            <div class="sop-editor-commandbar__actions">
                <button type="button" class="btn btn-sm btn-outline-primary" data-sop-insert-kop>
                    Sisipkan KOP
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-sop-insert-structure>
                    Sisipkan Struktur SOP
                </button>
            </div>
            <small class="text-muted">Heading dan paragraf tersedia di toolbar editor. Diagram prosedur bisa disisipkan melalui upload gambar di bagian berikutnya.</small>
        </div>

        <div class="mt-3">
            <textarea
                name="konten"
                rows="18"
                data-rich-text="editor"
                data-rich-text-mode="document"
                data-sop-document-editor
                class="form-control @error('konten') is-invalid @enderror"
                placeholder="Mulai tulis dokumen SOP di sini. Gunakan tombol Sisipkan KOP untuk membuat header dokumen.">{{ $documentContent }}</textarea>
        </div>
    </div>

    <div class="sop-form-section">
        <div class="sop-section-heading">
            <span class="sop-step-number">3</span>
            <div>
                <h6>Diagram Prosedur Pelaksanaan</h6>
                <p>Buat diagram di <a href="https://www.draw.io" target="_blank">draw.io</a>, Visio, <a href="https://www.canva.com" target="_blank">Canva</a>, atau aplikasi lain, lalu upload gambar dan sisipkan ke editor SOP.</p>
            </div>
        </div>

        <div
            class="sop-diagram-uploader"
            data-sop-diagram-uploader
            data-upload-url="{{ route('sop-pengetahuan.editor-image.upload') }}">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-5">
                    <div class="sop-diagram-uploader__drop">
                        <div class="sop-diagram-uploader__icon">
                            <i class="fas fa-image"></i>
                        </div>
                        <label class="form-label mb-2">Gambar Diagram</label>
                        <input
                            type="file"
                            class="form-control"
                            accept="image/png,image/jpeg,image/webp"
                            multiple
                            data-sop-diagram-image-input>
                        <small class="text-muted d-block mt-2">Bisa pilih lebih dari satu gambar. Format PNG, JPG, JPEG, atau WEBP. Maksimal 5 MB per file.</small>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="sop-diagram-uploader__preview">
                        <div class="empty-state py-4 mb-0" data-sop-diagram-empty>
                            <div class="empty-state-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <h5>Belum ada gambar dipilih</h5>
                            <p>Export diagram dari aplikasi lain, pilih gambar di sini, lalu sisipkan ke Prosedur Pelaksanaan.</p>
                        </div>
                        <div class="d-none" data-sop-diagram-preview>
                            <div class="sop-diagram-uploader__grid" data-sop-diagram-preview-list></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mt-3">
                <small class="text-muted">Semua gambar terpilih akan disisipkan ke bagian <strong>Prosedur Pelaksanaan</strong> di editor dokumen.</small>
                <button type="button" class="btn btn-success" data-sop-diagram-upload-button data-loading-text="Mengupload...">
                    Upload dan Sisipkan
                </button>
            </div>
        </div>
    </div>

    <div class="sop-form-section">
        <div class="sop-section-heading">
            <span class="sop-step-number">4</span>
            <div>
                <h6>File Lampiran</h6>
                <p>Upload formulir, contoh pengisian, atau dokumen pendukung. Daftar lampiran di dalam SOP bisa ditulis langsung di editor.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6" data-file-picker>
                <label class="form-label">{{ $sopPengetahuan ? 'Tambah File Lampiran' : 'File Lampiran' }}</label>
                <input type="file" name="lampiran[]" class="form-control @error('lampiran.*') is-invalid @enderror" multiple data-file-input-preview>
                @error('lampiran.*')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Maksimal 20 MB per file.</small>
                <div class="mt-2 d-none" data-selected-file-list></div>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        window.VOpsKnowledgeStages = @json($alurKerjaStageMap ?? []);
        window.VOpsSopSymbolLabels = @json($simbolOptions);
    </script>
@endonce
