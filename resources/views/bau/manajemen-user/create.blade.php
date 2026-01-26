@extends('layouts.app')

@push('styles')
<style>
    /* Style Dasar 13px untuk Form */
    .card-body .form-label { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 0.4rem; }
    .card-body .form-control, .card-body .form-select { 
        font-size: 13px; 
        padding: 0.5rem 0.75rem; 
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .card-body .form-control:focus, .card-body .form-select:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
    }
    .section-title {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #888;
        font-weight: 700;
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header py-3 bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-person-plus-fill me-2"></i> Tambah User Baru</h6>
                        <a href="{{ route('bau.manajemen-user.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('bau.manajemen-user.store') }}" method="POST">
                        @csrf
                        
                        <div class="section-title">Informasi Pribadi</div>
                        <div class="row mb-3">
                            <div class="col-md-7">
                                <label for="name" class="form-label">Nama Lengkap: <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Masukkan nama lengkap">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label for="no_hp" class="form-label">No. HP (WhatsApp):</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted small" style="font-size: 11px;">+62</span>
                                    <input type="text" class="form-control" id="no_hp" name="no_hp" placeholder="812345xxx" value="{{ old('no_hp') }}">
                                </div>
                            </div>
                        </div>

                        <div class="section-title mt-4">Kredensial & Kontak</div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Utama (Login): <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope small"></i></span>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="email@domain.com">
                                </div>
                                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email2" class="form-label">Email Cadangan (Opsional):</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope-plus small"></i></span>
                                    <input type="email" class="form-control @error('email2') is-invalid @enderror" id="email2" name="email2" value="{{ old('email2') }}" placeholder="email2@domain.com">
                                </div>
                                @error('email2') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password: <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Minimal 8 karakter">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password: <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi password">
                            </div>
                        </div>

                        <div class="section-title mt-4">Otoritas & Penempatan</div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role / Peran: <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="bau" {{ old('role') == 'bau' ? 'selected' : '' }}>Admin BAU</option>
                                    <option value="admin_rektor" {{ old('role') == 'admin_rektor' ? 'selected' : '' }}>Admin Rektor</option>
                                    <option value="satker" {{ old('role') == 'satker' ? 'selected' : '' }}>Admin Satker</option>
                                    <option value="pegawai" {{ old('role') == 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="satker_id" class="form-label">Satuan Kerja (Satker):</label>
                                <select class="form-select @error('satker_id') is-invalid @enderror" id="satker_id" name="satker_id">
                                    <option value="">-- Tidak Terhubung ke Satker --</option>
                                    @foreach ($daftarSatker as $satker)
                                        <option value="{{ $satker->id }}" {{ old('satker_id') == $satker->id ? 'selected' : '' }}>
                                            {{ $satker->nama_satker }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text mt-1" style="font-size: 11px;">Kosongkan jika Admin Rektor / Super Admin.</div>
                                @error('satker_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row mt-5">
                            <div class="col-md-12 text-end">
                                <button type="reset" class="btn btn-light rounded-pill px-4 me-2">Reset</button>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">
                                    <i class="bi bi-save-fill me-2"></i> Simpan User
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