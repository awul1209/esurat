@extends('layouts.app')

{{-- Menambahkan style 13px yang sama dengan form input --}}
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
    
    <div class="card shadow-sm border-0">
        <div class="card-header py-3 bg-light border-0">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-fill me-2"></i> Edit Surat Masuk</h6>
                <a href="{{ route('bau.surat.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            
            {{-- 
              ====================================================
              PERUBAHAN FORM:
              1. action -> route('bau.surat.update', $surat->id)
              2. @method('PUT') ditambahkan
              ====================================================
            --}}
            <form action="{{ route('bau.surat.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <h6 class="text-muted mb-3 fw-bold">Informasi Surat</h6>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="surat_dari" class="form-label">Surat dari:</label>
                        {{-- PERUBAHAN: Menambahkan value() dengan data lama --}}
                        <input type="text" class="form-control" id="surat_dari" name="surat_dari" 
                               value="{{ old('surat_dari', $surat->surat_dari) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tipe_surat" class="form-label">Tipe Surat:</label>
                        {{-- PERUBAHAN: Menambahkan logic 'selected' --}}
                        <select class="form-select" id="tipe_surat" name="tipe_surat" required>
                            <option value="eksternal" {{ old('tipe_surat', $surat->tipe_surat) == 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                            <option value="internal" {{ old('tipe_surat', $surat->tipe_surat) == 'internal' ? 'selected' : '' }}>Internal</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nomor_surat" class="form-label">Nomor Surat:</label>
                        <input type="text" class="form-control" id="nomor_surat" name="nomor_surat" 
                               value="{{ old('nomor_surat', $surat->nomor_surat) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal_surat" class="form-label">Tanggal Surat:</label>
                        {{-- PERUBAHAN: Menambahkan value() dan format tanggal --}}
                        <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" 
                               value="{{ old('tanggal_surat', $surat->tanggal_surat->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="perihal" class="form-label">Perihal:</label>
                    {{-- PERUBAHAN: Mengisi value di textarea --}}
                    <textarea class="form-control" id="perihal" name="perihal" rows="3" required>{{ old('perihal', $surat->perihal) }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="tujuan_satker_id" class="form-label">Tujuan Satker:</label>
                    {{-- PERUBAHAN: Menambahkan logic 'selected' --}}
                    <select class="form-select" id="tujuan_satker_id" name="tujuan_satker_id">
                        <option value="">-- Pilih Satuan Kerja Tujuan --</option>
                        @foreach ($daftarSatker as $satker)
                            <option value="{{ $satker->id }}" {{ old('tujuan_satker_id', $surat->tujuan_satker_id) == $satker->id ? 'selected' : '' }}>
                                {{ $satker->nama_satker }}
                            </option>
                        @endforeach
                        <option value="" {{ old('tujuan_satker_id', $surat->tujuan_satker_id) == null ? 'selected' : '' }}>(Lainnya / Tujuan Rektor)</option>
                    </select>
                </div>

                <hr class="my-4">

                <h6 class="text-muted mb-3 fw-bold">Informasi Agenda & File</h6>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="no_agenda" class="form-label">No. Agenda:</label>
                        <input type="text" class="form-control" id="no_agenda" name="no_agenda" 
                               value="{{ old('no_agenda', $surat->no_agenda) }}" required>
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
                    {{-- PERUBAHAN: File dibuat opsional (required dihapus) --}}
                    <label for="file_surat" class="form-label">Upload File Baru (Opsional):</label>
                    <input class="form-control" type="file" id="file_surat" name="file_surat" accept=".pdf,.jpg,.jpeg,.png">
                    {{-- PERUBAHAN: Menampilkan file yang ada saat ini --}}
                    <div class="form-text mt-2">
                        File saat ini: 
                        <a href="{{ Storage::url($surat->file_surat) }}" target="_blank">
                            <i class="bi bi-file-earmark-text"></i>
                            {{ $surat->perihal }} (Lihat)
                        </a>
                    </div>
                </div>

                {{-- 
                  ====================================================
                  PERUBAHAN: Tombol Aksi
                  Hanya ada satu tombol "Simpan Perubahan"
                  ====================================================
                --}}
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-save-fill me-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection