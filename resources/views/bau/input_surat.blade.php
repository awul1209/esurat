@extends('layouts.app')

@push('styles')
{{-- CSS Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* Style 13px */
    .card-body .form-label, .card-body .form-control, .card-body .form-select, .select2-container--bootstrap-5 .select2-selection { font-size: 13px; }
    .card-body .form-control, .card-body .form-select { padding: 0.3rem 0.6rem; }
    
    /* Preview Container */
    #file-preview-container {
        border: 1px dashed #ced4da;
        padding: 10px;
        border-radius: 5px;
        min-height: 100px;
        display: none; /* Hidden by default */
        text-align: center;
        background-color: #f8f9fa;
    }
    #file-preview-image { max-width: 100%; max-height: 200px; object-fit: contain; }
    #file-preview-pdf { width: 100%; height: 300px; border: none; }

    /* Agar pesan error invalid-feedback terlihat saat class is-invalid aktif */
    .invalid-feedback { font-size: 0.85rem; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">
    <div class="card shadow-sm border-0 mb-4 mt-2">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Input Surat Masuk Baru</h6>
        </div>
        <div class="card-body">
            
            {{-- Alert General Error (Server Side) --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

           <form action="{{ route('bau.surat.store') }}" method="POST" enctype="multipart/form-data" id="formSurat">
    @csrf
    
    {{-- Alert Global (Jika ada error validasi apapun) --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Perhatian!</strong> Terdapat kesalahan input. Silakan periksa kolom yang berwarna merah.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- BARIS 1: Surat Dari & Tipe Surat --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Surat Dari</label>
            <input type="text" name="surat_dari" class="form-control @error('surat_dari') is-invalid @enderror" value="{{ old('surat_dari') }}" required placeholder="Nama Instansi Pengirim">
            @error('surat_dari') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Tipe Surat</label>
            <select name="tipe_surat" class="form-select @error('tipe_surat') is-invalid @enderror" required>
                <option value="eksternal" {{ old('tipe_surat') == 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                <option value="internal" {{ old('tipe_surat') == 'internal' ? 'selected' : '' }}>Internal</option>
            </select>
            @error('tipe_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- BARIS 2: Nomor Surat & No Agenda (FOKUS PERBAIKAN) --}}
    <div class="row">
        {{-- Nomor Surat --}}
        <div class="col-md-6 mb-3 position-relative">
            <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
            <input type="text" name="nomor_surat" id="nomor_surat" 
                   class="form-control @error('nomor_surat') is-invalid @enderror" 
                   value="{{ old('nomor_surat') }}" required>
            
            {{-- Menampilkan Pesan Error dari Laravel (Backend) --}}
            @error('nomor_surat')
                <div class="invalid-feedback">
                    {{-- Custom Message jika ingin hardcode di view, atau ambil dari lang --}}
                    Nomor surat ini sudah terdaftar. Silakan gunakan nomor lain.
                </div>
            @enderror
        </div>

        {{-- No Agenda --}}
        <div class="col-md-6 mb-3 position-relative">
            <label class="form-label">No. Agenda <span class="text-danger">*</span></label>
            <input type="text" name="no_agenda" id="no_agenda" 
                   class="form-control @error('no_agenda') is-invalid @enderror" 
                   value="{{ old('no_agenda') }}" required>
            
            {{-- Menampilkan Pesan Error dari Laravel (Backend) --}}
            @error('no_agenda')
                <div class="invalid-feedback">
                    {{-- Custom Message Bahasa Indonesia --}}
                    Nomor agenda ini sudah digunakan. Mohon gunakan nomor agenda yang berbeda.
                </div>
            @enderror
        </div>
    </div>

    {{-- BARIS 3: Tanggal Surat & Diterima Tanggal --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Tanggal Surat</label>
            <input type="date" name="tanggal_surat" class="form-control @error('tanggal_surat') is-invalid @enderror" value="{{ old('tanggal_surat') }}" required>
            @error('tanggal_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Diterima Tanggal</label>
            <input type="date" name="diterima_tanggal" class="form-control @error('diterima_tanggal') is-invalid @enderror" value="{{ old('diterima_tanggal', date('Y-m-d')) }}" required>
            @error('diterima_tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- BARIS 4: Sifat Surat & Tujuan Surat --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Sifat Surat</label>
            <select name="sifat" class="form-select @error('sifat') is-invalid @enderror" required>
                <option value="Asli" {{ old('sifat') == 'Asli' ? 'selected' : '' }}>Asli</option>
                <option value="Tembusan" {{ old('sifat') == 'Tembusan' ? 'selected' : '' }}>Tembusan</option>
            </select>
            @error('sifat') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Tujuan Surat</label>
            <select name="tujuan_tipe" id="tujuan_tipe" class="form-select border-primary @error('tujuan_tipe') is-invalid @enderror" required>
                <option value="">-- Pilih Tipe Tujuan --</option>
                <option value="universitas" {{ old('tujuan_tipe') == 'universitas' ? 'selected' : '' }}>Rektor / Universitas (Disposisi)</option>
                </select>
            @error('tujuan_tipe') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- BARIS 5: Dropdown Dinamis (Satker/Pegawai) --}}
    <div class="row">
        <div class="col-12">
            <div class="mb-3" id="div_tujuan_satker" style="display: none;">
                <label class="form-label">Pilih Satker Tujuan</label>
                <select name="tujuan_satker_id" id="tujuan_satker_id" class="form-select select2">
                    <option value="">-- Cari Satker --</option>
                    @foreach ($daftarSatker as $satker)
                        <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3" id="div_tujuan_pegawai" style="display: none;">
                <label class="form-label">Pilih Pegawai Tujuan</label>
                <select name="tujuan_user_id" id="tujuan_user_id" class="form-select select2">
                    <option value="">-- Cari Pegawai --</option>
                    @foreach ($daftarPegawai as $pegawai)
                        <option value="{{ $pegawai->id }}">
                            {{ $pegawai->name }} - {{ $pegawai->satker->nama_satker ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- BARIS 6: Perihal & File (Berdampingan) --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Perihal</label>
            <textarea name="perihal" class="form-control @error('perihal') is-invalid @enderror" rows="5" required style="resize: none;">{{ old('perihal') }}</textarea>
            @error('perihal') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="form-label">File Surat (PDF/Gambar, Max 10MB)</label>
            <input type="file" name="file_surat" id="file_surat" class="form-control @error('file_surat') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
            
            {{-- Pesan Error File --}}
            @error('file_surat')
                <div class="invalid-feedback">
                    Format file harus PDF/JPG/PNG dan maksimal 10MB.
                </div>
            @enderror

            {{-- Area Preview --}}
            <div id="file-preview-container" class="mt-2" style="border: 1px dashed #ccc; min-height: 208px; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                <p class="text-muted small mb-0" id="preview-text">Preview akan muncul di sini</p>
                <img id="file-preview-image" src="" alt="Preview Gambar" style="display: none; max-height: 150px; width: auto;">
                <iframe id="file-preview-pdf" src="" style="display: none; width: 100%; height: 200px; border:none;"></iframe>
            </div>
        </div>
    </div>

    {{-- TOMBOL AKSI --}}
    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
        @php
            $previousUrl = url()->previous();
            $routeBatal = str_contains($previousUrl, 'internal') ? route('bau.surat.internal') : route('bau.surat.eksternal');
        @endphp
        <a href="{{ $routeBatal }}" class="btn btn-secondary me-2 px-4">Batal</a>
        <button type="submit" id="btn-submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i> Simpan Surat</button>
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
        
        // --- 1. INISIALISASI SELECT2 & LOGIKA TUJUAN ---
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Silakan pilih...', allowClear: true });

        const tipeSelect = $('#tujuan_tipe');
        const divSatker = $('#div_tujuan_satker');
        const divPegawai = $('#div_tujuan_pegawai');
        const selectSatker = $('#tujuan_satker_id');
        const selectPegawai = $('#tujuan_user_id');

        function toggleTujuan() {
            const val = tipeSelect.val();
            divSatker.hide(); divPegawai.hide();
            selectSatker.prop('required', false); selectPegawai.prop('required', false);

            if (val === 'satker') {
                divSatker.show(); selectSatker.prop('required', true);
            } else if (val === 'pegawai') {
                divPegawai.show(); selectPegawai.prop('required', true);
            }
        }
        tipeSelect.on('change', toggleTujuan);
        toggleTujuan();


        // --- 2. LOGIKA VALIDASI FILE (TIPE & UKURAN) BAHASA INDONESIA ---
        $('#file_surat').on('change', function(event) {
            const file = event.target.files[0];
            const inputElement = $(this);
            const errorElement = $('#error-file_surat');
            const btnSubmit = $('#btn-submit');
            
            // Elemen Preview
            const container = $('#file-preview-container');
            const imgPreview = $('#file-preview-image');
            const pdfPreview = $('#file-preview-pdf');
            const textPreview = $('#preview-text');

            // Reset Error
            inputElement.removeClass('is-invalid');
            btnSubmit.prop('disabled', false);

            if (file) {
                // A. VALIDASI UKURAN (Max 10MB = 10 * 1024 * 1024)
                if (file.size > 10485760) {
                    inputElement.addClass('is-invalid');
                    errorElement.text('Ukuran file terlalu besar! Maksimal 10MB.');
                    btnSubmit.prop('disabled', true);
                    
                    // Reset Preview
                    container.hide();
                    return; 
                }

                // B. VALIDASI TIPE (MIME TYPE)
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    inputElement.addClass('is-invalid');
                    errorElement.text('Format file harus: PDF, JPG, JPEG, atau PNG.');
                    btnSubmit.prop('disabled', true);
                    
                    container.hide();
                    return;
                }

                // C. JIKA LOLOS -> TAMPILKAN PREVIEW
                container.show();
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        imgPreview.attr('src', e.target.result).show();
                        pdfPreview.hide();
                        textPreview.text('Preview Gambar: ' + file.name);
                    } else if (file.type === 'application/pdf') {
                        pdfPreview.attr('src', e.target.result).show();
                        imgPreview.hide();
                        textPreview.text('Preview PDF: ' + file.name);
                    }
                }
                reader.readAsDataURL(file);

            } else {
                container.hide();
            }
        });


        // --- 3. LOGIKA CEK DUPLIKAT (AJAX) ---
        function checkDuplicate(field, value) {
            if(value.length < 3) return; 

            $.ajax({
                url: "{{ route('bau.surat.checkDuplicate') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    field: field, 
                    value: value
                },
                success: function(response) {
                    const inputElement = $('#' + field);
                    const errorElement = $('#error-' + field);
                    const btnSubmit = $('#btn-submit');
                    const alertBox = $('#client-alert');

                    if (response.exists) {
                        // JIKA DUPLIKAT
                        inputElement.addClass('is-invalid'); // Input jadi merah
                        alertBox.removeClass('d-none'); // Muncul alert kuning di atas
                        btnSubmit.prop('disabled', true); // Matikan tombol simpan
                    } else {
                        // JIKA AMAN
                        inputElement.removeClass('is-invalid');
                        
                        // Cek apakah ada error lain sebelum menyalakan tombol
                        if ($('.is-invalid').length === 0) {
                            alertBox.addClass('d-none');
                            btnSubmit.prop('disabled', false);
                        }
                    }
                },
                error: function() {
                    console.log('Gagal mengecek duplikat');
                }
            });
        }

        // Event Listener (Saat user selesai mengetik / pindah kolom)
        $('#nomor_surat').on('blur', function() {
            checkDuplicate('nomor_surat', $(this).val());
        });

        $('#no_agenda').on('blur', function() {
            checkDuplicate('no_agenda', $(this).val());
        });

    });
</script>
@endpush