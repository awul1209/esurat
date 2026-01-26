@extends('layouts.app')

@push('styles')
<style>
    /* Styling khusus untuk halaman profil agar lebih elegan */
    .card-profile {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    .card-header-profile {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 2rem;
        border-bottom: none;
    }
    .form-label {
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #4e73df;
        margin-bottom: 0.5rem;
    }
    .form-control {
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        border: 1px solid #eaecf4;
        background-color: #f8f9fc;
        transition: all 0.2s;
    }
    .form-control:focus {
        background-color: #fff;
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.15);
    }
    .input-group-text {
        border-radius: 0.75rem 0 0 0.75rem !important;
        border: 1px solid #eaecf4;
        background-color: #f8f9fc;
    }
    .btn-save {
        border-radius: 0.75rem;
        padding: 0.8rem 2.5rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        transition: all 0.3s;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(78, 115, 223, 0.3);
    }
    .section-divider {
        height: 1px;
        background: linear-gradient(to right, #eaecf4, transparent);
        margin: 2rem 0;
    }
    .section-title-text {
        font-size: 0.9rem;
        font-weight: 800;
        color: #5a5c69;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
    }
    .section-title-text i {
        margin-right: 10px;
        color: #4e73df;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12">

            {{-- Notifikasi Sukses --}}
            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Pembaruan Berhasil</h6>
                            <small>{{ session('success') }}</small>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card card-profile">
                {{-- Header Card --}}
                <div class="card-header card-header-profile">
                    <div class="d-flex align-items-center">
                        <div class="ms-4">
                            <h4 class="m-0 fw-bold text-white">Pengaturan Profil</h4>
                            <p class="m-0 text-white-50">Kelola informasi identitas dan keamanan akun Anda</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form action="{{ route('profil.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Section 1: Data Identitas --}}
                        <div class="section-title-text text-primary">
                            <i class="bi bi-card-list"></i> INFORMASI IDENTITAS
                        </div>
                        
                        <div class="row g-4 mb-2">
                            {{-- Input Nama Lengkap --}}
                            <div class="col-md-7">
                                <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                </div>
                                @error('name') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                            </div>

                            {{-- Input Nomor WhatsApp (Satu baris dengan Nama) --}}
                            <div class="col-md-5">
                                <label for="no_hp" class="form-label">Nomor WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" class="form-control @error('no_hp') is-invalid @enderror" 
                                           id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="Contoh: 628123456789">
                                </div>
                                @error('no_hp') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Utama (Login)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                </div>
                                @error('email') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email2" class="form-label">Email Cadangan</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope-plus"></i></span>
                                    <input type="email" class="form-control @error('email2') is-invalid @enderror" 
                                           id="email2" name="email2" value="{{ old('email2', $user->email2) }}" placeholder="email.cadangan@domain.com">
                                </div>
                                @error('email2') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="section-divider"></div>

                        {{-- Section 2: Keamanan --}}
                        <div class="section-title-text text-primary">
                            <i class="bi bi-shield-lock"></i> PENGATURAN KEAMANAN
                        </div>

                        <div class="p-3 rounded-4 mb-4" style="background-color: #fff9e6; border: 1px dashed #ffeeba;">
                            <div class="d-flex">
                                <i class="bi bi-info-circle-fill text-warning fs-5 me-3"></i>
                                <p class="mb-0 small text-dark">
                                    <strong>Tips Keamanan:</strong> Gunakan kombinasi huruf besar, kecil, angka, dan simbol. Jika tidak ingin mengganti password, cukup kosongkan kedua kolom di bawah ini.
                                </p>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Minimal 8 karakter">
                                </div>
                                @error('password') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                    <input type="password" class="form-control" 
                                           id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru">
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Submit --}}
                        <div class="d-flex justify-content-center justify-content-md-end mt-5 pt-3">
                            <button type="submit" class="btn btn-primary btn-save shadow-sm">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i> Perbarui Profil Saya
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection