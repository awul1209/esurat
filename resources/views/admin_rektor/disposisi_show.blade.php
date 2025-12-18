@extends('layouts.app')

@push('styles')
{{-- CSS Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* Style Font Kecil agar rapi */
    .form-label, .form-control, .form-select, .form-text, .select2-selection {
        font-size: 13px !important;
    }
    .form-control, .form-select {
         padding: 0.3rem 0.6rem; 
    }
    .disposisi-rektor-icon{ font-size:14px; }
    .disposisi-rektor-btn{ font-size:14px; }
    
    /* Fix tinggi Select2 Multiple */
    .select2-container--bootstrap-5 .select2-selection--multiple {
        min-height: 38px;
    }
    /* Fix tinggi Select2 Single */
    .select2-container--bootstrap-5 .select2-selection--single {
        min-height: 38px;
        padding-top: 3px;
    }

    /* Untuk input Select2 (bagian yang terlihat) */
.select2-container .select2-selection--single {
    font-size: 12px; /* Ukuran font saat belum dibuka */
    height: 38px; /* sesuaikan */
}

/* Untuk teks pilihan */
.select2-container .select2-selection__rendered {
    font-size: 12px;
    line-height: 36px; /* agar teks tidak terlalu atas */
}

/* Untuk dropdown saat dibuka */
.select2-container .select2-results__option {
    font-size: 12px !important;
}

/* Untuk input pencarian di dropdown */
.select2-container .select2-search__field {
    font-size: 12px !important;
}


</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        
        {{-- KOLOM KIRI: DETAIL SURAT & FILE --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Detail Surat: {{ $surat->perihal }}</h6>
                </div>
                <div class="card-body">
                    
                    @php
                        $tipe = $surat->tujuan_tipe;
                        
                        // Fallback jika tipe kosong (Data lama)
                        if (empty($tipe)) {
                            if ($surat->tujuan_satker_id) { $tipe = 'satker'; } 
                            elseif ($surat->tujuan_user_id) { $tipe = 'pegawai'; } 
                            else { $tipe = 'universitas'; }
                        }
                    @endphp

                    <table class="table table-sm table-borderless">
                        <tr>
                            <td style="width: 150px;"><strong>No. Agenda</strong></td>
                            <td>: {{ $surat->no_agenda }}</td>
                        </tr>
                        <tr>
                            <td><strong>Asal Surat</strong></td>
                            <td>: {{ $surat->surat_dari }}</td>
                        </tr>
                        <tr>
                            <td><strong>Perihal</strong></td>
                            <td>: {{ $surat->perihal }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Diterima</strong></td>
                            <td>: {{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tujuan Awal</strong></td>
                            <td>: 
                                @if($tipe == 'rektor')
                                    <span class="badge bg-primary">Rektor</span>
                                @elseif($tipe == 'universitas')
                                    <span class="badge bg-info text-dark">Universitas (Perlu Disposisi)</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($tipe) }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                         <h6 class="m-0 fw-bold text-primary">File Surat</h6>
                         <a href="{{ Storage::url($surat->file_surat) }}" class="btn btn-sm btn-outline-primary" download>
                            <i class="bi bi-download me-1"></i> Download File
                        </a>
                    </div>
                    
                    @php
                        $fileUrl = Storage::url($surat->file_surat);
                        $extension = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
                    @endphp

                    <div class="file-preview-wrapper" style="height: 75vh; max-height: 800px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: .375rem;">
                        @if ($extension == 'pdf')
                            <iframe src="{{ $fileUrl }}" width="100%" height="100%" style="border: none;"></iframe>
                        @elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']))
                            <img src="{{ $fileUrl }}" style="width: 100%; height: 100%; object-fit: contain; padding: 1rem;">
                        @else
                            <div class="text-center p-5">
                                <i class="bi bi-file-earmark-text h1 text-muted"></i>
                                <p class="mt-3">Preview tidak didukung untuk tipe file ini.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: FORM DISPOSISI --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i> Tindak Lanjut Rektor</h6>
                </div>
                <div class="card-body p-4">
                    {{-- Form mengarah ke method store di DisposisiController --}}
                    <form action="{{ route('adminrektor.disposisi.store', $surat->id) }}" method="POST">
                        @csrf
                        
                        @if($tipe == 'rektor')
                            {{-- KASUS 1: TUJUAN REKTOR (Langsung Selesai) --}}
                            <div class="alert alert-info py-4 mb-4 text-center">
                                <i class="bi bi-info-circle-fill h1 d-block mb-3 text-primary"></i>
                                <h6 class="fw-bold">Surat untuk Rektor</h6>
                                <p class="mb-0 small text-muted">Surat ini ditujukan personal kepada Rektor. Tidak memerlukan disposisi ke unit lain.</p>
                                <hr class="my-3">
                                <p class="mb-0 fw-bold">Klik tombol di bawah untuk menandai selesai & arsip.</p>
                            </div>

                            <input type="hidden" name="tujuan_satker_ids" value="">

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg disposisi-rektor-btn py-3">
                                    <i class="bi bi-check-circle-fill me-2 disposisi-rektor-icon"></i> Selesai & Arsipkan
                                </button>
                            </div>

                        @else
                            {{-- KASUS 2: TUJUAN UNIVERSITAS (Form Disposisi) --}}
                            <div class="alert alert-warning py-2 mb-3" style="font-size: 12px;">
                                <i class="bi bi-building-exclamation me-1"></i> 
                                Surat untuk <strong>Universitas</strong>. Mohon berikan disposisi ke Unit/Satker terkait.
                            </div>

                            {{-- 1. KLASIFIKASI --}}
                            <div class="mb-3">
                                <label for="klasifikasi_id" class="form-label">Klasifikasi Arsip:</label>
                                <select class="form-select select2" id="klasifikasi_id" name="klasifikasi_id" required>
                                    <option value="">-- Pilih Klasifikasi --</option>
                                    @foreach($daftarKlasifikasi as $klasifikasi)
                                        <option value="{{ $klasifikasi->id }}">
                                                {{ $klasifikasi->nama_klasifikasi }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 2. TUJUAN (MULTI SELECT) --}}
                            <div class="mb-3" id="wrapper_satker">
                                <label for="tujuan_satker_ids" class="form-label">Diteruskan Kepada:</label>
                                <select class="form-select select2" id="tujuan_satker_ids" name="tujuan_satker_ids[]" multiple="multiple" required>
                                    @foreach($daftarSatker as $satker)
                                        <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                                    @endforeach
                                    <option value="lainnya">Lainnya (Ormawa / Pihak Luar)</option>
                                </select>
                                <div class="form-text text-muted">Bisa pilih lebih dari satu Satker.</div>
                            </div>

                            {{-- 3. INPUT LAINNYA (Dinamis) --}}
                            <div class="mb-3" id="wrapper_tujuan_lain" style="display: none;">
                                <label for="disposisi_lain" class="form-label fw-bold text-primary">Tuliskan Tujuan Lainnya:</label>
                                {{-- NAME DIGANTI JADI 'disposisi_lain' AGAR SESUAI CONTROLLER --}}
                                <input type="text" class="form-control" id="disposisi_lain" name="disposisi_lain" placeholder="Contoh: BEM Universitas, UKM Musik">
                            </div>

                            {{-- 4. CATATAN REKTOR (Muncul jika ada satker terpilih ATAU Lainnya) --}}
                            <div class="mb-3" id="wrapper_catatan" style="display: none;">
                                <label for="catatan_rektor" class="form-label">Catatan / Arahan Rektor:</label>
                                <textarea class="form-control" id="catatan_rektor" name="catatan_rektor" rows="4" placeholder="Contoh: 'Tolong pelajari dan tindak lanjuti sesuai aturan.'"></textarea>
                            </div>

                            <hr>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg disposisi-rektor-btn" style="font-size:14px;">
                                    <i class="bi bi-send-check-fill me-2 disposisi-rektor-icon"></i> Simpan & Teruskan ke BAU
                                </button>
                            </div>
                        @endif

                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
{{-- Script Select2 --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Silakan pilih...',
            allowClear: true,
            closeOnSelect: false 
        });

        // Referensi Elemen
        const elKlasifikasi = $('#klasifikasi_id');
        const elSatker = $('#tujuan_satker_ids');
        const wrapSatker = $('#wrapper_satker');
        const wrapLain = $('#wrapper_tujuan_lain');
        const wrapCatatan = $('#wrapper_catatan');
        const inputLain = $('#disposisi_lain'); // ID updated
        
        // Logika Show/Hide
        function updateUI() {
            // Cek Klasifikasi (Arsip vs Bukan)
            var selectedOption = elKlasifikasi.find('option:selected');
            var klasifikasiText = selectedOption.length ? selectedOption.text().toLowerCase() : '';
            var isArsip = klasifikasiText.includes('arsip');

            if (isArsip) {
                // Jika Arsip -> Sembunyikan Semua Tujuan
                wrapSatker.hide();
                elSatker.val(null).trigger('change.select2');
                elSatker.prop('required', false);
                wrapLain.hide();
                wrapCatatan.hide();
            } else {
                // Jika Bukan Arsip -> Tampilkan Dropdown Tujuan
                wrapSatker.show();
                
                var selectedSatkers = elSatker.val() || [];
                
                // 1. CEK UNTUK OPSI "LAINNYA"
                if (selectedSatkers.includes('lainnya')) {
                    wrapLain.slideDown(); // Tampilkan input text
                    inputLain.prop('required', true);
                } else {
                    wrapLain.slideUp();
                    inputLain.prop('required', false);
                    inputLain.val('');
                }
                
                // 2. CEK UNTUK CATATAN REKTOR
                // Logika Baru: Tampilkan catatan jika ada APAPUN yang dipilih (Satker atau Lainnya)
                if (selectedSatkers.length > 0) {
                    wrapCatatan.slideDown();
                } else {
                    wrapCatatan.slideUp();
                }
            }
        }

        elKlasifikasi.on('change', updateUI);
        elSatker.on('change', updateUI);
        updateUI();
    });
</script>
@endpush