@extends('layouts.app')

{{-- Style untuk font 13px (konsisten) --}}
@push('styles')
<style>
    .card-body .form-label,
    .card-body .form-control,
    .card-body .form-text {
        font-size: 13px;
    }
    .card-body .form-control {
         padding: 0.3rem 0.6rem; 
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-8"> {{-- Buat form lebih sempit agar rapi --}}

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Terjadi kesalahan. Periksa input Anda.
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-person-fill-gear me-2"></i> Edit Profil Saya</h6>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('profil.update') }}" method="POST">
                        @csrf
                        @method('PUT') {{-- Gunakan method PUT untuk update --}}
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap:</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" 
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" 
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <h6 class="text-muted mb-3 fw-bold">Ubah Password (Opsional)</h6>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru:</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password">
                            <div class="form-text">Kosongkan jika Anda tidak ingin mengubah password.</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru:</label>
                            <input type="password" class="form-control" 
                                   id="password_confirmation" name="password_confirmation">
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