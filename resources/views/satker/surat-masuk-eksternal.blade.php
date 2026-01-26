@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS Penyesuaian */
    #tabelSuratUnified, .dataTables_wrapper, .modal-body { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    
    .info-modal-label { width: 140px; font-weight: 600; }
    .info-modal-data { word-break: break-word; }

    /* CSS Khusus Checkbox List Delegasi */
    .checklist-container {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0.5rem;
        background-color: #fff;
    }
    .form-check {
        margin-bottom: 0.25rem;
    }
    
    /* Tombol Aksi */
    .btn-group-xs > .btn, .btn-sm {
        padding: .25rem .4rem;
        font-size: .875rem;
        line-height: 1.5;
        border-radius: .2rem;
    }

.timeline { list-style: none; padding: 0; position: relative; }
.timeline:before { content: ''; position: absolute; top: 0; bottom: 0; left: 20px; width: 2px; background: #e9ecef; margin-left: -1.5px; }
.timeline > li { position: relative; margin-bottom: 20px; }
.timeline > li:last-child { margin-bottom: 0; }
.timeline-badge { width: 40px; height: 40px; font-size: 1.2em; text-align: center; position: absolute; top: 0; left: 0; background-color: #fff; z-index: 100; border-radius: 50%; border: 2px solid #e9ecef; display: flex; align-items: center; justify-content: center; }
.timeline-badge.success { border-color: #198754; color: #198754; }
.timeline-badge.warning { border-color: #ffc107; color: #ffc107; }
.timeline-badge.info { border-color: #0dcaf0; color: #0dcaf0; }
.timeline-badge.primary { border-color: #0d6efd; color: #0d6efd; }
.timeline-panel { margin-left: 60px; border: 1px solid #e9ecef; border-radius: 4px; padding: 15px; background: #fff; position: relative; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.timeline-panel:before { content: " "; display: inline-block; position: absolute; top: 13px; left: -8px; border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-right: 8px solid #e9ecef; }
.timeline-panel:after { content: " "; display: inline-block; position: absolute; top: 14px; left: -7px; border-top: 7px solid transparent; border-bottom: 7px solid transparent; border-right: 7px solid #fff; }
</style>

@endpush

@section('content')
<div class="container-fluid px-3">

    {{-- ALERT NOTIFIKASI --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- CARD UTAMA: FILTER & TABEL --}}
    <div class="card shadow-sm border-0 mb-4 mt-2">
        
        {{-- HEADER KARTU --}}
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-inbox-fill me-2"></i>Daftar Surat Masuk Eksternal</h6>
        </div>

        {{-- BAGIAN FILTER & TOMBOL --}}
        <div class="card-body bg-light border-bottom py-3">
            <form action="{{ route('satker.surat-masuk.eksternal') }}" method="GET">
                <div class="row g-2 align-items-end">
                    
                    {{-- Input Tanggal Awal --}}
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal (Surat)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                    </div>

                    {{-- Input Tanggal Akhir --}}
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar-fill"></i></span>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                    </div>

                    {{-- Tombol Filter & Reset --}}
                    <div class="col-md-auto">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm" title="Cari Data">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                            <a href="{{ route('satker.surat-masuk.eksternal') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </div>

                    {{-- AREA KANAN: EXPORT & INPUT MANUAL --}}
                    <div class="col-md ms-auto text-md-end mt-2 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            {{-- Tombol Export Excel --}}
                            <a href="{{ route('satker.surat-masuk.eksternal.export', request()->query()) }}" class="btn btn-success btn-sm shadow-sm">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                            </a>
                            
                            {{-- Tombol Input Manual --}}
                            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahSuratModal">
                                <i class="bi bi-plus-lg me-1"></i> Input Surat Manual
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        {{-- BAGIAN TABEL --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tabelSuratUnified" class="table table-hover align-middle table-sm w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center py-3">No</th>
                            <th scope="col">Asal Surat</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Tgl. Surat</th>
                            <th scope="col">Jalur Penerimaan</th>
                            <th scope="col">Status / Posisi</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                  <tbody>
 @php
    // Logika Penggabungan Data
    $allSurat = $suratMasukSatker->merge($suratEdaran)->unique('id')->sortByDesc('diterima_tanggal');
    $mySatkerId = Auth::user()->satker_id;
    $myUserId = Auth::id();
@endphp

@foreach ($allSurat as $index => $surat)
    @php
        $isEdaran = isset($surat->pivot);
        $isMyInput = ($surat->user_id == $myUserId);

        // Filter Tampilan Status
        $statusBolehDilihat = ['di_satker', 'arsip_satker', 'disimpan', 'selesai', 'selesai_edaran'];
        if (!$isEdaran && !in_array($surat->status, $statusBolehDilihat)) { continue; }

        // Logika Jalur & Disposisi
        $myDisposisi = $surat->disposisis->where('tujuan_satker_id', $mySatkerId)->first();
        $isDirectToMe = ($surat->tujuan_satker_id == $mySatkerId);
        
        $catatanRektor = $myDisposisi ? $myDisposisi->catatan_rektor : ($surat->disposisis->last()->catatan_rektor ?? '-');

        // --- BAGIAN DELEGASI ---
        // Ambil delegasi khusus yang dilakukan oleh Satker saya
        $myDelegations = $surat->delegasiPegawai->filter(function($pegawai) use ($mySatkerId) {
            return $pegawai->satker_id == $mySatkerId;
        });
        
        $delegatedCount = $myDelegations->count();
        $lastMyDelegation = $myDelegations->sortByDesc('pivot.created_at')->first();
        $catatanSatker = $lastMyDelegation ? ($lastMyDelegation->pivot->catatan ?? '-') : '-';

        // --- CEK APAKAH PERNAH DISEBAR KE SEMUA PEGAWAI ---
        // Kita cek di tabel riwayat_surats milik surat ini yang diinput oleh Admin Satker ini
        $riwayatSebarSemua = $surat->riwayats->where('user_id', $myUserId)
                                            ->filter(function($r) {
                                                return str_contains($r->status_aksi, 'Informasi Umum');
                                            })->first();

        // --- LOGIKA STATUS BADGE ---
        $isProcessed = false;
        $statusBadge = '';
        
        $isLocalDone = ($myDisposisi && $myDisposisi->status_penerimaan == 'selesai');
        $isGlobalDone = (!$myDisposisi && in_array($surat->status, ['arsip_satker', 'disimpan', 'selesai']));
        $isDelegated = ($delegatedCount > 0); 
        $isBroadcasted = ($riwayatSebarSemua != null); // Flag sebar semua

        if ($isLocalDone || $isGlobalDone || $isDelegated || $isBroadcasted) {
            $isProcessed = true;
            
            // 1. Cek jika Jalur Broadcast (Sebar Semua) - Prioritas Visual Utama
            if ($isBroadcasted) {
                $statusBadge = '<span class="badge bg-info text-dark shadow-sm"><i class="bi bi-megaphone-fill me-1"></i>Disebarkan ke Semua</span>';
            } 
            // 2. Cek jika Jalur Delegasi Personal
            elseif ($isDelegated) {
                $firstPegawaiName = $myDelegations->first()->name;
                $statusText = "Delegasi: " . $firstPegawaiName;
                
                if($delegatedCount > 1) {
                    $sisa = $delegatedCount - 1;
                    $statusBadge = '<span class="badge bg-primary shadow-sm"><i class="bi bi-people-fill me-1"></i>'.$statusText.' +'.$sisa.'</span>';
                } else {
                    $statusBadge = '<span class="badge bg-primary shadow-sm"><i class="bi bi-person-check-fill me-1"></i>'.$statusText.'</span>';
                }
            } 
            // 3. Jika Diarsipkan Manual (Tanpa Delegasi/Broadcast)
            else {
                $statusBadge = '<span class="badge bg-secondary shadow-sm">Selesai (Diarsipkan)</span>';
            }
        } elseif ($isEdaran && $surat->pivot->status == 'diteruskan_internal') {
            $isProcessed = true;
            $statusBadge = '<span class="badge bg-success shadow-sm">Disebarkan</span>';
        } else {
            $statusBadge = '<span class="badge bg-warning text-dark shadow-sm">Perlu Tindak Lanjut</span>';
        }
    @endphp
    
    <tr>
        <td class="text-center fw-bold">{{ $loop->iteration }}</td>

        {{-- PENGIRIM --}}
        <td>
            <span class="fw-bold">{{ $surat->surat_dari }}</span>
            <br><small class="text-muted">{{ $surat->nomor_surat }}</small>
            @if($isEdaran) <br><span class="badge bg-info text-dark" style="font-size: 0.65rem;">Edaran</span> @endif
        </td>

        {{-- PERIHAL --}}
        <td>{{ Str::limit($surat->perihal, 50) }}</td>

        {{-- TGL SURAT --}}
        <td>{{ $surat->tanggal_surat->format('d/m/Y') }}</td>
        
        {{-- JALUR PENERIMAAN --}}
        <td>
            @if ($myDisposisi)
                <span class="text-primary fw-bold" style="font-size: 0.85rem;">Disposisi Rektor</span>
            @elseif ($isMyInput)
                <span class="text-info fw-bold" style="font-size: 0.85rem;"><i class="bi bi-keyboard"></i> Input Manual</span>
            @elseif ($isDirectToMe)
                <span class="text-success fw-bold" style="font-size: 0.85rem;">Langsung dari BAU</span>
            @elseif ($isEdaran)
                <span class="text-muted" style="font-size: 0.85rem;">Edaran</span>
            @endif
        </td>

        {{-- STATUS --}}
<td class="text-center">{!! $statusBadge !!}</td>
    
{{-- AKSI --}}
<td class="text-center">
    <div class="d-flex justify-content-center gap-1">
        
        {{-- 1. TOMBOL LIHAT --}}
 @php
    // 1. Ambil Catatan Rektor Utama (Sesuai logika lembar disposisi Anda)
    // Pastikan variabel $surat->disposisis sudah di-load di Controller
    $disposisiRektorPertama = $surat->disposisis->first();
    $catatanRektorUtama = $disposisiRektorPertama->catatan_rektor ?? '-';

    // 2. Logika Delegasi Satker ke Pegawai (Multi-Pegawai)
    $allLogs = $surat->riwayats
        ->where('user_id', Auth::id())
        ->whereNotNull('penerima_id');

    // Filter hanya aksi Disposisi/Delegasi (Bukan Informasi Umum/Arsip)
    $filteredLogs = $allLogs->filter(function($log) {
        $aksi = strtolower($log->status_aksi);
        return (str_contains($aksi, 'disposisi') || str_contains($aksi, 'delegasi')) 
               && !str_contains($aksi, 'informasi umum');
    });

    $namaPegawai = $filteredLogs->map(function($log) {
        return $log->penerima->name ?? '-';
    })->unique()->join(', ');

    $lastLog = $filteredLogs->last();
@endphp

<button type="button" class="btn btn-sm btn-info text-white" 
    title="Lihat Detail"
    data-bs-toggle="modal" 
    data-bs-target="#detailSuratModal"
    data-perihal="{{ $surat->perihal }}"
    data-no-agenda="{{ $surat->no_agenda }}"
    data-nomor-surat="{{ $surat->nomor_surat }}"
    data-asal-surat="{{ $surat->surat_dari }}"
    data-tanggal-surat="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d/m/Y') }}"
    data-tanggal-diterima="{{ \Carbon\Carbon::parse($surat->diterima_tanggal)->format('d/m/Y') }}"
    data-file-url="{{ Storage::url($surat->file_surat) }}"
    
    {{-- SEKARANG SUDAH MENGAMBIL DARI RELASI DISPOSISI SEPERTI LEMBAR DISPOSISI --}}
    data-catatan-rektor="{{ $catatanRektorUtama }}"
    
    data-delegasi-user="{{ $namaPegawai }}"
    data-klasifikasi="{{ $lastLog ? $lastLog->status_aksi : '' }}"
    data-catatan-satker="{{ $lastLog ? $lastLog->catatan : '-' }}">
    <i class="bi bi-eye-fill"></i>
</button>

        {{-- 2. TOMBOL LOG --}}
        @if ($myDisposisi || $isMyInput)
            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" 
                    title="Riwayat Perjalanan Surat"
                    data-bs-toggle="modal" 
                    data-bs-target="#riwayatModal" 
                    data-url="{{ route('satker.surat.riwayat_disposisi', $surat->id) }}">
                <i class="bi bi-clock-history"></i>
            </button>
        @endif

        {{-- 3. JIKA INPUTAN SENDIRI (EDIT & HAPUS) --}}
        @if($surat->isMyInput)
            {{-- Edit muncul selama belum didisposisikan/disebar ke pegawai --}}
            @if(!$surat->isProcessed)
                <button class="btn btn-sm btn-warning text-white" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editSuratModal{{ $surat->id }}"
                        title="Edit Data">
                    <i class="bi bi-pencil-fill"></i>
                </button>
            @endif

            <form action="{{ route('satker.surat.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus surat manual ini?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </form>
        @endif

     {{-- 4. ACTION LANJUTAN (Delegasi / Arsipkan) --}}
@php
    // Definisikan ulang status "Sudah Dikerjakan" secara lokal di baris ini
    // 1. Cek apakah sudah diarsipkan (Manual/Sistem)
    $isAlreadyArchived = ($surat->status == 'arsip_satker' || ($myDisposisi && $myDisposisi->status_penerimaan == 'selesai'));
    
    // 2. Cek apakah sudah didistribusikan (Delegasi Pegawai atau Sebar Semua)
    // $isDelegated dan $isBroadcasted diambil dari hasil hitung di atas @foreach
    $hasBeenDistributed = ($isDelegated || $isBroadcasted || $surat->isProcessed);
@endphp

{{-- Tombol muncul HANYA JIKA: 
     BELUM didistribusikan DAN BELUM diarsipkan
--}}
@if(!$hasBeenDistributed && !$isAlreadyArchived)
    <button type="button" class="btn btn-primary btn-sm shadow-sm" 
        data-bs-toggle="modal" 
        data-bs-target="#delegasiModal" 
        data-id="{{ $surat->id }}" 
        data-perihal="{{ $surat->perihal }}" 
        data-tabel="{{ $isMyInput ? 'surat' : 'surat_keluar' }}"> 
        <i class="bi bi-share-fill"></i>
    </button>
    
    @if($isEdaran)
        <form action="{{ route('satker.surat.broadcastInternal', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sebarkan ke semua pegawai?');">
            @csrf <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Sebarkan"><i class="bi bi-people-fill"></i></button>
        </form>
    @else
        <form action="{{ route('satker.surat.arsipkan', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Selesaikan/Arsipkan surat ini?');">
            @csrf <button type="submit" class="btn btn-sm btn-secondary shadow-sm" title="Selesai/Arsipkan"><i class="bi bi-clipboard-check-fill"></i></button>
        </form>
    @endif
@endif
        
       {{-- 5. TOMBOL CETAK (Khusus Surat Sistem / Hasil Disposisi Rektor) --}}
{{-- 
    Syarat Muncul: 
    1. Bukan Input Manual ($isMyInput = false)
    2. ATAU Memiliki data disposisi dari pusat ($myDisposisi = true)
--}}
@if($myDisposisi || (!$isMyInput && !$isEdaran))
    <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" 
       class="btn btn-sm btn-outline-danger shadow-sm" 
       title="Cetak Lembar Disposisi">
        <i class="bi bi-printer-fill"></i>
    </a>
@endif
    </div>
</td>
        </tr>

     {{-- MODAL EDIT (Dalam Loop agar ID Unik) --}}
@if($isMyInput)
<div class="modal fade" id="editSuratModal{{ $surat->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('satker.surat.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header bg-warning text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Surat Masuk Eksternal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4 pb-5" style="max-height: 75vh; overflow-y: auto;">
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i> Data Identitas Surat
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_surat" class="form-control shadow-sm" value="{{ $surat->nomor_surat }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Asal Surat (Instansi) <span class="text-danger">*</span></label>
                            <input type="text" name="surat_dari" class="form-control shadow-sm" value="{{ $surat->surat_dari }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Perihal <span class="text-danger">*</span></label>
                        <textarea name="perihal" class="form-control shadow-sm" rows="2" required>{{ $surat->perihal }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Surat <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_surat" class="form-control shadow-sm" value="{{ $surat->tanggal_surat->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Diterima</label>
                            <input type="date" name="diterima_tanggal" class="form-control shadow-sm bg-light" value="{{ $surat->diterima_tanggal->format('Y-m-d') }}" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Update File Surat</label>
                            <input type="file" name="file_surat" class="form-control shadow-sm" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="form-text small mt-1">Kosongkan jika tidak ingin mengubah file.</div>
                        </div>
                    </div>

                    {{-- Info File Saat Ini --}}
                    <div class="bg-light p-2 rounded border mt-2">
                        <small class="fw-bold d-block mb-1 text-muted">File Terpilih:</small>
                        <a href="{{ Storage::url($surat->file_surat) }}" target="_blank" class="btn btn-sm btn-outline-info w-100 py-1">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Lihat File Saat Ini
                        </a>
                    </div>
                    
                    <div style="height: 20px;"></div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold text-white shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

    @endforeach
</tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL RIWAYAT --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="riwayatModalLabel">Riwayat Perjalanan Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="riwayatModalBody" style="max-height: 70vh; overflow-y: auto;">
                {{-- Konten Loading default --}}
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH SURAT MANUAL --}}
{{-- MODAL TAMBAH SURAT MASUK EKSTERNAL MANUAL (KHUSUS SATKER) --}}
<div class="modal fade" id="tambahSuratModal" tabindex="-1" aria-hidden="true">
    {{-- Tambahkan modal-dialog-scrollable --}}
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('satker.surat.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                {{-- Hidden input untuk tipe eksternal --}}
                <input type="hidden" name="tipe_surat" value="eksternal">

                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Input Surat Masuk Eksternal (Manual)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                {{-- Body dengan scroll --}}
                <div class="modal-body p-4 pb-5" style="max-height: 75vh; overflow-y: auto;">
                    
                    {{-- SECTION 1: DATA UTAMA --}}
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-file-text me-1"></i> Identitas Surat
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_surat" class="form-control shadow-sm" placeholder="Contoh: 001/A/2024" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Asal Surat (Instansi Luar) <span class="text-danger">*</span></label>
                            <input type="text" name="surat_dari" class="form-control shadow-sm" placeholder="Contoh: Bank Jatim / PT. Maju Jaya" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Perihal <span class="text-danger">*</span></label>
                        <textarea name="perihal" class="form-control shadow-sm" rows="2" placeholder="Isi perihal surat..." required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Diterima</label>
                            <input type="date" name="diterima_tanggal" class="form-control shadow-sm bg-light" value="{{ date('Y-m-d') }}" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Upload File (PDF/JPG) <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" class="form-control shadow-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>

                    {{-- SECTION 2: TINDAK LANJUT / DISPOSISI --}}
                    <h6 class="fw-bold text-success border-bottom pb-2 mt-4 mb-3">
                        <i class="bi bi-send-check me-1"></i> Tindak Lanjuti Satker
                    </h6>
                    
                    <div class="bg-light p-3 rounded-3 border">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-success">Pilih Alur Surat:</label>
                            <select name="target_tipe" id="target_tipe_eksternal" class="form-select shadow-sm border-success" onchange="toggleAlurEksternal()">
                                <option value="arsip">Simpan & Arsipkan Saja (Inbox Admin)</option>
                                <option value="pribadi">Disposisi ke Pegawai Tertentu (Pribadi)</option>
                                <option value="semua">Informasi Umum (Sebar ke Semua Pegawai)</option>
                            </select>
                        </div>

                        {{-- Area Pilih Pegawai --}}
                        <div id="group_pegawai_eksternal" style="display: none;" class="mb-3">
                            <label class="form-label small fw-bold">Pilih Pegawai Penerima Disposisi:</label>
                            <div class="p-2 bg-white rounded border shadow-sm" style="max-height: 180px; overflow-y: auto;">
                                @forelse ($daftarPegawai as $pegawai)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="delegasi_user_ids[]" value="{{ $pegawai->id }}" id="eks_pegawai_{{ $pegawai->id }}">
                                        <label class="form-check-label small" for="eks_pegawai_{{ $pegawai->id }}">
                                            {{ $pegawai->name }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted text-center small my-1">Tidak ada pegawai terdaftar.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Area Catatan --}}
                        <div id="group_catatan_eksternal" style="display: none;">
                            <label class="form-label small fw-bold">Instruksi / Catatan Tambahan:</label>
                            <textarea name="catatan_delegasi" id="catatan_eksternal" class="form-control shadow-sm" rows="2" placeholder="Tulis instruksi atau keterangan..."></textarea>
                        </div>
                    </div>
                    
                    <div style="height: 30px;"></div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-all me-1"></i> Simpan & Proses
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL SURAT --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Surat Masuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <h4 class="mb-3 fw-bold" id="modal-perihal"></h4>
                        <table class="table table-borderless table-sm small">
                           <tr><td class="info-modal-label">No. Agenda</td><td class="info-modal-data">: <span id="modal-no-agenda"></span></td></tr>
                           <tr><td class="info-modal-label">Nomor Surat</td><td class="info-modal-data">: <span id="modal-nomor-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Asal Surat</td><td class="info-modal-data">: <span id="modal-asal-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Tanggal Surat</td><td class="info-modal-data">: <span id="modal-tanggal-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Tanggal Diterima</td><td class="info-modal-data">: <span id="modal-tanggal-diterima"></span></td></tr>
                           <tr class="border-top">
                               <td class="info-modal-label pt-2">Catatan Rektor</td>
                               <td class="info-modal-data pt-2">: <span id="modal-catatan-rektor" class="fst-italic text-muted"></span></td>
                           </tr>
                           {{-- Tambahkan di dalam table table-borderless di Modal Detail --}}
<tr id="row-delegasi-user" style="display: none;">
    <td class="info-modal-label text-primary">Didelegasikan Ke</td>
    <td class="info-modal-data">: <span id="modal-delegasi-user" class="fw-bold"></span></td>
</tr>
<tr id="row-klasifikasi" style="display: none;">
    <td class="info-modal-label text-primary">Klasifikasi</td>
    <td class="info-modal-data">: <span id="modal-klasifikasi" class="badge bg-primary"></span></td>
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


{{-- MODAL DELEGASI --}}
{{-- MODAL DELEGASI --}}
<div class="modal fade" id="delegasiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form id="formDelegasi" action="" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-fill-add me-2"></i>Delegasikan Surat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 p-2 bg-light rounded border-start border-4 border-primary">
                        <label class="form-label fw-bold mb-0 small text-muted">Perihal Surat:</label>
                        <p id="delegasi-perihal" class="fw-bold mb-0 text-dark"></p>
                    </div>

                    {{-- Target Tipe --}}
                    <div class="mb-3">
                        <label class="form-label d-block fw-bold small text-uppercase text-muted">Target Delegasi</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="target_tipe" id="target_pribadi" value="pribadi" checked>
                            <label class="form-check-label" for="target_pribadi">Pilih Pegawai (Disposisi)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="target_tipe" id="target_semua" value="semua">
                            <label class="form-check-label" for="target_semua">Semua Pegawai (Informasi)</label>
                        </div>
                    </div>

                    {{-- List Pegawai --}}
                    <div class="mb-3" id="wrapper_pilih_pegawai">
                        <label class="form-label fw-bold small text-uppercase text-muted">Pilih Pegawai <span class="text-danger">*</span></label>
                        <div class="checklist-container border p-2 rounded bg-white" style="max-height: 180px; overflow-y: auto;">
                            @if($daftarPegawai->isEmpty())
                                <p class="text-muted text-center small my-2">Tidak ada pegawai tersedia.</p>
                            @else
                                @foreach ($daftarPegawai as $pegawai)
                                    <div class="form-check border-bottom py-1">
                                        <input class="form-check-input checkbox-pegawai" type="checkbox" name="tujuan_user_ids[]" value="{{ $pegawai->id }}" id="pegawai_{{ $pegawai->id }}">
                                        <label class="form-check-label w-100" for="pegawai_{{ $pegawai->id }}">{{ $pegawai->name }}</label>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Area Instruksi (Hanya muncul jika Pribadi) --}}
                    <div id="wrapper_instruksi">
                        {{-- Klasifikasi Delegasi --}}
                        <div class="mb-3">
                            <label for="klasifikasi" class="form-label fw-bold small text-uppercase text-muted">Klasifikasi / Instruksi Disposisi</label>
                            <select class="form-select" name="klasifikasi" id="klasifikasi">
                                <option value="Segera Tindak Lanjuti">Segera Tindak Lanjuti</option>
                                <option value="Untuk Diketahui / Arsip">Untuk Diketahui / Arsip</option>
                                <option value="Pelajari & Laporkan">Pelajari & Laporkan</option>
                                <option value="Siapkan Bahan / Draft">Siapkan Bahan / Draft</option>
                                <option value="Hadir / Wakili">Hadir / Wakili</option>
                                <option value="Koordinasikan">Koordinasikan</option>
                            </select>
                        </div>

                        {{-- Catatan Manual --}}
                        <div class="mb-3">
                            <label for="catatan_satker" class="form-label fw-bold small text-uppercase text-muted">Catatan Tambahan Instruksi</label>
                            <textarea class="form-control" id="catatan_satker" name="catatan_satker" rows="3" placeholder="Contoh: Tolong siapkan laporannya sebelum hari jumat..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btn-submit-delegasi">
                        <i class="bi bi-send-fill me-2"></i>Kirim Delegasi
                    </button>
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
        // Init DataTable
        new DataTable('#tabelSuratUnified', {
            pagingType: 'simple_numbers',
            ordering: false, // Matikan sorting default agar logika sort controller terjaga
            language: {
                search: "Cari:", lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                paginate: { next: "Next", previous: "Prev" }
            }
        });

        // Script Modal Detail
       var detailModal = document.getElementById('detailSuratModal');
if (detailModal) {
    detailModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        // Data Dasar
        detailModal.querySelector('#modal-perihal').textContent = button.getAttribute('data-perihal');
        detailModal.querySelector('#modal-no-agenda').textContent = button.getAttribute('data-no-agenda') || '-';
        detailModal.querySelector('#modal-nomor-surat').textContent = button.getAttribute('data-nomor-surat');
        detailModal.querySelector('#modal-asal-surat').textContent = button.getAttribute('data-asal-surat');
        detailModal.querySelector('#modal-tanggal-surat').textContent = button.getAttribute('data-tanggal-surat');
        detailModal.querySelector('#modal-tanggal-diterima').textContent = button.getAttribute('data-tanggal-diterima');

        // LOGIKA DELEGASI SPESIFIK
        var delegasiUser = button.getAttribute('data-delegasi-user');
        var klasifikasi = button.getAttribute('data-klasifikasi');
        var catatanSatker = button.getAttribute('data-catatan-satker');
        
        var rowUser = detailModal.querySelector('#row-delegasi-user');
        var rowKlas = detailModal.querySelector('#row-klasifikasi');

        if (delegasiUser && delegasiUser.trim() !== "") {
            // Tampilkan baris jika ada data delegasi
            rowUser.style.display = 'table-row';
            rowKlas.style.display = 'table-row';
            
            detailModal.querySelector('#modal-delegasi-user').textContent = delegasiUser;
            
            // Hilangkan kata "Disposisi: " jika ada agar lebih bersih
            var cleanKlasifikasi = klasifikasi ? klasifikasi.replace('Disposisi: ', '') : '-';
            detailModal.querySelector('#modal-klasifikasi').textContent = cleanKlasifikasi;
            
            // Isi Instruksi/Catatan Satker
            detailModal.querySelector('#modal-catatan-satker').textContent = (catatanSatker && catatanSatker !== '-') ? catatanSatker : 'Tidak ada instruksi khusus.';
        } else {
            // Sembunyikan jika tidak didelegasikan (Misal: hanya diarsipkan/disebar semua)
            rowUser.style.display = 'none';
            rowKlas.style.display = 'none';
            detailModal.querySelector('#modal-catatan-satker').textContent = 'Surat tidak didelegasikan ke pegawai spesifik.';
        }

        // Catatan Rektor
        var catatanRektor = button.getAttribute('data-catatan-rektor');
        detailModal.querySelector('#modal-catatan-rektor').textContent = (catatanRektor && catatanRektor !== '-' && catatanRektor !== 'null') ? catatanRektor : '(Tidak ada)';

        // Logika File Preview
        var fileUrl = button.getAttribute('data-file-url');
        detailModal.querySelector('#modal-download-button').href = fileUrl;
        
        var wrapper = detailModal.querySelector('#modal-file-preview-wrapper');
        wrapper.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';

        if(fileUrl && fileUrl.length > 5) {
            var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0]; 
            setTimeout(function() {
                if(ext === 'pdf'){
                    wrapper.innerHTML = '<iframe src="'+fileUrl+'#toolbar=0" width="100%" height="100%" frameborder="0"></iframe>';
                } else if(['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                    wrapper.innerHTML = '<img src="'+fileUrl+'" class="img-fluid" style="max-height: 100%; width: 100%; object-fit: contain;">';
                } else {
                    wrapper.innerHTML = '<div class="text-center p-5"><i class="bi bi-file-earmark-x fs-1"></i><p class="mt-3">Preview tidak didukung untuk tipe file ini.</p></div>';
                }
            }, 300);
        } else {
             wrapper.innerHTML = '<div class="text-center p-5"><p class="mt-3">File tidak tersedia.</p></div>';
        }
    });
}

       // Script Modal Delegasi
var delegasiModal = document.getElementById('delegasiModal');
if (delegasiModal) {
    var radioPribadi = document.getElementById('target_pribadi');
    var radioSemua = document.getElementById('target_semua');
    var wrapperPegawai = document.getElementById('wrapper_pilih_pegawai');
    var wrapperInstruksi = document.getElementById('wrapper_instruksi');

    delegasiModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var perihal = button.getAttribute('data-perihal');
        
        // Update Tampilan Modal
        delegasiModal.querySelector('#delegasi-perihal').textContent = perihal;
        
        // Reset Form
        delegasiModal.querySelector('#formDelegasi').reset();
        
        // Reset Visibility ke Default (Pribadi)
        wrapperPegawai.style.display = 'block';
        wrapperInstruksi.style.display = 'block';

        // Set URL Form Action (Bisa mendukung rute berbeda jika perlu)
     var form = delegasiModal.querySelector('#formDelegasi');

// Opsi 1: Menggunakan template literal JS (Pastikan id surat benar)
form.action = "/satker/surat-masuk-eksternal/" + id + "/delegasi";
    });

    // Toggle Visibility Berdasarkan Target
    function toggleTargetFields() {
        if (radioPribadi.checked) {
            wrapperPegawai.style.display = 'block';
            wrapperInstruksi.style.display = 'block';
        } else {
            wrapperPegawai.style.display = 'none';
            wrapperInstruksi.style.display = 'none';
        }
    }

    if (radioPribadi && radioSemua) {
        radioPribadi.addEventListener('change', toggleTargetFields);
        radioSemua.addEventListener('change', toggleTargetFields);
    }

    // Validasi Sederhana sebelum submit
    document.getElementById('formDelegasi').addEventListener('submit', function(e) {
        if (radioPribadi.checked) {
            var checked = document.querySelectorAll('.checkbox-pegawai:checked').length;
            if (checked === 0) {
                e.preventDefault();
                alert('Pilih minimal satu pegawai untuk didelegasikan!');
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

                var dataUrl = button.getAttribute('data-url'); // URL dari tombol
                var modalBody = riwayatModal.querySelector('#riwayatModalBody');
                var modalLabel = riwayatModal.querySelector('#riwayatModalLabel');

                // 1. Reset & Tampilkan Loading
                modalLabel.textContent = 'Riwayat Perjalanan Surat';
                modalBody.innerHTML = `
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Sedang mengambil data perjalanan surat...</p>
                    </div>`;
                
                // 2. Fetch Data dari Controller
                fetch(dataUrl)
                    .then(response => {
                        if (!response.ok) { throw new Error('Gagal mengambil data'); }
                        return response.json();
                    })
                    .then(data => {
                        // 3. Render Data
                        if (data.status === 'error') {
                            throw new Error(data.message);
                        }

                        modalLabel.textContent = 'Riwayat: ' + (data.perihal || 'Tanpa Perihal');
                        var html = '<ul class="timeline">'; // Pastikan CSS timeline ada
                        
                       // Di dalam .then(data => { ... }) pada loop render riwayat
if (data.riwayats && data.riwayats.length > 0) {
    // Filter untuk membuang duplikasi status "Informasi Umum" jika masih terbawa
    data.riwayats.filter(item => {
        const status = item.status_aksi.toLowerCase();
        return !status.includes('informasi umum'); 
    }).forEach((item) => {
        var badge = 'primary'; 
        var icon = 'bi-circle-fill';
        var status = item.status_aksi || '';

        // Styling badge tetap sama sesuai logika Anda
        if (status.includes('Selesai') || status.includes('Arsip')) {
            badge = 'success'; icon = 'bi-check-lg';
        } else if (status.includes('Disposisi') || status.includes('Delegasi') || status.includes('Disebar')) {
            badge = 'warning'; icon = 'bi-arrow-return-right';
        } else if (status.includes('Masuk') || status.includes('Input') || status.includes('Dikirim')) {
            badge = 'info'; icon = 'bi-envelope';
        }

        html += `
            <li class="mb-3 position-relative ps-4">
                <div class="position-absolute start-0 top-0 mt-1">
                    <span class="badge rounded-circle bg-${badge} p-2 border border-light">
                        <i class="bi ${icon}"></i>
                    </span>
                </div>
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="fw-bold mb-0 text-${badge}">${status}</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">${item.tanggal_f}</small>
                        </div>
                        <p class="mb-1 small text-dark">${item.catatan || '-'}</p>
                        <small class="text-muted fst-italic" style="font-size: 0.7rem;">
                            <i class="bi bi-person"></i> ${item.user_name}
                        </small>
                    </div>
                </div>
            </li>`;
    });
} else {
                            html += '<li class="text-center p-3 text-muted">Belum ada riwayat tercatat.</li>';
                        }
                        
                        html += '</ul>';
                        modalBody.innerHTML = html;
                    })
                    .catch(err => {
                        console.error("Error:", err);
                        modalBody.innerHTML = `
                            <div class="alert alert-danger text-center m-3">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Gagal Memuat Data</strong><br>
                                <small>${err.message}</small>
                            </div>`;
                    });
            });
        }
    });
</script>

<!-- modal tambah -->
<script>
function toggleAlurEksternal() {
    const tipe = document.getElementById('target_tipe_eksternal').value;
    const gPeg = document.getElementById('group_pegawai_eksternal');
    const gCat = document.getElementById('group_catatan_eksternal');

    if (tipe === 'arsip') {
        $(gPeg).slideUp();
        $(gCat).slideUp();
    } else if (tipe === 'semua') {
        $(gPeg).slideUp();
        $(gCat).slideDown();
    } else {
        $(gPeg).slideDown();
        $(gCat).slideDown();
    }
}

// Reset alur saat modal dibuka
$('#tambahSuratModal').on('shown.bs.modal', function () {
    toggleAlurEksternal();
});
</script>
@endpush