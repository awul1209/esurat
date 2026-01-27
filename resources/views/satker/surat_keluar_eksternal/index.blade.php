@extends('layouts.app')
<!-- menambah kolom surat kirim -->
@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS Penyesuaian */
    #tabelSuratKeluar, .dataTables_wrapper { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    
    /* Tombol Aksi Modern */
    .btn-action {
        width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s ease-in-out; border: none;
    }
    .btn-action:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); }
    .btn-action-edit { background: linear-gradient(135deg, #ffc107, #ffca2c); color: #fff; }
    .btn-action-delete { background: linear-gradient(135deg, #dc3545, #e35d6a); color: #fff; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- CARD UTAMA: GABUNGAN FILTER & TABEL --}}
    <div class="card shadow-sm border-0 mb-4 mt-2">
        
        {{-- HEADER: JUDUL --}}
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-primary">
                <i class="bi bi-send-check-fill me-2"></i> Surat Keluar Eksternal
            </h6>
        </div>

        {{-- BODY BAGIAN 1: FILTER & TOMBOL AKSI --}}
        <div class="card-body bg-light border-bottom py-3">
            <form action="{{ route('satker.surat-keluar.eksternal.index') }}" method="GET">
                <div class="row g-2 align-items-end">
                    
                    {{-- Input Tanggal Awal --}}
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                    </div>

                    {{-- Input Tanggal Akhir --}}
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar-fill"></i></span>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                    </div>

                    {{-- Tombol Filter & Reset --}}
                    <div class="col-md-auto">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm" title="Cari Data">
                                <i class="bi bi-search"></i> Cari
                            </button>
                            <a href="{{ route('satker.surat-keluar.eksternal.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </div>

                    {{-- AREA KANAN: EXPORT & BUAT BARU --}}
                    <div class="col-md ms-auto text-md-end mt-2 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            {{-- Tombol Export --}}
                            <a href="{{ route('satker.surat-keluar.eksternal.export', request()->query()) }}" class="btn btn-success btn-sm shadow-sm">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                            </a>
                            
                            {{-- Tombol Buat Baru (Ditaruh di sini sesuai permintaan) --}}
                            <a href="{{ route('satker.surat-keluar.eksternal.create') }}" class="btn btn-primary btn-sm shadow-sm">
                                <i class="bi bi-plus-lg me-1"></i> Buat Surat Baru
                            </a>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        {{-- BODY BAGIAN 2: TABEL DATA --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tabelSuratKeluar" class="table table-hover align-middle w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" width="5%">No</th>
                            <th width="25%">Tujuan Surat</th>
                            <th width="30%">No. Surat & Perihal</th>
                            <th width="15%">Tanggal Kirim</th>
                            <th width="15%">Tanggal Surat</th>
                            <th class="text-center" width="10%">File</th>
                            <th class="text-center" width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suratKeluar as $surat)
                        <tr>
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            
                            {{-- KOLOM TUJUAN EKSTERNAL --}}
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="fw-bold text-dark mb-1">{{ $surat->tujuan_luar ?? '-' }}</span>
                                    <span class="badge bg-light text-dark border text-start" style="width: fit-content;">
                                        <i class="bi bi-globe me-1 text-primary"></i> Pihak Eksternal
                                    </span>
                                </div>
                            </td>

                            {{-- NO SURAT & PERIHAL --}}
                            <td>
                                <span class="fw-bold text-primary">{{ $surat->nomor_surat }}</span>
                                <br>
                                <span class="text-muted small text-wrap d-block mt-1" style="line-height: 1.2;">
                                    {{ Str::limit($surat->perihal, 60) }}
                                </span>
                            </td>

                            {{-- TANGGAL --}}
                            <td>
                                <div class="d-flex align-items-center text-muted">
                                    <i class="bi bi-calendar3 me-2"></i>
                                    {{ \Carbon\Carbon::parse($surat->created_at)->format('d/m/Y') }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center text-muted">
                                    <i class="bi bi-calendar3 me-2"></i>
                                    {{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d/m/Y') }}
                                </div>
                            </td>

                            {{-- FILE --}}
                            <td class="text-center">
                                @if($surat->file_surat)
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-icon" 
                                            title="Lihat File"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#filePreviewModal"
                                            data-title="{{ $surat->perihal }}"
                                            data-file="{{ asset('storage/' . $surat->file_surat) }}">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </button>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                           {{-- AKSI --}}
<td class="text-center">
    <div class="d-flex justify-content-center gap-2">
        {{-- 1. TOMBOL EDIT: Hanya muncul jika belum dikunci/diproses --}}
        {{-- Jika Anda tidak menggunakan variabel $isLocked di sini, Anda bisa menghapus @if nya --}}
        @if(!($isLocked ?? false))
            <a href="{{ route('satker.surat-keluar.eksternal.edit', $surat->id) }}" class="btn btn-sm btn-action btn-action-edit" title="Edit Data" style="color:white">
                <i class="bi bi-pencil-fill small"></i>
            </a>
        @else
            <button class="btn btn-sm btn-secondary shadow-sm" disabled title="Surat sudah diproses, tidak bisa diedit">
                <i class="bi bi-lock-fill small"></i>
            </button>
        @endif

        {{-- 2. TOMBOL HAPUS: Selalu muncul agar admin bisa membersihkan arsip --}}
        <form action="{{ route('satker.surat-keluar.eksternal.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat ini?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-action btn-action-delete" title="Hapus Data" style="color:white">
                <i class="bi bi-trash-fill small"></i>
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
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="filePreviewModalLabel">Preview Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-secondary bg-opacity-10">
                <div id="file-viewer-container" class="d-flex align-items-center justify-content-center" style="height: 75vh; width: 100%;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btn-download-file" class="btn btn-primary shadow-sm" download target="_blank">
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
            pagingType: 'simple_numbers',
            ordering: false,
            language: { 
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ surat",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                zeroRecords: "Tidak ada data yang cocok",
                paginate: { next: "Lanjut", previous: "Kembali" }
            }
        });

        // Logika Modal Preview
        var fileModal = document.getElementById('filePreviewModal');
        fileModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file');
            var title = button.getAttribute('data-title');
            
            var modalTitle = fileModal.querySelector('.modal-title');
            var container = fileModal.querySelector('#file-viewer-container');
            var downloadBtn = fileModal.querySelector('#btn-download-file');

            modalTitle.textContent = "Preview: " + title;
            downloadBtn.href = fileUrl;
            container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

            if(fileUrl) {
                var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0];
                setTimeout(function() {
                    if (extension === 'pdf') {
                        container.innerHTML = `<iframe src="${fileUrl}" width="100%" height="100%" style="border:none;"></iframe>`;
                    } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
                        container.innerHTML = `<img src="${fileUrl}" class="img-fluid shadow-sm rounded" style="max-height: 95%; max-width: 95%; object-fit: contain;">`;
                    } else {
                        container.innerHTML = `<div class="text-center p-5 bg-white rounded shadow-sm"><i class="bi bi-file-earmark-break h1 text-warning d-block mb-3" style="font-size: 3rem;"></i><h5 class="text-muted">Preview tidak tersedia</h5><p class="text-secondary small">Silakan unduh file untuk melihat isinya.</p></div>`;
                    }
                }, 300);
            } else {
                container.innerHTML = '<div class="text-center p-5 text-danger bg-white rounded">File tidak ditemukan.</div>';
            }
        });
        
        fileModal.addEventListener('hidden.bs.modal', function () {
            var container = fileModal.querySelector('#file-viewer-container');
            container.innerHTML = '';
        });
    });
</script>
@endpush