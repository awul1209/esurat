@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS Penyesuaian */
    .table td, .table th { vertical-align: middle; font-size: 14px; }
    .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-white border-bottom">
            <h6 class="m-0 fw-bold text-primary">Daftar Surat Masuk (Dari Satker Lain)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratMasuk" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="25%">Pengirim</th>
                            <th width="40%">No. Surat & Perihal</th>
                            <th width="20%">Tanggal Surat</th>
                            <th class="text-center" width="10%">File</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suratMasuk as $surat)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                {{-- Menampilkan Pengirim (Satker) --}}
                                @if($surat->user && $surat->user->satker)
                                    <span class="fw-bold text-primary">{{ $surat->user->satker->nama_satker }}</span>
                                    <br>
                                    <small class="text-muted"><i class="bi bi-person-fill"></i> {{ $surat->user->name }}</small>
                                @else
                                    <span class="text-danger">Pengirim Tidak Dikenal</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $surat->nomor_surat }}</div>
                                <div class="text-muted small">{{ $surat->perihal }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center text-secondary">
                                    <i class="bi bi-calendar-event me-2"></i>
                                    {{ $surat->tanggal_surat->format('d M Y') }}
                                </div>
                            </td>
                            <td class="text-center">
                                {{-- 
                                    TOMBOL PREVIEW FILE
                                    Memicu Modal #filePreviewModal
                                --}}
                                <button type="button" class="btn btn-outline-info btn-sm btn-icon" 
                                    title="Lihat & Download File"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#filePreviewModal"
                                    data-title="{{ $surat->perihal }}"
                                    data-file-url="{{ Storage::url($surat->file_surat) }}">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW FILE --}}
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filePreviewModalLabel">Preview Surat Masuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Area Preview (Iframe/Image) --}}
                <div id="file-viewer-container" class="bg-light d-flex align-items-center justify-content-center" style="height: 75vh; width: 100%;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <span class="me-auto text-muted small" id="preview-filename"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btn-download-file" class="btn btn-primary" download>
                    <i class="bi bi-download me-1"></i> Download File
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
<script>
    $(document).ready(function () {
        // Inisialisasi DataTable
        $('#tabelSuratMasuk').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json",
                emptyTable: "Belum ada surat masuk internal."
            },
            order: [[ 3, 'desc' ]] // Urutkan berdasarkan tanggal
        });

        // Logika Modal Preview (Sama persis dengan Surat Keluar)
        var fileModal = document.getElementById('filePreviewModal');
        fileModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file-url');
            var title = button.getAttribute('data-title');
            var modalTitle = fileModal.querySelector('.modal-title');
            var container = fileModal.querySelector('#file-viewer-container');
            var downloadBtn = fileModal.querySelector('#btn-download-file');
            var filenameLabel = fileModal.querySelector('#preview-filename');

            modalTitle.textContent = "Preview: " + title;
            downloadBtn.href = fileUrl;
            filenameLabel.textContent = fileUrl.split('/').pop();
            
            // Tampilkan Loading
            container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

            var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0];

            setTimeout(function() {
                var contentHtml = '';
                if (extension === 'pdf') {
                    // PDF
                    contentHtml = `<iframe src="${fileUrl}" width="100%" height="100%" style="border:none;"></iframe>`;
                } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
                    // Gambar
                    contentHtml = `<img src="${fileUrl}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">`;
                } else {
                    // Lainnya
                    contentHtml = `<div class="text-center p-5"><i class="bi bi-file-earmark-break h1 text-warning d-block mb-3"></i><h5 class="text-muted">Preview tidak tersedia untuk format .${extension}</h5><p>Silakan unduh file untuk melihat isinya.</p></div>`;
                }
                container.innerHTML = contentHtml;
            }, 300);
        });
        
        fileModal.addEventListener('hidden.bs.modal', function () {
            var container = fileModal.querySelector('#file-viewer-container');
            container.innerHTML = '';
        });
    });
</script>
@endpush