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

    /* CSS Timeline Log Aktivitas (Tambahan Baru) */
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

@php
    $isEksternal = Request::routeIs('bau.surat-keluar.eksternal');
    $tipeURL = $isEksternal ? 'eksternal' : 'internal';
    $labelJudul = $isEksternal ? 'Eksternal' : 'Internal';
    $routeFilter = $isEksternal ? route('bau.surat-keluar.eksternal') : route('bau.surat-keluar.internal');
@endphp

<div class="container-fluid px-3">

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
        <th width="20%">Perihal</th>
        <th width="20%">Tujuan</th>
        <th width="10%">Tanggal</th>
        {{-- KOLOM BARU: STATUS --}}
        {{-- PERBAIKAN: Hanya muncul jika BUKAN Eksternal --}}
        @if(!$isEksternal)
            <th width="15%" class="text-center">Status</th>
        @endif
        <th width="15%" class="text-center">Aksi</th>
    </tr>
</thead>
<tbody>
@foreach ($suratKeluars as $surat)
    @php
        // 1. Setup Data Awal
        // PERBAIKAN PINTAR: Jika Internal, prioritas ambil dari Pivot (agar tidak duplikat dengan kolom tujuan_surat yang lama)
        $tujuanModalText = $surat->tujuan_surat; 
        if ($surat->tipe_kirim == 'internal') {
            if ($surat->penerimaInternal->count() > 0) {
                // Jika ada data di pivot, gunakan data pivot (ini yang paling akurat)
                $tujuanModalText = $surat->penerimaInternal->pluck('nama_satker')->implode(', ');
            }
        }

        // 2. LOGIKA STATUS & KUNCI TOMBOL (KHUSUS INTERNAL)
        $isLocked = false; 
        $statusDisplay = 'Terkirim';
        $badgeColor = 'warning';

        if (!$isEksternal) {
            // --- A. CEK JALUR BIROKRASI (REKTOR/UNIV) ---
            $linkedSurat = \App\Models\Surat::where('nomor_surat', trim($surat->nomor_surat))
                            ->latest()
                            ->first();

            if ($linkedSurat) {
                $statusRemote = $linkedSurat->status;
                $isLocked = true; 

                if (stripos($statusRemote, 'Arsip') !== false || stripos($statusRemote, 'Selesai') !== false) {
                    $statusDisplay = 'Selesai (Diarsipkan)';
                    $badgeColor = 'success';
                } elseif ($statusRemote == 'di_satker' || stripos($statusRemote, 'Disposisi') !== false) {
                    $statusDisplay = 'Selesai (Didisposisi)';
                    $badgeColor = 'success';
                } elseif (stripos($statusRemote, 'di_admin_rektor') !== false) {
                    $statusDisplay = 'Di Admin Rektor';
                    $badgeColor = 'info';
                }
            } 
            
            // --- B. CEK JALUR ANTAR SATKER (PIVOT) ---
            if ($statusDisplay == 'Terkirim' || $statusDisplay == 'Di Admin Rektor') {
                $totalPenerima = $surat->penerimaInternal->count();
                
                if ($totalPenerima > 0) {
                    $jumlahSelesai = $surat->penerimaInternal->whereIn('pivot.is_read', [2, 3])->count();
                    $jumlahBaca = $surat->penerimaInternal->where('pivot.is_read', 1)->count();

                    if ($jumlahSelesai > 0) {
                        $isLocked = true;
                        if ($jumlahSelesai == $totalPenerima) {
                            $statusDisplay = 'Selesai';
                            $badgeColor = 'success';
                        } else {
                            $statusDisplay = 'Diterima Sebagian (' . $jumlahSelesai . '/' . $totalPenerima . ')';
                            $badgeColor = 'info';
                        }
                    } elseif ($jumlahBaca > 0) {
                        $isLocked = true;
                        $statusDisplay = 'Dibaca (' . $jumlahBaca . '/' . $totalPenerima . ')';
                        $badgeColor = 'primary';
                    }
                }
            }

            // --- C. PERBAIKAN: CEK JALUR PEGAWAI SPESIFIK (PERSONAL) ---
            if ($statusDisplay == 'Terkirim') {
                $logPersonal = $surat->riwayats->whereNotNull('penerima_id')->first();
                if ($logPersonal) {
                    if ($logPersonal->is_read == 2) {
                        $statusDisplay = 'Selesai (Diterima)';
                        $badgeColor = 'success';
                        $isLocked = true;
                    } elseif ($logPersonal->is_read == 1) {
                        $statusDisplay = 'Dibaca (Pegawai)';
                        $badgeColor = 'primary';
                        $isLocked = true;
                    }
                }
            }

            // --- D. FALLBACK STATUS LOKAL BAU ---
            if ($surat->status == 'Selesai di BAU' && $statusDisplay == 'Terkirim') {
                $isLocked = true;
                $statusDisplay = 'Selesai (Arsip BAU)';
                $badgeColor = 'success';
            }
        }
    @endphp

    <tr>
        <td class="text-center fw-bold">{{ $loop->iteration }}</td>
        <td class="fw-600 text-primary">{{ $surat->nomor_surat }}</td>
        <td>{{ Str::limit($surat->perihal, 50) }}</td>
        
        {{-- KOLOM TUJUAN --}}
        <td>
            <div class="d-flex flex-column gap-1">
                @php
                    $currentBauId = Auth::id();
                    $penerimaPersonalPusat = $surat->riwayats->whereNotNull('penerima_id')
                        ->filter(function($r) use ($currentBauId) {
                            return $r->user_id == $currentBauId || 
                                   in_array($r->pengirim->role ?? '', ['bau', 'admin_rektor', 'admin']);
                        })->first();
                @endphp

                @if($penerimaPersonalPusat && $penerimaPersonalPusat->penerima)
                    <span class="badge bg-light text-dark border text-start">
                        <i class="bi bi-person-fill me-1 text-warning"></i> {{ $penerimaPersonalPusat->penerima->name }}
                    </span>
                @elseif(!empty($surat->tujuan_surat))
                    {{-- Teks manual hanya muncul jika kolom tujuan_surat memang berisi (biasanya eksternal) --}}
                    <span class="badge bg-light text-dark border text-start">
                        <i class="bi bi-person-fill me-1 text-warning"></i> {{ Str::limit($surat->tujuan_surat, 25) }}
                    </span>
                @endif

                @if($surat->penerimaInternal->count() > 0)
                    @foreach($surat->penerimaInternal->take(2) as $penerima)
                        <span class="badge bg-light text-dark border text-start">
                            <i class="bi bi-building me-1 text-success"></i> {{ $penerima->nama_satker }}
                        </span>
                    @endforeach
                    @if($surat->penerimaInternal->count() > 2)
                        <span class="badge bg-secondary align-self-start">+{{ $surat->penerimaInternal->count() - 2 }} lainnya</span>
                    @endif
                @endif
            </div>
        </td>
        
        {{-- TANGGAL --}}
        <td>
            <div class="d-flex flex-column">
                <span class="fw-bold">{{ $surat->tanggal_surat->isoFormat('D MMM YY') }}</span>
                <small class="text-muted" style="font-size: 0.7rem">Input: {{ $surat->created_at->format('d/m/y') }}</small>
            </div>
        </td>

        {{-- KOLOM STATUS (KHUSUS INTERNAL) --}}
        @if(!$isEksternal)
            <td class="text-center">
                <span class="badge bg-{{ $badgeColor }} text-wrap shadow-sm" style="font-size: 0.75rem;">
                    {{ $statusDisplay }}
                </span>
            </td>
        @endif
        
       {{-- KOLOM AKSI --}}
<td class="text-center">
    <div class="d-flex justify-content-center gap-1">
        
        {{-- 1. TOMBOL LIHAT --}}
        <button type="button" class="btn btn-sm btn-info text-white" 
            data-bs-toggle="modal" 
            data-bs-target="#detailSuratModal"
            data-perihal="{{ $surat->perihal }}"
            data-nomor-surat="{{ $surat->nomor_surat }}"
            data-tujuan-surat="{{ $tujuanModalText }}"
            data-tanggal-surat="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->isoFormat('D MMMM Y') }}"
            data-tanggal-input="{{ $surat->created_at->isoFormat('D MMMM Y, HH:mm') }} WIB"
            data-file-url="{{ asset('storage/' . $surat->file_surat) }}">
            <i class="bi bi-eye"></i>
        </button>

        @if(!$isEksternal)
            {{-- 2. TOMBOL RIWAYAT --}}
            <button class="btn btn-sm btn-secondary btn-icon" 
                    title="Riwayat Surat"
                    data-bs-toggle="modal" 
                    data-bs-target="#riwayatModal" 
                    data-url="{{ route('bau.surat-keluar.riwayat', $surat->id) }}">
                <i class="bi bi-clock-history"></i>
            </button>
        @endif

        {{-- 3. TOMBOL EDIT (Hanya muncul jika BELUM diproses/locked) --}}
        @if(!$isLocked)
            <a href="{{ route('bau.surat-keluar.edit', $surat->id) }}" class="btn btn-sm btn-warning text-white" title="Edit">
                <i class="bi bi-pencil-fill"></i>
            </a>
        @endif

        {{-- 4. TOMBOL HAPUS (Selalu muncul, baik sudah selesai maupun belum) --}}
        <form action="{{ route('bau.surat-keluar.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Hapus surat ini?');" class="d-inline">
            @csrf @method('DELETE')
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

{{-- MODAL DETAIL FILE --}}
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

{{-- MODAL RIWAYAT (LOG AKTIVITAS) --}}
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
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
<script>
    $(document).ready(function () {
        // 1. Init DataTables
        new DataTable('#tabelSuratKeluar', {
            pagingType: 'simple_numbers',
            ordering: false, 
            dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>',
            columnDefs: [ { orderable: false, targets: -1 } ],
            language: {
                search: "Cari:", lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                zeroRecords: "Tidak ada data surat keluar yang ditemukan.",
                paginate: { first: "Awal", last: "Akhir", next: "Lanjut", previous: "Kembali" }
            }
        });

      // 2. Logic Modal Detail & Download
var detailSuratModal = document.getElementById('detailSuratModal');
detailSuratModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget; // Tombol yang diklik
    
    // Ambil data dari atribut data-
    var perihal = button.getAttribute('data-perihal');
    var nomor = button.getAttribute('data-nomor-surat');
    var tujuan = button.getAttribute('data-tujuan-surat');
    var tglSurat = button.getAttribute('data-tanggal-surat');
    var tglInput = button.getAttribute('data-tanggal-input');
    var fileUrl = button.getAttribute('data-file-url');

    // Isi konten modal
    detailSuratModal.querySelector('#modal-perihal').textContent = perihal;
    detailSuratModal.querySelector('#modal-nomor-surat').textContent = nomor;
    detailSuratModal.querySelector('#modal-tujuan-surat').textContent = tujuan || '-';
    detailSuratModal.querySelector('#modal-tanggal-surat').textContent = tglSurat;
    detailSuratModal.querySelector('#modal-tanggal-input').textContent = tglInput; // Pastikan ini terisi
    
    var btnDl = detailSuratModal.querySelector('#modal-download-button');
    var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0];
    
    btnDl.href = fileUrl;
    btnDl.setAttribute('download', 'Surat Keluar - ' + perihal + '.' + extension);

    // Preview Logic
    var fileHtml = '';
    if (extension == 'pdf') {
        // Tambahkan #toolbar=0 untuk tampilan lebih bersih jika perlu
        fileHtml = '<iframe src="' + fileUrl + '" width="100%" height="100%" frameborder="0" style="border-radius:8px; min-height:500px;"></iframe>';
    } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        fileHtml = '<img src="' + fileUrl + '" class="img-fluid rounded shadow-sm" style="max-height: 100%; object-fit: contain;">';
    } else {
        fileHtml = '<div class="text-center text-muted"><i class="bi bi-file-earmark-x h1 d-block"></i><p class="mt-2">Preview tidak tersedia untuk tipe file ini</p></div>';
    }
    
    detailSuratModal.querySelector('#modal-file-preview-wrapper').innerHTML = fileHtml;
});

        // 3. Logic Riwayat Modal (Log Aktivitas)
      // 3. Logic Riwayat Modal (Log Aktivitas)
var riwayatModal = document.getElementById('riwayatModal');
if (riwayatModal) {
    riwayatModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var dataUrl = button.getAttribute('data-url');
        var modalBody = riwayatModal.querySelector('#riwayatModalBody');
        var modalLabel = riwayatModal.querySelector('#riwayatModalLabel');

        modalBody.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Memuat data riwayat...</p>
            </div>
        `;

        fetch(dataUrl)
            .then(response => {
                if (!response.ok) { throw new Error('Network response was not ok'); }
                return response.json();
            })
            .then(surat => {
                modalLabel.textContent = 'Riwayat: ' + surat.nomor_surat;

                var html = '<ul class="timeline">';
                
                if (surat.riwayats && surat.riwayats.length > 0) {
                    surat.riwayats.forEach((item) => {
                        var badgeColor = 'primary'; 
                        var iconClass = 'bi-check-circle'; 
                        var status = item.status_aksi || '';

                        // Penyesuaian Ikon & Warna
  // Di dalam loop foreach riwayats pada JS:
if (status.includes('Selesai') || status.includes('Arsip')) {
    badgeColor = 'success'; iconClass = 'bi-archive-fill';
} else if (status.includes('Didelegasikan')) {
    badgeColor = 'info'; iconClass = 'bi-person-check-fill';
} else if (status.includes('Diterima oleh')) { // Log untuk Pegawai
    badgeColor = 'success'; iconClass = 'bi-person-badge-fill'; 
} else if (status.includes('Diterima Satker')) {
    badgeColor = 'primary'; iconClass = 'bi-building-check';
} else if (status.includes('Dikirim')) {
    badgeColor = 'info'; iconClass = 'bi-send-fill';
}

                        var dateStr = item.tanggal_f ? item.tanggal_f : (item.created_at ?? '-');
                        var userName = (item.user && item.user.name) ? item.user.name : 'Sistem';

                        html += `
                            <li>
                                <div class="timeline-badge ${badgeColor}"><i class="bi ${iconClass}"></i></div>
                                <div class="timeline-panel shadow-sm border-0">
                                    <div class="timeline-heading">
                                        <h6 class="timeline-title fw-bold">${status}</h6>
                                        <p class="mb-0"><small class="text-muted">
                                            <i class="bi bi-clock"></i> ${dateStr} &bull; Oleh: <strong>${userName}</strong>
                                        </small></p>
                                    </div>
                                    <div class="timeline-body mt-2">
                                        <p class="mb-0 text-dark small">${item.catatan ?? '-'}</p>
                                    </div>
                                </div>
                            </li>
                        `;
                    });
                } else {
                    html += '<li class="text-center text-muted p-3">Belum ada riwayat.</li>';
                }

                html += '</ul>';
                modalBody.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = '<div class="alert alert-danger">Gagal memuat data riwayat.</div>';
            });
    });
}
    });
</script>
@endpush