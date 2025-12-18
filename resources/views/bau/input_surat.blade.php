@extends('layouts.app')

@push('styles')
{{-- CSS Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
{{-- CSS Bootstrap 5 Theme untuk Select2 --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* Style 13px */
    .card-body .form-label,
    .card-body .form-control,
    .card-body .form-select,
    .select2-container--bootstrap-5 .select2-selection {
        font-size: 13px;
    }
    .card-body .form-control,
    .card-body .form-select {
         padding: 0.3rem 0.6rem; 
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Input Surat Masuk Baru</h6>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bau.surat.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <!-- KOLOM KIRI -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Surat Dari</label>
                            <input type="text" name="surat_dari" class="form-control" value="{{ old('surat_dari') }}" required placeholder="Nama Instansi Pengirim">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipe Surat</label>
                            <select name="tipe_surat" class="form-select" required>
                                <option value="eksternal" {{ old('tipe_surat') == 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                                <option value="internal" {{ old('tipe_surat') == 'internal' ? 'selected' : '' }}>Internal</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor Surat</label>
                            <input type="text" name="nomor_surat" class="form-control" value="{{ old('nomor_surat') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat') }}" required>
                        </div>
                    </div>

                    <!-- KOLOM KANAN -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">No. Agenda</label>
                            <input type="text" name="no_agenda" class="form-control" value="{{ old('no_agenda') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Diterima Tanggal</label>
                            <input type="date" name="diterima_tanggal" class="form-control" value="{{ old('diterima_tanggal', date('Y-m-d')) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sifat Surat</label>
                            <select name="sifat" class="form-select" required>
                                <option value="Asli" {{ old('sifat') == 'Asli' ? 'selected' : '' }}>Asli</option>
                                <option value="Tembusan" {{ old('sifat') == 'Tembusan' ? 'selected' : '' }}>Tembusan</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">File Surat (PDF/Gambar, Max 10MB)</label>
                            <input type="file" name="file_surat" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Perihal</label>
                    <textarea name="perihal" class="form-control" rows="2" required>{{ old('perihal') }}</textarea>
                </div>

                <hr>

                <!-- TUJUAN SURAT -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Tujuan Surat</label>
                    <select name="tujuan_tipe" id="tujuan_tipe" class="form-select border-primary" required>
                        <option value="">-- Pilih Tipe Tujuan --</option>
                        
                        {{-- Opsi 1: Rektor / Universitas (Satu Jalur: Disposisi) --}}
                        <option value="universitas" {{ old('tujuan_tipe') == 'universitas' ? 'selected' : '' }}>Rektor / Universitas (Disposisi)</option>
                        
                        {{-- Opsi 2: Satker (Langsung) --}}
                        <option value="satker" {{ old('tujuan_tipe') == 'satker' ? 'selected' : '' }}>Satker (Langsung)</option>
                        
                        {{-- Opsi 3: Pegawai (Langsung) --}}
                        <option value="pegawai" {{ old('tujuan_tipe') == 'pegawai' ? 'selected' : '' }}>Pegawai/Dosen (Langsung)</option>
                        
                    </select>
                </div>

                <!-- PILIH SATKER (Muncul jika 'satker' dipilih) -->
                <div class="mb-3" id="div_tujuan_satker" style="display: none;">
                    <label class="form-label">Pilih Satker Tujuan</label>
                    <select name="tujuan_satker_id" id="tujuan_satker_id" class="form-select select2">
                        <option value="">-- Cari Satker --</option>
                        @foreach ($daftarSatker as $satker)
                            <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- PILIH PEGAWAI (Muncul jika 'pegawai' dipilih) -->
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

                <div class="d-flex justify-content-end mt-4">
                    @php
                        $previousUrl = url()->previous();
                        $routeBatal = str_contains($previousUrl, 'internal') ? route('bau.surat.internal') : route('bau.surat.eksternal');
                    @endphp
                    <a href="{{ $routeBatal}}" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> Simpan Surat</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- JQuery (Wajib untuk Select2) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- Script Select2 --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        
        // 1. Inisialisasi Select2 pada class .select2
        $('.select2').select2({
            theme: 'bootstrap-5', 
            width: '100%',        
            placeholder: 'Silakan pilih...',
            allowClear: true
        });

        // 2. Logika Tampilan Dropdown Tujuan
        const tipeSelect = $('#tujuan_tipe');
        const divSatker = $('#div_tujuan_satker');
        const divPegawai = $('#div_tujuan_pegawai');
        
        const selectSatker = $('#tujuan_satker_id');
        const selectPegawai = $('#tujuan_user_id');

        function toggleTujuan() {
            const val = tipeSelect.val();
            
            // Sembunyikan semua dulu
            divSatker.hide();
            divPegawai.hide();

            // Reset requirement
            selectSatker.prop('required', false);
            selectPegawai.prop('required', false);

            // Logika Tampilan
            // Jika pilih 'universitas' (Rektor/Univ), inputan satker/pegawai TETAP DISEMBUNYIKAN
            // karena akan diproses di Admin Rektor (Disposisi)
            
            if (val === 'satker') {
                divSatker.show();
                selectSatker.prop('required', true);
            } 
            else if (val === 'pegawai') {
                divPegawai.show();
                selectPegawai.prop('required', true);
            }
        }

        // Jalankan saat change
        tipeSelect.on('change', toggleTujuan);
        
        // Jalankan saat load
        toggleTujuan();
    });
</script>
@endpush