
@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
<style>
    #tabelSuratMasuk, .dataTables_wrapper, .modal-body { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .table td, .table th { vertical-align: middle; }
    .btn-icon { width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
    
    /* Style Checkbox Delegasi */
    .checklist-container {
        max-height: 150px; 
        overflow-y: auto; 
        border: 1px solid #ced4da; 
        padding: 10px; 
        border-radius: 5px;
        background: #f8f9fa;
    }


    {{-- CSS Timeline Sederhana (Inline agar tidak perlu file CSS terpisah) --}}

    ul.timeline {
        list-style-type: none;
        padding: 0;
        position: relative;
        margin-bottom: 0;
    }
    /* Garis vertikal timeline */
    ul.timeline:before {
        content: ' ';
        background: #dee2e6;
        display: inline-block;
        position: absolute;
        left: 15px; /* Sesuaikan dengan posisi badge */
        width: 2px;
        height: 100%;
        z-index: 0;
        top: 10px;
    }
    ul.timeline > li {
        z-index: 2;
        position: relative;
    }
    /* Menghilangkan garis pada item terakhir agar tidak 'bocor' ke bawah */
    ul.timeline > li:last-child {
        margin-bottom: 0 !important;
    }


</style>
@endpush

@section('content')
<div class="container-fluid px-3">

    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-exclamation-circle-fill me-2"></i> Gagal menyimpan. Mohon periksa form input kembali.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- CARD UTAMA --}}
    <div class="card shadow-sm border-0 mb-4 mt-2">
        
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-inbox me-2"></i>Daftar Surat Masuk (Internal)</h6>
        </div>

        {{-- FILTER & EXPORT --}}
        <div class="card-body bg-light border-bottom py-3">
            <form action="{{ route('satker.surat-masuk.internal') }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Cari</button>
                        <a href="{{ route('satker.surat-masuk.internal') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                    </div>
                    <div class="col-md ms-auto text-md-end mt-2 mt-md-0">
                        <a href="{{ route('satker.surat-masuk.internal.export', request()->query()) }}" class="btn btn-success btn-sm shadow-sm">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                        </a>
                        {{-- TOMBOL TAMBAH MANUAL --}}
                        <button type="button" class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahSuratModal">
                            <i class="bi bi-plus-lg me-1"></i> Input Manual
                        </button>
                    </div>
                    
                </div>
            </form>
        </div>

        {{-- TABEL DATA --}}
        <div class="card-body p-0">
            <div class="table-responsive">
          <table id="tabelSuratMasuk" class="table table-hover align-middle table-sm w-100 mb-0">
    <thead class="table-light">
        <tr>
            <th class="text-center py-3" width="5%">No</th>
            <th width="20%">Pengirim (Satker)</th>
            <th width="30%">No. Surat & Perihal</th>
            <th width="10%">Tgl Surat</th>
            <th width="10%">Tgl Terima</th>
            <th width="10%" class="text-center">Status</th>
            <th class="text-center" width="15%">Aksi</th>
        </tr>
    </thead>
    <tbody>
 @foreach($suratMasuk as $surat)
@php
    // --- 1. SETUP DATA AWAL & DETEKSI SUMBER ---
    $isManual = ($surat instanceof \App\Models\Surat);
    $isMyInput = ($surat->user_id == Auth::id() && $isManual);

    // Variabel Pendukung Nama Pengirim
    if ($isManual) {
        $namaSatker = $surat->surat_dari; 
        $penginput = 'Saya (Manual)';
    } else {
        if($surat->user && ($surat->user->role == 'admin_rektor' || $surat->user->role == 'bau')){
            $namaSatker = 'Rektorat / Rektor';
        } else {
            $namaSatker = $surat->satker->nama_satker ?? ($surat->user->satker->nama_satker ?? 'Internal');
        }
        $penginput = $surat->user->name ?? '-';
    }

    // --- 3. LOGIKA STATUS PENERIMAAN (PIVOT) ---
    $statusPivot = 0;
    if (!$isManual) {
        $myPivot = $surat->penerimaInternal->where('id', Auth::user()->satker_id)->first();
        if ($myPivot) {
            $statusPivot = $myPivot->pivot->is_read;
        }
    }

    // --- 4. LOGIKA DELEGASI (DARI TABEL RIWAYAT) ---
    $logDelegasi = $surat->riwayats->where('user_id', Auth::id());
    
    $isSebarSemua = $logDelegasi->contains(function($value) {
        return str_contains($value->status_aksi, 'Informasi');
    });

    $isDisposisiPribadi = $logDelegasi->contains(function($value) {
        return str_contains($value->status_aksi, 'Disposisi');
    });

    $delegasiTerakhir = $logDelegasi->sortByDesc('created_at')->first();

    // --- 5. STATUS FINAL (LOGIKA PERBAIKAN) ---
    // Jika surat manual, gunakan status 'selesai' dari tabel surats.
    // Jika surat sistem (Rektor/Satker), ABAIKAN $surat->status agar tidak terkunci oleh pusat.
    if ($isManual) {
        $isSelesaiProses = ($statusPivot == 2 || $isSebarSemua || $isDisposisiPribadi || $surat->status == 'selesai');
    } else {
        $isSelesaiProses = ($statusPivot == 2 || $isSebarSemua || $isDisposisiPribadi);
    }
@endphp

<tr>
    <td class="text-center fw-bold">{{ $loop->iteration }}</td>
    <td>
        <!-- <span class="fw-bold text-dark">{{ $namaSatker }}</span><br> -->
        <small class="text-muted"><i class="bi bi-person-circle"></i> {{ $penginput }}</small>
    </td>
    <td>
        <div class="fw-bold text-dark">{{ $surat->nomor_surat }}</div>
        <div class="text-muted small text-truncate" style="max-width: 250px;">{{ $surat->perihal }}</div>
    </td>
    <td>{{ $surat->tanggal_surat->format('d/m/Y') }}</td>
    <td>{{ $surat->created_at->format('d/m/Y H:i') }}</td>

    {{-- KOLOM STATUS --}}
    <td class="text-center">
        @if($isSebarSemua)
            <span class="badge bg-info text-dark shadow-sm">
                <i class="bi bi-megaphone-fill me-1"></i> Selesai (Sebar Semua)
            </span>
        @elseif($isDisposisiPribadi)
            <div class="d-flex flex-column align-items-center">
                <span class="badge bg-primary shadow-sm">
                    <i class="bi bi-person-check-fill me-1"></i> Selesai (Disposisi)
                </span>
                @if($delegasiTerakhir && $logDelegasi->count() == 1)
                    <small class="text-muted mt-1" style="font-size: 0.65rem; font-weight: 600;">
                        Ke: {{ Str::limit($delegasiTerakhir->penerima->name ?? 'Pegawai', 12) }}
                    </small>
                @elseif($logDelegasi->count() > 1)
                    <small class="text-muted mt-1" style="font-size: 0.65rem; font-weight: 600;">
                        Ke: {{ $logDelegasi->count() }} Pegawai
                    </small>
                @endif
            </div>
        @elseif($statusPivot == 2)
            <span class="badge bg-success shadow-sm">
                <i class="bi bi-archive-fill me-1"></i> Diarsipkan
            </span>
        @elseif($statusPivot == 1)
            <span class="badge bg-secondary">Dibaca</span>
        @elseif($isManual)
            <span class="badge bg-info text-dark">Diarsipkan (Manual)</span>
        @else
            <span class="badge bg-warning text-dark"><i class="bi bi-envelope me-1"></i> Baru Masuk</span>
        @endif
    </td>

    {{-- KOLOM AKSI (SATKER INTERNAL) ini yang ada btn cetak disposisi satker --}}
<!-- <td class="text-center">
    <div class="d-flex justify-content-center gap-1">
        
        @php
            // 1. Ambil semua riwayat aksi yang dilakukan oleh user (Admin Satker) ini
            $myRiwayats = $surat->riwayats->where('user_id', Auth::id());

            // 2. Cek apakah sudah diproses (Disposisi atau Informasi)
            $isAlreadyProcessed = $myRiwayats->filter(function($r) {
                $aksi = strtolower($r->status_aksi);
                return str_contains($aksi, 'disposisi') || str_contains($aksi, 'informasi');
            })->isNotEmpty();

            // 3. LOGIKA KHUSUS CETAK: Cek apakah ada riwayat yang bertipe "Disposisi" 
            // DAN memiliki penerima_id (artinya didelegasikan ke pegawai tertentu)
            $isDelegatedToStaff = $myRiwayats->filter(function($r) {
                return str_contains(strtolower($r->status_aksi), 'disposisi') && !empty($r->penerima_id);
            })->isNotEmpty();

            // 4. Data log untuk preview modal
            $logDisposisi = $myRiwayats->filter(function($r) {
                return str_contains(strtolower($r->status_aksi), 'disposisi');
            });

            // 5. Cek riwayat dari Rektor
            $hasRektorDisposisi = $surat->riwayats->filter(function($r) {
                return $r->pengirim && $r->pengirim->role == 'admin_rektor';
            })->isNotEmpty();
        @endphp

        {{-- 1. TOMBOL SHOW --}}
        <button type="button" class="btn btn-info btn-sm text-white shadow-sm" 
            data-bs-toggle="modal" data-bs-target="#filePreviewModal" 
            data-title="{{ $surat->perihal }}"
            data-file-url="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '' }}"
            data-delegasi-info="{{ $logDisposisi->pluck('penerima.name')->unique()->join(', ') }}"
            data-instruksi="{{ $logDisposisi->last() ? $logDisposisi->last()->status_aksi : '' }}"
            data-catatan="{{ $logDisposisi->last() ? $logDisposisi->last()->catatan : '' }}"
            title="Lihat Detail">
            <i class="bi bi-eye-fill"></i>
        </button>

        {{-- 2. TOMBOL LOG RIWAYAT --}}
        <button type="button" class="btn btn-secondary btn-sm shadow-sm" 
            data-bs-toggle="modal" data-bs-target="#riwayatModal" 
            data-url="{{ route('satker.surat-masuk.internal.riwayat', $surat->id) }}"
            title="Riwayat Surat">
            <i class="bi bi-clock-history"></i>
        </button>

        {{-- 3. TOMBOL EDIT --}}
        @if($surat->is_manual && $surat->user_id == Auth::id() && !$isAlreadyProcessed)
            <button type="button" class="btn btn-warning btn-sm text-white shadow-sm btn-edit-manual"
                data-bs-toggle="modal" data-bs-target="#editSuratModal"
                data-id="{{ $surat->id }}"
                data-nomor="{{ $surat->nomor_surat }}"
                data-perihal="{{ $surat->perihal }}"
                data-tanggal="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('Y-m-d') }}"
                data-satker="{{ $surat->surat_dari }}"
                data-file-url="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '' }}"
                title="Edit Surat">
                <i class="bi bi-pencil-fill"></i>
            </button>
        @endif

        {{-- 4. TOMBOL HAPUS --}}
        @if($surat->is_manual && $surat->user_id == Auth::id())
            <form action="{{ route('satker.surat-masuk.internal.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Pindahkan ke tempat sampah?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm shadow-sm" title="Hapus">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </form>
        @endif

        {{-- 5. TOMBOL DISPOSISI & ARSIPKAN --}}
        @if(!$isAlreadyProcessed && $surat->status != 'arsip_satker')
            <button type="button" class="btn btn-primary btn-sm shadow-sm" 
                data-bs-toggle="modal" data-bs-target="#delegasiModal" 
                data-id="{{ $surat->id }}" 
                data-perihal="{{ $surat->perihal }}" 
                data-tabel="{{ $surat->is_manual ? 'surat' : 'surat_keluar' }}" 
                title="Disposisikan">
                <i class="bi bi-share-fill"></i>
            </button>

            @if(!$surat->is_manual)
                <form action="{{ route('satker.surat-masuk.internal.arsipkan', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Arsipkan surat ini?');">
                    @csrf 
                    <button type="submit" class="btn btn-success btn-sm shadow-sm" title="Terima & Arsipkan">
                        <i class="bi bi-clipboard-check-fill"></i>
                    </button>
                </form>
            @endif
        @endif

        {{-- 6. TOMBOL CETAK --}}
        
        {{-- Tombol Cetak Satker: Muncul HANYA jika didelegasikan ke pegawai (bukan informasi umum) --}}
        @if($isDelegatedToStaff)
            <a href="{{ route('cetak.disposisi.satker', $surat->id) }}" target="_blank" class="btn btn-dark btn-sm shadow-sm" title="Cetak Disposisi Satker">
                <i class="bi bi-printer-fill"></i>
            </a>
        @endif

        {{-- Tombol Cetak Rektor: Muncul jika ada riwayat disposisi dari rektor --}}
        @if($hasRektorDisposisi)
            <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" class="btn btn-outline-dark btn-sm shadow-sm" title="Cetak Disposisi Rektor">
                <i class="bi bi-printer"></i>
            </a>
        @endif

    </div>
</td> -->

   {{-- KOLOM AKSI (SATKER INTERNAL) ini yang bug  surat keluar internal rektor tidak bisa didelegasi atau arsip --}}
    
<!-- <td class="text-center">
    <div class="d-flex justify-content-center gap-1">
        
        {{-- Logika Pendeteksi Status --}}
        @php
            $myRiwayats = $surat->riwayats->where('user_id', Auth::id());
            
            // 1. Cek apakah di riwayat sudah ada aksi 'Arsip', 'Disposisi', atau 'Selesai'
            $isAlreadyProcessed = $myRiwayats->filter(function($r) {
                $aksi = strtolower($r->status_aksi);
                return str_contains($aksi, 'disposisi') || 
                       str_contains($aksi, 'informasi') || 
                       str_contains($aksi, 'arsip') || 
                       str_contains($aksi, 'selesai');
            })->isNotEmpty();

            // 2. Cek status surat dari tabel induk (surats / surat_keluars)
            // Pastikan mengecek status 'selesai' atau 'arsip_satker'
            $isStatusArchived = in_array(strtolower($surat->status), ['arsip_satker', 'selesai', 'arsip']);
        @endphp

        {{-- 1. TOMBOL SHOW (Selalu Muncul) --}}
<button type="button" class="btn btn-info btn-sm text-white shadow-sm" 
    data-bs-toggle="modal" data-bs-target="#filePreviewModal" 
    data-title="{{ $surat->perihal }}"
    data-file-url="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '' }}"
    {{-- Tambahkan Baris di Bawah Ini --}}
    data-delegasi-info="{{ $logDisposisi->pluck('penerima.name')->unique()->join(', ') }}"
    data-instruksi="{{ $logDisposisi->last() ? $logDisposisi->last()->status_aksi : '' }}"
    data-catatan="{{ $logDisposisi->last() ? $logDisposisi->last()->catatan : '' }}"
    title="Lihat Detail">
    <i class="bi bi-eye-fill"></i>
</button>

        {{-- 2. TOMBOL LOG RIWAYAT (Selalu Muncul) --}}
        <button type="button" class="btn btn-secondary btn-sm shadow-sm" 
            data-bs-toggle="modal" data-bs-target="#riwayatModal" 
            data-url="{{ route('satker.surat-masuk.internal.riwayat', $surat->id) }}"
            title="Riwayat Surat">
            <i class="bi bi-clock-history"></i>
        </button>

        {{-- 3. LOGIKA TOMBOL PROSES (HANYA MUNCUL JIKA BELUM DIPROSES & STATUS BUKAN ARSIP) --}}
        @if(!$isAlreadyProcessed && !$isStatusArchived)
            
            {{-- Tombol Disposisi/Delegasi --}}
            <button type="button" class="btn btn-primary btn-sm shadow-sm" 
                data-bs-toggle="modal" data-bs-target="#delegasiModal" 
                data-id="{{ $surat->id }}" 
                data-perihal="{{ $surat->perihal }}" 
                data-tabel="{{ $surat->is_manual ? 'surat' : 'surat_keluar' }}" 
                title="Disposisikan">
                <i class="bi bi-share-fill"></i>
            </button>

            {{-- Tombol Arsipkan (Hanya untuk surat masuk dari sistem/satker lain) --}}
            @if(!$surat->is_manual)
                <form action="{{ route('satker.surat-masuk.internal.arsipkan', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Arsipkan surat ini?');">
                    @csrf 
                    <button type="submit" class="btn btn-success btn-sm shadow-sm" title="Terima & Arsipkan">
                        <i class="bi bi-clipboard-check-fill"></i>
                    </button>
                </form>
            @endif

        @endif

        {{-- 4. TOMBOL CETAK (Muncul jika sudah didelegasikan) --}}
        @if($isAlreadyProcessed)
            <a href="{{ route('cetak.disposisi.satker', $surat->id) }}" target="_blank" class="btn btn-dark btn-sm shadow-sm" title="Cetak Disposisi Satker">
                <i class="bi bi-printer-fill"></i>
            </a>
        @endif

    </div>
</td> -->
   {{-- KOLOM AKSI (SATKER INTERNAL)  --}}
    
<td class="text-center">
    <div class="d-flex justify-content-center gap-1">
        
        {{-- Logika Pendeteksi Status --}}
       @php
    // 1. Ambil riwayat aksi milik Admin Satker yang sedang login
    $myRiwayats = $surat->riwayats->where('user_id', Auth::id());
    
    // 2. Cek apakah sudah pernah diproses oleh user ini (Disposisi/Arsip)
    $isAlreadyProcessed = $myRiwayats->filter(function($r) {
        $aksi = strtolower($r->status_aksi);
        return str_contains($aksi, 'disposisi') || 
               str_contains($aksi, 'informasi') || 
               str_contains($aksi, 'arsip') || 
               str_contains($aksi, 'selesai');
    })->isNotEmpty();

    // 3. Ambil status global dari tabel induk (surats atau surat_keluars)
    $isStatusArchived = in_array(strtolower($surat->status), ['arsip_satker', 'selesai', 'arsip']);

    // ========================================================================
    // LOGIKA TAMBAHAN UNTUK MENANGANI BUG SURAT REKTOR -> BAU -> SATKER
    // ========================================================================
    // Ambil data pivot khusus untuk Satker yang sedang login
    $myPivot = $surat->penerimaInternal->where('id', Auth::user()->satker_id)->first();

    // Jika status global 'selesai' TAPI di tabel pivot kita is_read-nya masih 0,
    // maka kita PAKSA variabel $isStatusArchived menjadi false agar tombol tetap muncul.
    if ($isStatusArchived && $myPivot && $myPivot->pivot->is_read == 0) {
        $isStatusArchived = false;
    }
    // ========================================================================
@endphp

        {{-- 1. TOMBOL SHOW (Selalu Muncul) --}}
<button type="button" class="btn btn-info btn-sm text-white shadow-sm" 
    data-bs-toggle="modal" data-bs-target="#filePreviewModal" 
    data-title="{{ $surat->perihal }}"
    data-file-url="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '' }}"
    {{-- Tambahkan Baris di Bawah Ini --}}
    data-delegasi-info="{{ $logDisposisi->pluck('penerima.name')->unique()->join(', ') }}"
    data-instruksi="{{ $logDisposisi->last() ? $logDisposisi->last()->status_aksi : '' }}"
    data-catatan="{{ $logDisposisi->last() ? $logDisposisi->last()->catatan : '' }}"
    title="Lihat Detail">
    <i class="bi bi-eye-fill"></i>
</button>

        {{-- 2. TOMBOL LOG RIWAYAT (Selalu Muncul) --}}
        <button type="button" class="btn btn-secondary btn-sm shadow-sm" 
            data-bs-toggle="modal" data-bs-target="#riwayatModal" 
            data-url="{{ route('satker.surat-masuk.internal.riwayat', $surat->id) }}"
            title="Riwayat Surat">
            <i class="bi bi-clock-history"></i>
        </button>

        {{-- 3. LOGIKA TOMBOL PROSES (HANYA MUNCUL JIKA BELUM DIPROSES & STATUS BUKAN ARSIP) --}}
        @if(!$isAlreadyProcessed && !$isStatusArchived)
            
            {{-- Tombol Disposisi/Delegasi --}}
            <button type="button" class="btn btn-primary btn-sm shadow-sm" 
                data-bs-toggle="modal" data-bs-target="#delegasiModal" 
                data-id="{{ $surat->id }}" 
                data-perihal="{{ $surat->perihal }}" 
                data-tabel="{{ $surat->is_manual ? 'surat' : 'surat_keluar' }}" 
                title="Disposisikan">
                <i class="bi bi-share-fill"></i>
            </button>

            {{-- Tombol Arsipkan (Hanya untuk surat masuk dari sistem/satker lain) --}}
            @if(!$surat->is_manual)
                <form action="{{ route('satker.surat-masuk.internal.arsipkan', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Arsipkan surat ini?');">
                    @csrf 
                    <button type="submit" class="btn btn-success btn-sm shadow-sm" title="Terima & Arsipkan">
                        <i class="bi bi-clipboard-check-fill"></i>
                    </button>
                </form>
            @endif

        @endif

        {{-- 4. TOMBOL CETAK (Muncul jika sudah didelegasikan) --}}
        @if($isAlreadyProcessed)
            <!-- <a href="{{ route('cetak.disposisi.satker', $surat->id) }}" target="_blank" class="btn btn-dark btn-sm shadow-sm" title="Cetak Disposisi Satker">
                <i class="bi bi-printer-fill"></i>
            </a> -->
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

{{-- ====================== MODAL TAMBAH ====================== --}}
{{-- MODAL TAMBAH SURAT MASUK INTERNAL MANUAL (KHUSUS SATKER) --}}
{{-- MODAL TAMBAH SURAT MASUK INTERNAL MANUAL (KHUSUS SATKER) --}}
<div class="modal fade" id="tambahSuratModal" tabindex="-1" aria-hidden="true">
    {{-- 1. Tambahkan class modal-dialog-scrollable di sini --}}
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('satker.surat-masuk.internal.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                {{-- Hidden input karena kita sudah di halaman Internal --}}
                <input type="hidden" name="tipe_surat" value="internal">

                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Input Surat Masuk Internal (Manual)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                {{-- 2. Tambahkan max-height dan overflow-y agar area isi bisa di-scroll --}}
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
                            <label class="form-label small fw-bold">Asal Satker Pengirim <span class="text-danger">*</span></label>
                            <select name="asal_satker_id" class="form-select shadow-sm" required>
                                <option value="">-- Pilih Satker --</option>
                                @foreach($daftarSatker as $satker)
                                    <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                                @endforeach
                            </select>
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
                            <select name="target_tipe" id="target_tipe_satker" class="form-select shadow-sm border-success" onchange="toggleAlurSatker()">
                                <option value="arsip">Simpan & Arsipkan Saja (Inbox Admin)</option>
                                <option value="pribadi">Disposisi ke Pegawai Tertentu (Pribadi)</option>
                                <option value="semua">Informasi Umum (Sebar ke Semua Pegawai)</option>
                            </select>
                        </div>

                        {{-- Area Pilih Pegawai --}}
                        <div id="group_pegawai_satker" style="display: none;" class="mb-3">
                            <label class="form-label small fw-bold">Pilih Pegawai Penerima Disposisi:</label>
                            <div class="p-2 bg-white rounded border shadow-sm" style="max-height: 180px; overflow-y: auto;">
                                @forelse ($pegawaiList as $pegawai)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="delegasi_user_ids[]" value="{{ $pegawai->id }}" id="satker_pegawai_{{ $pegawai->id }}">
                                        <label class="form-check-label small" for="satker_pegawai_{{ $pegawai->id }}">
                                            {{ $pegawai->name }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted text-center small my-1">Tidak ada pegawai terdaftar.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Area Catatan --}}
                        <div id="group_catatan_satker" style="display: none;">
                            <label class="form-label small fw-bold">Instruksi / Catatan Tambahan:</label>
                            <textarea name="catatan_delegasi" id="catatan_satker" class="form-control shadow-sm" rows="2" placeholder="Tulis instruksi atau keterangan..."></textarea>
                        </div>
                    </div>

                    {{-- Extra padding bawah agar scroll tidak mepet footer --}}
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


{{-- ====================== MODAL EDIT ====================== --}}
<div class="modal fade" id="editSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form id="formEditManual" action="" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header bg-warning text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Surat Masuk Manual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Nomor Surat</label>
                            <input type="text" name="nomor_surat" id="edit_nomor_surat" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Asal Satker Pengirim</label>
                            <select name="asal_satker_id" id="edit_asal_satker_id" class="form-select shadow-sm" required>
                                <option value="">-- Pilih Satker --</option>
                                @foreach($daftarSatker as $satker)
                                    {{-- Pencocokan dilakukan via JS berdasarkan TEXT --}}
                                    <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Perihal</label>
                        <textarea name="perihal" id="edit_perihal" class="form-control shadow-sm" rows="2" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Tgl Surat</label>
                            <input type="date" name="tanggal_surat" id="edit_tanggal_surat" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">File Surat Saat Ini</label>
                            <div id="edit_file_info" class="mb-2">
                                {{-- Link file akan muncul di sini via JS --}}
                            </div>
                            <input type="file" name="file_surat" class="form-control shadow-sm" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Biarkan kosong jika tidak ingin ganti file.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold text-white shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW (Standard) --}}
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Preview Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    {{-- Kolom Kiri: Preview File --}}
                    <div class="col-lg-8 border-end">
                        <div id="file-viewer-container" class="bg-dark d-flex align-items-center justify-content-center" style="height: 75vh;">
                            <div class="spinner-border text-primary"></div>
                        </div>
                    </div>
                    {{-- Kolom Kanan: Detail Disposisi --}}
                    <div class="col-lg-4 bg-white" id="disposisi-panel" style="display:none;">
                        <div class="p-4">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Detail Disposisi</h6>
                            <hr>
                            <div class="mb-3">
                                <label class="small text-muted d-block">Diteruskan Kepada:</label>
                                <div id="display-penerima" class="fw-bold text-dark"></div>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted d-block">Instruksi:</label>
                                <div id="display-instruksi" class="text-dark fw-semibold"></div>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted d-block">Catatan Tambahan:</label>
                                <div id="display-catatan" class="p-3 bg-light rounded small italic"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <span class="me-auto text-muted small" id="preview-filename"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btn-download-file" class="btn btn-primary" download>Download</a>
            </div>
        </div>
    </div>
</div>

<!-- modal delegasi satker -->
<div class="modal fade" id="delegasiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('satker.surat-masuk.internal.delegate') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="delegasi_surat_id">
            <input type="hidden" name="asal_tabel" id="delegasi_asal_tabel">

            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-up me-2"></i> Delegasikan Surat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold">Perihal Surat</label>
                        <p id="text_perihal" class="text-muted small italic"></p>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Opsi Delegasi</label>
                        <select name="target_tipe" id="target_tipe" class="form-select shadow-sm" required onchange="togglePegawai()">
                            <option value="pribadi">Pegawai Spesifik (Disposisi)</option>
                            <option value="semua">Sebar ke Semua Pegawai (Informasi)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="group_pegawai">
                        <label class="small fw-bold">Pilih Pegawai (Bisa lebih dari satu)</label>
                        <select name="user_id[]" id="select_pegawai" class="form-select shadow-sm select2-delegasi" multiple="multiple">
                            @foreach($pegawaiList as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Group Instruksi yang akan disembunyikan/muncul --}}
                    <div id="group_instruksi">
                        <div class="mb-3">
                            <label class="small fw-bold">Klasifikasi / Instruksi</label>
                            <input type="text" name="klasifikasi" id="input_klasifikasi" class="form-control shadow-sm" placeholder="Contoh: Segera tindak lanjuti" required>
                        </div>

                        <div class="mb-0">
                            <label class="small fw-bold">Catatan Tambahan</label>
                            <textarea name="catatan" id="input_catatan" class="form-control shadow-sm" rows="3" placeholder="Masukkan instruksi khusus..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Delegasi</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
{{-- 2. Load Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    {{-- 3. Load DataTables --}}
    <script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
<script>
    $(document).ready(function () {
        // Init DataTable dengan Bahasa Indonesia (Hardcoded untuk menghindari CORS)
        $('#tabelSuratMasuk').DataTable({
            pagingType: 'simple_numbers',
            order: [],
            language: {
                "emptyTable": "Tidak ada data yang tersedia pada tabel ini",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                "infoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                "lengthMenu": "Tampilkan _MENU_ entri",
                "loadingRecords": "Sedang memuat...",
                "processing": "Sedang memproses...",
                "search": "Cari:",
                "zeroRecords": "Tidak ditemukan data yang sesuai",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });

      

var fileModal = document.getElementById('filePreviewModal');
if (fileModal) {
    fileModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var fileUrl = button.getAttribute('data-file-url');
        var title = button.getAttribute('data-title');
        
        // Ambil data delegasi yang baru saja kita tambahkan di tombol
        var delegasiInfo = button.getAttribute('data-delegasi-info');
        var instruksi = button.getAttribute('data-instruksi');
        var catatan = button.getAttribute('data-catatan');
        
        fileModal.querySelector('.modal-title').textContent = "Detail Surat: " + title;
        fileModal.querySelector('#btn-download-file').href = fileUrl;
        
        var panelDisposisi = fileModal.querySelector('#disposisi-panel');
        var containerFile = fileModal.querySelector('#file-viewer-container').parentElement;

        // Cek apakah ada data delegasi (Jika surat didelegasikan ke pegawai tertentu)
        if (delegasiInfo && delegasiInfo.trim() !== "" && delegasiInfo !== "null") {
            panelDisposisi.style.display = 'block';
            containerFile.className = "col-lg-8 border-end"; 
            
            // Isi konten detail
            fileModal.querySelector('#display-penerima').innerHTML = '<i class="bi bi-people me-2"></i>' + delegasiInfo;
            fileModal.querySelector('#display-instruksi').textContent = instruksi || '-';
            fileModal.querySelector('#display-catatan').textContent = catatan || 'Tidak ada catatan khusus.';
        } else {
            // Jika surat hanya sebar semua / informasi umum (tanpa delegasi spesifik)
            panelDisposisi.style.display = 'none';
            containerFile.className = "col-lg-12"; 
        }

        // --- PREVIEW FILE ---
        var container = fileModal.querySelector('#file-viewer-container');
        container.innerHTML = '<div class="text-white"><div class="spinner-border text-primary me-2"></div> Memuat file...</div>';

        var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
        setTimeout(function() {
            if (ext === 'pdf') {
                container.innerHTML = `<iframe src="${fileUrl}#toolbar=0" width="100%" height="100%" style="border:none;"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                container.innerHTML = `<img src="${fileUrl}" class="img-fluid" style="max-height: 100%; object-fit: contain;">`;
            } else {
                container.innerHTML = `<div class="text-white text-center p-5"><i class="bi bi-file-earmark-x fs-1"></i><br>Preview tidak tersedia.</div>`;
            }
        }, 400);
    });
}
        
        // --- RE-OPEN MODAL JIKA ERROR VALIDASI ---
        @if ($errors->any())
            @if(session('error_code') == 'create')
                var createModalEl = document.getElementById('tambahSuratModal');
                if(createModalEl) {
                    new bootstrap.Modal(createModalEl).show();
                }
            @elseif(session('error_code') == 'edit')
                var editId = "{{ session('edit_id') }}";
                // Cari tombol edit dengan ID tersebut lalu klik otomatis agar modal terbuka dengan data benar
                $('.btn-edit-manual[data-id="'+editId+'"]').click();
            @endif
        @endif
    });
</script>
<!-- TAMBAH MANUAL -->
<script>
function toggleAlurSatker() {
    const tipe = document.getElementById('target_tipe_satker').value;
    const gPeg = document.getElementById('group_pegawai_satker');
    const gCat = document.getElementById('group_catatan_satker');

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

// Inisialisasi saat modal terbuka
$('#tambahSuratModal').on('shown.bs.modal', function () {
    toggleAlurSatker();
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

                modalLabel.textContent = 'Memuat Data...';
                modalBody.innerHTML = `
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Sedang mengambil riwayat surat...</p>
                    </div>`;
                
                fetch(dataUrl)
                    .then(async response => {
                        if (!response.ok) { 
                            const errData = await response.json().catch(() => ({}));
                            throw new Error(errData.message || 'Terjadi kesalahan pada server');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status && data.status === 'error') {
                            throw new Error(data.message);
                        }

                        var judul = data.perihal ? data.perihal : (data.nomor_surat || 'Detail Surat');
                        modalLabel.textContent = 'Riwayat: ' + judul;

                        var html = '<ul class="timeline">';
                        
                        if (data.riwayats && data.riwayats.length > 0) {
                            data.riwayats.forEach((item) => {
                                var badge = 'primary'; 
                                var icon = 'bi-circle-fill';
                                var status = item.status_aksi || '';

                                // --- LOGIKA WARNA & ICON ---
                                if (status.includes('Selesai') || status.includes('Arsip') || status.includes('Diarsipkan')) {
                                    badge = 'success'; icon = 'bi-check-lg';
                                } else if (status.includes('Didelegasikan')) {
                                    badge = 'info'; icon = 'bi-person-check'; // Icon Khusus Delegasi
                                } else if (status.includes('Disposisi') || status.includes('Diteruskan')) {
                                    badge = 'warning'; icon = 'bi-arrow-return-right';
                                } else if (status.includes('Masuk') || status.includes('Input') || status.includes('Dikirim')) {
                                    badge = 'info'; icon = 'bi-envelope';
                                } else if (status.includes('Dibaca')) {
                                    badge = 'secondary'; icon = 'bi-eye';
                                }

                                var userName = (item.user && item.user.name) ? item.user.name : 'Sistem';

                                html += `
                                    <li class="mb-3 position-relative ps-4">
                                        <div class="position-absolute start-0 top-0 mt-1">
                                            <span class="badge rounded-circle bg-${badge} p-2 border border-light shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi ${icon}"></i>
                                            </span>
                                        </div>
                                        <div class="card border-0 shadow-sm bg-light">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="fw-bold mb-0 text-${badge}">${status}</h6>
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        <i class="bi bi-clock me-1"></i>${item.tanggal_f}
                                                    </small>
                                                </div>
                                                <p class="mb-1 small text-dark">${item.catatan || '-'}</p>
                                                <div class="text-end border-top pt-2 mt-2">
                                                    <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                                                        <i class="bi bi-person-circle me-1"></i> ${userName}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </li>`;
                            });
                        } else {
                            html += `
                                <li class="text-center p-4">
                                    <i class="bi bi-info-circle text-muted fs-1"></i>
                                    <p class="text-muted mt-2">Belum ada riwayat aktivitas tercatat.</p>
                                </li>`;
                        }
                        
                        html += '</ul>';
                        modalBody.innerHTML = html;
                    })
                    .catch(err => {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger text-center m-3 border-0">
                                <i class="bi bi-exclamation-triangle-fill fs-1"></i><br>
                                <strong class="fs-5">Gagal Memuat Data</strong>
                                <p class="small mt-2">${err.message}</p>
                            </div>`;
                    });
            });
        }
    });
</script>
<!-- edit -->
<script>
$(document).ready(function() {
    $('.btn-edit-manual').on('click', function() {
        // 1. Ambil data dari atribut tombol yang diklik
        const id      = $(this).attr('data-id');
        const nomor   = $(this).attr('data-nomor');
        const perihal = $(this).attr('data-perihal');
        const tanggal = $(this).attr('data-tanggal');
        const satker  = $(this).attr('data-satker'); // Berisi Nama Satker
        const fileUrl = $(this).attr('data-file-url');

        // 2. Update Action Form agar mengarah ke ID yang benar
        let actionUrl = "{{ route('satker.surat-masuk.internal.update', ':id') }}";
        actionUrl = actionUrl.replace(':id', id);
        $('#formEditManual').attr('action', actionUrl);

        // 3. Isi Nilai Input
        $('#edit_nomor_surat').val(nomor);
        $('#edit_perihal').val(perihal);
        $('#edit_tanggal_surat').val(tanggal);

        // 4. LOGIKA PENCARIAN SATKER (Berdasarkan Text Nama Satker)
        $('#edit_asal_satker_id option').each(function() {
            if ($(this).text().trim() === satker.trim()) {
                $(this).prop('selected', true);
            }
        });

        // 5. LOGIKA PREVIEW FILE
        if (fileUrl && fileUrl !== '') {
            $('#edit_file_info').html(`
                <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center">
                    <i class="bi bi-file-earmark-pdf-fill fs-4 me-2"></i>
                    <a href="${fileUrl}" target="_blank" class="text-decoration-none fw-bold">Lihat File Tersimpan</a>
                </div>
            `);
        } else {
            $('#edit_file_info').html('<span class="badge bg-secondary">Tidak ada file</span>');
        }
    });
});
</script>
<script>
$(document).ready(function() {
    // 1. Inisialisasi Select2
    $('.select2-delegasi').select2({
        theme: 'bootstrap-5',
        placeholder: "-- Pilih Satu atau Lebih Pegawai --",
        allowClear: true,
        dropdownParent: $('#delegasiModal'),
        width: '100%'
    });

    // 2. Passing data ke modal
$('#delegasiModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); 
    var id = button.data('id');
    var perihal = button.data('perihal');
    var tabel = button.data('tabel'); 

    // Reset Form
    $(this).find('form')[0].reset();
    $('.select2-delegasi').val(null).trigger('change');

    // Mengisi input berdasarkan ID elemen
    $('#delegasi_surat_id').val(id);
    $('#delegasi_asal_tabel').val(tabel);
    $('#text_perihal').text(perihal);

    console.log("ID Surat: " + id + " | Asal Tabel: " + tabel);
    togglePegawai();
});
});

// 3. Fungsi Toggle Tampilan
function togglePegawai() {
    var tipe = $('#target_tipe').val();
    if (tipe === 'semua') {
        $('#group_pegawai').hide();
        // Opsional: Kosongkan pilihan pegawai jika memilih 'semua'
        $('.select2-delegasi').val(null).trigger('change');
    } else {
        $('#group_pegawai').show();
    }
}
</script>

<script>
    function togglePegawai() {
    const tipe = document.getElementById('target_tipe').value;
    const groupPegawai = document.getElementById('group_pegawai');
    const groupInstruksi = document.getElementById('group_instruksi');
    const inputKlasifikasi = document.getElementById('input_klasifikasi');
    const selectPegawai = document.getElementById('select_pegawai');

    if (tipe === 'semua') {
        // Sembunyikan Pilihan Pegawai & Instruksi
        groupPegawai.style.display = 'none';
        groupInstruksi.style.display = 'none';
        
        // Lepas required agar tidak error saat submit
        inputKlasifikasi.removeAttribute('required');
        selectPegawai.removeAttribute('required');
        
        // Bersihkan pilihan jika sebelumnya sempat memilih pegawai
        if($('.select2-delegasi').length) {
            $('.select2-delegasi').val(null).trigger('change');
        }
    } else {
        // Tampilkan Kembali
        groupPegawai.style.display = 'block';
        groupInstruksi.style.display = 'block';
        
        // Pasang kembali required
        inputKlasifikasi.setAttribute('required', 'required');
        // selectPegawai biasanya dikontrol lewat JS/Select2 untuk required-nya
    }
}

// Jalankan fungsi sekali saat modal dibuka untuk memastikan state awal benar
$('#delegasiModal').on('shown.bs.modal', function () {
    togglePegawai();
});
</script>
@endpush