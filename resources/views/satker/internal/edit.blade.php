@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .form-label { font-weight: 600; font-size: 0.85rem; color: #495057; }
    .card-header { background: linear-gradient(45deg, #f8f9fa, #e9ecef); }
    .revisi-alert { background-color: #fff5f5; border-left: 4px solid #f56565; border-radius: 8px; }
    
    .tujuan-row { 
        background-color: #f8f9fa; 
        border: 1px solid #dee2e6; 
        border-radius: 8px; 
        padding: 15px; 
        position: relative;
    }
    .btn-remove-row { position: absolute; top: -10px; right: -10px; border-radius: 50%; width: 25px; height: 25px; padding: 0; line-height: 25px; }

    #preview-container { display: none; margin-top: 10px; border: 1px dashed #ced4da; padding: 10px; border-radius: 5px; text-align: center; }
    #preview-pdf { width: 100%; height: 500px; border: 1px solid #dee2e6; border-radius: 4px; }
    .current-file-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; }
    .badge-optional { background-color: #e9ecef; color: #6c757d; font-weight: normal; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            
            {{-- AREA NOTIFIKASI REVISI --}}
            @php $revisi = $surat->validasis->where('status', 'revisi')->first(); @endphp
            @if($revisi)
            <div class="revisi-alert p-3 mb-4 shadow-sm">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-exclamation-octagon-fill text-danger fs-4 me-2"></i>
                    <h6 class="m-0 fw-bold text-danger">Catatan Revisi Pimpinan</h6>
                </div>
                <div class="bg-white p-3 rounded border border-danger-subtle shadow-sm">
                    <p class="mb-0 text-dark">"{{ $revisi->catatan }}"</p>
                </div>
            </div>
            @endif

            <div class="card shadow border-0 rounded-3">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-pencil-square text-primary me-2 fs-5"></i>
                        <h6 class="m-0 fw-bold text-primary">Edit Surat Keluar Internal</h6>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('satker.surat-keluar.internal.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Data Dasar Surat --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_surat" class="form-control" value="{{ old('nomor_surat', $surat->nomor_surat) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', $surat->tanggal_surat->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Sifat Surat <span class="text-danger">*</span></label>
                                <select name="sifat" class="form-select" required>
                                    <option value="Biasa" {{ $surat->sifat == 'Biasa' ? 'selected' : '' }}>Biasa</option>
                                    <option value="Penting" {{ $surat->sifat == 'Penting' ? 'selected' : '' }}>Penting</option>
                                    <option value="Rahasia" {{ $surat->sifat == 'Rahasia' ? 'selected' : '' }}>Rahasia</option>
                                    <option value="Segera" {{ $surat->sifat == 'Segera' ? 'selected' : '' }}>Segera</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Perihal <span class="text-danger">*</span></label>
                                <input type="text" name="perihal" class="form-control" value="{{ old('perihal', $surat->perihal) }}" required>
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
                            @php
                                // Mengelompokkan riwayat berdasarkan user pengirim untuk membedakan tujuan asli
                                $pimpinanIds = $surat->validasis->pluck('pimpinan_id')->toArray();
                                $tujuanEksisting = $surat->riwayats->where('user_id', $surat->user_id)
                                    ->whereNotNull('penerima_id')
                                    ->whereNotIn('penerima_id', $pimpinanIds)
                                    ->groupBy(function($item) {
                                        return $item->penerima->satker_id ?? 'rektor';
                                    });
                            @endphp

                            @forelse($tujuanEksisting as $satkerId => $riwayats)
                            <div class="tujuan-row mb-3 shadow-sm">
                                @if(!$loop->first)
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="bi bi-x"></i></button>
                                @endif
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-primary">1. Pilih Fakultas / Unit</label>
                                        <select class="form-select select-satker-dynamic" required>
                                            <option value="rektor" {{ $satkerId == 'rektor' ? 'selected' : '' }}>Rektorat / Universitas</option>
                                            @foreach($daftarSatker as $satker)
                                                <option value="{{ $satker->id }}" {{ $satkerId == $satker->id ? 'selected' : '' }}>{{ $satker->nama_satker }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label text-primary">2. Pilih Pejabat / Pegawai Penerima</label>
                                        <select name="tujuan_user_ids[]" class="form-select select-user-dynamic" multiple="multiple" required>
                                            @foreach($riwayats as $r)
                                                <option value="{{ $r->penerima_id }}" selected>{{ $r->penerima->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @empty
                            {{-- Row default jika data tidak ditemukan --}}
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
                            @endforelse
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-diagram-3-fill me-2"></i>Alur Validasi & Tembusan <span class="badge badge-optional ms-1">Opsional</span></h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-primary">Mengetahui / Validasi</label>
                                <select name="pimpinan_ids[]" id="select-pimpinan" class="form-select" multiple="multiple">
                                    @php $selectedPimpinan = $surat->validasis->pluck('pimpinan_id')->toArray(); @endphp
                                    @foreach($pimpinans as $p)
                                        <option value="{{ $p->id }}" {{ in_array($p->id, $selectedPimpinan) ? 'selected' : '' }}>
                                            {{ $p->name }} ({{ $p->jabatan->nama_jabatan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-info">Tembusan Tambahan</label>
                                <select name="tembusan_ids[]" id="select-tembusan" class="form-select" multiple="multiple">
                                    @php 
                                        // Logika untuk mengambil tembusan lama jika ada
                                        $selectedTembusanUser = $surat->riwayats->where('status_aksi', 'LIKE', '%Tembusan%')->pluck('penerima_id')->toArray();
                                    @endphp
                                    <optgroup label="Pimpinan / Pejabat">
                                        @foreach($pimpinans as $p)
                                            <option value="user_{{ $p->id }}" {{ in_array($p->id, $selectedTembusanUser) ? 'selected' : '' }}>
                                                {{ $p->name }} ({{ $p->jabatan->nama_jabatan }})
                                            </option>
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

                        {{-- File Management --}}
                        <div class="mb-4">
                            <label class="form-label">File Surat (PDF)</label>
                            
                            @if($surat->file_surat)
                            <div class="current-file-box d-flex justify-content-between align-items-center mb-3">
                                <span><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i> File Aktif: <strong>{{ basename($surat->file_surat) }}</strong></span>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFileLama">
                                    <i class="bi bi-eye"></i> Lihat File
                                </button>
                            </div>
                            @endif

                            <input type="file" name="file_surat" id="fileInput" class="form-control" accept=".pdf" onchange="previewFile()">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengganti file.</small>
                            
                            <div id="preview-container">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-warning text-dark">Pratinjau File Baru</span>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 fw-bold" onclick="resetFileInput()">Hapus</button>
                                </div>
                                <iframe id="preview-pdf" src="" style="display: none;"></iframe>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4 border-top pt-3">
                            <a href="{{ route('satker.surat-keluar.internal') }}" class="btn btn-light px-4 border">Batal</a>
                            <button type="submit" id="btnSubmit" class="btn btn-primary px-5 shadow-sm">
                                <i class="bi bi-save-fill me-1"></i> Simpan & Kirim Kembali
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL FILE LAMA --}}
<div class="modal fade" id="modalFileLama" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">File Surat Terarsip</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe src="{{ asset('storage/' . $surat->file_surat) }}" width="100%" height="75vh" style="border:none;"></iframe>
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
        function initSelect2(element, placeholder = 'Pilih...') {
            $(element).select2({ 
                theme: 'bootstrap-5', 
                width: '100%', 
                placeholder: placeholder,
                allowClear: true 
            });
        }

        // Inisialisasi awal
        initSelect2('.select-satker-dynamic');
        initSelect2('.select-user-dynamic');
        initSelect2('#select-pimpinan');
        initSelect2('#select-tembusan');

        // Load users untuk baris yang sudah ada (edit mode)
        $('.select-satker-dynamic').each(function() {
            const satkerId = $(this).val();
            const row = $(this).closest('.tujuan-row');
            const userSelect = row.find('.select-user-dynamic');
            const existingValues = userSelect.val(); // Simpan ID yang sudah terpilih

            if (satkerId) {
                $.get("{{ route('api.get-pegawai-by-satker') }}", { satker_id: satkerId }, function(data) {
                    // Jangan hapus yang sudah terpilih, cukup tambahkan pilihan lainnya
                    data.forEach(u => {
                        if (!userSelect.find(`option[value="${u.id}"]`).length) {
                            userSelect.append(`<option value="${u.id}">${u.name}</option>`);
                        }
                    });
                    userSelect.trigger('change');
                });
            }
        });

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
    });

    function previewFile() {
        const file = document.getElementById('fileInput').files[0];
        if (file && file.type === 'application/pdf') {
            const url = URL.createObjectURL(file);
            $('#preview-pdf').attr('src', url).show();
            $('#preview-container').show();
        }
    }

    function resetFileInput() {
        document.getElementById('fileInput').value = '';
        $('#preview-container').hide();
        $('#preview-pdf').attr('src', '');
    }
</script>
@endpush