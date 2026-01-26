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

    /* CSS Timeline Admin Rektor (DIPERBAIKI: Tag Style Ganda Dihapus) */
    .timeline { list-style: none; padding: 20px 0 20px; position: relative; }
    .timeline:before { top: 0; bottom: 0; position: absolute; content: " "; width: 3px; background-color: #eeeeee; left: 25px; margin-right: -1.5px; }
    .timeline > li { margin-bottom: 20px; position: relative; }
    .timeline > li:before, .timeline > li:after { content: " "; display: table; }
    .timeline > li:after { clear: both; }
    .timeline > li > .timeline-panel { width: calc(100% - 75px); float: right; border: 1px solid #d4d4d4; border-radius: 2px; padding: 10px; position: relative; -webkit-box-shadow: 0 1px 6px rgba(0, 0, 0, 0.175); box-shadow: 0 1px 6px rgba(0, 0, 0, 0.175); background: #fff; }
    .timeline > li > .timeline-panel:before { position: absolute; top: 26px; left: -15px; display: inline-block; border-top: 15px solid transparent; border-left: 0 solid #ccc; border-right: 15px solid #ccc; border-bottom: 15px solid transparent; content: " "; }
    .timeline > li > .timeline-panel:after { position: absolute; top: 27px; left: -14px; display: inline-block; border-top: 14px solid transparent; border-left: 0 solid #fff; border-right: 14px solid #fff; border-bottom: 14px solid transparent; content: " "; }
    .timeline > li > .timeline-badge { color: #fff; width: 50px; height: 50px; line-height: 50px; font-size: 1.4em; text-align: center; position: absolute; top: 16px; left: 0px; margin-right: -25px; background-color: #999999; z-index: 100; border-top-right-radius: 50%; border-top-left-radius: 50%; border-bottom-right-radius: 50%; border-bottom-left-radius: 50%; }
    .timeline-badge.primary { background-color: #2e6da4 !important; }
    .timeline-badge.success { background-color: #3f903f !important; }
    .timeline-badge.warning { background-color: #f0ad4e !important; }
    .timeline-badge.danger { background-color: #d9534f !important; }
    .timeline-badge.info { background-color: #5bc0de !important; }
    .timeline-title { margin-top: 0; color: inherit; }
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
                            
                            {{-- Tombol Buat Baru --}}
                            <a href="{{ route('satker.surat-keluar.internal.create') }}" class="btn btn-primary btn-sm shadow-sm">
                                <i class="bi bi-plus-lg me-1"></i> Buat Surat Baru
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABEL DATA --}}
        <div class="card-body p-0"> 
            <div class="table-responsive">
                <table id="tabelSuratKeluar" class="table table-hover align-middle w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" width="5%">No</th>
                            <th width="20%">Tujuan Surat</th>
                            <th width="25%">No. Surat & Perihal</th>
                            <th width="12%">Tanggal</th> 
                            <th class="text-center" width="8%">File</th>
                            {{-- KOLOM BARU --}}
                            <th class="text-center" width="15%">Status & Riwayat</th>
                            <th class="text-center" width="15%">Aksi</th>
                        </tr>
                    </thead>
    <tbody>
@foreach($suratKeluar as $surat)
    @php
        // 1. Setup Awal
        $totalPenerima = $surat->penerimaInternal->count();
        $isAntarSatker = $totalPenerima > 0;
        
        $tujuanTeks = strtolower(trim($surat->tujuan_surat));
        $isKeRektorat = !empty($tujuanTeks) && 
                        (str_contains($tujuanTeks, 'rektor') || 
                         str_contains($tujuanTeks, 'univ'));
        
        // --- PENANGANAN PEGAWAI ---
        $riwayatPegawai = $surat->riwayats->whereNotNull('penerima_id');
        
        // Kunci: Surat dianggap "Ke Pegawai" hanya jika tidak ada tujuan Satker (Direct ke Pegawai)
        $isKePegawaiDirect = !$isAntarSatker && !$isKeRektorat && $riwayatPegawai->isNotEmpty();
        
        $isLocked = false;
        $statusDisplay = 'Terkirim'; 
        $badgeColor = 'warning'; 

        // --- LOGIKA STATUS ---
        if ($isKeRektorat) {
            $linkedSurat = \App\Models\Surat::where('nomor_surat', trim($surat->nomor_surat))->latest()->first();
            if ($linkedSurat) {
                $statusRemote = $linkedSurat->status;
                $isLocked = true; 
                if ($statusRemote == 'di_satker' || stripos($statusRemote, 'Disposisi') !== false) {
                    $statusDisplay = 'Selesai (Didisposisi)'; $badgeColor = 'success';
                } elseif (stripos($statusRemote, 'Arsip') !== false || stripos($statusRemote, 'Selesai') !== false) {
                    $statusDisplay = 'Selesai (Diarsipkan)'; $badgeColor = 'success';
                } elseif (stripos($statusRemote, 'di_admin_rektor') !== false) {
                    $statusDisplay = 'Di Admin Rektor'; $badgeColor = 'info';
                } elseif (stripos($statusRemote, 'Diteruskan') !== false) {
                    $statusDisplay = 'Diteruskan BAU'; $badgeColor = 'info';
                }
            } else {
                $statusDisplay = 'Diterima BAU'; $badgeColor = 'info';
            }
        } elseif ($isAntarSatker) {
            $jumlahArsip = $surat->penerimaInternal->where('pivot.is_read', 2)->count();
            if ($jumlahArsip > 0) {
                $isLocked = true;
                $statusDisplay = ($jumlahArsip == $totalPenerima) ? 'Selesai' : 'Diterima Sebagian';
                $badgeColor = ($jumlahArsip == $totalPenerima) ? 'success' : 'info';
            }
        } elseif ($isKePegawaiDirect) {
            $totalPegawai = $riwayatPegawai->count();
            $jumlahDiterima = $riwayatPegawai->where('is_read', 2)->count();
            if ($jumlahDiterima > 0) {
                $isLocked = true;
                $statusDisplay = ($jumlahDiterima == $totalPegawai) ? 'Selesai' : 'Diterima Sebagian';
                $badgeColor = ($jumlahDiterima == $totalPegawai) ? 'success' : 'info';
            }
        }
    @endphp

    <tr>
        <td class="text-center fw-bold">{{ $loop->iteration }}</td>
        
        {{-- KOLOM TUJUAN (PERBAIKAN) --}}
        <td>
            <div class="d-flex flex-column gap-1">
                {{-- 1. PRIORITAS UTAMA: JIKA KE ANTAR SATKER --}}
                @if($isAntarSatker)
                    @foreach($surat->penerimaInternal as $penerima)
                        <span class="badge bg-light text-dark border text-start" style="width: fit-content; font-size: 11px;">
                            <i class="bi bi-building me-1 text-success"></i> {{ $penerima->nama_satker }}
                        </span>
                    @endforeach

                {{-- 2. PRIORITAS KEDUA: JIKA KE REKTORAT / BAU --}}
                @elseif($isKeRektorat)
                    <div class="d-flex flex-column mb-1">
                        <span class="fw-bold text-dark">{{ $surat->tujuan_surat }}</span>
                        <span class="badge bg-light text-dark border text-start" style="width: fit-content; font-size: 10px;">
                            <i class="bi bi-arrow-right-circle me-1 text-warning"></i> Via BAU
                        </span>
                    </div>

                {{-- 3. PRIORITAS KETIGA: JIKA DIRECT KE PEGAWAI (BUKAN DELEGASI SATKER) --}}
                @elseif($isKePegawaiDirect)
                    @foreach($riwayatPegawai as $rp)
                        <div class="d-flex flex-column mb-1">
                            <span class="fw-bold text-dark">{{ $rp->penerima->name ?? 'User Tidak Ditemukan' }}</span>
                            <span class="badge bg-light text-dark border text-start" style="width: fit-content; font-size: 10px;">
                                <i class="bi bi-person me-1 text-primary"></i> Personal
                            </span>
                        </div>
                    @endforeach

                {{-- 4. JIKA TIDAK ADA SAMA SEKALI --}}
                @else
                    <span class="text-muted fst-italic">- Tidak ada tujuan -</span>
                @endif
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
            <small class="text-muted d-block">Kirim: {{ \Carbon\Carbon::parse($surat->created_at)->format('d/m/y') }}</small>
            <small class="text-muted d-block">Surat: {{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d/m/y') }}</small>
        </td>

        {{-- FILE --}}
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

        {{-- KOLOM STATUS --}}
        <td class="text-center">
            <span class="badge bg-{{ $badgeColor }} text-wrap shadow-sm" style="font-size: 0.8rem; max-width: 150px;">
                {{ $statusDisplay }}
            </span>
        </td>

        {{-- KOLOM AKSI --}}
<td class="text-center">
    <div class="d-flex justify-content-center gap-2">
        @if(!$isLocked)
            {{-- 1. TOMBOL EDIT (Hanya jika belum locked) --}}
            <a href="{{ route('satker.surat-keluar.internal.edit', $surat->id) }}" class="btn btn-sm btn-warning text-white" title="Edit">
                <i class="bi bi-pencil-fill small"></i>
            </a>
        @endif

        {{-- 2. TOMBOL HAPUS (Selalu muncul sesuai permintaan Anda) --}}
        <form action="{{ route('satker.surat-keluar.internal.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus surat ini?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                <i class="bi bi-trash-fill small"></i>
            </button>
        </form>

        {{-- 3. TOMBOL LOG/RIWAYAT (Selalu muncul, desain menyesuaikan kondisi locked) --}}
        @if(!$isLocked)
            <button class="btn btn-sm btn-light border btn-icon rounded-circle" 
                    data-bs-toggle="modal" 
                    data-bs-target="#riwayatModal" 
                    data-url="{{ route('satker.surat-keluar.internal.riwayat-status', $surat->id) }}"
                    title="Lihat Log">
                <i class="bi bi-clock-history"></i>
            </button>
        @else
            <button type="button" class="btn btn-secondary btn-sm btn-icon shadow-sm"
                    data-id="{{ $surat->id }}" 
                    data-bs-toggle="modal" 
                    data-bs-target="#riwayatModal" 
                    data-url="{{ route('satker.surat-keluar.internal.riwayat-status', $surat->id) }}"
                    title="Lihat Log">
                 <i class="bi bi-clock-history"></i>
            </button>
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

<div class="modal fade" id="riwayatModal" tabindex="-1" aria-labelledby="riwayatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="riwayatModalLabel">Riwayat Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light" id="riwayatModalBody">
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
        if(fileModal){
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
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        var riwayatModal = document.getElementById('riwayatModal');
        
        // Cek dulu apakah modalnya ada di DOM untuk mencegah error 'qt'
        if (riwayatModal) {
            riwayatModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                // Pengaman jika button tidak ada (misal dipanggil manual)
                if (!button) return;

                var dataUrl = button.getAttribute('data-url');
                var modalBody = riwayatModal.querySelector('#riwayatModalBody');
                var modalLabel = riwayatModal.querySelector('#riwayatModalLabel');

                // 1. Tampilkan Loading
                modalBody.innerHTML = `
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Memuat data riwayat...</p>
                    </div>
                `;

                // 2. Fetch Data
                if(dataUrl) {
                    fetch(dataUrl)
                        .then(response => {
                            if (!response.ok) { throw new Error('Network response was not ok'); }
                            return response.json();
                        })
                        .then(surat => {
                            // Update Judul
                            modalLabel.textContent = 'Riwayat No: ' + surat.nomor_surat;

                            // 3. Render Timeline
                            var html = '<ul class="timeline">';
                            
                            if (surat.riwayats && surat.riwayats.length > 0) {
                                surat.riwayats.forEach((item) => {
                                    var badgeColor = 'primary'; 
                                    var iconClass = 'bi-check';
                                    var status = item.status_aksi || '';

                                   // Di dalam loop foreach riwayats pada JS:
                                    if (status.includes('Selesai') || status.includes('Arsip')) {
                                        badgeColor = 'success'; iconClass = 'bi-check-all';
                                    } else if (status.includes('Disposisi') || status.includes('Diteruskan')) {
                                        badgeColor = 'warning'; iconClass = 'bi-arrow-right-short';
                                    } else if (status.includes('Diterima')) {
                                        badgeColor = 'info'; iconClass = 'bi-box-arrow-in-down';
                                    } else if (status.includes('Dibaca')) {
                                        badgeColor = 'primary'; iconClass = 'bi-eye-fill';
                                    } else if (status.includes('Dikirim') || status.includes('Input')) {
                                        badgeColor = 'secondary'; iconClass = 'bi-send';
                                    }

                                    var dateObj = new Date(item.created_at);
                                    var dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                                    var userName = item.user ? item.user.name : 'Sistem';

                                    html += `
                                        <li>
                                            <div class="timeline-badge ${badgeColor}"><i class="bi ${iconClass}"></i></div>
                                            <div class="timeline-panel">
                                                <div class="timeline-heading">
                                                    <h6 class="timeline-title fw-bold">${status}</h6>
                                                    <p class="mb-0"><small class="text-muted"><i class="bi bi-clock"></i> ${dateStr} &bull; Oleh: <strong>${userName}</strong></small></p>
                                                </div>
                                                <div class="timeline-body mt-2">
                                                    <p class="mb-0 text-dark small">${item.catatan ?? '-'}</p>
                                                </div>
                                            </div>
                                        </li>
                                    `;
                                });
                            } else {
                                html += '<li class="text-center text-muted p-3">Belum ada riwayat aktivitas.</li>';
                            }

                            html += '</ul>';
                            modalBody.innerHTML = html;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            modalBody.innerHTML = '<div class="alert alert-danger">Gagal memuat data riwayat. Silakan coba lagi.</div>';
                        });
                }
            });
        }
    });
</script>
@endpush