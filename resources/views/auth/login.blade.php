@extends('layouts.guest')

@push('styles')
<style>
    /* Background Gradient Halus */
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    /* Styling Card Login */
    .login-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    /* Aksen garis warna di atas card */
    .login-card::before {
        content: "";
        display: block;
        height: 6px;
        background: linear-gradient(90deg, #0d6efd, #0dcaf0); /* Warna Biru Khas Bootstrap/Unija */
        width: 100%;
    }

    /* Animasi pada input */
    .form-control:focus {
        box-shadow: none;
        border-color: #0d6efd;
        background-color: #f8fbff;
    }

    /* Styling Tombol */
    .btn-login {
        border-radius: 50px;
        padding: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        background: #0d6efd;
        border: none;
        transition: all 0.3s;
    }

    .btn-login:hover {
        background: #0b5ed7;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
    }
    
    /* Logo Effect */
    .logo-img {
        transition: transform 0.3s;
    }
    .logo-img:hover {
        transform: scale(1.05);
    }
</style>
@endpush

@section('content')
<div class="container h-100">
    <div class="row justify-content-center align-items-center min-vh-100">
        
        <div class="col-md-8 col-lg-5 col-xl-5">
            
            <div class="card login-card animate__animated animate__fadeInUp">
                <div class="card-body p-4 p-md-5">

                    {{-- Header Logo & Judul --}}
                    <div class="text-center mb-5">
                        <img src="{{ asset('images/unija.jpg') }}" alt="Logo UNIJA" width="100px" class="logo-img mb-3 rounded-circle shadow-sm p-1 bg-white">
                        <h4 class="fw-bold text-dark">Sistem e-Surat</h4>
                        <p class="text-muted small text-uppercase letter-spacing-1">Universitas Wiraraja</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        {{-- Input Email dengan Floating Label --}}
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" placeholder="name@example.com" 
                                   value="{{ old('email') }}" required autocomplete="email" autofocus>
                            <label for="email" class="text-muted"><i class="bi bi-envelope me-1"></i> Alamat Email</label>
                            @error('email')
                                <span class="invalid-feedback text-start" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Input Password dengan Floating Label --}}
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" placeholder="Password" required autocomplete="current-password">
                            <label for="password" class="text-muted"><i class="bi bi-lock me-1"></i> Password</label>
                            @error('password')
                                <span class="invalid-feedback text-start" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Checkbox Remember Me --}}
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label small text-muted" for="remember">
                                    Ingat Saya
                                </label>
                            </div>
                            
                            @if (Route::has('password.request'))
                                <a class="text-decoration-none small text-primary fw-bold" href="{{ route('password.request') }}">
                                    Lupa Password?
                                </a>
                            @endif
                        </div>

                        {{-- Tombol Login --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-login btn-lg text-white">
                                MASUK <i class="bi bi-box-arrow-in-right ms-2"></i>
                            </button>
                        </div>

                    </form>
                </div>
                
                {{-- Footer Kecil di dalam Card --}}
                <div class="card-footer bg-light border-0 text-center py-3">
                    <small class="text-muted">© {{ date('Y') }} Universitas Wiraraja</small>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection