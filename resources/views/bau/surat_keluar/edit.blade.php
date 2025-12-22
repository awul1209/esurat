@extends('layouts.app')

@push('styles')
<style>
    .card-body .form-label, .card-body .form-control, .form-text, .form-select { font-size: 13px; }
    .card-body .form-control, .form-select { padding: 0.3rem 0.6rem; }
    
    /* Styling Preview */
    #file-preview-container {
        border: 1px dashed #ced4da;
        padding: 10px;
        border-radius: 5px;
        min-height: 100px;
        background-color: #f8f9fa;
        margin-top: 10px;
        display: none; /* Default hide sampai ada file baru */
    }
    #file-preview-image { max-width: 100%; max-height: 300px; object-fit: contain; }
    #file-preview-pdf { width: 100%; height: 300px; border: none; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 mt-4 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-fill me-2"></i> Edit Arsip Surat Keluar</h6>
                        
                        {{-- TOMBOL KEMBALI DINAMIS (OPSIONAL: JIKA INGIN DIPAKAI LAGI NANTI) --}}
                        {{-- Saya hapus sesuai permintaan, tapi saya ganti dengan tombol batal di bawah form --}}
                    </div>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('bau.surat-keluar.update', $suratKeluar->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <h6 class="text-muted mb-3 fw-bold">Informasi Surat</h6>

                        <div class="row mb-3">
                            <div class="col-md-7">
                                <label for="nomor_surat" class="form-label">Nomor Surat: <span class="text-danger">*</span></label>
                                <input readonly type="text" class="form-control @error('nomor_surat') is-invalid @enderror" id="nomor_surat" name="nomor_surat" value="{{ old('nomor_surat', $suratKeluar->nomor_surat) }}" required>
                                @error('nomor_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label for="tanggal_surat" class="form-label">Tanggal Surat: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_surat') is-invalid @enderror" id="tanggal_surat" name="tanggal_surat" value="{{ old('tanggal_surat', $suratKeluar->tanggal_surat->format('Y-m-d')) }}" required>
                                @error('tanggal_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tujuan_surat" class="form-label">Tujuan Surat: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('tujuan_surat') is-invalid @enderror" id="tujuan_surat" name="tujuan_surat" value="{{ old('tujuan_surat', $suratKeluar->tujuan_surat) }}" required>
                            @error('tujuan_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="perihal" class="form-label">Perihal: <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('perihal') is-invalid @enderror" id="perihal" name="perihal" rows="3" required>{{ old('perihal', $suratKeluar->perihal) }}</textarea>
                            @error('perihal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr class="my-4">
                        <h6 class="text-muted mb-3 fw-bold">File Arsip</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">File Saat Ini:</label>
                                <div class="border rounded p-3 text-center bg-light" style="min-height: 200px;">
                                    {{-- Preview File Lama --}}
                                    @php
                                        $ext = pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION);
                                        $url = Storage::url($suratKeluar->file_surat);
                                    @endphp

                                    @if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                                        <img src="{{ $url }}" class="img-fluid" style="max-height: 180px;">
                                    @elseif(strtolower($ext) == 'pdf')
                                        <iframe src="{{ $url }}" width="100%" height="180px" style="border:none;"></iframe>
                                    @else
                                        <div class="py-5 text-muted"><i class="bi bi-file-earmark-text h1"></i><br>Preview tidak tersedia</div>
                                    @endif
                                    
                                    <div class="mt-2">
                                        <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Lihat Full</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="file_surat" class="form-label">Ganti File (Opsional):</label>
                                <input class="form-control @error('file_surat') is-invalid @enderror" type="file" id="file_surat" name="file_surat" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text">Biarkan kosong jika tidak ingin mengubah file.</div>
                                @error('file_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                {{-- Area Preview File Baru --}}
                                <div id="file-preview-container">
                                    <p class="text-muted small mb-0 fw-bold">Preview File Baru:</p>
                                    <img id="file-preview-image" src="" alt="Preview" style="display: none;">
                                    <iframe id="file-preview-pdf" src="" style="display: none;"></iframe>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            {{-- LOGIKA TOMBOL BATAL DINAMIS --}}
                            @php
                                $routeBatal = ($suratKeluar->tipe_kirim == 'internal') 
                                    ? route('bau.surat-keluar.internal') 
                                    : route('bau.surat-keluar.eksternal');
                            @endphp

                            <div class="col-md-6">
                                <a href="{{ $routeBatal }}" class="btn btn-secondary w-100">
                                    <i class="bi bi-arrow-left me-2"></i> Batal
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary w-100">
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

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        // Logic Preview File Baru
        $('#file_surat').on('change', function(e) {
            var file = e.target.files[0];
            var container = $('#file-preview-container');
            var imgPreview = $('#file-preview-image');
            var pdfPreview = $('#file-preview-pdf');

            container.hide();

            if(file) {
                container.show();
                var reader = new FileReader();
                reader.onload = function(ev) {
                    if(file.type.startsWith('image/')) {
                        imgPreview.attr('src', ev.target.result).show();
                        pdfPreview.hide();
                    } else if(file.type === 'application/pdf') {
                        pdfPreview.attr('src', ev.target.result).show();
                        imgPreview.hide();
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    });
</script>
@endpush