@extends('layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        
        <div class="col-md-8 col-lg-6 col-xl-5">
            
            <div class="card shadow-lg border-0 rounded-3 my-5">
                
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <img src="{{ Vite::asset('resources/images/unija.jpg') }}" alt="Logo UNIJA" width="120px">
                        <h3 class="mt-3 mb-0">Sistem e-Surat</h3>
                        <p class="text-muted">Universitas Wiraraja</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Alamat Email</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Ingat Saya
                            </label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Login
                            </button>
                        </div>
                        
                        @if (Route::has('password.request'))
                            <div class="text-center mt-3">
                                <a class="btn btn-link" href="{{ route('password.request') }}">
                                    Lupa Password Anda?
                                </a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection