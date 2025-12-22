@extends('layouts.app')

@push('styles')
<style>
    /* Styling khusus untuk halaman profil agar lebih elegan */
    .card-profile {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        overflow: hidden; /* Agar border radius header tidak terpotong */
    }
    .card-header-profile {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 1.5rem;
        border-bottom: none;
    }
    .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #5a5c69;
        margin-bottom: 0.5rem;
    }
    .form-control {
        border-radius: 0.5rem;
        padding: 0.6rem 1rem; /* Padding input yang nyaman */
        font-size: 0.9rem;
        border: 1px solid #d1d3e2;
    }
    .form-control:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }
    .btn-save {
        border-radius: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    .section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #b7b9cc;
        margin-bottom: 1rem;
        display: block;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-12"> {{-- Lebar kolom yang optimal untuk form --}}

            {{-- Alert Sukses --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Berhasil!</strong> {{ session('success') }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Alert Error --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Terjadi Kesalahan!</strong> Mohon periksa kembali inputan Anda di bawah ini.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card card-profile">
                {{-- Header Card --}}
                <div class="card-header card-header-profile d-flex align-items-center">
                    <div class="me-3 bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-person-lines-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="m-0 fw-bold" style="color:white;">Edit Profil Saya</h5>
                        <p class="m-0 small text-white-50">Perbarui informasi akun dan kata sandi Anda.</p>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <form action="{{ route('profil.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Bagian 1: Informasi Dasar --}}
                        <span class="section-title"><i class="bi bi-person-badge me-1"></i> Informasi Akun</span>
                        
                        <div class="row g-4 mb-4">
                            {{-- Input Nama --}}
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0 @error('name') is-invalid @enderror" 
                                           id="name" name="name" 
                                           value="{{ old('name', $user->name) }}" required placeholder="Masukkan nama lengkap Anda">
                                </div>
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            {{-- Input Email --}}
                            <div class="col-md-6">
                                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" 
                                           id="email" name="email" 
                                           value="{{ old('email', $user->email) }}" required placeholder="contoh@email.com">
                                </div>
                                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-4 border-light">

                        {{-- Bagian 2: Keamanan (Password) --}}
                        <span class="section-title"><i class="bi bi-shield-lock me-1"></i> Keamanan & Password</span>
                        
                        <div class="alert alert-light border border-light-subtle d-flex align-items-start mb-4" role="alert">
                            <i class="bi bi-info-circle-fill text-info me-2 mt-1"></i>
                            <small class="text-muted">Biarkan kolom password kosong jika Anda tidak ingin mengubah kata sandi saat ini.</small>
                        </div>

                        <div class="row g-4 mb-4">
                            {{-- Input Password Baru --}}
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Minimal 8 karakter">
                                </div>
                                @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            {{-- Input Konfirmasi Password --}}
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-check2-circle"></i></span>
                                    <input type="password" class="form-control border-start-0 ps-0" 
                                           id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru">
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="d-flex justify-content-end pt-3">
                            <button type="submit" class="btn btn-primary btn-save px-5 shadow-sm">
                                <i class="bi bi-floppy2-fill me-2"></i> Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection