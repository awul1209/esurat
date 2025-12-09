@extends('layouts.app')

@push('styles')
<style>
    .card-body .form-label, .card-body .form-control, .form-text, .form-select { font-size: 13px; }
    .card-body .form-control, .form-select { padding: 0.3rem 0.6rem; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header py-3 bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-fill me-2"></i> Edit Arsip Surat Keluar</h6>
                        <a href="{{ route('bau.surat-keluar.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('bau.surat-keluar.update', $suratKeluar->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <h6 class="text-muted mb-3 fw-bold">Informasi Surat</h6>

                        <div class="row mb-3">
                            <div class="col-md-7">
                                <label for="nomor_surat" class="form-label">Nomor Surat: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nomor_surat') is-invalid @enderror" id="nomor_surat" name="nomor_surat" value="{{ old('nomor_surat', $suratKeluar->nomor_surat) }}" required>
                                @error('nomor_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label for="tanggal_surat" class="form-label">Tanggal Surat: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_surat') is-invalid @enderror" id="tanggal_surat" name="tanggal_surat" value="{{ old('tanggal_surat', $suratKeluar->tanggal_surat->format('Y-m-d')) }}" required>
                                @error('tanggal_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tujuan_surat" class="form-label">Tujuan Surat: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('tujuan_surat') is-invalid @enderror" id="tujuan_surat" name="tujuan_surat" value="{{ old('tujuan_surat', $suratKeluar->tujuan_surat) }}" required>
                            @error('tujuan_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="perihal" class="form-label">Perihal: <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('perihal') is-invalid @enderror" id="perihal" name="perihal" rows="3" required>{{ old('perihal', $suratKeluar->perihal) }}</textarea>
                            @error('perihal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr class="my-4">
                        <h6 class="text-muted mb-3 fw-bold">File Arsip</h6>

                        <div class="mb-3">
                            <label for="file_surat" class="form-label">Upload File Baru (Opsional):</label>
                            <input class="form-control @error('file_surat') is-invalid @enderror" type="file" id="file_surat" name="file_surat" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="form-text mt-2">
                                File saat ini: 
                                <a href="{{ Storage::url($suratKeluar->file_surat) }}" target="_blank">
                                    <i class="bi bi-file-earmark-text"></i>
                                    {{ $suratKeluar->nomor_surat }} (Lihat)
                                </a>
                            </div>
                            @error('file_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

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
    </div>
</div>
@endsection