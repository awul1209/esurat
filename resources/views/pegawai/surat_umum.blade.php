@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px */
    #tabelSuratUmum, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .dataTables_wrapper .dataTables_paginate { margin-top: 0.5rem !important; }
    table.dataTable thead > tr > th.sorting::before, table.dataTable thead > tr > th.sorting_asc::before, table.dataTable thead > tr > th.sorting_desc::before, table.dataTable thead > tr > th.sorting::after, table.dataTable thead > tr > th.sorting_asc::after, table.dataTable thead > tr > th.sorting_desc::after { font-size: 0.8em !important; bottom: 0.6em !important; opacity: 0.4 !important; }
    table.dataTable thead > tr > th.sorting_asc::before, table.dataTable thead > tr > th.sorting_desc::after { opacity: 1 !important; }
    .card-header h6 { font-size: 14px !important; }
    .info-modal-label { width: 150px; font-weight: 600; }
    .info-modal-data { word-break: break-word; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    
    <h1 class="h3 mb-4 text-gray-800">Surat Umum (Edaran)</h1>

    {{-- BAGIAN TABEL SURAT --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Daftar Surat Umum / Edaran</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratUmum" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">No. Agenda</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Asal Surat</th>
                            <th scope="col">Diterima Tanggal</th>
                            <th scope="col">Diteruskan Oleh</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratUmum as $surat)
                        <tr>
                            <th scope="row" class="text-center">{{ $surat->no_agenda }}</th>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->surat_dari }}</td>
                            <td>{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}</td>
                            <td>
                                {{-- Ini adalah Admin BAU yang menginput --}}
                                @if($surat->riwayats->first())
                                    {{ $surat->riwayats->first()->user->name ?? 'Sistem' }}
                                    (BAU)
                                @else
                                    'Sistem'
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-info" 
                                    title="Lihat Detail"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#detailSuratModal"
                                    data-perihal="{{ $surat->perihal }}"
                                    data-asal-surat="{{ $surat->surat_dari }}"
                                    data-no-agenda="{{ $surat->no_agenda }}"
                                    data-nomor-surat="{{ $surat->nomor_surat }}"
                                    data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                    data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                    data-file-url="{{ Storage::url($surat->file_surat) }}">
                                    <i class="bi bi-eye-fill"></i> Lihat
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

{{-- Modal Box Detail Surat --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-labelledby="detailSuratModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailSuratModalLabel">Detail Surat Umum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <h4 class="mb-3" id="modal-perihal">Perihal Surat</h4>
                        <table class="table table-borderless table-sm small" style="font-size: 13px;">
                           <tr>
                               <td class="info-modal-label">No. Agenda</td>
                               <td class="info-modal-data">: <span id="modal-no-agenda"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Asal Surat</td>
                               <td class="info-modal-data">: <span id="modal-asal-surat"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Nomor Surat</td>
                               <td class="info-modal-data">: <span id="modal-nomor-surat"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Tanggal Surat</td>
                               <td class="info-modal-data">: <span id="modal-tanggal-surat"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Tanggal Diterima</td>
                               <td class="info-modal-data">: <span id="modal-tanggal-diterima"></span></td>
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
        new DataTable('#tabelSuratUmum', {
            pagingType: 'simple_numbers',
            order: [[ 3, 'desc' ]], // Urutkan berdasarkan Tanggal Diterima
            language: {
                search: "Cari:",
                lengthMenu: "_MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: { next: "Selanjutnya", previous: "Sebelumnya" },
                zeroRecords: "Tidak ada surat umum untuk Anda."
            }
        });
        
        // Script untuk Modal Box (Sama seperti modal lainnya)
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            
            var button = event.relatedTarget;
            
            var perihal = button.getAttribute('data-perihal');
            var asalSurat = button.getAttribute('data-asal-surat');
            var noAgenda = button.getAttribute('data-no-agenda');
            var nomorSurat = button.getAttribute('data-nomor-surat');
            var tanggalSurat = button.getAttribute('data-tanggal-surat');
            var tanggalDiterima = button.getAttribute('data-tanggal-diterima');
            var fileUrl = button.getAttribute('data-file-url'); 

            var modalPerihal = detailSuratModal.querySelector('#modal-perihal');
            var modalNoAgenda = detailSuratModal.querySelector('#modal-no-agenda');
            var modalAsalSurat = detailSuratModal.querySelector('#modal-asal-surat');
            var modalNomorSurat = detailSuratModal.querySelector('#modal-nomor-surat');
            var modalTanggalSurat = detailSuratModal.querySelector('#modal-tanggal-surat');
            var modalTanggalDiterima = detailSuratModal.querySelector('#modal-tanggal-diterima');
            var modalFileWrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            var modalDownloadButton = detailSuratModal.querySelector('#modal-download-button');

            modalPerihal.textContent = perihal;
            modalNoAgenda.textContent = noAgenda;
            modalAsalSurat.textContent = asalSurat;
            modalNomorSurat.textContent = nomorSurat;
            modalTanggalSurat.textContent = tanggalSurat;
            modalTanggalDiterima.textContent = tanggalDiterima;
            
            modalDownloadButton.href = fileUrl;
            modalDownloadButton.setAttribute('download', 'Surat - ' + perihal + '.pdf');
            
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