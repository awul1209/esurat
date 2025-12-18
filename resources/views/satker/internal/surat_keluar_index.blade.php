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
<div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
    <h6 class="m-0 fw-bold text-primary">Riwayat Surat Dikirim</h6>
    <a href="{{ route('satker.surat-keluar.internal.create') }}" class="btn btn-primary btn-sm shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Buat Surat Baru
    </a>
</div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratKeluar" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="30%">Tujuan</th>
                            <th width="30%">No. Surat & Perihal</th>
                            <th width="15%">Tanggal Kirim</th>
                            <th class="text-center" width="10%">File</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suratKeluar as $surat)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    
                                    {{-- 1. Tampilkan jika ada tujuan Manual (Rektor/Universitas via BAU) --}}
                                    @if($surat->tujuan_surat)
                                        <span class="badge bg-warning text-dark mb-1 text-start border border-warning border-opacity-25">
                                            <i class="bi bi-arrow-return-right"></i> {{ $surat->tujuan_surat }}
                                        </span>
                                    @endif

                                    {{-- 2. Tampilkan tujuan Satker (Pivot) --}}
                                    @if($surat->penerimaInternal->count() > 0)
                                        @foreach($surat->penerimaInternal->take(2) as $penerima)
                                            <span class="badge bg-primary bg-opacity-10 text-primary mb-1 text-start border border-primary border-opacity-10">
                                                {{ $penerima->nama_satker }}
                                            </span>
                                        @endforeach
                                        
                                        @if($surat->penerimaInternal->count() > 2)
                                            <small class="text-muted fst-italic">+{{ $surat->penerimaInternal->count() - 2 }} Satker lainnya</small>
                                        @endif
                                    @endif

                                    {{-- Jika kosong semua --}}
                                    @if(!$surat->tujuan_surat && $surat->penerimaInternal->count() == 0)
                                        <span class="text-danger small fst-italic">Belum ada penerima</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $surat->nomor_surat }}</div>
                                <div class="text-muted small">{{ $surat->perihal }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center text-secondary">
                                    <i class="bi bi-calendar-event me-2"></i>
                                    {{ $surat->created_at->format('d M Y') }}
                                    <br>
                                    <small class="ms-1">({{ $surat->created_at->format('H:i') }})</small>
                                </div>
                            </td>
                            <td class="text-center">
                                {{-- TOMBOL PREVIEW FILE --}}
                                <button type="button" class="btn btn-outline-primary btn-sm btn-icon" 
                                    title="Lihat & Download File"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#filePreviewModal"
                                    data-title="{{ $surat->perihal }}"
                                    data-file-url="{{ Storage::url($surat->file_surat) }}">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- TOMBOL EDIT --}}
                                    <a href="{{ route('satker.surat-keluar.internal.edit', $surat->id) }}" class="btn btn-warning btn-sm btn-icon text-white" title="Edit Surat">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    {{-- TOMBOL HAPUS --}}
                                    <form action="{{ route('satker.surat-keluar.internal.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat ini? Data yang dihapus tidak bisa dikembalikan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Hapus Surat">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
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
                <h5 class="modal-title" id="filePreviewModalLabel">Preview Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
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
        $('#tabelSuratKeluar').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json",
                emptyTable: "Belum ada surat keluar internal."
            },
            order: [[ 3, 'desc' ]]
        });

        // Logika Modal Preview
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
            container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

            var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0];

            setTimeout(function() {
                var contentHtml = '';
                if (extension === 'pdf') {
                    contentHtml = `<iframe src="${fileUrl}" width="100%" height="100%" style="border:none;"></iframe>`;
                } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
                    contentHtml = `<img src="${fileUrl}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">`;
                } else {
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