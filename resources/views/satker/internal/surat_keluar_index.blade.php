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
    // --- 1. SETUP AWAL (TETAP) ---
    $totalPenerimaSatkerRaw = $surat->penerimaInternal->count();
    $tujuanTeks = strtolower(trim($surat->tujuan_surat));
    $isKeRektorat = !empty($tujuanTeks) && 
                    (str_contains($tujuanTeks, 'rektor') || 
                     str_contains($tujuanTeks, 'univ'));
    
    $pimpinanIds = $surat->validasis->pluck('pimpinan_id')->toArray();

    // --- 2. FILTER TUJUAN ASLI (TETAP) ---
    $riwayatPegawaiDirect = $surat->riwayats->filter(function($riwayat) use ($pimpinanIds, $surat) {
        return !empty($riwayat->penerima_id) && 
                !in_array($riwayat->penerima_id, $pimpinanIds) &&
                $riwayat->user_id == $surat->user_id; 
    });

    $isKePegawaiDirect = $riwayatPegawaiDirect->isNotEmpty();

    $satkerIdsDariPegawai = $riwayatPegawaiDirect->map(fn($rp) => $rp->penerima->satker_id ?? null)
                            ->filter()->unique()->toArray();

    $satkerTujuanMurni = $surat->penerimaInternal->filter(function($s) use ($satkerIdsDariPegawai) {
        return !in_array($s->id, $satkerIdsDariPegawai);
    });

    $isAntarSatker = $satkerTujuanMurni->isNotEmpty();
    
    // --- 3. HITUNG TARGET & RESPON (PERBAIKAN DI SINI) ---
    // Target tetap dihitung dari unik Pegawai + Satker Murni
    $totalTargetSurat = $riwayatPegawaiDirect->count() + $satkerTujuanMurni->count();
    
    // PERBAIKAN: Gunakan $surat->penerimaInternal (koleksi lengkap) bukan $satkerTujuanMurni
    // Agar jika Admin Satker mengarsipkan, statusnya tetap terhitung
    $arsipSatker = $surat->penerimaInternal->where('pivot.is_read', 2)->count();
    $arsipPegawai = $riwayatPegawaiDirect->where('is_read', 2)->count();
    
    $totalSudahRespon = $arsipSatker + $arsipPegawai;

    // Hitung Dibaca (is_read = 1)
    $dibacaSatker = $surat->penerimaInternal->where('pivot.is_read', 1)->count();
    $dibacaPegawai = $riwayatPegawaiDirect->where('is_read', 1)->count();
    $totalDibaca = $dibacaSatker + $dibacaPegawai + $totalSudahRespon;

    // Default Status
    $isLocked = false;
    $statusDisplay = 'Terkirim'; 
    $badgeColor = 'warning'; 

    // --- 4. LOGIKA VALIDASI PIMPINAN (TETAP) ---
    $totalValidasi = $surat->validasis->count();
    $sudahValidasi = $surat->validasis->where('status', 'setuju')->count();
    $adaRevisi = $surat->validasis->where('status', 'revisi')->count();
    $isWaitingValidation = $totalValidasi > 0 && ($sudahValidasi < $totalValidasi) && ($adaRevisi == 0);

    if ($adaRevisi > 0) {
        $statusDisplay = "Minta Revisi"; $badgeColor = 'danger'; $isLocked = false; 
    } elseif ($isWaitingValidation) {
        $statusDisplay = "Menunggu Validasi ($sudahValidasi/$totalValidasi)"; $badgeColor = 'secondary';
        $isLocked = ($sudahValidasi > 0); 
    } else {
        $isLocked = true;
        
        // --- 5. LOGIKA PENENTUAN STATUS AKHIR (PERBAIKAN DI SINI) ---
        if ($isKePegawaiDirect || $totalPenerimaSatkerRaw > 0) {
            if ($totalSudahRespon > 0) {
                // Gunakan perbandingan total respon vs total target
                $statusDisplay = ($totalSudahRespon >= $totalTargetSurat) ? 'Selesai' : 'Diterima Sebagian';
                $badgeColor = ($totalSudahRespon >= $totalTargetSurat) ? 'success' : 'info';
            } elseif ($totalDibaca > 0) {
                $statusDisplay = 'Dibaca';
                $badgeColor = 'info';
            }
        } 
        
        // Logika Sinkronisasi Rektorat (Tetap)
        if ($isKeRektorat && $statusDisplay == 'Terkirim') {
            $linkedSurat = \App\Models\Surat::where('nomor_surat', trim($surat->nomor_surat))->latest()->first();
            if ($linkedSurat) {
                $statusRemote = $linkedSurat->status;
                if ($statusRemote == 'di_satker' || stripos($statusRemote, 'Disposisi') !== false || $statusRemote == 'selesai') {
                    $statusDisplay = 'Selesai'; $badgeColor = 'success';
                } elseif (stripos($statusRemote, 'Arsip') !== false || stripos($statusRemote, 'Selesai') !== false) {
                    $statusDisplay = 'Selesai'; $badgeColor = 'success';
                } elseif (stripos($statusRemote, 'di_admin_rektor') !== false) {
                    $statusDisplay = 'Di Admin Rektor'; $badgeColor = 'info';
                } elseif (stripos($statusRemote, 'Diteruskan') !== false) {
                    $statusDisplay = 'Diteruskan BAU'; $badgeColor = 'info';
                }
            } else {
                $statusDisplay = 'Diterima BAU'; $badgeColor = 'info';
            }
        }
    }
@endphp

<tr>
    <td class="text-center fw-bold">{{ $loop->iteration }}</td>
    
    {{-- KOLOM TUJUAN --}}
    <td>
        <div class="d-flex flex-column gap-1">
            {{-- 1. PEGAWAI TUJUAN LANGSUNG --}}
            @if($isKePegawaiDirect)
                @foreach($riwayatPegawaiDirect as $rp)
                    @php $namaPegawai = $rp->penerima->name ?? 'User Tidak Ditemukan'; @endphp
                    <div class="d-flex flex-column mb-1">
                        <span class="badge bg-light text-dark border text-start" style="width: fit-content; font-size: 10px;">
                            <i class="bi bi-person-fill me-1 text-primary"></i> {{ $namaPegawai }}
                        </span>
                    </div>
                @endforeach
            @endif

            {{-- 2. SATKER TUJUAN (Hanya muncul jika bukan Satker dari pegawai di atas) --}}
            @if($isAntarSatker)
                @foreach($satkerTujuanMurni as $penerima)
                    <span class="badge bg-light text-dark border text-start" style="width: fit-content; font-size: 11px;">
                        <i class="bi bi-building me-1 text-success"></i> {{ $penerima->nama_satker }}
                    </span>
                @endforeach
            @endif

            {{-- 3. REKTORAT --}}
            @if($isKeRektorat && !$isKePegawaiDirect && !$isAntarSatker)
                <div class="d-flex flex-column mb-1">
                    <span class="fw-bold text-dark" style="font-size: 12px;">{{ $surat->tujuan_surat }}</span>
                    <span class="badge bg-light text-dark border text-start" style="width: fit-content; font-size: 10px;">
                        <i class="bi bi-arrow-right-circle me-1 text-warning"></i> Via BAU
                    </span>
                </div>
            @endif

            {{-- FALLBACK --}}
            @if(!$isKePegawaiDirect && !$isAntarSatker && !$isKeRektorat)
                <span class="text-muted fst-italic">{{ $surat->tujuan_surat ?: '- Tidak ada tujuan -' }}</span>
            @endif
        </div>
    </td>

        {{-- NO SURAT & PERIHAL --}}
        <td>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-bold text-primary">{{ $surat->nomor_surat }}</span>
                @if($surat->sifat)
                    <span class="badge bg-info p-1" style="font-size: 9px;">{{ $surat->sifat }}</span>
                @endif
            </div>
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
                @php
                    $mengetahui = $surat->validasis->map(function($v) {
                        return ['nama' => $v->pimpinan->name, 'status' => $v->status, 'catatan' => $v->catatan];
                    });
                    $tembusan = $surat->tembusans->map(function($t) {
                        return $t->satker->nama_satker ?? ($t->user->name ?? 'User');
                    });
                @endphp
                <button type="button" class="btn btn-outline-primary btn-sm btn-icon" 
                        data-bs-toggle="modal" 
                        data-bs-target="#filePreviewModal"
                        data-title="{{ $surat->perihal }}"
                        data-nomor="{{ $surat->nomor_surat }}"
                        data-file-url="{{ asset('storage/' . $surat->file_surat) }}"
                        data-mengetahui='@json($mengetahui)'
                        data-tembusan='@json($tembusan)'>
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                </button>
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
                    {{-- Edit --}}
                    <a href="{{ route('satker.surat-keluar.internal.edit', $surat->id) }}" class="btn btn-sm btn-warning text-white" title="Edit">
                        <i class="bi bi-pencil-fill small"></i>
                    </a>
                    
                    {{-- Kirim Ulang (Hanya Saat Revisi) --}}
                    @if($adaRevisi > 0)
                        <form action="{{ route('satker.satker.surat-keluar.internal.resend', $surat->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success text-white" title="Kirim Ulang">
                                <i class="bi bi-send-fill small"></i>
                            </button>
                        </form>
                    @endif

                    {{-- Hapus --}}
                    <form action="{{ route('satker.surat-keluar.internal.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus surat ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                            <i class="bi bi-trash-fill small"></i>
                        </button>
                    </form>
                @endif

                <button class="btn btn-sm {{ $isLocked ? 'btn-secondary' : 'btn-light border' }} btn-icon rounded-circle" 
                        data-bs-toggle="modal" 
                        data-bs-target="#riwayatModal" 
                        data-url="{{ route('satker.surat-keluar.internal.riwayat-status', $surat->id) }}"
                        title="Lihat Log">
                    <i class="bi bi-clock-history"></i>
                </button>
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
            <div class="modal-body bg-light" id="riwayatModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW FILE --}}
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom">
                <h6 class="modal-title fw-bold text-primary" id="filePreviewModalLabel">Preview Surat</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 d-flex flex-column flex-lg-row" style="height: 80vh;">
                <div id="file-viewer-container" class="flex-grow-1 bg-dark d-flex align-items-center justify-content-center">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div class="bg-light border-start" style="width: 350px; overflow-y: auto;">
                    <div class="p-3">
                        <h6 class="fw-bold small text-secondary mb-3 border-bottom pb-2">DETAIL DOKUMEN</h6>
                        <div id="detail-nomor" class="mb-2 fw-bold text-primary small"></div>
                        <div id="detail-perihal" class="small text-muted mb-4" style="font-size: 11px;"></div>

                        <div class="mb-4">
                            <h6 class="fw-bold small" style="font-size: 12px;"><i class="bi bi-shield-check me-2"></i>MENGETAHUI</h6>
                            <div id="container-mengetahui" class="d-flex flex-column gap-2 mt-2">
                                </div>
                        </div>

                        <div>
                            <h6 class="fw-bold small" style="font-size: 12px;"><i class="bi bi-info-circle me-2"></i>TEMBUSAN</h6>
                            <div id="container-tembusan" class="d-flex flex-column gap-2 mt-2">
                                </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btn-download-file" class="btn btn-primary btn-sm shadow-sm" download target="_blank">
                    <i class="bi bi-download me-1"></i> Download
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

      var fileModal = document.getElementById('filePreviewModal');
if(fileModal){
    fileModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var fileUrl = button.getAttribute('data-file-url');
        var title = button.getAttribute('data-title');
        var nomor = button.getAttribute('data-nomor');
        
        // Parsing data JSON
        var dataMengetahui = JSON.parse(button.getAttribute('data-mengetahui') || '[]');
        var dataTembusan = JSON.parse(button.getAttribute('data-tembusan') || '[]');

        var modalTitle = fileModal.querySelector('.modal-title');
        var container = fileModal.querySelector('#file-viewer-container');
        var downloadBtn = fileModal.querySelector('#btn-download-file');
        
        // Panel Elements
        var contMengetahui = fileModal.querySelector('#container-mengetahui');
        var contTembusan = fileModal.querySelector('#container-tembusan');
        fileModal.querySelector('#detail-nomor').textContent = nomor;
        fileModal.querySelector('#detail-perihal').textContent = title;

        modalTitle.textContent = "Preview Surat";
        downloadBtn.href = fileUrl;

// --- Render Mengetahui ---
contMengetahui.innerHTML = dataMengetahui.length ? '' : '<small class="text-muted italic">Tidak ada validasi</small>';
dataMengetahui.forEach(function(v) {
    let color = v.status === 'setuju' ? 'success' : (v.status === 'revisi' ? 'danger' : 'secondary');
    let icon = v.status === 'setuju' ? 'bi-check-circle-fill' : (v.status === 'revisi' ? 'bi-exclamation-circle-fill' : 'bi-hourglass-split');
    
    // Logika Catatan Revisi
    let catatanHtml = (v.status === 'revisi' && v.catatan) 
        ? `<div class="mt-1 p-1 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded text-danger" style="font-size: 9px; line-height: 1.1;">
                <strong>Catatan:</strong> ${v.catatan}
           </div>` 
        : '';

    contMengetahui.innerHTML += `
        <div class="badge bg-light text-dark border d-flex flex-column p-2 text-start mb-1" style="white-space: normal;">
            <div class="d-flex align-items-center">
                <i class="bi ${icon} text-${color} me-2"></i>
                <div style="font-size: 10px;">
                    <span class="fw-bold d-block text-wrap">${v.nama}</span>
                    <span class="text-${color} small" style="font-size: 9px;">${v.status.toUpperCase()}</span>
                </div>
            </div>
            ${catatanHtml}
        </div>`;
});

        // --- Render Tembusan ---
        contTembusan.innerHTML = dataTembusan.length ? '' : '<small class="text-muted italic">Tidak ada tembusan</small>';
        dataTembusan.forEach(function(t) {
            contTembusan.innerHTML += `
                <div class="badge bg-white text-dark border p-2 text-start small fw-normal" style="font-size: 10px;">
                    <i class="bi bi-building text-primary me-2"></i>${t}
                </div>`;
        });

        // --- Render File (Logika lama Anda) ---
        if(fileUrl) {
            var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0];
            if (extension === 'pdf') {
                container.innerHTML = `<iframe src="${fileUrl}#toolbar=0" width="100%" height="100%" style="border:none;"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
                container.innerHTML = `<img src="${fileUrl}" class="img-fluid" style="max-height: 98%;">`;
            }
        }
    });
}
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var riwayatModal = document.getElementById('riwayatModal');
    if (riwayatModal) {
        riwayatModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var dataUrl = button.getAttribute('data-url');
            var modalBody = riwayatModal.querySelector('#riwayatModalBody');
            var modalLabel = riwayatModal.querySelector('#riwayatModalLabel');

            modalBody.innerHTML = `<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat data...</p></div>`;

            if(dataUrl) {
                fetch(dataUrl)
                    .then(response => response.json())
                    .then(surat => {
                        modalLabel.textContent = 'Riwayat No: ' + surat.nomor_surat;
                        var html = '<ul class="timeline">';
                        
                        if (surat.riwayats && surat.riwayats.length > 0) {
                            surat.riwayats.forEach((item) => {
                                var badgeColor = 'primary'; 
                                var iconClass = 'bi-check';
                                var status = item.status_aksi || '';

                                if (status.includes('Selesai') || status.includes('Arsip') || status.includes('Diterima oleh Pegawai')) {
                                    badgeColor = 'success'; 
                                    iconClass = 'bi-check-all';
                                } else if (status.includes('Dikirim')) {
                                    badgeColor = 'secondary';
                                    iconClass = 'bi-send';
                                } else if (status.includes('Diterima')) {
                                    badgeColor = 'primary';
                                    iconClass = 'bi-box-arrow-in-down';
                                }

                                var dateObj = new Date(item.created_at);
                                var dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                                
                                html += `
                                    <li>
                                        <div class="timeline-badge ${badgeColor}"><i class="bi ${iconClass}"></i></div>
                                        <div class="timeline-panel">
                                            <div class="timeline-heading">
                                                <h6 class="timeline-title fw-bold">${status}</h6>
                                                <p class="mb-0 small text-muted"><i class="bi bi-clock"></i> ${dateStr} &bull; Oleh: <strong>${item.user ? item.user.name : 'Sistem'}</strong></p>
                                            </div>
                                            <div class="timeline-body mt-2">
                                                <p class="mb-0 text-dark small">${item.catatan ?? '-'}</p>
                                            </div>
                                        </div>
                                    </li>`;
                            });
                        } else {
                            html += '<li class="text-center text-muted p-3">Belum ada riwayat.</li>';
                        }
                        html += '</ul>';
                        modalBody.innerHTML = html;
                    });
            }
        });
    }
});
</script>
@endpush