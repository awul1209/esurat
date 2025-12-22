@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
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

    <div class="card shadow-sm border-0 mb-4 mt-2">
        
        {{-- HEADER: JUDUL + TOMBOL BUAT BARU --}}
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 fw-bold text-primary">
                <i class="bi bi-send-check-fill me-2"></i> Daftar Surat Keluar Internal
            </h6>

        </div>

        {{-- SUB-HEADER: FILTER TANGGAL & EXPORT --}}
        <div class="card-body bg-light border-bottom py-3">
            <form action="{{ route('satker.surat-keluar.internal') }}" method="GET">
                <div class="row g-2 align-items-end">
                    
                    {{-- Filter Tanggal --}}
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar-fill"></i></span>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                    </div>

                    {{-- Tombol Filter & Reset --}}
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-auto">
                        <a href="{{ route('satker.surat-keluar.internal') }}" class="btn btn-outline-secondary btn-sm w-100" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>

                        {{-- AREA KANAN: EXPORT & BUAT BARU --}}
                    <div class="col-md ms-auto text-md-end mt-2 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            {{-- Tombol Export --}}
                        <a href="{{ route('satker.surat-keluar.internal.export', request()->query()) }}" class="btn btn-success btn-sm shadow-sm">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                        </a>
                            
                            {{-- Tombol Buat Baru (Ditaruh di sini sesuai permintaan) --}}
           <a href="{{ route('satker.surat-keluar.internal.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Surat Baru
            </a>
                        </div>
                    </div>


                </div>
            </form>
        </div>

        {{-- TABEL DATA --}}
        <div class="card-body p-0"> {{-- Padding 0 agar tabel full width --}}
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
                            
                            {{-- KOLOM TUJUAN --}}
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @if(!empty($surat->tujuan_surat))
                                        <span class="fw-bold text-dark mb-1">{{ $surat->tujuan_surat }}</span>
                                        <span class="badge bg-light text-dark border text-start" style="width: fit-content;">
                                            <i class="bi bi-arrow-right-circle me-1 text-warning"></i> Via BAU
                                        </span>
                                    @elseif($surat->penerimaInternal->count() > 0)
                                        @foreach($surat->penerimaInternal->take(2) as $penerima)
                                            <span class="badge bg-light text-dark border text-start">
                                                <i class="bi bi-building me-1 text-success"></i> {{ $penerima->nama_satker }}
                                            </span>
                                        @endforeach
                                        @if($surat->penerimaInternal->count() > 2)
                                            <span class="badge bg-secondary align-self-start">+{{ $surat->penerimaInternal->count() - 2 }} lainnya</span>
                                        @endif
                                    @else
                                        <span class="text-muted fst-italic">- Tidak ada tujuan -</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <span class="fw-bold text-primary">{{ $surat->nomor_surat }}</span>
                                <br>
                                <span class="text-muted small text-wrap d-block mt-1" style="line-height: 1.2;">
                                    {{ Str::limit($surat->perihal, 60) }}
                                </span>
                            </td>

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

                            <td class="text-center">
                                @if($surat->file_surat)
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-icon" 
                                            title="Lihat File"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#filePreviewModal"
                                            data-title="{{ $surat->perihal }}"
                                            data-file-url="{{ asset('storage/' . $surat->file_surat) }}">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </button>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('satker.surat-keluar.internal.edit', $surat->id) }}" class="btn btn-sm btn-action btn-action-edit" title="Edit Data" style="color:white">
                                        <i class="bi bi-pencil-fill small"></i>
                                    </a>
                                    <form action="{{ route('satker.surat-keluar.internal.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat ini?');">
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

        // Logika Modal Preview (Sama seperti sebelumnya)
        var fileModal = document.getElementById('filePreviewModal');
        fileModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file-url');
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