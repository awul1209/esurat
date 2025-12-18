@extends('layouts.app')

@push('styles')
<style>
    .card-body .form-label,
    .card-body .form-control,
    .card-body .form-select,
    .card-body .form-text {
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
    
    <div class="card shadow-sm border-0 mt-4 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-fill me-2"></i> Edit / Verifikasi Surat Masuk</h6>
                
                {{-- Tombol Kembali Dinamis --}}
                @php
                    $backRoute = ($surat->tipe_surat == 'internal') ? 'bau.surat.internal' : 'bau.surat.eksternal';
                @endphp
                <a href="{{ route($backRoute) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            
            <form action="{{ route('bau.surat.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <h6 class="text-muted mb-3 fw-bold border-bottom pb-2">1. Informasi Surat</h6>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="surat_dari" class="form-label">Surat dari:</label>
                        <input type="text" class="form-control" id="surat_dari" name="surat_dari" 
                               value="{{ old('surat_dari', $surat->surat_dari) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tipe_surat" class="form-label">Tipe Surat:</label>
                        {{-- Disabled karena tipe tidak boleh berubah --}}
                        <input type="text" class="form-control bg-light" value="{{ ucfirst($surat->tipe_surat) }}" readonly>
                        <input type="hidden" name="tipe_surat" value="{{ $surat->tipe_surat }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nomor_surat" class="form-label">Nomor Surat (Asli):</label>
                        <input type="text" class="form-control" id="nomor_surat" name="nomor_surat" 
                               value="{{ old('nomor_surat', $surat->nomor_surat) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal_surat" class="form-label">Tanggal Surat:</label>
                        <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" 
                               value="{{ old('tanggal_surat', $surat->tanggal_surat->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="perihal" class="form-label">Perihal:</label>
                    <textarea class="form-control" id="perihal" name="perihal" rows="2" required>{{ old('perihal', $surat->perihal) }}</textarea>
                </div>

                {{-- Hanya tampilkan tujuan, tidak perlu diedit jika sudah benar --}}
                <div class="mb-3">
                    <label class="form-label">Tujuan Surat:</label>
                    <input type="text" class="form-control bg-light" value="{{ ucfirst($surat->tujuan_tipe) }}" readonly>
                </div>

                <h6 class="text-muted mb-3 fw-bold border-bottom pb-2 mt-4">2. Registrasi Agenda (Diisi oleh BAU)</h6>

                <div class="row mb-3">
                    {{-- 
                        ==========================================================
                        INPUT NO. AGENDA (YANG DIMINTA)
                        BAU mengubah "PENDING-XXX" menjadi Nomor Agenda Asli disini
                        ==========================================================
                    --}}
                    <div class="col-md-4">
                        <label for="no_agenda" class="form-label fw-bold text-primary">No. Agenda Baru:</label>
                        <input type="text" class="form-control border-primary" id="no_agenda" name="no_agenda" 
                               value="{{ old('no_agenda', $surat->no_agenda) }}" 
                               placeholder="Contoh: 001/UN/2025" required>
                        <div class="form-text text-primary">
                            <i class="bi bi-info-circle"></i> Masukkan nomor agenda resmi sebelum diteruskan.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="diterima_tanggal" class="form-label">Diterima Tanggal:</label>
                        <input type="date" class="form-control" id="diterima_tanggal" name="diterima_tanggal" 
                               value="{{ old('diterima_tanggal', $surat->diterima_tanggal->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="sifat" class="form-label">Sifat:</label>
                        <select class="form-select" id="sifat" name="sifat" required>
                            <option value="Asli" {{ old('sifat', $surat->sifat) == 'Asli' ? 'selected' : '' }}>Asli</option>
                            <option value="Tembusan" {{ old('sifat', $surat->sifat) == 'Tembusan' ? 'selected' : '' }}>Tembusan</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="file_surat" class="form-label">File Surat (Opsional):</label>
                    <input class="form-control" type="file" id="file_surat" name="file_surat" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="form-text mt-2">
                        File saat ini: 
                        <a href="{{ Storage::url($surat->file_surat) }}" target="_blank">
                            <i class="bi bi-file-earmark-text"></i>
                            Lihat File Asli
                        </a>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-warning text-white btn-lg w-100">
                            <i class="bi bi-save-fill me-2"></i> Simpan & Perbarui Agenda
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection