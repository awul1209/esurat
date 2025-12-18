@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 mb-4 mt-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-box-arrow-up me-2"></i>Buat Surat Keluar Eksternal</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('satker.surat-keluar.eksternal.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_surat" class="form-control" placeholder="Nomor Surat..." required>
                        </div>

                        {{-- INPUT MANUAL TUJUAN --}}
                        {{-- Ganti name="tujuan_eksternal" menjadi name="tujuan_luar" --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tujuan Surat (Pihak Luar) <span class="text-danger">*</span></label>
                            <input type="text" name="tujuan_luar" class="form-control" placeholder="Contoh: Dinas Pendidikan, PT. Telkom" required>
                            <div class="form-text">Tuliskan nama instansi atau perseorangan tujuan secara lengkap.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Perihal <span class="text-danger">*</span></label>
                            <input type="text" name="perihal" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal Surat <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_surat" class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">File Surat <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" class="form-control" accept=".pdf,.jpg,.png" required>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('satker.surat-keluar.eksternal.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i> Simpan Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection