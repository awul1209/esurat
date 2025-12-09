@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px */
    #tabelDisposisi, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .dataTables_wrapper .dataTables_paginate { margin-top: 0.5rem !important; }
    table.dataTable thead > tr > th.sorting::before, table.dataTable thead > tr > th.sorting_asc::before, table.dataTable thead > tr > th.sorting_desc::before, table.dataTable thead > tr > th.sorting::after, table.dataTable thead > tr > th.sorting_asc::after, table.dataTable thead > tr > th.sorting_desc::after { font-size: 0.8em !important; bottom: 0.6em !important; opacity: 0.4 !important; }
    table.dataTable thead > tr > th.sorting_asc::before, table.dataTable thead > tr > th.sorting_desc::after { opacity: 1 !important; }
    
    /* Style tambahan untuk label di modal */
    .info-modal-label { width: 150px; font-weight: 600; }
    .info-modal-data { word-break: break-word; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Daftar Disposisi Rektor (Perlu Diteruskan ke Satker)</h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelDisposisi" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">No. Agenda</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Asal Surat</th>
                            <th scope="col">Status</th>
                            <th scope="col">Tujuan Disposisi (dari Rektor)</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratDisposisi as $surat)
                        @php
                            $disposisi = $surat->disposisis->last();
                            $tujuanNama = $disposisi->tujuanSatker->nama_satker ?? 'N/A';
                            $catatanRektor = $disposisi->catatan_rektor ?? '-';
                        @endphp
                        <tr>
                            <th scope="row" class="text-center">{{ $surat->no_agenda }}</th>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->surat_dari }}</td>
                            <td>
                                <span class="badge text-bg-warning">Perlu Diteruskan</span>
                            </td>
                            <td>
                                @if ($disposisi)
                                    <strong>Ke:</strong> {{ $tujuanNama }}
                                    <br>
                                    <small class="text-muted">
                                        Catatan: "{{ $catatanRektor }}"
                                    </small>
                                @else
                                    <span class="text-danger small"><em>(Data disposisi tidak ditemukan)</em></span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- Tombol Lihat Detail --}}
                                    <button type="button" class="btn btn-sm btn-info text-white" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailSuratModal"
                                        data-no-agenda="{{ $surat->no_agenda }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-asal-surat="{{ $surat->surat_dari }}"
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                        data-tujuan-disposisi="{{ $tujuanNama }}" 
                                        data-catatan-rektor="{{ $catatanRektor }}"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    
                                    {{-- 
                                        PERBAIKAN: Menggunakan rute 'bau.surat.edit' yang sudah ada di web.php
                                    --}}
                                    <a href="{{ route('bau.surat.edit', $surat->id) }}" class="btn btn-sm btn-warning" title="Edit Surat">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    {{-- Tombol Teruskan --}}
                                    <form action="{{ route('bau.surat.forwardToSatker', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin meneruskan surat ini ke Satker tujuan?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Teruskan ke Satker">
                                            <i class="bi bi-send-check-fill"></i>
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
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-labelledby="detailSuratModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailSuratModalLabel">Detail Surat Masuk</h5>
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
                               <td class="info-modal-label">Tanggal Surat</td>
                               <td class="info-modal-data">: <span id="modal-tanggal-surat"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Tanggal Diterima</td>
                               <td class="info-modal-data">: <span id="modal-tanggal-diterima"></span></td>
                           </tr>
                           
                           <tr class="border-top">
                               <td class="info-modal-label pt-3">Tujuan Disposisi</td>
                               <td class="info-modal-data pt-3">: <span id="modal-tujuan-disposisi" class="fw-bold text-primary"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Catatan Rektor</td>
                               <td class="info-modal-data">: <span id="modal-catatan-rektor" class="fst-italic text-muted"></span></td>
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
        new DataTable('#tabelDisposisi', {
            pagingType: 'simple_numbers',
            order: [[ 3, 'desc' ]],
            language: {
                search: "Cari:",
                lengthMenu: "_MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: { next: "Selanjutnya", previous: "Sebelumnya" },
                zeroRecords: "Tidak ada surat yang perlu diteruskan."
            }
        });
        
        // --- Script untuk Modal Box ---
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Ambil data dari atribut tombol
            var noAgenda = button.getAttribute('data-no-agenda');
            var perihal = button.getAttribute('data-perihal');
            var asalSurat = button.getAttribute('data-asal-surat');
            var tanggalSurat = button.getAttribute('data-tanggal-surat');
            var tanggalDiterima = button.getAttribute('data-tanggal-diterima');
            var tujuanDisposisi = button.getAttribute('data-tujuan-disposisi'); 
            var catatanRektor = button.getAttribute('data-catatan-rektor');    
            var fileUrl = button.getAttribute('data-file-url');

            // Ambil elemen di modal
            var modalPerihal = detailSuratModal.querySelector('#modal-perihal');
            var modalNoAgenda = detailSuratModal.querySelector('#modal-no-agenda');
            var modalAsalSurat = detailSuratModal.querySelector('#modal-asal-surat');
            var modalTanggalSurat = detailSuratModal.querySelector('#modal-tanggal-surat');
            var modalTanggalDiterima = detailSuratModal.querySelector('#modal-tanggal-diterima');
            var modalTujuanDisposisi = detailSuratModal.querySelector('#modal-tujuan-disposisi'); 
            var modalCatatanRektor = detailSuratModal.querySelector('#modal-catatan-rektor');    
            var modalFileWrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            var modalDownloadButton = detailSuratModal.querySelector('#modal-download-button');

            // Isi data ke modal
            modalPerihal.textContent = perihal;
            modalNoAgenda.textContent = noAgenda;
            modalAsalSurat.textContent = asalSurat;
            modalTanggalSurat.textContent = tanggalSurat;
            modalTanggalDiterima.textContent = tanggalDiterima;
            modalTujuanDisposisi.textContent = tujuanDisposisi; 
            modalCatatanRektor.textContent = catatanRektor;    
            
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