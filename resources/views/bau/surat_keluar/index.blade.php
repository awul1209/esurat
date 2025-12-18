@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px */
    #tabelSuratKeluar, .dataTables_wrapper { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .dataTables_wrapper .dataTables_paginate { margin-top: 0.5rem !important; }
    table.dataTable thead > tr > th.sorting::before, table.dataTable thead > tr > th.sorting_asc::before, table.dataTable thead > tr > th.sorting_desc::before, table.dataTable thead > tr > th.sorting::after, table.dataTable thead > tr > th.sorting_asc::after, table.dataTable thead > tr > th.sorting_desc::after { font-size: 0.8em !important; bottom: 0.6em !important; opacity: 0.4 !important; }
    table.dataTable thead > tr > th.sorting_asc::before, table.dataTable thead > tr > th.sorting_desc::after { opacity: 1 !important; }
    
    .info-modal-label { width: 120px; font-weight: 600; }
    .info-modal-data { word-break: break-word; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 13px;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Arsip Surat Keluar</h6>
                <a href="{{ route('bau.surat-keluar.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-plus-circle-fill me-2"></i> Arsipkan Surat Keluar Baru
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratKeluar" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Nomor Surat</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Tujuan</th>
                            <th scope="col">Tanggal Surat</th>
                            {{-- PERUBAHAN 1: Tambah Kolom Tgl. Diinput --}}
                            <th scope="col">Tgl. Diinput</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratKeluars as $surat)
                        <tr>
                            <td>{{ $surat->nomor_surat }}</td>
                            <td>{{ $surat->perihal }}</td>
                           {{-- ==================================================== --}}
                            {{-- BAGIAN PERBAIKAN LOGIKA TAMPILAN TUJUAN --}}
                            {{-- ==================================================== --}}
                            <td>
                                <div class="d-flex flex-column align-items-start">
                                    
                                    {{-- 1. Cek Jika Ada Tujuan Manual (Rektor/Universitas/Eksternal Manual) --}}
                                    @if(!empty($surat->tujuan_surat))
                                        <span class="badge bg-warning text-dark badge-tujuan border border-warning">
                                            <i class="bi bi-person-fill"></i> {{ $surat->tujuan_surat }}
                                        </span>
                                    @endif

                                    {{-- 2. Cek Jika Ada Tujuan Satker (Internal via Pivot) --}}
                                    {{-- Kita gunakan $surat->penerimaInternal --}}
                                    @if($surat->penerimaInternal->count() > 0)
                                        @foreach($surat->penerimaInternal as $penerima)
                                            <span class="badge bg-success badge-tujuan">
                                                <i class="bi bi-building"></i> {{ $penerima->nama_satker }}
                                            </span>
                                        @endforeach
                                    @endif

                                    {{-- 3. Jika Kosong --}}
                                    @if(empty($surat->tujuan_surat) && $surat->penerimaInternal->count() == 0)
                                        <span class="text-muted small fst-italic">- Tidak ada tujuan spesifik -</span>
                                    @endif
                                </div>
                            </td>
                            {{-- ==================================================== --}}
                            <td>{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}</td>
                            {{-- PERUBAHAN 2: Isi data Tgl. Diinput --}}
                            <td>{{ $surat->created_at->isoFormat('D MMM YYYY') }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- PERUBAHAN 3: Tambah data-tanggal-input --}}
                                    <button type="button" class="btn btn-sm btn-info" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailSuratModal"
                                        data-nomor-surat="{{ $surat->nomor_surat }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-tujuan-surat="{{ $surat->tujuan_surat }}"
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-input="{{ $surat->created_at->isoFormat('D MMMM YYYY, \p\u\k\u\l H:i') }} WIB"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    <a href="{{ route('bau.surat-keluar.edit', $surat->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <form action="{{ route('bau.surat-keluar.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus arsip surat ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
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

{{-- MODAL DETAIL SURAT KELUAR --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-labelledby="detailSuratModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailSuratModalLabel">Detail Surat Keluar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <h4 class="mb-3" id="modal-perihal">Perihal Surat</h4>
                        <table class="table table-borderless table-sm small" style="font-size: 13px;">
                           <tr>
                               <td class="info-modal-label">Nomor Surat</td>
                               <td class="info-modal-data">: <span id="modal-nomor-surat"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Tujuan Surat</td>
                               <td class="info-modal-data">: <span id="modal-tujuan-surat"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Tanggal Surat</td>
                               <td class="info-modal-data">: <span id="modal-tanggal-surat"></span></td>
                           </tr>
                           {{-- PERUBAHAN 4: Tambah baris "Diinput oleh BAU" --}}
                           <tr>
                               <td class="info-modal-label">Diinput oleh BAU</td>
                               <td class="info-modal-data">: <span id="modal-tanggal-input"></span></td>
                           </tr>
                        </table>
                    </div>
                    <div class="col-md-7">
                        <div id="modal-file-preview-wrapper" style="height: 70vh; border: 1px solid #dee2e6; border-radius: .375rem;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="modal-download-button" class="btn btn-primary" download><i class="bi bi-download me-2"></i> Download File</a>
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
        // Inisialisasi DataTables
        new DataTable('#tabelSuratKeluar', {
            pagingType: 'simple_numbers',
            order: [[ 3, 'desc' ]], // Urutkan berdasarkan Tanggal Surat
            language: {
                search: "Cari:",
                lengthMenu: "_MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: { next: "Selanjutnya", previous: "Sebelumnya" },
                zeroRecords: "Tidak ada data surat keluar."
            }
        });

        {{-- 
          ====================================================
          PERBAIKAN 5: Perbarui JavaScript Modal
          ====================================================
        --}}
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Ambil data dari tombol
            var perihal = button.getAttribute('data-perihal');
            var nomorSurat = button.getAttribute('data-nomor-surat');
            var tujuanSurat = button.getAttribute('data-tujuan-surat');
            var tanggalSurat = button.getAttribute('data-tanggal-surat');
            var tanggalInput = button.getAttribute('data-tanggal-input'); // <-- BARU
            var fileUrl = button.getAttribute('data-file-url');

            // Ambil elemen modal
            var modalPerihal = detailSuratModal.querySelector('#modal-perihal');
            var modalNomorSurat = detailSuratModal.querySelector('#modal-nomor-surat');
            var modalTujuanSurat = detailSuratModal.querySelector('#modal-tujuan-surat');
            var modalTanggalSurat = detailSuratModal.querySelector('#modal-tanggal-surat');
            var modalTanggalInput = detailSuratModal.querySelector('#modal-tanggal-input'); // <-- BARU
            var modalFileWrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            var modalDownloadButton = detailSuratModal.querySelector('#modal-download-button');

            // Isi data ke modal
            modalPerihal.textContent = perihal;
            modalNomorSurat.textContent = nomorSurat;
            modalTujuanSurat.textContent = tujuanSurat;
            modalTanggalSurat.textContent = tanggalSurat;
            modalTanggalInput.textContent = tanggalInput; // <-- BARU
            
            modalDownloadButton.href = fileUrl;
            modalDownloadButton.setAttribute('download', 'Surat Keluar - ' + perihal + '.pdf');

            var fileHtml = '';
            var extension = fileUrl.split('.').pop().toLowerCase();
            
            if (extension == 'pdf') {
                fileHtml = '<iframe src="' + fileUrl + '" width="100%" height="100%" frameborder="0"></iframe>';
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                fileHtml = '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 70vh; object-fit: contain; width: 100%;">';
            } else {
                 fileHtml = '<div class="text-center p-5"><i class="bi bi-file-earmark-text h1 text-muted"></i><p class="mt-3">Preview tidak didukung.</p></div>';
            }
            modalFileWrapper.innerHTML = fileHtml;
        });

    });
</script>
@endpush