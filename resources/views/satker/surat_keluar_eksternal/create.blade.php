@extends('layouts.app')

@push('styles')
<style>
    .form-label { font-weight: 600; font-size: 0.9rem; color: #495057; }
    .card-header { background: linear-gradient(45deg, #f8f9fa, #e9ecef); }
    
    /* Style untuk area preview */
    #preview-container { 
        display: none; 
        background-color: #f8f9fa; 
        border: 2px dashed #dee2e6; 
        border-radius: 8px; 
        padding: 15px; 
        text-align: center;
        margin-top: 10px;
    }
    #preview-image { max-width: 70%; max-height: 150px; object-fit: contain; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    #preview-pdf { width: 40%; height: 200px; border: 1px solid #dee2e6; border-radius: 4px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header py-3 border-bottom d-flex align-items-center">
                    <i class="bi bi-box-arrow-up text-primary me-2 fs-5"></i>
                    <h6 class="m-0 fw-bold text-primary">Buat Surat Keluar Eksternal</h6>
                </div>
                
                <div class="card-body p-4">
                    
                    {{-- Alert General Error --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Mohon periksa kembali inputan Anda.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('satker.surat-keluar.eksternal.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        {{-- Baris 1: No Surat & Tanggal --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-123"></i></span>
                                    <input type="text" name="nomor_surat" 
                                           class="form-control @error('nomor_surat') is-invalid @enderror" 
                                           value="{{ old('nomor_surat') }}" 
                                           placeholder="Contoh: 005/UND/2025" required>
                                    
                                    {{-- Feedback Error Unik --}}
                                    @error('nomor_surat') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_surat" 
                                       class="form-control @error('tanggal_surat') is-invalid @enderror" 
                                       value="{{ old('tanggal_surat', date('Y-m-d')) }}" required>
                                @error('tanggal_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Baris 2: Tujuan Luar --}}
                        <div class="mb-3">
                            <label class="form-label">Tujuan Surat (Pihak Luar) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                <input type="text" name="tujuan_luar" 
                                       class="form-control @error('tujuan_luar') is-invalid @enderror" 
                                       value="{{ old('tujuan_luar') }}" 
                                       placeholder="Contoh: Dinas Pendidikan, PT. Telkom" required>
                                @error('tujuan_luar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-text text-muted small"><i class="bi bi-info-circle me-1"></i> Tuliskan nama instansi atau perseorangan tujuan secara lengkap.</div>
                        </div>

                        {{-- Baris 3: Perihal --}}
                        <div class="mb-3">
                            <label class="form-label">Perihal <span class="text-danger">*</span></label>
                            <textarea name="perihal" class="form-control @error('perihal') is-invalid @enderror" rows="2" required placeholder="Isi perihal surat...">{{ old('perihal') }}</textarea>
                            @error('perihal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Baris 4: File Upload & Preview --}}
                        <div class="mb-4">
                            <label class="form-label">Upload File Surat <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" id="fileInput" 
                                   class="form-control @error('file_surat') is-invalid @enderror" 
                                   accept=".pdf,.jpg,.jpeg,.png" required onchange="previewFile()">
                            <div class="form-text text-muted">Format: PDF/JPG/PNG. Maks: 10MB.</div>
                            @error('file_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            {{-- AREA PREVIEW --}}
                            <div id="preview-container">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="fw-bold text-muted">Preview File:</small>
                                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="resetFile()">Hapus Preview</button>
                                </div>
                                
                                {{-- Preview Gambar --}}
                                <img id="preview-image" src="#" alt="Preview Gambar" style="display: none;">
                                
                                {{-- Preview PDF --}}
                                <iframe id="preview-pdf" src="" style="display: none;"></iframe>
                                
                                {{-- Preview Pesan Error / Info --}}
                                <div id="preview-text" class="text-muted fst-italic py-3" style="display: none;"></div>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="{{ route('satker.surat-keluar.eksternal.index') }}" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-send-fill me-1"></i> Simpan Data
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewFile() {
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('preview-container');
        const previewImage = document.getElementById('preview-image');
        const previewPdf = document.getElementById('preview-pdf');
        const previewText = document.getElementById('preview-text');

        const file = fileInput.files[0];

        if (file) {
            const fileType = file.type;
            const fileURL = URL.createObjectURL(file);

            previewContainer.style.display = 'block';
            
            // Reset Tampilan
            previewImage.style.display = 'none';
            previewPdf.style.display = 'none';
            previewText.style.display = 'none';

            if (fileType.match('image.*')) {
                // Tampilkan Gambar
                previewImage.src = fileURL;
                previewImage.style.display = 'inline-block';
            } else if (fileType === 'application/pdf') {
                // Tampilkan PDF
                previewPdf.src = fileURL;
                previewPdf.style.display = 'block';
            } else {
                // Format lain
                previewText.innerHTML = '<i class="bi bi-file-earmark-break display-4 text-warning"></i><br>Preview tidak tersedia untuk format ini.<br>File: <b>' + file.name + '</b>';
                previewText.style.display = 'block';
            }
        }
    }

    function resetFile() {
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('preview-container');
        
        fileInput.value = ''; // Reset input file
        previewContainer.style.display = 'none'; // Sembunyikan container
    }
</script>
@endpush