@extends('layouts.app')

@push('styles')
<style>
    /* Konsistensi Style Dashboard */
    .card-body .form-label { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 0.4rem; }
    .card-body .form-control, .card-body .form-select { 
        font-size: 13px; 
        padding: 0.5rem 0.75rem; 
        border-radius: 8px;
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
<div class="container-fluid px-3 py-1">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-primary">
                        <i class="bi bi-person-fill-gear me-2"></i>Edit Data User
                    </h5>
                    <a href="{{ route('bau.manajemen-user.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('bau.manajemen-user.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Bagian 1: Identitas --}}
                        <div class="section-title">Informasi Pribadi</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $user->name) }}" required placeholder="Nama Lengkap User">
                                </div>
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Bagian 2: Kontak --}}
                        <div class="section-title">Kredensial & Kontak</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Utama (Login) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $user->email) }}" required placeholder="email@contoh.com">
                                </div>
                                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email2" class="form-label">Email Cadangan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope-plus"></i></span>
                                    <input type="email" class="form-control @error('email2') is-invalid @enderror" 
                                           id="email2" name="email2" value="{{ old('email2', $user->email2) }}" placeholder="email2@contoh.com">
                                </div>
                                @error('email2') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-12">
    <label for="no_hp" class="form-label">Nomor WhatsApp / HP</label>
    <div class="input-group">
        <span class="input-group-text bg-light"><i class="bi bi-whatsapp"></i></span>
        <input type="text" class="form-control" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="62812xxx, 62877xxx">
    </div>
    <div class="form-text small">Gunakan awalan 62. Pisahkan dengan koma jika lebih dari satu nomor.</div>
</div>

                        {{-- Bagian 3: Hak Akses --}}
                        <div class="section-title">Otoritas & Penempatan</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role / Peran <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="bau" {{ old('role', $user->role) == 'bau' ? 'selected' : '' }}>Admin BAU</option>
                                    <option value="admin_rektor" {{ old('role', $user->role) == 'admin_rektor' ? 'selected' : '' }}>Admin Rektor</option>
                                    <option value="satker" {{ old('role', $user->role) == 'satker' ? 'selected' : '' }}>Admin Satker</option>
                                    <option value="pegawai" {{ old('role', $user->role) == 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                                </select>
                                @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="satker_id" class="form-label">Satuan Kerja (Satker)</label>
                                <select class="form-select @error('satker_id') is-invalid @enderror" id="satker_id" name="satker_id">
                                    <option value="">-- Tidak Terhubung ke Satker --</option>
                                    @foreach ($daftarSatker as $satker)
                                        <option value="{{ $satker->id }}" {{ old('satker_id', $user->satker_id) == $satker->id ? 'selected' : '' }}>
                                            {{ $satker->nama_satker }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('satker_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Bagian 4: Keamanan --}}
                        <div class="bg-light p-4 rounded-3 border mb-4">
                            <div class="section-title border-0 mb-2">Ganti Password</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Kosongkan jika tidak diubah">
                                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" 
                                           id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru">
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="row mt-4">
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">
                                    <i class="bi bi-check-lg me-2"></i> Update Data User
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