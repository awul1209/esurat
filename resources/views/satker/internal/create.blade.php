@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .form-label { font-weight: 600; font-size: 0.85rem; color: #495057; }
    .card-header { background: linear-gradient(45deg, #f8f9fa, #e9ecef); }
    #preview-container { display: none; margin-top: 10px; border: 1px dashed #ced4da; padding: 10px; border-radius: 5px; text-align: center; }
    
    .tujuan-row { 
        background-color: #f8f9fa; 
        border: 1px solid #dee2e6; 
        border-radius: 8px; 
        padding: 15px; 
        position: relative;
    }
    .btn-remove-row { position: absolute; top: -10px; right: -10px; border-radius: 50%; width: 25px; height: 25px; padding: 0; line-height: 25px; }

    .method-box {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        transition: all 0.3s ease;
        cursor: pointer;
        height: 100%;
        position: relative;
    }
    .method-input:checked + .method-box { border-color: #0d6efd; background-color: #e7f1ff; }
    .method-input { position: absolute; opacity: 0; }
    
    .badge-optional { background-color: #e9ecef; color: #6c757d; font-weight: normal; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-send-check-fill text-primary me-2 fs-5"></i>
                        <h6 class="m-0 fw-bold text-primary">Buat Surat Keluar Internal (PDF)</h6>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('satker.surat-keluar.internal.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Data Surat --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_surat" class="form-control" required placeholder="001/...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_surat" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Sifat Surat <span class="text-danger">*</span></label>
                                <select name="sifat" class="form-select" required>
                                    <option value="Biasa">Biasa</option>
                                    <option value="Penting">Penting</option>
                                    <option value="Rahasia">Rahasia</option>
                                    <option value="Segera">Segera</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Perihal <span class="text-danger">*</span></label>
                                <input type="text" name="perihal" class="form-control" required placeholder="Hal surat...">
                            </div>
                        </div>

                        {{-- BARIS BARU: Password Keamanan --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-danger"><i class="bi bi-key-fill me-1"></i> Password Keamanan Surat (Min. 6 Karakter) <span class="text-danger">*</span></label>
                                <input type="text" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Masukkan password unik (huruf/angka/simbol)" minlength="6">
                                <small class="text-muted">Password ini akan digunakan saat mendaftarkan keabsahan ke sistem kampus.</small>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr>

                        {{-- Area Tujuan Berjenjang --}}
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-secondary m-0">Daftar Tujuan Surat</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-tujuan">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Unit Tujuan
                            </button>
                        </div>

                        <div id="wrapper-tujuan">
                            <div class="tujuan-row mb-3 shadow-sm">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-primary">1. Pilih Fakultas / Unit</label>
                                        <select class="form-select select-satker-dynamic" required>
                                            <option value="">-- Pilih Satker --</option>
                                            <option value="rektor">Rektorat / Universitas</option>
                                            @foreach($daftarSatker as $satker)
                                                <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label text-primary">2. Pilih Pejabat / Pegawai Penerima</label>
                                        <select name="tujuan_user_ids[]" class="form-select select-user-dynamic" multiple="multiple" required></select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-diagram-3-fill me-2"></i>Alur Validasi & Tembusan <span class="badge badge-optional ms-1">Opsional</span></h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-primary">Mengetahui / Validasi</label>
                                <select name="pimpinan_ids[]" id="select-pimpinan" class="form-select" multiple="multiple">
                                    @foreach($pimpinans as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->jabatan->nama_jabatan }})</option>
                                    @endforeach
                                </select>
                            </div>

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
                                            @if($s->nama_satker != 'Biro Administrasi Umum (BAU)') 
                                                <option value="satker_{{ $s->id }}">{{ $s->nama_satker }}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Metode TTD --}}
                        <div class="mb-4">
                            <label class="form-label d-block mb-3">Metode Pengesahan <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="radio" name="metode_ttd" id="method_qr" value="qr_png" class="method-input" checked>
                                    <label for="method_qr" class="method-box d-block text-center">
                                        <i class="bi bi-qr-code-scan fs-3 text-primary"></i>
                                        <span class="fw-bold d-block mt-2">Tanda Tangan Digital</span>
                                        <small class="text-muted">Arahkan ke Editor Layout setelah Simpan.</small>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" name="metode_ttd" id="method_manual" value="manual" class="method-input">
                                    <label for="method_manual" class="method-box d-block text-center">
                                        <i class="bi bi-pen-fill fs-3 text-primary"></i>
                                        <span class="fw-bold d-block mt-2">Sudah Ada TTD Basah</span>
                                        <small class="text-muted">Langsung Kirim (Wajib PDF).</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- File --}}
                        <div class="mb-4">
                            <label class="form-label" id="labelFile">Upload Draf Surat (Hanya PDF) <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" id="fileInput" class="form-control" accept=".pdf" required onchange="previewFile()">
                            <div id="preview-container">
                                <iframe id="preview-pdf" src="" width="100%" height="400px" style="display: none; border: 1px solid #ddd;"></iframe>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('satker.surat-keluar.internal') }}" class="btn btn-light px-4">Batal</a>
                            <button type="submit" id="btnSubmit" class="btn btn-primary px-5">
                                <i class="bi bi-layout-text-window-reverse me-1"></i> <span>Lanjutkan ke Editor Barcode</span>
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
        function initSelect2(element) {
            $(element).select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Pilih...' });
        }

        initSelect2('.select-satker-dynamic');
        initSelect2('.select-user-dynamic');
        initSelect2('#select-pimpinan');
        initSelect2('#select-tembusan');

        $('#add-tujuan').on('click', function() {
            let newRow = `
            <div class="tujuan-row mb-3 shadow-sm">
                <button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="bi bi-x"></i></button>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-primary">1. Pilih Fakultas / Unit</label>
                        <select class="form-select select-satker-dynamic" required>
                            <option value="">-- Pilih Satker --</option>
                            <option value="rektor">Rektorat / Universitas</option>
                            @foreach($daftarSatker as $satker)
                                <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label text-primary">2. Pilih Nama Penerima (User)</label>
                        <select name="tujuan_user_ids[]" class="form-select select-user-dynamic" multiple="multiple" required></select>
                    </div>
                </div>
            </div>`;
            $('#wrapper-tujuan').append(newRow);
            initSelect2($('#wrapper-tujuan .tujuan-row:last-child .select-satker-dynamic'));
            initSelect2($('#wrapper-tujuan .tujuan-row:last-child .select-user-dynamic'));
        });

        $(document).on('click', '.btn-remove-row', function() {
            $(this).closest('.tujuan-row').remove();
        });

        $(document).on('change', '.select-satker-dynamic', function() {
            const satkerId = $(this).val();
            const row = $(this).closest('.tujuan-row');
            const userSelect = row.find('.select-user-dynamic');

            if (satkerId) {
                userSelect.empty().append('<option value="">Loading...</option>');
                $.get("{{ route('api.get-pegawai-by-satker') }}", { satker_id: satkerId }, function(data) {
                    userSelect.empty();
                    data.forEach(u => userSelect.append(`<option value="${u.id}">${u.name}</option>`));
                    userSelect.trigger('change');
                });
            }
        });

        $('input[name="metode_ttd"]').on('change', function() {
            if ($(this).val() === 'manual') {
                $('#btnSubmit span').text('Kirim Surat Sekarang');
                $('#btnSubmit i').attr('class', 'bi bi-send-fill me-1');
            } else {
                $('#btnSubmit span').text('Lanjutkan ke Editor Barcode');
                $('#btnSubmit i').attr('class', 'bi bi-layout-text-window-reverse me-1');
            }
        });
    });

    function previewFile() {
        const file = document.getElementById('fileInput').files[0];
        if (file && file.type === 'application/pdf') {
            $('#preview-pdf').attr('src', URL.createObjectURL(file)).show();
            $('#preview-container').show();
        }
    }
</script>
@endpush