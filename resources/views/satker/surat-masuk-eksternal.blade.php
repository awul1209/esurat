@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px agar konsisten */
    #tabelSuratUnified, .dataTables_wrapper, .modal-body { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    
    .info-modal-label { width: 140px; font-weight: 600; }
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
            <h6 class="m-0 fw-bold text-primary">Daftar Semua Surat Masuk Eksternal</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratUnified" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">No</th>
                            <th scope="col">Asal Surat</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Tgl. Diterima</th>
                            <th scope="col">Status / Posisi</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Menggabungkan Surat Disposisi dan Surat Edaran jadi satu koleksi
                            $allSurat = $suratMasukSatker->merge($suratEdaran)->unique('id')->sortByDesc('diterima_tanggal');
                        @endphp

                        @foreach ($allSurat as $index => $surat)
                        @php
                            $isEdaran = isset($surat->pivot);
                            $disposisi = $surat->disposisis->last();
                            $catatanRektor = $disposisi->catatan_rektor ?? '-';

                            // Ambil Catatan Delegasi
                            $riwayatDelegasi = $surat->riwayats->filter(function($r) {
                                return str_contains($r->status_aksi, 'Didelegasikan') || str_contains($r->status_aksi, 'Diteruskan ke Pegawai');
                            })->sortByDesc('created_at')->first();
                            
                            $catatanSatker = '-';
                            if ($riwayatDelegasi) {
                                if (str_contains($riwayatDelegasi->catatan, 'Catatan:')) {
                                     $parts = explode('Catatan:', $riwayatDelegasi->catatan);
                                     $catatanSatker = trim(end($parts), ' "');
                                } else {
                                     $catatanSatker = $riwayatDelegasi->catatan;
                                }
                            }

                            // --- LOGIKA STATUS & TOMBOL ---
                            // Cek apakah surat sudah diproses (Selesai/Delegasi/Broadcast)
                            $isProcessed = false;
                            $statusBadge = '';

                            if ($surat->status == 'arsip_satker') {
                                // Kasus 1: Sudah Diarsipkan/Selesai di Satker
                                $isProcessed = true;
                                $statusBadge = '<span class="badge bg-secondary">Selesai (Diarsipkan)</span>';
                            } elseif ($surat->tujuan_user_id) {
                                // Kasus 2: Sudah Didelegasikan ke 1 Pegawai
                                $isProcessed = true;
                                $statusBadge = '<span class="badge bg-primary">Delegasi: '.($surat->tujuanUser->name ?? 'Pegawai').'</span>';
                            } elseif ($isEdaran && $surat->pivot->status == 'diteruskan_internal') {
                                // Kasus 3: Sudah Disebarkan ke Semua Pegawai (Edaran)
                                $isProcessed = true;
                                $statusBadge = '<span class="badge bg-success">Disebarkan ke Semua</span>';
                            } else {
                                // Kasus 4: Belum Diproses
                                $statusBadge = '<span class="badge bg-warning text-dark">Baru / Belum Diproses</span>';
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                {{ $surat->surat_dari }}
                                <br><small class="text-muted">{{ $surat->nomor_surat }}</small>
                                @if($isEdaran)
                                    <br><span class="badge bg-info text-dark" style="font-size: 0.65rem;">Edaran/Umum</span>
                                @endif
                            </td>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->diterima_tanggal->isoFormat('D MMM YYYY') }}</td>
                            <td>{!! $statusBadge !!}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    
                                    {{-- 1. TOMBOL LIHAT (Selalu Muncul) --}}
                                    <button type="button" class="btn btn-sm btn-info" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailSuratModal"
                                        data-nomor-surat="{{ $surat->nomor_surat }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-asal-surat="{{ $surat->surat_dari }}"
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-catatan-rektor="{{ $catatanRektor }}"
                                        data-catatan-satker="{{ $catatanSatker }}"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    {{-- 
                                        LOGIKA TOMBOL PROSES 
                                        Hanya muncul jika surat BELUM diproses ($isProcessed == false)
                                    --}}
                                    @if(!$isProcessed)
                                        
                                        {{-- 2. TOMBOL DELEGASI PEGAWAI (Personal) --}}
                                        <button type="button" class="btn btn-sm btn-primary" 
                                            title="Delegasi ke 1 Pegawai"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#delegasiModal"
                                            data-id="{{ $surat->id }}"
                                            data-perihal="{{ $surat->perihal }}">
                                            <i class="bi bi-person-fill-add"></i>
                                        </button>

                                        {{-- 3. TOMBOL DELEGASI SEMUA (Broadcast) --}}
                                        {{-- Hanya untuk Surat Edaran --}}
                                        @if($isEdaran)
                                            <form action="{{ route('satker.surat.broadcastInternal', $surat->id) }}" method="POST" onsubmit="return confirm('Sebarkan surat ini ke SEMUA pegawai?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Delegasi ke Semua Pegawai">
                                                    <i class="bi bi-people-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- 4. TOMBOL SELESAI (Arsip) --}}
                                        {{-- Ikon diperbarui menjadi Clipboard Check agar lebih bagus --}}
                                        <form action="{{ route('satker.surat.arsipkan', $surat->id) }}" method="POST" onsubmit="return confirm('Tandai surat ini sebagai SELESAI (Arsip) tanpa delegasi?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Selesai & Arsip (Tanpa Delegasi)">
                                                <i class="bi bi-clipboard-check-fill"></i>
                                            </button>
                                        </form>

                                    @endif
                                    
                                    {{-- 5. TOMBOL CETAK --}}
                                    {{-- Muncul jika SUDAH diproses ($isProcessed == true) --}}
                                    @if($isProcessed)
                                        <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak Disposisi">
                                            <i class="bi bi-printer-fill"></i>
                                        </a>
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

{{-- MODAL 1: DETAIL SURAT --}}
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
                               <td class="info-modal-label">Nomor Surat</td>
                               <td class="info-modal-data">: <span id="modal-nomor-surat"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Asal Surat</td>
                               <td class="info-modal-data">: <span id="modal-asal-surat"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label">Tanggal Surat</td>
                               <td class="info-modal-data">: <span id="modal-tanggal-surat"></span></td>
                           </tr>
                           
                           <tr class="border-top">
                               <td class="info-modal-label pt-2">Catatan Rektor</td>
                               <td class="info-modal-data pt-2">: <span id="modal-catatan-rektor" class="fst-italic text-muted"></span></td>
                           </tr>
                           
                           <tr>
                               <td class="info-modal-label text-primary">Instruksi Anda</td>
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

{{-- MODAL 2: DELEGASI KE PEGAWAI (PERSONAL) --}}
<div class="modal fade" id="delegasiModal" tabindex="-1" aria-labelledby="delegasiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formDelegasi" action="" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="delegasiModalLabel">Delegasikan Surat (Personal)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Perihal Surat:</label>
                        <p id="delegasi-perihal" class="text-muted mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <label for="tujuan_user_id" class="form-label">Pilih Pegawai Tujuan: <span class="text-danger">*</span></label>
                        <select class="form-select" id="tujuan_user_id" name="tujuan_user_id" required>
                            <option value="">-- Pilih Pegawai --</option>
                            @foreach ($daftarPegawai as $pegawai)
                                <option value="{{ $pegawai->id }}">{{ $pegawai->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="catatan_satker" class="form-label">Catatan / Instruksi (Opsional):</label>
                        <textarea class="form-control" id="catatan_satker" name="catatan_satker" rows="3" placeholder="Contoh: Tolong segera diproses..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Delegasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
<script>
    $(document).ready(function () {
        // Init DataTable Tunggal
        new DataTable('#tabelSuratUnified', {
            pagingType: 'simple_numbers',
            order: [[ 3, 'desc' ]], // Urut berdasarkan Tgl Diterima
            language: {
                search: "Cari:",
                lengthMenu: "_MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                paginate: { next: "Next", previous: "Prev" }
            }
        });

        // Modal Detail Script
        var detailModal = document.getElementById('detailSuratModal');
        detailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Ambil data
            var perihal = button.getAttribute('data-perihal');
            var nomorSurat = button.getAttribute('data-nomor-surat');
            var asalSurat = button.getAttribute('data-asal-surat');
            var tanggalSurat = button.getAttribute('data-tanggal-surat');
            var catatanRektor = button.getAttribute('data-catatan-rektor');
            var catatanSatker = button.getAttribute('data-catatan-satker');
            var fileUrl = button.getAttribute('data-file-url');

            // Set content
            detailModal.querySelector('#modal-perihal').textContent = perihal;
            detailModal.querySelector('#modal-nomor-surat').textContent = nomorSurat;
            detailModal.querySelector('#modal-asal-surat').textContent = asalSurat;
            detailModal.querySelector('#modal-tanggal-surat').textContent = tanggalSurat;
            detailModal.querySelector('#modal-catatan-rektor').textContent = catatanRektor;
            
            var elCatatanSatker = detailModal.querySelector('#modal-catatan-satker');
            elCatatanSatker.textContent = catatanSatker;
            
            if (catatanSatker !== '-') {
                elCatatanSatker.classList.add('text-primary');
                elCatatanSatker.classList.remove('text-muted');
            } else {
                elCatatanSatker.classList.remove('text-primary');
                elCatatanSatker.classList.add('text-muted');
            }

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

        // Modal Delegasi Script
        var delegasiModal = document.getElementById('delegasiModal');
        delegasiModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var perihal = button.getAttribute('data-perihal');
            
            delegasiModal.querySelector('#delegasi-perihal').textContent = perihal;
            
            var form = delegasiModal.querySelector('#formDelegasi');
            form.action = '/satker/surat/' + id + '/delegasi-ke-pegawai'; 
        });
    });
</script>
@endpush