@extends('layouts.app')

@push('styles')
<style>
    /* Styling Form (Sama dengan Internal) */
    .form-label { font-weight: 600; font-size: 0.9rem; color: #495057; margin-bottom: 0.4rem; }
    .card { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); }
    /* Warna Header Beda Sedikit (Hijau/Teal) untuk membedakan Eksternal */
    .card-header { 
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); 
        color: white; 
        border-radius: 0.75rem 0.75rem 0 0 !important;
        padding: 1rem 1.5rem;
    }
    
    .input-group-text { background-color: #f8f9fc; border-color: #d1d3e2; color: #6e707e; }
    .form-control:focus { border-color: #1cc88a; box-shadow: 0 0 0 0.25rem rgba(28, 200, 138, 0.25); }
    
    /* Preview Container */
    #preview-container { 
        display: none; 
        margin-top: 15px; 
        border: 2px dashed #d1d3e2; 
        padding: 20px; 
        border-radius: 0.5rem; 
        text-align: center; 
        background-color: #f8f9fc;
        transition: all 0.3s ease;
    }
    #preview-image { max-width: 100%; max-height: 300px; object-fit: contain; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    #preview-pdf { width: 100%; height: 500px; border: 1px solid #d1d3e2; border-radius: 4px; }
    #preview-text { font-size: 0.95rem; color: #858796; font-weight: 500; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row">
        <div class="col-12"> 
            
            <div class="card mb-4">
                {{-- Header Card --}}
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-globe2 fs-4 me-3 text-white-50"></i>
                    <div>
                        <h5 class="m-0 fw-bold" style="color:white;">Buat Surat Keluar Eksternal</h5>
                        <small class="text-white-50">Kirim surat ke Instansi Luar atau Pihak Eksternal.</small>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    
                    {{-- Alert Error Global --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
                            <div class="d-flex">
                                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                                <div>
                                    <strong>Terjadi Kesalahan!</strong> Mohon periksa kembali inputan Anda.
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('adminrektor.surat-keluar-eksternal.store') }}" method="POST" enctype="multipart/form-data" id="formSurat">
                        @csrf
                        
                        {{-- BARIS 1: Nomor Surat & Tanggal Surat --}}
                        <div class="row g-4 mb-4">
                            {{-- Input 1: Nomor Surat --}}
                            <div class="col-md-6">
                                <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="text" name="nomor_surat" 
                                           class="form-control @error('nomor_surat') is-invalid @enderror" 
                                           value="{{ old('nomor_surat') }}" 
                                           placeholder="Contoh: 001/EKSTERNAL/2025" required>
                                    
                                    @error('nomor_surat') 
                                        <div class="invalid-feedback">
                                            <strong>{{ $message }}</strong> (Nomor surat ini sudah terdaftar).
                                        </div> 
                                    @enderror
                                </div>
                            </div>

                            {{-- Input 2: Tanggal Surat --}}
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="tanggal_surat" 
                                           class="form-control @error('tanggal_surat') is-invalid @enderror" 
                                           value="{{ old('tanggal_surat', date('Y-m-d')) }}" required>
                                </div>
                                @error('tanggal_surat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- BARIS 2: Tujuan Eksternal (Manual) & Perihal --}}
                        <div class="row g-4 mb-4">
                            {{-- Input 1: Tujuan Eksternal --}}
                            <div class="col-md-6">
                                <label class="form-label">Tujuan Eksternal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    {{-- INPUT MANUAL (Bukan Select2) --}}
                                    <input type="text" name="tujuan_luar" 
                                           class="form-control @error('tujuan_luar') is-invalid @enderror" 
                                           value="{{ old('tujuan_luar') }}" 
                                           placeholder="Contoh: Kementerian Pendidikan, PT. Telkom..." required>
                                </div>
                                @error('tujuan_luar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text text-muted small ms-1">Ketik nama instansi atau penerima tujuan.</div>
                            </div>

                            {{-- Input 2: Perihal --}}
                            <div class="col-md-6">
                                <label class="form-label">Perihal / Hal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                    <input type="text" name="perihal" class="form-control @error('perihal') is-invalid @enderror" value="{{ old('perihal') }}" placeholder="Contoh: Permohonan Kerjasama..." required>
                                </div>
                                @error('perihal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- BARIS 3: File Upload --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="p-3 bg-light rounded border">
                                    <label class="form-label mb-2"><i class="bi bi-paperclip me-1"></i> Upload File Surat <span class="text-danger">*</span></label>
                                    
                                    <input type="file" name="file_surat" id="fileInput" 
                                           class="form-control @error('file_surat') is-invalid @enderror" 
                                           accept=".pdf,.jpg,.jpeg,.png" required onchange="previewFile()">
                                    
                                    <div class="d-flex justify-content-between mt-1">
                                        <div class="form-text text-muted small">Format: <strong>PDF, JPG, PNG</strong>. Maks: <strong>5MB</strong>.</div>
                                        @error('file_surat') <div class="text-danger small fw-bold">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- Preview Area --}}
                                    <div id="preview-container">
                                        <p id="preview-text" class="mb-0 text-muted"><i class="bi bi-eye-slash me-1"></i> Belum ada file dipilih.</p>
                                        <img id="preview-image" src="#" alt="Preview Gambar" style="display: none;" class="mx-auto mt-2">
                                        <iframe id="preview-pdf" src="" style="display: none;" class="mt-2 mx-auto"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TOMBOL AKSI --}}
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('adminrektor.surat-keluar-eksternal.index') }}" class="btn btn-light border px-4">
                                <i class="bi bi-x-lg me-1"></i> Batal
                            </a>
                            <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" onclick="konfirmasiKirim()">
                                <i class="bi bi-send-fill me-2"></i> Simpan Surat
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
{{-- Tidak perlu Select2 JS karena input manual --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 

<script>
    // Fungsi Preview File (Sama persis)
    function previewFile() {
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('preview-container');
        const previewImage = document.getElementById('preview-image');
        const previewPdf = document.getElementById('preview-pdf');
        const previewText = document.getElementById('preview-text');

        const file = fileInput.files[0];

        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('File Terlalu Besar', 'Maksimal ukuran file adalah 5MB.', 'warning');
                fileInput.value = ""; 
                resetPreview();
                return;
            }

            const fileType = file.type;
            const fileURL = URL.createObjectURL(file);

            previewContainer.style.display = 'block';
            previewText.style.display = 'none';
            previewImage.style.display = 'none';
            previewPdf.style.display = 'none';

            if (fileType.match('image.*')) {
                previewImage.src = fileURL;
                previewImage.style.display = 'block';
            } else if (fileType === 'application/pdf') {
                previewPdf.src = fileURL;
                previewPdf.style.display = 'block';
            } else {
                previewText.style.display = 'block';
                previewText.innerHTML = `<div class="py-3"><i class="bi bi-file-earmark-check-fill text-success fs-1"></i><br><span class="fw-bold text-dark">${file.name}</span><br><small class="text-muted">Preview tidak tersedia.</small></div>`;
            }
        } else {
            resetPreview();
        }
    }

    function resetPreview() {
        const previewContainer = document.getElementById('preview-container');
        previewContainer.style.display = 'none';
    }

    // Konfirmasi Kirim (SweetAlert)
    function konfirmasiKirim() {
        if(!document.getElementById('formSurat').checkValidity()) {
            document.getElementById('formSurat').reportValidity();
            return;
        }

        Swal.fire({
            title: 'Simpan Surat?',
            text: "Pastikan data surat keluar eksternal sudah benar.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1cc88a', // Hijau agar sesuai tema eksternal
            cancelButtonColor: '#858796',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formSurat').submit();
            }
        });
    }
</script>
@endpush