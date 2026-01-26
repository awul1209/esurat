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

<div class="container-fluid px-3">
    <div class="card shadow-sm border-0 mb-4 mt-2">
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
        <input type="text" name="nomor_surat" id="nomor_surat" 
               class="form-control @error('nomor_surat') is-invalid @enderror" 
               value="{{ old('nomor_surat') }}" 
               placeholder="Contoh: 005/BAU/2025" required>
        <div class="invalid-feedback" id="error-nomor_surat_js">Nomor surat ini sudah ada!</div>
        @error('nomor_surat')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        @if($tipe == 'internal')
            <label class="form-label">Tipe Tujuan <span class="text-danger">*</span></label>
            <div class="mb-2">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tujuan_tipe" id="tipe_satker" value="satker" checked>
                    <label class="form-check-label" for="tipe_satker">Satuan Kerja (Umum)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tujuan_tipe" id="tipe_pegawai" value="pegawai">
                    <label class="form-check-label" for="tipe_pegawai">Pegawai Spesifik (Personal)</label>
                </div>
            </div>

            {{-- Container Dropdown Satker --}}
            <div id="container-satker">
                <label class="form-label">Pilih Satker Tujuan</label>
                <select name="tujuan_satker_ids[]" id="select-satker" class="form-select select2" multiple="multiple">
                    <optgroup label="Pimpinan">
                        <option value="rektor">Rektor/Universitas</option>
                    </optgroup>
                    <optgroup label="Satuan Kerja">
                        @foreach($daftarSatker as $satker)
                            <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            {{-- Container Dropdown Pegawai (Hidden by Default) --}}
            <div id="container-pegawai" class="d-none mt-2">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Filter Satker Pegawai</label>
                        <select id="filter-satker-pegawai" class="form-select form-select-sm">
                            <option value="">-- Pilih Satker --</option>
                            @foreach($daftarSatker as $satker)
                                <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Pilih Pegawai <span class="text-danger">*</span></label>
                        <select name="tujuan_user_id" id="select-pegawai" class="form-select select2" style="width: 100%">
                            <option value="">-- Pilih Pegawai --</option>
                        </select>
                    </div>
                </div>
            </div>
        @else
            <label class="form-label">Tujuan Surat (Eksternal) <span class="text-danger">*</span></label>
            <input type="text" name="tujuan_luar" class="form-control" value="{{ old('tujuan_luar') }}" placeholder="Dinas Pendidikan..." required>
        @endif
    </div>
</div>

                {{-- BARIS 2: Perihal & Tanggal --}}
               {{-- BARIS 2: Perihal, No. Agenda (Kondisional), & Tanggal --}}
<div class="row">
    {{-- Input Perihal --}}
    <div class="col-md-6 mb-3">
        <label class="form-label">Perihal <span class="text-danger">*</span></label>
        <input type="text" name="perihal" class="form-control @error('perihal') is-invalid @enderror" 
               value="{{ old('perihal') }}" required>
        @error('perihal')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Input Tanggal --}}
    <div class="col-md-6 mb-3">
        <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
        <input type="date" name="tanggal_surat" class="form-control @error('tanggal_surat') is-invalid @enderror" 
               value="{{ old('tanggal_surat', date('Y-m-d')) }}" required>
    </div>
</div>

{{-- INPUT NO AGENDA (KHUSUS REKTOR) --}}
{{-- Default hidden (d-none). Akan muncul via JS jika tujuan = Rektor --}}
<div class="row d-none" id="row-no-agenda">
    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold text-primary">
            Nomor Agenda (Khusus Rektor) <span class="text-danger">*</span>
        </label>
        <input type="text" name="no_agenda" id="input-no-agenda" 
               class="form-control @error('no_agenda') is-invalid @enderror"
               value="{{ old('no_agenda') }}"
               placeholder="Masukkan Nomor Agenda Khusus...">
        <small class="text-muted fst-italic">
            Wajib diisi karena surat ditujukan kepada Rektor/Universitas.
        </small>
        @error('no_agenda')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
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
            // Ambil elemen
    var selectTujuan = $('select[name="tujuan_satker_ids[]"]');
    var rowAgenda = $('#row-no-agenda');
    var inputAgenda = $('#input-no-agenda');

    // Fungsi Cek Tujuan Rektor
    function checkTujuanRektor() {
        // Ambil value yang dipilih (array)
        var selectedValues = selectTujuan.val();

        // Cek apakah 'rektor' ada di dalam array pilihan
        if (selectedValues && selectedValues.includes('rektor')) {
            rowAgenda.removeClass('d-none'); // Tampilkan
            inputAgenda.prop('required', true); // Wajib diisi
        } else {
            rowAgenda.addClass('d-none'); // Sembunyikan
            inputAgenda.prop('required', false); // Tidak wajib
            inputAgenda.val(''); // Reset nilai (opsional)
        }
    }

    // Jalankan saat Select2 berubah
    selectTujuan.on('change', checkTujuanRektor);

    // Jalankan sekali saat halaman dimuat (untuk handle old input saat validasi gagal)
    checkTujuanRektor();
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

    $(document).ready(function() {
    // 1. Logika Toggle Tipe Tujuan
    $('input[name="tujuan_tipe"]').on('change', function() {
        if ($(this).val() === 'pegawai') {
            $('#container-satker').addClass('d-none');
            $('#container-pegawai').removeClass('d-none');
            $('#select-satker').val(null).trigger('change').prop('required', false);
            $('#select-pegawai').prop('required', true);
        } else {
            $('#container-satker').removeClass('d-none');
            $('#container-pegawai').addClass('d-none');
            $('#select-pegawai').val(null).trigger('change').prop('required', false);
            $('#select-satker').prop('required', true);
        }
    });

    // 2. AJAX Dependent Dropdown: Ambil Pegawai berdasarkan Satker
    $('#filter-satker-pegawai').on('change', function() {
        let satkerId = $(this).val();
        let $pegawaiSelect = $('#select-pegawai');

        // Reset dropdown pegawai
        $pegawaiSelect.html('<option value="">Sedang memuat...</option>');

        if (satkerId) {
            $.ajax({
               url: "{{ route('bau.surat-keluar.get-pegawai-by-satker') }}",
                type: "GET",
                data: { satker_id: satkerId },
                success: function(response) {
                    $pegawaiSelect.html('<option value="">-- Pilih Pegawai --</option>');
                    $.each(response, function(key, item) {
                        $pegawaiSelect.append(`<option value="${item.id}">${item.name} (${item.role})</option>`);
                    });
                },
                error: function() {
                    alert('Gagal mengambil data pegawai.');
                }
            });
        } else {
            $pegawaiSelect.html('<option value="">-- Pilih Pegawai --</option>');
        }
    });

    // 3. Logika Munculkan Nomor Agenda jika Rektor dipilih
    $('#select-satker').on('change', function() {
        let selectedValues = $(this).val() || [];
        if (selectedValues.includes('rektor')) {
            $('#row-no-agenda').removeClass('d-none');
            $('#input-no-agenda').prop('required', true);
        } else {
            $('#row-no-agenda').addClass('d-none');
            $('#input-no-agenda').prop('required', false).val('');
        }
    });
});
</script>

@endpush