@extends('layouts.app')

@push('styles')
{{-- DataTables Bootstrap 5 CSS --}}
<link href="https://cdn.datatables.net/v/bs5/dt-1.13.6/datatables.min.css" rel="stylesheet">
<style>
    /* Global Styles */
    body { font-family: 'Nunito', sans-serif; background-color: #f8f9fc; }
    
    /* Header & Filter */
    .page-title { color: #0d6efd; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; }
    .filter-card { border: 1px solid #e3e6f0; border-radius: 0.35rem; background: #fff; margin-bottom: 20px; }
    .filter-body { padding: 1.2rem; }
    .form-label-custom { font-size: 0.85rem; font-weight: 600; color: #5a5c69; margin-bottom: 0.3rem; }
    
    /* Table Styling */
    .table-custom thead th {
        background-color: #f8f9fc; color: #3a3b45; font-weight: 700; font-size: 0.85rem;
        border-bottom: 2px solid #e3e6f0; padding: 12px;
    }
    .table-custom tbody td {
        font-size: 0.9rem; vertical-align: middle; padding: 10px; color: #5a5c69; border-bottom: 1px solid #e3e6f0;
    }
    
    /* Specific Items */
    .text-no-surat { color: #0d6efd; font-weight: 700; text-decoration: none; }
    .text-no-surat:hover { text-decoration: underline; }
    .badge-via {
        background-color: #f8f9fa; border: 1px solid #e3e6f0; color: #858796;
        font-size: 0.7rem; padding: 3px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;
    }
    /* .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; } */
    .btn-file { background-color: #fff; border: 1px solid #d1d3e2; color: #0d6efd; transition: all 0.2s; }
    .btn-file:hover { background-color: #0d6efd; color: #fff; border-color: #0d6efd; }

    /* Modal Preview */
    #file-viewer-container {
        min-height: 400px; max-height: 75vh; overflow: auto;
        display: flex; align-items: center; justify-content: center; background-color: #f8f9fc;
        border-radius: 0.3rem; border: 1px solid #dee2e6;
    }
    #preview-image { max-width: 100%; height: auto; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); }
    
    /* DATATABLES CUSTOMIZATION (PENTING) */
    .dataTables_wrapper .dataTables_length select {
        padding-right: 30px; 
        font-size: 0.85rem;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #d1d3e2;
        border-radius: 4px;
        padding: 5px 10px;
    }
    .dataTables_wrapper .dataTables_paginate .page-item .page-link {
        color: #0d6efd;
        font-size: 0.85rem;
    }
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 pt-2">


    {{-- SECTION FILTER (Tetap Dipertahankan) --}}
    <div class="filter-card shadow-sm">
        <div class="filter-body">
            <form action="{{ route('adminrektor.surat-keluar-internal.index') }}" method="GET">
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label-custom">Dari Tanggal</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-custom">Sampai Tanggal</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary text-white fw-bold w-100"><i class="bi bi-search me-1"></i> </button>
                            <a href="{{ route('adminrektor.surat-keluar-internal.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="{{ route('adminrektor.surat-keluar-internal.export', request()->query()) }}" class="btn btn-success text-white fw-bold me-2">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('adminrektor.surat-keluar-internal.create') }}" class="btn btn-primary text-white fw-bold">
                            <i class="bi bi-plus-lg me-1"></i> Buat Surat Baru
                        </a>
                    </div>
                </div>
            </form>
        </div>
    
        {{-- SECTION TABEL --}}
        <div class="border-top p-3">
            <div class="table-responsive">
                <table id="tabelSurat" class="table table-custom w-100">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">Tujuan Surat</th>
                            <th width="25%">No. Surat & Perihal</th>
                            <th width="15%">Tanggal Kirim</th>
                            <th width="15%">Tanggal Surat</th>
                            <th width="5%" class="text-center">File</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suratKeluar as $index => $surat)
                        <tr>
                            {{-- Gunakan $loop->iteration murni karena kita pakai get() --}}
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            
                            <td>
                                <div class="fw-bold text-dark">
                                    @if($surat->penerimaInternal->count() > 0)
                                        {{ $surat->penerimaInternal->first()->nama_satker }}
                                        @if($surat->penerimaInternal->count() > 1)
                                            <span class="text-muted small ms-1">(+{{ $surat->penerimaInternal->count() - 1 }} lainnya)</span>
                                        @endif
                                    @else - @endif
                                </div>
                                <div class="badge-via"><i class="bi bi-arrow-right-circle me-1"></i> Via Internal</div>
                            </td>
                            <td>
                                <a href="#" class="text-no-surat d-block mb-1">{{ $surat->nomor_surat }}</a>
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($surat->perihal, 40) }}</div>
                            </td>
                            <td><div class="text-muted"><i class="bi bi-calendar4-week me-2"></i>{{ $surat->created_at->format('d/m/Y') }}</div></td>
                            <td><div class="text-muted"><i class="bi bi-calendar4-week me-2"></i>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d/m/Y') }}</div></td>
                            
                            {{-- MODAL FILE BUTTON --}}
                            <td class="text-center">
                                @if($surat->file_surat)
                                    <button type="button" class="btn btn-action btn-file shadow-sm" 
                                            title="Lihat File"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPreview"
                                            data-file-url="{{ Storage::url($surat->file_surat) }}"
                                            data-file-name="{{ basename($surat->file_surat) }}"
                                            data-title="{{ $surat->perihal }}">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

<td class="text-center">
    <div class="d-flex justify-content-center gap-1">
        {{-- Tombol Edit (Link ke Route Edit) --}}
        <a href="{{ route('adminrektor.surat-keluar-internal.edit', $surat->id) }}" 
           class="btn btn-action btn-warning text-white shadow-sm" 
           title="Edit">
            <i class="bi bi-pencil-fill" style="font-size: 0.8rem;"></i>
        </a>
        
        {{-- Tombol Hapus (Form Delete) --}}
        <form action="{{ route('adminrektor.surat-keluar-internal.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-action btn-danger text-white shadow-sm" title="Hapus">
                <i class="bi bi-trash-fill" style="font-size: 0.8rem;"></i>
            </button>
        </form>
    </div>
</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- TIDAK ADA LAGI {{ $suratKeluar->links() }} KARENA SUDAH DITANGANI DATATABLES --}}
        </div>
    </div>
</div>

{{-- MODAL PREVIEW (SAMA SEPERTI SEBELUMNYA) --}}
<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <div class="d-flex align-items-center overflow-hidden">
                    <i class="bi bi-file-earmark-richtext fs-4 me-2"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-truncate" id="previewTitle" style="max-width: 600px;">Preview File</h6>
                        <small class="text-white-50 d-block" id="previewFilename">nama_file.pdf</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="file-viewer-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <div class="mt-2 text-muted">Memuat preview...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnDownload" class="btn btn-success px-4" download target="_blank">
                    <i class="bi bi-download me-2"></i> Download File
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- Script DataTables --}}
<script src="https://cdn.datatables.net/v/bs5/dt-1.13.6/datatables.min.js"></script>
<script>
    $(document).ready(function() {
        // --- KONFIGURASI DATATABLES (AGAR ADA PAGINATION & SHOW ENTRIES) ---
        $('#tabelSurat').DataTable({
            "paging": true,        // AKTIFKAN PAGINATION
            "lengthChange": true,  // AKTIFKAN DROPDOWN "TAMPILKAN 10 DATA"
            "searching": true,     // AKTIFKAN PENCARIAN
            "ordering": false,     // MATIKAN SORTING CLIENT-SIDE (Agar urutan Controller 'latest' tidak berubah)
            "info": true,          // TAMPILKAN "Menampilkan 1 sampai 10..."
            "autoWidth": false,
            "pageLength": 10,      // DEFAULT 10 DATA
            "lengthMenu": [ [5, 10, 25, 50, -1], [5, 10, 25, 50, "Semua"] ], // PILIHAN DATA
            "language": {
                "search": "Cari Cepat:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "zeroRecords": "Data tidak ditemukan",
                "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 s/d 0 dari 0 data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": {
                    "first": "Awal",
                    "last": "Akhir",
                    "next": "&raquo;",
                    "previous": "&laquo;"
                }
            },
            // Layout Bootstrap 5 yang rapi
            "dom": "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center'l><'col-sm-12 col-md-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        // --- LOGIKA MODAL PREVIEW (SAMA) ---
        var modalPreview = document.getElementById('modalPreview');
        modalPreview.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file-url');
            var fileName = button.getAttribute('data-file-name');
            var title = button.getAttribute('data-title');

            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewFilename').textContent = fileName;
            document.getElementById('btnDownload').href = fileUrl;

            var container = document.getElementById('file-viewer-container');
            var ext = fileName.split('.').pop().toLowerCase();

            container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>`;

            setTimeout(function() {
                if (ext === 'pdf') {
                    container.innerHTML = `<iframe src="${fileUrl}" width="100%" height="600px" style="border:none;"></iframe>`;
                } else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                    container.innerHTML = `<img src="${fileUrl}" id="preview-image" alt="Preview Gambar">`;
                } else {
                    container.innerHTML = `<div class="text-center p-5"><i class="bi bi-file-earmark-x fs-1 text-muted"></i><h5 class="mt-3">Preview tidak tersedia</h5><p class="text-muted">Unduh file untuk melihatnya.</p></div>`;
                }
            }, 300);
        });

        modalPreview.addEventListener('hidden.bs.modal', function () {
            document.getElementById('file-viewer-container').innerHTML = '';
        });
    });
</script>
@endpush