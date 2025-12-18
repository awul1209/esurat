@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px */
    #tabelDisposisi, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .dataTables_wrapper .dataTables_paginate { margin-top: 0.5rem !important; }
    
    .info-modal-label { width: 150px; font-weight: 600; }
    .info-modal-data { word-break: break-word; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Daftar Disposisi Rektor (Perlu Diteruskan/Diproses)</h6>
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
                            $listDisposisi = $surat->disposisis;
                            $tujuanList = [];
                            $catatanRektor = '-';
                            
                            // Flag untuk menentukan jenis tujuan
                            $hasSatkerInternal = false; 
                            $hasLainnya = false;

                            foreach($listDisposisi as $disp) {
                                // 1. Cek Satker Internal
                                if($disp->tujuanSatker) {
                                    $tujuanList[] = '<span class="text-primary"><i class="bi bi-building"></i> ' . $disp->tujuanSatker->nama_satker . '</span>';
                                    $hasSatkerInternal = true;
                                } 
                                // 2. Cek Disposisi Lain (Ormawa/Eksternal)
                                elseif($disp->disposisi_lain) {
                                    $tujuanList[] = '<span class="text-dark fst-italic"><i class="bi bi-people"></i> ' . $disp->disposisi_lain . ' (Non-Satker)</span>';
                                    $hasLainnya = true;
                                }

                                if($disp->catatan_rektor) {
                                    $catatanRektor = $disp->catatan_rektor;
                                }
                            }
                            
                            // Logic tombol: Jika TIDAK ADA satker internal (berarti cuma ke Lainnya/Ormawa), maka tombolnya Arsip.
                            // Jika ada minimal 1 satker internal, tombolnya Forward (Sistem).
                            $isManualForward = ($hasLainnya && !$hasSatkerInternal);

                            // String Data Attribute (strip html tags for modal data)
                            $cleanTujuan = implode(', ', array_map('strip_tags', $tujuanList));
                        @endphp

                        <tr>
                            <th scope="row" class="text-center">{{ $surat->no_agenda }}</th>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->surat_dari }}</td>
                            <td>
                                <span class="badge text-bg-warning">Perlu Tindak Lanjut</span>
                            </td>
                            <td>
                                @if (count($tujuanList) > 0)
                                    <ul class="mb-1 ps-3 small" style="list-style-type: none; padding-left: 0 !important;">
                                        @foreach($tujuanList as $tujuanHTML)
                                            <li>{!! $tujuanHTML !!}</li>
                                        @endforeach
                                    </ul>
                                    <small class="text-muted fst-italic d-block mt-1">
                                        Catatan: "{{ \Illuminate\Support\Str::limit($catatanRektor, 50) }}"
                                    </small>
                                @else
                                    <span class="text-danger small"><em>(Data disposisi belum lengkap)</em></span>
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
                                        data-tujuan-disposisi="{{ $cleanTujuan }}" 
                                        data-catatan-rektor="{{ $catatanRektor }}"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    
                                    {{-- TOMBOL AKSI BERDASARKAN TUJUAN --}}
                                    @if($isManualForward)
                                        {{-- KASUS: Tujuan adalah LAINNYA (Ormawa/Hardcopy) --}}
                                        <form action="{{ route('bau.surat.selesaikanLainnya', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Surat ini untuk Pihak Luar/Ormawa (Non-Sistem).\n\nApakah Anda sudah menyerahkan Hardcopy/File kepada yang bersangkutan?\n\nKlik OK untuk menandai Selesai & Arsip.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Tandai Selesai & Arsip (Manual)">
                                                <i class="bi bi-archive-fill"></i> Arsip
                                            </button>
                                        </form>
                                    @else
                                        {{-- KASUS: Tujuan adalah SATKER INTERNAL (Sistem) --}}
                                        <form action="{{ route('bau.surat.forwardToSatker', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Teruskan surat ini ke akun Satker tujuan secara sistem?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Teruskan ke Sistem Satker">
                                                <i class="bi bi-send-check-fill"></i>
                                            </button>
                                        </form>
                                    @endif

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

{{-- MODAL DETAIL (Sama seperti sebelumnya) --}}
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
                           <tr><td class="info-modal-label">No. Agenda</td><td class="info-modal-data">: <span id="modal-no-agenda"></span></td></tr>
                           <tr><td class="info-modal-label">Asal Surat</td><td class="info-modal-data">: <span id="modal-asal-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Tanggal Surat</td><td class="info-modal-data">: <span id="modal-tanggal-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Tanggal Diterima</td><td class="info-modal-data">: <span id="modal-tanggal-diterima"></span></td></tr>
                           <tr class="border-top">
                               <td class="info-modal-label pt-3">Tujuan Disposisi</td>
                               <td class="info-modal-data pt-3">: <span id="modal-tujuan-disposisi" class="fw-bold text-primary"></span></td>
                           </tr>
                           <tr><td class="info-modal-label">Catatan Rektor</td><td class="info-modal-data">: <span id="modal-catatan-rektor" class="fst-italic text-muted"></span></td></tr>
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
                paginate: { next: "Next", previous: "Prev" }
            }
        });
        
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var noAgenda = button.getAttribute('data-no-agenda');
            var perihal = button.getAttribute('data-perihal');
            var asalSurat = button.getAttribute('data-asal-surat');
            var tanggalSurat = button.getAttribute('data-tanggal-surat');
            var tanggalDiterima = button.getAttribute('data-tanggal-diterima');
            var tujuanDisposisi = button.getAttribute('data-tujuan-disposisi'); 
            var catatanRektor = button.getAttribute('data-catatan-rektor');    
            var fileUrl = button.getAttribute('data-file-url');

            detailSuratModal.querySelector('#modal-perihal').textContent = perihal;
            detailSuratModal.querySelector('#modal-no-agenda').textContent = noAgenda;
            detailSuratModal.querySelector('#modal-asal-surat').textContent = asalSurat;
            detailSuratModal.querySelector('#modal-tanggal-surat').textContent = tanggalSurat;
            detailSuratModal.querySelector('#modal-tanggal-diterima').textContent = tanggalDiterima;
            detailSuratModal.querySelector('#modal-tujuan-disposisi').textContent = tujuanDisposisi; 
            detailSuratModal.querySelector('#modal-catatan-rektor').textContent = catatanRektor;    
            
            var btnDownload = detailSuratModal.querySelector('#modal-download-button');
            btnDownload.href = fileUrl;
            
            var wrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
            if (ext == 'pdf') {
                wrapper.innerHTML = '<iframe src="' + fileUrl + '" width="100%" height="100%" frameborder="0"></iframe>';
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                wrapper.innerHTML = '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 100%; width: 100%; object-fit: contain;">';
            } else {
                 wrapper.innerHTML = '<div class="text-center p-5"><i class="bi bi-file-earmark-text h1 text-muted"></i><p class="mt-3">Preview tidak didukung.</p></div>';
            }
        });
    });
</script>
@endpush