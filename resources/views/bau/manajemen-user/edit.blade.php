@extends('layouts.app')

@push('styles')
<style>
    /* Style 13px */
    .card-body .form-label, .card-body .form-control, .card-body .form-select, .form-text { font-size: 13px; }
    .card-body .form-control, .card-body .form-select { padding: 0.3rem 0.6rem; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header py-3 bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-fill me-2"></i> Edit User: {{ $user->name }}</h6>
                        <a href="{{ route('bau.manajemen-user.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('bau.manajemen-user.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email: <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr class="my-4">
                        
                        <h6 class="text-muted mb-3 fw-bold">Ubah Password (Opsional)</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password Baru:</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password Baru:</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="text-muted mb-3 fw-bold">Role & Satker</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role / Peran: <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="bau" {{ old('role', $user->role) == 'bau' ? 'selected' : '' }}>Admin BAU</option>
                                    <option value="admin_rektor" {{ old('role', $user->role) == 'admin_rektor' ? 'selected' : '' }}>Admin Rektor</option>
                                    <option value="satker" {{ old('role', $user->role) == 'satker' ? 'selected' : '' }}>Admin Satker</option>
                                    <option value="pegawai" {{ old('role', $user->role) == 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="satker_id" class="form-label">Satuan Kerja (Satker):</label>
                                <select class="form-select @error('satker_id') is-invalid @enderror" id="satker_id" name="satker_id">
                                    <option value="">-- Tidak Terhubung ke Satker --</option>
                                    @foreach ($daftarSatker as $satker)
                                        <option value="{{ $satker->id }}" {{ old('satker_id', $user->satker_id) == $satker->id ? 'selected' : '' }}>
                                            {{ $satker->nama_satker }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Kosongkan jika Admin Rektor / Super Admin.</div>
                                @error('satker_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
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