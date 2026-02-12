@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    .form-label { font-weight: 600; font-size: 0.9rem; color: #495057; }
    .card-header { background: linear-gradient(45deg, #f8f9fa, #e9ecef); }
    .select2-container--bootstrap-5 .select2-selection { font-size: 0.85rem; }
    
    #preview-container { 
        display: none; 
        background-color: #f8f9fa; 
        border: 2px dashed #dee2e6; 
        border-radius: 8px; 
        padding: 15px; 
        text-align: center;
        margin-top: 10px;
    }
    #preview-pdf { width: 100%; height: 500px; border: 1px solid #dee2e6; border-radius: 4px; }
    
    .badge-optional { background-color: #e9ecef; color: #6c757d; font-weight: normal; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-box-arrow-up text-primary me-2 fs-5"></i>
                        <h6 class="m-0 fw-bold text-primary">Buat Surat Keluar Eksternal</h6>
                    </div>
                    <span class="badge bg-info">Langkah 1: Isi Data & Upload Draf</span>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('satker.surat-keluar.eksternal.store') }}" method="POST" enctype="multipart/form-data" id="mainForm">
                        @csrf
                        
                        {{-- DATA UTAMA SURAT --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_surat" class="form-control @error('nomor_surat') is-invalid @enderror" value="{{ old('nomor_surat') }}" placeholder="Contoh: 005/UND/2025" required>
                                @error('nomor_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_surat" class="form-control @error('tanggal_surat') is-invalid @enderror" value="{{ old('tanggal_surat', date('Y-m-d')) }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tujuan Surat (Pihak Luar) <span class="text-danger">*</span></label>
                            <input type="text" name="tujuan_luar" class="form-control @error('tujuan_luar') is-invalid @enderror" value="{{ old('tujuan_luar') }}" placeholder="Contoh: Dinas Pendidikan, PT. Telkom" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Perihal <span class="text-danger">*</span></label>
                            <textarea name="perihal" class="form-control" rows="2" required placeholder="Isi perihal surat...">{{ old('perihal') }}</textarea>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-diagram-3-fill me-2"></i>Alur Validasi & Tembusan <span class="badge badge-optional ms-1">Opsional</span></h6>

                        <div class="row g-3 mb-4">
                            {{-- PILIH PIMPINAN (MENGETAHUI) --}}
                            <div class="col-md-6">
                                <label class="form-label text-primary">Mengetahui / Validasi</label>
                                <select name="pimpinan_ids[]" id="select-pimpinan" class="form-select" multiple="multiple">
                                    @foreach($pimpinans as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->jabatan->nama_jabatan }})</option>
                                    @endforeach
                                </select>
                                <div class="form-text mt-1" style="font-size: 11px;">Kosongkan jika tidak memerlukan validasi pimpinan.</div>
                            </div>

                            {{-- PILIH TEMBUSAN --}}
                            <div class="col-md-6">
                                <label class="form-label text-info">Tembusan Tambahan</label>
                                <select name="tembusan_ids[]" id="select-tembusan" class="form-select" multiple="multiple">
                                    <optgroup label="Pimpinan / Pejabat">
                                        @foreach($pimpinans as $p)
                                            <option value="user_{{ $p->id }}">{{ $p->name }} ({{ $p->jabatan->nama_jabatan }})</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Unit / Satuan Kerja">
                                        @foreach($satkers as $s)
                                            @if($s->nama_satker != 'BAU') 
                                                <option value="satker_{{ $s->id }}">{{ $s->nama_satker }}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                </select>
                                <div class="form-text mt-1" style="font-size: 11px;">BAU otomatis menerima tembusan.</div>
                            </div>
                        </div>

                        {{-- FILE UPLOAD --}}
                        <div class="mb-4">
                            <label class="form-label">Upload File Surat (Draf PDF) <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" id="fileInput" class="form-control" accept=".pdf" required onchange="previewFile()">
                            <div id="preview-container">
                                <div class="alert alert-warning py-2 small mb-2"><i class="bi bi-info-circle me-1"></i> Setelah klik simpan, Anda akan diarahkan ke halaman <b>Bubuhkan Barcode</b>.</div>
                                <iframe id="preview-pdf" src="" style="display: none;"></iframe>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="{{ route('satker.surat-keluar.eksternal.index') }}" class="btn btn-outline-secondary px-4">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-arrow-right-circle-fill me-1"></i> Simpan & Atur Barcode
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
    $('#select-pimpinan').select2({
        theme: 'bootstrap-5',
        placeholder: " Pilih Pimpinan (Opsional)",
        allowClear: true
    });

    $('#select-tembusan').select2({
        theme: 'bootstrap-5',
        placeholder: " Pilih Unit Tembusan (Opsional)",
        allowClear: true
    });
});

function previewFile() {
    const file = document.getElementById('fileInput').files[0];
    const previewContainer = document.getElementById('preview-container');
    const previewPdf = document.getElementById('preview-pdf');

    if (file && file.type === 'application/pdf') {
        const fileURL = URL.createObjectURL(file);
        previewContainer.style.display = 'block';
        previewPdf.src = fileURL;
        previewPdf.style.display = 'block';
    }
}
</script>
@endpush