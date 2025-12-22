@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* Global Styles */
    .bg-gradient-primary-to-secondary { background: linear-gradient(45deg, #4e73df, #224abe); color: white; }
    .text-sm { font-size: 0.875rem; }
    .fw-600 { font-weight: 600; }
    
    /* Table Styles */
    #tabelSuratKeluar thead th { 
        background-color: #f8f9fc; 
        color: #4e73df; 
        font-weight: 700; 
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e3e6f0;
        vertical-align: middle;
    }
    #tabelSuratKeluar tbody td { 
        font-size: 0.85rem; 
        color: #5a5c69; 
        vertical-align: middle;
    }
    
    /* Badge Styles */
    .badge-tujuan { font-size: 0.75rem; padding: 0.4em 0.6em; border-radius: 4px; }
    
    /* DataTables Customization */
    .dataTables_wrapper .dataTables_paginate .page-link { 
        border-radius: 50%; width: 30px; height: 30px; padding: 0; line-height: 30px; text-align: center; margin: 0 2px; border: none; color: #858796;
    }
    .dataTables_wrapper .dataTables_paginate .page-link:hover { background-color: #eaecf4; }
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link { background-color: #4e73df; color: white; }
</style>
@endpush

@section('content')

@php
    $isEksternal = Request::routeIs('bau.surat-keluar.eksternal');
    $tipeURL = $isEksternal ? 'eksternal' : 'internal';
    $labelJudul = $isEksternal ? 'Eksternal' : 'Internal';
    $routeFilter = $isEksternal ? route('bau.surat-keluar.eksternal') : route('bau.surat-keluar.internal');
@endphp

<div class="container-fluid px-4">

    {{-- ALERT MESSAGES --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-start-success" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                <div><strong>Berhasil!</strong> {{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- CARD UTAMA --}}
    <div class="card shadow border-0 mb-4 rounded-3">
        
        {{-- CARD HEADER: FILTER, TOMBOL TAMBAH & EXPORT --}}
        <div class="card-header bg-white py-3">
            <form action="{{ $routeFilter }}" method="GET">
                <div class="row g-3 align-items-end">
                    
                    {{-- 1. BAGIAN KIRI: FILTER TANGGAL --}}
                    <div class="col-lg-7 col-md-12">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-funnel-fill me-1"></i>Filter Tanggal</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-calendar-range"></i></span>
                            <input type="date" name="start_date" class="form-control bg-light border-start-0 ps-0" placeholder="Dari" value="{{ request('start_date') }}" title="Dari Tanggal">
                            <span class="input-group-text bg-white border-start-0 border-end-0 text-muted">s/d</span>
                            <input type="date" name="end_date" class="form-control bg-light border-start-0" placeholder="Sampai" value="{{ request('end_date') }}" title="Sampai Tanggal">
                            
                            <button type="submit" class="btn btn-primary px-3" title="Terapkan Filter">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ $routeFilter }}" class="btn btn-outline-secondary px-3" title="Reset Filter">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        </div>
                    </div>

                    {{-- 2. BAGIAN KANAN: TOMBOL AKSI (CREATE & EXPORT) --}}
                    <div class="col-lg-5 col-md-12 text-lg-end">
                        <div class="d-flex gap-2 justify-content-lg-end justify-content-start">
                            {{-- TOMBOL BUAT BARU --}}
                            <a href="{{ route('bau.surat-keluar.create', ['type' => $tipeURL]) }}" class="btn btn-primary shadow-sm text-nowrap">
                                <i class="bi bi-plus-lg me-1"></i> Buat Surat Baru
                            </a>

                            {{-- TOMBOL EXPORT EXCEL --}}
                            <button type="submit" formaction="{{ route('bau.surat-keluar.export') }}" name="type" value="{{ $tipeURL }}" class="btn btn-success text-white shadow-sm text-nowrap">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratKeluar" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="15%">Nomor Surat</th>
                            <th width="25%">Perihal</th>
                            <th width="25%">Tujuan</th>
                            <th width="15%">Tanggal Surat</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratKeluars as $surat)
                        @php
                            $tujuanModalText = $surat->tujuan_surat; 
                            if (empty($tujuanModalText) && $surat->penerimaInternal->count() > 0) {
                                $tujuanModalText = $surat->penerimaInternal->pluck('nama_satker')->implode(', ');
                            }
                        @endphp
                        <tr>
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            <td class="fw-600 text-primary">{{ $surat->nomor_surat }}</td>
                            <td>{{ $surat->perihal }}</td>
                            
                            {{-- KOLOM TUJUAN --}}
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @if(!empty($surat->tujuan_surat))
                                        <span class="badge bg-light text-dark border badge-tujuan text-start">
                                            <i class="bi bi-person-fill me-1 text-warning"></i> {{ Str::limit($surat->tujuan_surat, 30) }}
                                        </span>
                                    @endif

                                    @if($surat->penerimaInternal->count() > 0)
                                        @foreach($surat->penerimaInternal->take(2) as $penerima)
                                            <span class="badge bg-light text-dark border badge-tujuan text-start">
                                                <i class="bi bi-building me-1 text-success"></i> {{ $penerima->nama_satker }}
                                            </span>
                                        @endforeach
                                        @if($surat->penerimaInternal->count() > 2)
                                            <span class="badge bg-secondary badge-tujuan align-self-start">+{{ $surat->penerimaInternal->count() - 2 }} lainnya</span>
                                        @endif
                                    @endif

                                    @if(empty($surat->tujuan_surat) && $surat->penerimaInternal->count() == 0)
                                        <span class="text-muted fst-italic small">- Tidak ada tujuan -</span>
                                    @endif
                                </div>
                            </td>
                            
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold">{{ $surat->tanggal_surat->isoFormat('D MMM YYYY') }}</span>
                                    <small class="text-muted" style="font-size: 0.75rem">Input: {{ $surat->created_at->format('d/m/y H:i') }}</small>
                                </div>
                            </td>
                            
                            {{-- KOLOM AKSI (DILUAR / INLINE) --}}
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    
                                    {{-- 1. TOMBOL LIHAT (INFO) --}}
                                    <button type="button" class="btn btn-sm btn-info text-white" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailSuratModal"
                                        data-nomor-surat="{{ $surat->nomor_surat }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-tujuan-surat="{{ $tujuanModalText }}" 
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-input="{{ $surat->created_at->isoFormat('D MMMM YYYY, \p\u\k\u\l H:i') }} WIB"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    {{-- 2. TOMBOL EDIT (WARNING) --}}
                                    <a href="{{ route('bau.surat-keluar.edit', $surat->id) }}" class="btn btn-sm btn-warning text-white" title="Edit Data">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    {{-- 3. TOMBOL HAPUS (DANGER) --}}
                                    <form action="{{ route('bau.surat-keluar.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus arsip surat ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data">
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

{{-- MODAL DETAIL --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary-to-secondary text-white">
                <h5 class="modal-title"><i class="bi bi-envelope-open-fill me-2"></i>Detail Surat Keluar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="mb-4">
                            <label class="d-block text-muted text-uppercase small fw-bold mb-1">Perihal Surat</label>
                            <h5 class="fw-bold text-dark" id="modal-perihal"></h5>
                        </div>
                        
                        <div class="card bg-light border-0 p-3 mb-3">
                            <table class="table table-borderless table-sm mb-0">
                               <tr><td class="info-label">Nomor Surat</td><td class="info-value" id="modal-nomor-surat"></td></tr>
                               <tr><td class="info-label">Tujuan</td><td class="info-value text-primary" id="modal-tujuan-surat"></td></tr>
                               <tr><td class="info-label">Tanggal Surat</td><td class="info-value" id="modal-tanggal-surat"></td></tr>
                               <tr><td class="info-label">Waktu Input</td><td class="info-value" id="modal-tanggal-input"></td></tr>
                            </table>
                        </div>
                        <div class="d-grid">
                            <a href="#" id="modal-download-button" class="btn btn-primary" download>
                                <i class="bi bi-cloud-download-fill me-2"></i> Download Dokumen
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <label class="d-block text-muted text-uppercase small fw-bold mb-2">Preview Dokumen</label>
                        <div id="modal-file-preview-wrapper" class="d-flex align-items-center justify-content-center" style="height: 500px;">
                            {{-- Content inserted via JS --}}
                        </div>
                    </div>
                </div>
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
        // 1. Init DataTables dengan Bahasa Indonesia Manual (Anti CORS Error)
        new DataTable('#tabelSuratKeluar', {
            pagingType: 'simple_numbers',
            ordering: false, 
            dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>',
            columnDefs: [ { orderable: false, targets: -1 } ],
            language: {
                // Tulis manual di sini agar tidak perlu load file JSON dari luar
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                zeroRecords: "Tidak ada data surat keluar yang ditemukan.",
                paginate: {
                    first: "Awal",
                    last: "Akhir",
                    next: "Lanjut",
                    previous: "Kembali"
                }
            }
        });

        // 2. Logic Modal Detail & Download
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Isi Teks Modal
            detailSuratModal.querySelector('#modal-perihal').textContent = button.getAttribute('data-perihal');
            detailSuratModal.querySelector('#modal-nomor-surat').textContent = button.getAttribute('data-nomor-surat');
            detailSuratModal.querySelector('#modal-tujuan-surat').textContent = button.getAttribute('data-tujuan-surat');
            detailSuratModal.querySelector('#modal-tanggal-surat').textContent = button.getAttribute('data-tanggal-surat');
            detailSuratModal.querySelector('#modal-tanggal-input').textContent = button.getAttribute('data-tanggal-input');
            
            // Logic File URL & Download
            var fileUrl = button.getAttribute('data-file-url');
            var btnDl = detailSuratModal.querySelector('#modal-download-button');
            
            // Ambil ekstensi asli dari file (jpg, png, pdf, dll)
            var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0];
            
            btnDl.href = fileUrl;
            // Set nama file download sesuai ekstensi aslinya
            btnDl.setAttribute('download', 'Surat Keluar - ' + button.getAttribute('data-perihal') + '.' + extension);

            // Logic Preview
            var fileHtml = '';
            if (extension == 'pdf') {
                fileHtml = '<iframe src="' + fileUrl + '" width="100%" height="100%" frameborder="0" style="border-radius:8px;"></iframe>';
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                fileHtml = '<img src="' + fileUrl + '" class="img-fluid rounded" style="max-height: 100%;">';
            } else {
                fileHtml = '<div class="text-center text-muted"><i class="bi bi-file-earmark-x h1"></i><p class="mt-2">Preview tidak tersedia</p></div>';
            }
            
            detailSuratModal.querySelector('#modal-file-preview-wrapper').innerHTML = fileHtml;
        });
    });
</script>
@endpush