@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .form-label { font-weight: 600; font-size: 0.9rem; color: #495057; }
    .card-header { background: linear-gradient(45deg, #f8f9fa, #e9ecef); }
    #preview-container { display: none; margin-top: 10px; border: 1px dashed #ced4da; padding: 10px; border-radius: 5px; text-align: center; }
    #preview-image { max-width: 50%; max-height: 150px; object-fit: contain; }
    #preview-text { font-size: 0.9rem; color: #6c757d; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row justify-content-center">
        <div class="col-lg-12"> {{-- Diperlebar sedikit agar muat 1 baris --}}
            <div class="card shadow border-0 rounded-3">
                <div class="card-header py-3 border-bottom d-flex align-items-center">
                    <i class="bi bi-send-check-fill text-primary me-2 fs-5"></i>
                    <h6 class="m-0 fw-bold text-primary">Buat Surat Keluar Internal</h6>
                </div>
                
                <div class="card-body p-4">
                    
                    {{-- Alert General Error --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Terdapat kesalahan pada inputan Anda.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('satker.surat-keluar.internal.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        {{-- Baris 1: No Surat & Tanggal --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-123"></i></span>
                                    <input type="text" name="nomor_surat" 
                                           class="form-control @error('nomor_surat') is-invalid @enderror" 
                                           value="{{ old('nomor_surat') }}" required placeholder="Contoh: 001/SATKER/2025">
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

                        {{-- Baris 2: Tujuan & Perihal (SEJAJAR 1 BARIS) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tujuan Surat <span class="text-danger">*</span></label>
                                <select name="tujuan_satker_ids[]" class="form-select select2 @error('tujuan_satker_ids') is-invalid @enderror" multiple="multiple" required>
                                    
                                    <optgroup label="Pimpinan (Via BAU)">
                                        <option value="universitas" {{ (collect(old('tujuan_satker_ids'))->contains('universitas')) ? 'selected' : '' }}>Rektor / Universitas</option>
                                    </optgroup>

                                    <optgroup label="Satuan Kerja (Langsung)">
                                        @foreach($daftarSatker as $satker)
                                            <option value="{{ $satker->id }}" {{ (collect(old('tujuan_satker_ids'))->contains($satker->id)) ? 'selected' : '' }}>
                                                {{ $satker->nama_satker }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                
                                @error('tujuan_satker_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                <div class="form-text text-muted small"><i class="bi bi-info-circle me-1"></i> Rektor (Via BAU) atau Satker (Langsung).</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Perihal / Hal <span class="text-danger">*</span></label>
                                <textarea name="perihal" class="form-control @error('perihal') is-invalid @enderror" rows="1" style="height: 80px;" required placeholder="Isi perihal surat...">{{ old('perihal') }}</textarea>
                                @error('perihal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Baris 3: File Upload & Preview --}}
                        <div class="mb-4">
                            <label class="form-label">Upload File Surat <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" id="fileInput" class="form-control @error('file_surat') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required onchange="previewFile()">
                            <div class="form-text text-muted">Format: PDF/JPG/PNG. Maks: 5MB.</div>
                            @error('file_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            {{-- Area Preview --}}
                            <div id="preview-container">
                                <p id="preview-text" class="mb-0">Preview akan muncul di sini</p>
                                <img id="preview-image" src="#" alt="Preview Gambar" style="display: none;">
                                <iframe id="preview-pdf" src="" width="50%" height="250px" style="display: none; border: none;"></iframe>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="{{ route('satker.surat-keluar.internal') }}" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-send-fill me-1"></i> Kirim Surat
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Init Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: 'Cari dan Pilih Tujuan...',
            allowClear: true,
            width: '100%' 
        });
    });

    // Fungsi Preview File
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
            previewText.style.display = 'none';

            if (fileType.match('image.*')) {
                // Tampilkan Gambar
                previewImage.src = fileURL;
                previewImage.style.display = 'inline-block';
                previewPdf.style.display = 'none';
            } else if (fileType === 'application/pdf') {
                // Tampilkan PDF
                previewPdf.src = fileURL;
                previewPdf.style.display = 'block';
                previewImage.style.display = 'none';
            } else {
                // Format lain (hanya teks info)
                previewImage.style.display = 'none';
                previewPdf.style.display = 'none';
                previewText.style.display = 'block';
                previewText.innerHTML = '<i class="bi bi-file-earmark-text fs-1"></i><br>File terpilih: ' + file.name;
            }
        } else {
            // Reset jika batal pilih
            previewContainer.style.display = 'none';
            previewImage.src = '#';
            previewPdf.src = '';
        }
    }
</script>
@endpush