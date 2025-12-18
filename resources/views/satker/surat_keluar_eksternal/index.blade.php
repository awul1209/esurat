@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4 mt-4">
        <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">Surat Keluar Eksternal</h6>
            <a href="{{ route('satker.surat-keluar.eksternal.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Surat Baru
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm" id="tabelEksternal">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Tujuan Surat</th>
                            <th>No. Surat & Perihal</th>
                            <th>Tgl. Surat</th>
                            <th class="text-center">File</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suratKeluar as $surat)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <span class="fw-bold text-dark">{{ $surat->tujuan_luar }}</span>
                                <br><small class="text-muted"><i class="bi bi-building"></i> Pihak Luar</small>
                            </td>
                            <td>
                                <span class="fw-bold">{{ $surat->nomor_surat }}</span>
                                <br><small class="text-muted">{{ $surat->perihal }}</small>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d/m/Y') }}</td>
                            
                            {{-- KOLOM FILE: TRIGGER MODAL --}}
                            <td class="text-center">
                                @if($surat->file_surat)
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#previewFileModal"
                                            data-title="{{ $surat->perihal }}"
                                            data-file="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye me-1"></i> Lihat
                                    </button>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('satker.surat-keluar.eksternal.edit', $surat->id) }}" class="btn btn-sm btn-warning text-white" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <form action="{{ route('satker.surat-keluar.eksternal.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus surat ini?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Hapus">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada surat keluar eksternal.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW FILE --}}
<div class="modal fade" id="previewFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl"> {{-- Ukuran Extra Large agar lega --}}
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Area Preview --}}
                <div id="fileContainer" class="d-flex justify-content-center align-items-center bg-light" style="height: 75vh; width: 100%; overflow: hidden;">
                    {{-- Konten akan diisi via JavaScript --}}
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="downloadBtn" class="btn btn-primary" download>
                    <i class="bi bi-download me-1"></i> Download File
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        var previewModal = document.getElementById('previewFileModal');
        
        previewModal.addEventListener('show.bs.modal', function (event) {
            // 1. Ambil data dari tombol yang diklik
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file');
            var title = button.getAttribute('data-title');

            // 2. Set Judul dan Link Download
            var modalTitle = previewModal.querySelector('#previewTitle');
            var downloadBtn = previewModal.querySelector('#downloadBtn');
            var container = previewModal.querySelector('#fileContainer');

            modalTitle.textContent = title;
            downloadBtn.href = fileUrl;

            // 3. Logika Tampilkan Preview (PDF / Gambar)
            container.innerHTML = ''; // Bersihkan konten lama

            if (fileUrl) {
                var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];

                if (ext === 'pdf') {
                    // Jika PDF, pakai Iframe
                    container.innerHTML = '<iframe src="' + fileUrl + '" width="100%" height="100%" style="border:none;"></iframe>';
                } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) {
                    // Jika Gambar, pakai IMG tag
                    container.innerHTML = '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">';
                } else {
                    // Format lain (misal DOCX) tidak bisa dipreview di browser
                    container.innerHTML = `
                        <div class="text-center p-5">
                            <i class="bi bi-file-earmark-text h1 text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3">Preview tidak tersedia untuk format ini.</h5>
                            <p class="text-muted">Silakan klik tombol download di bawah.</p>
                        </div>
                    `;
                }
            } else {
                container.innerHTML = '<div class="text-center p-5 text-danger">File tidak ditemukan.</div>';
            }
        });

        // Bersihkan iframe saat modal ditutup agar video/pdf berhenti memuat
        previewModal.addEventListener('hidden.bs.modal', function () {
            var container = previewModal.querySelector('#fileContainer');
            container.innerHTML = ''; 
        });
    });
</script>
@endpush