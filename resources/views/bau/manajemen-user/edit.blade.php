@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-1">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            
            {{-- Card Utama --}}
            <div class="card shadow border-0 rounded-3">
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

                        {{-- Bagian 1: Informasi Akun --}}
                        <h6 class="text-secondary text-uppercase fw-bold mb-3 small border-bottom pb-2">Informasi Akun</h6>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0 @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $user->name) }}" required placeholder="Nama Lengkap User">
                                </div>
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $user->email) }}" required placeholder="email@contoh.com">
                                </div>
                                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="no_hp" class="form-label fw-semibold">Nomor WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-whatsapp text-success"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0 @error('no_hp') is-invalid @enderror" 
                                           id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" 
                                           placeholder="Contoh: 62812345, 62898765 (Pisahkan dengan koma)">
                                </div>
                                <div class="form-text text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> Masukkan nomor dengan awalan 62. Jika lebih dari satu, pisahkan dengan koma (,).
                                </div>
                                {{-- Tambahkan pesan error untuk no_hp --}}
                                @error('no_hp') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Bagian 2: Role & Akses --}}
                        <h6 class="text-secondary text-uppercase fw-bold mb-3 small border-bottom pb-2">Role & Akses</h6>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold">Role / Peran <span class="text-danger">*</span></label>
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
                                <label for="satker_id" class="form-label fw-semibold">Satuan Kerja (Satker)</label>
                                <select class="form-select @error('satker_id') is-invalid @enderror" id="satker_id" name="satker_id">
                                    <option value="">-- Tidak Terhubung ke Satker --</option>
                                    @foreach ($daftarSatker as $satker)
                                        <option value="{{ $satker->id }}" {{ old('satker_id', $user->satker_id) == $satker->id ? 'selected' : '' }}>
                                            {{ $satker->nama_satker }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small">Wajib dipilih jika Role adalah Satker atau Pegawai.</div>
                                @error('satker_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Bagian 3: Keamanan --}}
                        <div class="bg-light p-3 rounded border mb-4">
                            <h6 class="text-dark fw-bold mb-3 small"><i class="bi bi-shield-lock me-2"></i>Ubah Password (Opsional)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label small">Password Baru</label>
                                    <input type="password" class="form-control form-control-sm @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Minimal 8 karakter">
                                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label small">Konfirmasi Password</label>
                                    <input type="password" class="form-control form-control-sm" 
                                           id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru">
                                </div>
                                <div class="col-12">
                                    <div class="form-text small fst-italic text-muted">Biarkan kosong jika tidak ingin mengubah password user.</div>
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                <i class="bi bi-check-lg me-2"></i> Simpan Perubahan Data
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection