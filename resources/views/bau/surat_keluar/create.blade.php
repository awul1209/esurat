@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* Paksa warna merah untuk feedback error */
    .invalid-feedback { 
        font-size: 0.85rem; 
        color: #dc3545; /* Merah Bootstrap */
        display: none; /* Default sembunyi, nanti JS yang show */
    }
    /* Style Preview */
    #file-preview-container {
        border: 1px dashed #ced4da;
        padding: 10px;
        border-radius: 5px;
        min-height: 100px;
        display: none;
        text-align: center;
        background-color: #f8f9fa;
        margin-top: 10px;
    }
    #file-preview-image { max-width: 100%; max-height: 200px; object-fit: contain; }
    #file-preview-pdf { width: 100%; height: 250px; border: none; }
</style>
@endpush

@section('content')

@php
    $tipe = request('type', 'internal');
    // Jika old('tipe_kirim') ada (karena gagal validasi), pakai itu.
    if(old('tipe_kirim')) {
        $tipe = old('tipe_kirim');
    }
    
    $labelTipe = ($tipe == 'eksternal') ? 'Eksternal' : 'Internal';
    $routeBatal = ($tipe == 'eksternal') ? route('bau.surat-keluar.eksternal') : route('bau.surat-keluar.internal');
@endphp

<div class="container-fluid px-4">
    <div class="card shadow-sm border-0 mb-4 mt-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">
                <i class="bi bi-send-plus-fill me-2"></i>Buat Surat Keluar {{ $labelTipe }} (BAU)
            </h6>
        </div>
        <div class="card-body">
            
            {{-- Alert Error Global (Jika ada error validasi server) --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Gagal Menyimpan!</strong> Silakan perbaiki isian di bawah ini.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Alert Error Client Side (JS) --}}
            <div id="client-alert" class="alert alert-warning alert-dismissible fade show d-none" role="alert">
                <strong>Perhatian!</strong> Harap perbaiki isian yang salah (cek file atau nomor surat).
                <button type="button" class="btn-close" onclick="$('#client-alert').addClass('d-none')"></button>
            </div>

            <form action="{{ route('bau.surat-keluar.store') }}" method="POST" enctype="multipart/form-data" id="formSuratKeluar">
                @csrf
                <input type="hidden" name="tipe_kirim" value="{{ $tipe }}">

                {{-- BARIS 1: Nomor Surat & Tujuan --}}
                <div class="row">
                    <div class="col-md-6 mb-3 position-relative">
                        <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                        {{-- PERBAIKAN: value="{{ old('nomor_surat') }}" agar tidak hilang --}}
                        <input type="text" name="nomor_surat" id="nomor_surat" 
                               class="form-control @error('nomor_surat') is-invalid @enderror" 
                               value="{{ old('nomor_surat') }}" 
                               placeholder="Contoh: 005/BAU/2025" required>
                        
                        {{-- Pesan Error JS (Client Side) --}}
                        <div class="invalid-feedback" id="error-nomor_surat_js">
                            Nomor surat ini sudah ada di database!
                        </div>
                        
                        {{-- Pesan Error Laravel (Server Side) --}}
                        @error('nomor_surat')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        @if($tipe == 'internal')
                            <label class="form-label">Tujuan Surat (Internal) <span class="text-danger">*</span></label>
                            <select name="tujuan_satker_ids[]" class="form-select select2" multiple="multiple" required>
                                <optgroup label="Pimpinan">
                                    <option value="rektor" {{ collect(old('tujuan_satker_ids'))->contains('rektor') ? 'selected' : '' }}>Rektor/Universitas</option>
                                </optgroup>
                                <optgroup label="Satuan Kerja">
                                    @foreach($daftarSatker as $satker)
                                        {{-- PERBAIKAN: Logic 'selected' agar pilihan tidak hilang --}}
                                        <option value="{{ $satker->id }}" {{ collect(old('tujuan_satker_ids'))->contains($satker->id) ? 'selected' : '' }}>
                                            {{ $satker->nama_satker }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        @else
                            <label class="form-label">Tujuan Surat (Eksternal) <span class="text-danger">*</span></label>
                            <input type="text" name="tujuan_luar" class="form-control @error('tujuan_luar') is-invalid @enderror" 
                                   value="{{ old('tujuan_luar') }}" 
                                   placeholder="Contoh: Dinas Pendidikan, PT. Telkom" required>
                        @endif
                    </div>
                </div>

                {{-- BARIS 2: Perihal & Tanggal --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Perihal <span class="text-danger">*</span></label>
                        <input type="text" name="perihal" class="form-control @error('perihal') is-invalid @enderror" 
                               value="{{ old('perihal') }}" required>
                        @error('perihal')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_surat" class="form-control @error('tanggal_surat') is-invalid @enderror" 
                               value="{{ old('tanggal_surat', date('Y-m-d')) }}" required>
                    </div>
                </div>

                {{-- BARIS 3: File Surat --}}
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">File Surat (PDF/Gambar, Max 5MB) <span class="text-danger">*</span></label>
                        <input type="file" name="file_surat" id="file_surat" class="form-control @error('file_surat') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                        
                        {{-- DIV ALERT ERROR FILE (JS) --}}
                        <div class="invalid-feedback" id="error-file_surat_js"></div>

                        {{-- Error Server Side --}}
                        @error('file_surat')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        {{-- Area Preview --}}
                        <div id="file-preview-container">
                            <p class="text-muted small mb-0" id="preview-text">Preview File</p>
                            <img id="file-preview-image" src="" alt="Preview" style="display: none;">
                            <iframe id="file-preview-pdf" src="" style="display: none;"></iframe>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="{{ $routeBatal }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" id="btn-submit" class="btn btn-primary px-4">
                        <i class="bi bi-send-fill me-1"></i> Kirim Surat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        
        // 1. Init Select2
        if ($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap-5', placeholder: 'Pilih Tujuan...', allowClear: true });
        }

        // 2. Cek Duplikat Nomor Surat (AJAX)
        $('#nomor_surat').on('blur', function() {
            var val = $(this).val();
            var input = $(this);
            var errDiv = $('#error-nomor_surat_js');
            
            if(val.length < 3) return;

            $.ajax({
                url: "{{ route('bau.surat-keluar.checkDuplicate') }}", 
                type: "POST",
                data: { _token: "{{ csrf_token() }}", value: val },
                success: function(res) {
                    if(res.exists) {
                        input.addClass('is-invalid');
                        errDiv.show(); // PAKSA MUNCUL
                        $('#btn-submit').prop('disabled', true);
                        $('#client-alert').removeClass('d-none');
                    } else {
                        input.removeClass('is-invalid');
                        errDiv.hide(); // SEMBUNYIKAN
                        // Cek error lain sebelum enable tombol
                        if($('.is-invalid').length === 0) $('#btn-submit').prop('disabled', false);
                        $('#client-alert').addClass('d-none');
                    }
                }
            });
        });

        // 3. Validasi & Preview File
        $('#file_surat').on('change', function(e) {
            var file = e.target.files[0];
            var input = $(this);
            var errDiv = $('#error-file_surat_js');
            var container = $('#file-preview-container');
            var imgPreview = $('#file-preview-image');
            var pdfPreview = $('#file-preview-pdf');
            var btn = $('#btn-submit');

            // Reset State
            input.removeClass('is-invalid');
            errDiv.hide(); // Sembunyikan error lama
            btn.prop('disabled', false);
            container.hide(); // Sembunyikan preview lama

            if(file) {
                // A. Cek Ukuran (5MB)
                if(file.size > 5242880) { 
                    input.addClass('is-invalid');
                    errDiv.text('Ukuran file terlalu besar! Maksimal 5MB.').show(); // Force Show
                    btn.prop('disabled', true);
                    return; // Stop
                }
                
                // B. Cek Tipe (Support jpg dan jpeg)
                var validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                if(!validTypes.includes(file.type)) {
                    input.addClass('is-invalid');
                    errDiv.text('Format file salah! Harus PDF, JPG, JPEG, atau PNG.').show(); // Force Show
                    btn.prop('disabled', true);
                    return; // Stop
                }

                // C. Jika Lolos -> Tampilkan Preview
                container.show();
                var reader = new FileReader();
                reader.onload = function(ev) {
                    if(file.type.startsWith('image/')) {
                        imgPreview.attr('src', ev.target.result).show();
                        pdfPreview.hide();
                    } else {
                        pdfPreview.attr('src', ev.target.result).show();
                        imgPreview.hide();
                    }
                }
                reader.readAsDataURL(file);
            }
        });

    });
</script>
@endpush