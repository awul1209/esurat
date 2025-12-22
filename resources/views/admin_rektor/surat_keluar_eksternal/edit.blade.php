@extends('layouts.app')

@push('styles')
<style>
    /* Styling Form Konsisten */
    .form-label { font-weight: 600; font-size: 0.9rem; color: #495057; margin-bottom: 0.4rem; }
    .card { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); }
    
    /* Header Edit (Nuansa Kuning/Orange seperti Internal) */
    .card-header { 
          background: linear-gradient(135deg, #0b60de 0%, #0b60de 100%); 
        color: white; 
        border-radius: 0.75rem 0.75rem 0 0 !important;
        padding: 1rem 1.5rem;
    }
    .input-group-text { background-color: #f8f9fc; border-color: #d1d3e2; color: #6e707e; }
    .form-control:focus { border-color: #f6c23e; box-shadow: 0 0 0 0.25rem rgba(246, 194, 62, 0.25); }
    
    /* File Preview Box */
    .file-preview-box {
        border: 2px dashed #d1d3e2; padding: 20px; border-radius: 0.5rem; 
        text-align: center; background-color: #f8f9fc; transition: all 0.3s ease;
    }
    #preview-image { max-width: 100%; max-height: 300px; object-fit: contain; border-radius: 4px; }
    #preview-pdf { width: 100%; height: 500px; border: 1px solid #d1d3e2; border-radius: 4px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row">
        <div class="col-12">
            
            <div class="card mb-4">
                {{-- Header Card --}}
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-pencil-square fs-4 me-3 text-white-50"></i>
                    <div>
                        <h5 class="m-0 fw-bold" style="color:white;">Edit Surat Keluar Eksternal</h5>
                        <small class="text-white-50">Perbarui data surat untuk pihak luar.</small>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    
                    {{-- Alert Error Global --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
                            <div class="d-flex">
                                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                                <div><strong>Periksa kembali inputan Anda!</strong></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Form Start --}}
                    <form action="{{ route('adminrektor.surat-keluar-eksternal.update', $surat->id) }}" method="POST" enctype="multipart/form-data" id="formSurat">
                        @csrf
                        @method('PUT') {{-- Method PUT untuk Update --}}
                        
                        {{-- BARIS 1: Nomor Surat & Tanggal Surat --}}
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="text" name="nomor_surat" 
                                           class="form-control @error('nomor_surat') is-invalid @enderror" 
                                           value="{{ old('nomor_surat', $surat->nomor_surat) }}" required>
                                    
                                    @error('nomor_surat') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="tanggal_surat" 
                                           class="form-control @error('tanggal_surat') is-invalid @enderror" 
                                           value="{{ old('tanggal_surat', $surat->tanggal_surat->format('Y-m-d')) }}" required>
                                </div>
                                @error('tanggal_surat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- BARIS 2: Tujuan Eksternal (Manual) & Perihal --}}
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Tujuan Eksternal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    {{-- INPUT MANUAL --}}
                                    <input type="text" name="tujuan_luar" 
                                           class="form-control @error('tujuan_luar') is-invalid @enderror" 
                                           value="{{ old('tujuan_luar', $surat->tujuan_luar) }}" required>
                                </div>
                                @error('tujuan_luar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text text-muted small ms-1">Contoh: Dinas Pendidikan, PT. Telkom, dll.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Perihal / Hal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                    <input type="text" name="perihal" class="form-control @error('perihal') is-invalid @enderror" value="{{ old('perihal', $surat->perihal) }}" required>
                                </div>
                                @error('perihal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- BARIS 3: File Upload & Preview --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="p-3 bg-light rounded border">
                                    <label class="form-label mb-2">
                                        <i class="bi bi-paperclip me-1"></i> File Surat
                                    </label>
                                    
                                    {{-- Info File Lama --}}
                                    @if($surat->file_surat)
                                        <div class="alert alert-secondary py-2 d-flex align-items-center mb-3">
                                            <i class="bi bi-file-earmark-check me-2 fs-5 text-success"></i>
                                            <div class="overflow-hidden">
                                                <small class="text-muted d-block">File saat ini:</small>
                                                <a href="{{ Storage::url($surat->file_surat) }}" target="_blank" class="fw-bold text-dark text-decoration-none text-truncate d-block">
                                                    {{ basename($surat->file_surat) }}
                                                </a>
                                            </div>
                                        </div>
                                    @endif

                                    <label class="small text-muted mb-1">Ganti File (Opsional):</label>
                                    <input type="file" name="file_surat" id="fileInput" 
                                           class="form-control @error('file_surat') is-invalid @enderror" 
                                           accept=".pdf,.jpg,.jpeg,.png" onchange="previewFile()">
                                    
                                    <div class="form-text text-muted small mt-1">Biarkan kosong jika tidak ingin mengubah file.</div>
                                    @error('file_surat') <div class="text-danger small fw-bold">{{ $message }}</div> @enderror

                                    {{-- Preview Area (Untuk File Baru) --}}
                                    <div id="preview-container" style="display: none;" class="file-preview-box mt-3">
                                        <p id="preview-text" class="mb-0 text-muted"><i class="bi bi-eye me-1"></i> Preview File Baru:</p>
                                        <img id="preview-image" src="#" style="display: none;" class="mx-auto mt-2">
                                        <iframe id="preview-pdf" src="" style="display: none;" class="mt-2 mx-auto"></iframe>
                                    </div>
                                    
                                    {{-- Preview File Lama (Jika tidak ada file baru dipilih) --}}
                                    @if($surat->file_surat)
                                        <div id="current-file-preview" class="file-preview-box mt-3">
                                            <p class="mb-2 text-muted fw-bold">Preview File Saat Ini:</p>
                                            @php $ext = pathinfo($surat->file_surat, PATHINFO_EXTENSION); @endphp
                                            
                                            @if(in_array(strtolower($ext), ['jpg','jpeg','png']))
                                                <img src="{{ Storage::url($surat->file_surat) }}" style="max-height: 300px; max-width: 100%; border-radius: 4px;">
                                            @elseif(strtolower($ext) == 'pdf')
                                                <iframe src="{{ Storage::url($surat->file_surat) }}" width="100%" height="500px" style="border:none;"></iframe>
                                            @else
                                                <div class="py-4 text-muted"><i class="bi bi-file-earmark-x fs-1"></i><br>Preview tidak tersedia.</div>
                                            @endif
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>

                        {{-- TOMBOL AKSI --}}
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('adminrektor.surat-keluar-eksternal.index') }}" class="btn btn-light border px-4">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-warning text-white px-4 fw-bold shadow-sm">
                                <i class="bi bi-save me-2"></i> Update Perubahan
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 

<script>
    // Fungsi Preview File Baru (Menimpa tampilan file lama)
    function previewFile() {
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('preview-container');
        const currentFilePreview = document.getElementById('current-file-preview'); // Preview lama
        const previewImage = document.getElementById('preview-image');
        const previewPdf = document.getElementById('preview-pdf');

        const file = fileInput.files[0];

        if (file) {
            // Validasi Ukuran (5MB)
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('File Terlalu Besar', 'Maksimal ukuran file adalah 5MB.', 'warning');
                fileInput.value = ""; 
                resetPreview();
                return;
            }

            // Sembunyikan preview lama jika ada
            if(currentFilePreview) currentFilePreview.style.display = 'none';

            const fileType = file.type;
            const fileURL = URL.createObjectURL(file);

            previewContainer.style.display = 'block';
            previewImage.style.display = 'none';
            previewPdf.style.display = 'none';

            if (fileType.match('image.*')) {
                previewImage.src = fileURL;
                previewImage.style.display = 'block';
            } else if (fileType === 'application/pdf') {
                previewPdf.src = fileURL;
                previewPdf.style.display = 'block';
            }
        } else {
            resetPreview();
        }
    }

    function resetPreview() {
        const previewContainer = document.getElementById('preview-container');
        const currentFilePreview = document.getElementById('current-file-preview');
        
        previewContainer.style.display = 'none';
        
        // Tampilkan kembali preview lama jika user membatalkan upload
        if(currentFilePreview) currentFilePreview.style.display = 'block';
    }
</script>
@endpush