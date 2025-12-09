@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px agar konsisten */
    #tabelSuratMasuk, .dataTables_wrapper, .modal-body { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    
    .info-modal-label { width: 150px; font-weight: 600; }
    .info-modal-data { word-break: break-word; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    
    <h1 class="h3 mb-4 text-gray-800">Surat Masuk Untuk Saya</h1>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 13px;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Daftar Surat Masuk (Delegasi)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratMasuk" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Asal Surat</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Tgl. Diterima</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- PERBAIKAN: Menggunakan $suratUntukSaya sesuai Controller Anda --}}
                        @foreach ($suratUntukSaya as $index => $surat)
                        @php
                            // 1. Ambil Info Disposisi Rektor (Jika ada)
                            $disposisi = $surat->disposisis->last();
                            $catatanRektor = $disposisi->catatan_rektor ?? '-';
                            $tujuanDisposisi = $disposisi->tujuanSatker->nama_satker ?? '-';

                            // 2. LOGIKA UTAMA: Ambil Catatan Delegasi dari Satker (Pimpinan Anda)
                            // Cari riwayat terakhir dimana statusnya adalah delegasi
                            $riwayatDelegasi = $surat->riwayats->filter(function($r) {
                                return str_contains($r->status_aksi, 'Didelegasikan') || str_contains($r->status_aksi, 'Diteruskan ke Pegawai');
                            })->sortByDesc('created_at')->first();
                            
                            $catatanSatker = '-';
                            if ($riwayatDelegasi) {
                                // Parsing teks catatan: "Didelegasikan oleh... Catatan: ISI PESAN"
                                // Jika ada kata 'Catatan:', ambil teks setelahnya.
                                if (str_contains($riwayatDelegasi->catatan, 'Catatan:')) {
                                     $parts = explode('Catatan:', $riwayatDelegasi->catatan);
                                     $catatanSatker = trim(end($parts), ' "');
                                } else {
                                     // Jika tidak ada format khusus, ambil seluruh catatan riwayat
                                     $catatanSatker = $riwayatDelegasi->catatan;
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $surat->surat_dari }}<br><small class="text-muted">{{ $surat->nomor_surat }}</small></td>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->diterima_tanggal->isoFormat('D MMM YYYY') }}</td>
                            <td>
                                @if($surat->status == 'selesai')
                                    <span class="badge bg-success">Selesai</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Diproses</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- TOMBOL LIHAT (Dengan Data Lengkap) --}}
                                    <button type="button" class="btn btn-sm btn-info" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailSuratModal"
                                        data-nomor-surat="{{ $surat->nomor_surat }}"
                                        data-no-agenda="{{ $surat->no_agenda }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-asal-surat="{{ $surat->surat_dari }}"
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                        data-sifat="{{ $surat->sifat }}"
                                        data-tipe="{{ $surat->tipe_surat }}"
                                        data-tujuan-disposisi="{{ $tujuanDisposisi }}"
                                        data-catatan-rektor="{{ $catatanRektor }}"
                                        data-catatan-satker="{{ $catatanSatker }}" {{-- PENTING: Data Catatan Satker --}}
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    {{-- Tombol Tandai Selesai (Jika belum) --}}
                                    @if($surat->status != 'selesai')
                                    <form action="{{ route('pegawai.surat.selesai', $surat->id) }}" method="POST" onsubmit="return confirm('Tandai surat ini sebagai selesai dikerjakan?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Tandai Selesai">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    @endif

                                    {{-- TOMBOL CETAK (BARU) --}}
                                    <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak Lembar Disposisi">
                                        <i class="bi bi-printer-fill"></i>
                                    </a>
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

{{-- MODAL DETAIL SURAT --}}
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
                        <table class="table table-borderless table-sm small">
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
                           <tr>
                               <td class="info-modal-label">Sifat / Tipe</td>
                               <td class="info-modal-data">: <span id="modal-sifat"></span> / <span id="modal-tipe" class="text-capitalize"></span></td>
                           </tr>
                           
                           <tr class="border-top">
                               <td class="info-modal-label pt-3">Tujuan Disposisi</td>
                               <td class="info-modal-data pt-3">: <span id="modal-tujuan-disposisi" class="fw-bold"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Catatan Rektor</td>
                               <td class="info-modal-data">: <span id="modal-catatan-rektor" class="fst-italic text-muted"></span></td>
                           </tr>
                           
                           {{-- BARIS PENTING: PESAN DARI SATKER --}}
                           <tr>
                               <td class="info-modal-label text-primary">Pesan/Instruksi<br>Satker (Delegasi)</td>
                               <td class="info-modal-data">: <span id="modal-catatan-satker" class="fw-bold text-primary"></span></td>
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
        // Init DataTable
        new DataTable('#tabelSuratMasuk', {
            pagingType: 'simple_numbers',
            order: [[ 3, 'desc' ]], 
            language: {
                search: "Cari:",
                lengthMenu: "_MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                paginate: { next: "Next", previous: "Prev" }
            }
        });

        // ----------------------------------------------------
        // SCRIPT MODAL DETAIL (DENGAN CATATAN SATKER)
        // ----------------------------------------------------
        var detailModal = document.getElementById('detailSuratModal');
        detailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Ambil data dari tombol
            var perihal = button.getAttribute('data-perihal');
            var noAgenda = button.getAttribute('data-no-agenda');
            var nomorSurat = button.getAttribute('data-nomor-surat');
            var asalSurat = button.getAttribute('data-asal-surat');
            var tanggalSurat = button.getAttribute('data-tanggal-surat');
            var tanggalDiterima = button.getAttribute('data-tanggal-diterima');
            var sifat = button.getAttribute('data-sifat');
            var tipe = button.getAttribute('data-tipe');
            var tujuanDisposisi = button.getAttribute('data-tujuan-disposisi');
            var catatanRektor = button.getAttribute('data-catatan-rektor');
            var catatanSatker = button.getAttribute('data-catatan-satker'); // AMBIL DATA
            var fileUrl = button.getAttribute('data-file-url');

            // Isi ke elemen modal
            detailModal.querySelector('#modal-perihal').textContent = perihal;
            detailModal.querySelector('#modal-no-agenda').textContent = noAgenda;
            detailModal.querySelector('#modal-nomor-surat').textContent = nomorSurat;
            detailModal.querySelector('#modal-asal-surat').textContent = asalSurat;
            detailModal.querySelector('#modal-tanggal-surat').textContent = tanggalSurat;
            detailModal.querySelector('#modal-tanggal-diterima').textContent = tanggalDiterima;
            detailModal.querySelector('#modal-sifat').textContent = sifat;
            detailModal.querySelector('#modal-tipe').textContent = tipe;
            detailModal.querySelector('#modal-tujuan-disposisi').textContent = tujuanDisposisi;
            detailModal.querySelector('#modal-catatan-rektor').textContent = catatanRektor;
            
            // ISI CATATAN SATKER
            var elCatatanSatker = detailModal.querySelector('#modal-catatan-satker');
            elCatatanSatker.textContent = catatanSatker;
            
            // Styling jika ada isi
            if (catatanSatker !== '-') {
                elCatatanSatker.classList.add('text-primary');
                elCatatanSatker.classList.remove('text-muted');
            } else {
                elCatatanSatker.classList.remove('text-primary');
                elCatatanSatker.classList.add('text-muted');
            }

            // File Preview
            var btnDownload = detailModal.querySelector('#modal-download-button');
            btnDownload.href = fileUrl;
            
            var wrapper = detailModal.querySelector('#modal-file-preview-wrapper');
            var ext = fileUrl.split('.').pop().toLowerCase();
            if(ext === 'pdf'){
                wrapper.innerHTML = '<iframe src="'+fileUrl+'" width="100%" height="100%" frameborder="0"></iframe>';
            } else {
                wrapper.innerHTML = '<img src="'+fileUrl+'" class="img-fluid" style="max-height: 100%; width: 100%; object-fit: contain;">';
            }
        });
    });
</script>
@endpush