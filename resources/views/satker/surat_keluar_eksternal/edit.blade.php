@extends('layouts.app')

@push('styles')
<style>
    .form-label { font-weight: 600; font-size: 0.9rem; color: #495057; }
    .card-header { background: linear-gradient(45deg, #f8f9fa, #e9ecef); }
    
    /* Area Preview Upload Baru */
    #preview-container { 
        display: none; 
        background-color: #f8f9fa; 
        border: 2px dashed #dee2e6; 
        border-radius: 8px; 
        padding: 15px; 
        text-align: center;
        margin-top: 10px;
    }
    #preview-image { max-width: 100%; max-height: 400px; object-fit: contain; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    #preview-pdf { width: 100%; height: 500px; border: 1px solid #dee2e6; border-radius: 4px; }
    
    /* File Lama Info */
    .current-file-box {
        background-color: #e9ecef;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 10px;
        font-size: 0.9rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header py-3 border-bottom d-flex align-items-center">
                    <i class="bi bi-pencil-square text-warning me-2 fs-5"></i>
                    <h6 class="m-0 fw-bold text-primary">Edit Surat Keluar Eksternal</h6>
                </div>
                
                <div class="card-body p-4">
                    
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Periksa kembali inputan Anda.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('satker.surat-keluar.eksternal.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT') 
                        
                        {{-- Baris 1: No Surat & Tanggal --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-123"></i></span>
                                    <input type="text" name="nomor_surat" 
                                           class="form-control @error('nomor_surat') is-invalid @enderror" 
                                           value="{{ old('nomor_surat', $surat->nomor_surat) }}" required>
                                    @error('nomor_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_surat" 
                                       class="form-control @error('tanggal_surat') is-invalid @enderror" 
                                       value="{{ old('tanggal_surat', $surat->tanggal_surat->format('Y-m-d')) }}" required>
                                @error('tanggal_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Baris 2: Tujuan Luar (Manual Input) --}}
                        <div class="mb-3">
                            <label class="form-label">Tujuan Surat (Pihak Luar) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                <input type="text" name="tujuan_luar" 
                                       class="form-control @error('tujuan_luar') is-invalid @enderror" 
                                       value="{{ old('tujuan_luar', $surat->tujuan_luar) }}" required>
                                @error('tujuan_luar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-text text-muted small"><i class="bi bi-info-circle me-1"></i> Ubah nama instansi atau tujuan jika diperlukan.</div>
                        </div>

                        {{-- Baris 3: Perihal --}}
                        <div class="mb-3">
                            <label class="form-label">Perihal <span class="text-danger">*</span></label>
                            <textarea name="perihal" class="form-control @error('perihal') is-invalid @enderror" rows="2" required>{{ old('perihal', $surat->perihal) }}</textarea>
                            @error('perihal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Baris 4: File Upload & Preview --}}
                        <div class="mb-4">
                            <label class="form-label">File Surat</label>
                            
                            {{-- Info File Saat Ini (Trigger Modal) --}}
                            @if($surat->file_surat)
                                <div class="current-file-box">
                                    <span>
                                        <i class="bi bi-file-earmark-check text-success me-2"></i> 
                                        File saat ini: <strong>Tersedia</strong>
                                    </span>
                                    
                                    {{-- TOMBOL PEMICU MODAL --}}
                                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#existingFileModal"
                                            data-file-url="{{ asset('storage/' . $surat->file_surat) }}">
                                        <i class="bi bi-eye me-1"></i> Lihat File Lama
                                    </button>
                                </div>
                            @endif

                            <input type="file" name="file_surat" id="fileInput" 
                                   class="form-control @error('file_surat') is-invalid @enderror" 
                                   accept=".pdf,.jpg,.jpeg,.png" onchange="previewNewFile()">
                            <div class="form-text text-muted">Upload file baru HANYA jika ingin mengganti file lama. (Max: 10MB)</div>
                            @error('file_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            {{-- Area Preview File Baru --}}
                            <div id="preview-container">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="fw-bold text-muted">Preview File Baru:</small>
                                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="resetFile()">Batal Ganti</button>
                                </div>
                                <img id="preview-image" src="#" alt="Preview Gambar" style="display: none;">
                                <iframe id="preview-pdf" src="" style="display: none;"></iframe>
                                <div id="preview-text" class="text-muted fst-italic py-3" style="display: none;"></div>
                            </div>
                        </div>
                        
                        {{-- Tombol Aksi --}}
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="{{ route('satker.surat-keluar.eksternal.index') }}" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-warning text-white px-4 shadow-sm">
                                <i class="bi bi-save-fill me-1"></i> Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW FILE LAMA --}}
<div class="modal fade" id="existingFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark">Preview File Lama</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-secondary bg-opacity-10">
                <div id="existing-file-viewer" class="d-flex align-items-center justify-content-center" style="height: 75vh; width: 100%;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btn-download-existing" class="btn btn-primary" download target="_blank">
                    <i class="bi bi-download me-1"></i> Download File
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        // --- LOGIKA MODAL PREVIEW FILE LAMA ---
        var existingFileModal = document.getElementById('existingFileModal');
        
        existingFileModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file-url');
            
            var container = existingFileModal.querySelector('#existing-file-viewer');
            var downloadBtn = existingFileModal.querySelector('#btn-download-existing');

            // Set link download
            downloadBtn.href = fileUrl;
            
            // Tampilkan Loading
            container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

            if(fileUrl) {
                var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0];
                setTimeout(function() {
                    if (extension === 'pdf') {
                        container.innerHTML = `<iframe src="${fileUrl}" width="100%" height="100%" style="border:none;"></iframe>`;
                    } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
                        container.innerHTML = `<img src="${fileUrl}" class="img-fluid shadow-sm rounded" style="max-height: 95%; max-width: 95%; object-fit: contain;">`;
                    } else {
                        container.innerHTML = `<div class="text-center p-5 bg-white rounded shadow-sm"><i class="bi bi-file-earmark-break h1 text-warning d-block mb-3" style="font-size: 3rem;"></i><h5 class="text-muted">Preview tidak tersedia</h5><p class="text-secondary small">Silakan unduh file untuk melihat isinya.</p></div>`;
                    }
                }, 300);
            }
        });

        // Bersihkan saat modal ditutup
        existingFileModal.addEventListener('hidden.bs.modal', function () {
            var container = existingFileModal.querySelector('#existing-file-viewer');
            container.innerHTML = '';
        });
    });

    // --- FUNGSI PREVIEW FILE BARU (Saat Upload) ---
    function previewNewFile() {
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
                previewImage.src = fileURL;
                previewImage.style.display = 'inline-block';
            } else if (fileType === 'application/pdf') {
                previewPdf.src = fileURL;
                previewPdf.style.display = 'block';
            } else {
                previewText.innerHTML = '<i class="bi bi-file-earmark-break display-4 text-warning"></i><br>Preview tidak tersedia.<br>File: <b>' + file.name + '</b>';
                previewText.style.display = 'block';
            }
        }
    }

    function resetFile() {
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('preview-container');
        
        fileInput.value = ''; // Reset input
        previewContainer.style.display = 'none'; // Sembunyikan preview
    }
</script>
@endpush