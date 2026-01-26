@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS Penyesuaian */
    #tabelSuratMasuk, .dataTables_wrapper { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .btn-icon { padding: 0.25rem 0.5rem; }
    .info-modal-label { width: 140px; font-weight: 600; color: #555; }
    .info-modal-data { font-weight: 500; color: #000; word-break: break-word; }
</style>
@endpush

@section('content')

{{-- 1. DETEKSI TIPE HALAMAN (Internal / Eksternal) --}}
@php
    $isEksternal = Request::routeIs('bau.surat.eksternal');
    $labelTipe   = $isEksternal ? 'Eksternal' : 'Internal';
    $badgeColor  = $isEksternal ? 'bg-success' : 'bg-primary'; 
    $tipeParam   = $isEksternal ? 'eksternal' : 'internal'; // Untuk parameter create url
@endphp

<div class="container-fluid px-3">

    {{-- ALERT MESSAGES --}}
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

    <div class="card shadow-sm border-0 mb-4 mt-2">
        <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            
            {{-- 2. JUDUL DINAMIS SESUAI HALAMAN --}}
            <h6 class="m-0 fw-bold text-dark d-flex align-items-center">
                <i class="bi bi-inbox-fill me-2 text-secondary fs-5"></i>
                <span>Daftar Surat Masuk</span>
                {{-- Badge Keterangan Internal / Eksternal --}}
                <span class="badge {{ $badgeColor }} ms-2">{{ $labelTipe }}</span>
            </h6>

            {{-- Tombol Tambah (Kirim parameter tipe agar form create tahu konteksnya) --}}
            <a href="{{ route('bau.surat.create', ['type' => $tipeParam]) }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Input Surat {{ $labelTipe }}
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratMasuk" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="20%">Asal Surat</th>
                            <th width="25%">Perihal</th>
                            <th width="10%">Tujuan</th>
                            <th width="15%">Status</th>
                            <th class="text-center" width="15%">Aksi</th>
                        </tr>
                    </thead>
 <tbody>
    {{-- 1. INISIALISASI NOMOR URUT MANUAL --}}
    @php $nomor = 1; @endphp

    @foreach ($semuaSurat as $surat)

    @php
        // 2. BERSIHKAN STRING ASAL SURAT
        $asal = strtoupper(trim($surat->surat_dari));

        // 3. FILTER SEMBUNYIKAN SURAT DARI BAU
        if (
            $asal == 'BAU' || 
            $asal == 'B A U' ||
            $asal == 'ADMIN BAU' ||
            str_contains($asal, 'BIRO ADMINISTRASI UMUM')
        ) {
            continue; // Skip data ini (Nomor $nomor TIDAK akan bertambah)
        }
    @endphp

    @php
        // --- LOGIKA TUJUAN ---
        $tipe = $surat->tujuan_tipe;
        $detailTujuan = '-';
        
        if (empty($tipe)) {
            if ($surat->tujuan_satker_id) { $tipe = 'satker'; }
            elseif ($surat->tujuan_user_id) { $tipe = 'pegawai'; }
            else { $tipe = 'universitas'; }
        }

        if ($tipe == 'satker') {
            $detailTujuan = $surat->tujuanSatker->nama_satker ?? 'Satker Tidak Ditemukan';
        } elseif ($tipe == 'pegawai') {
            $detailTujuan = $surat->tujuanUser->name ?? 'Pegawai Tidak Ditemukan';
        }

        $htmlTujuan = '';
        $modalTujuan = '';

        if ($tipe == 'rektor') {
            $htmlTujuan = '<span class="badge bg-primary">Rektor</span>';
            $modalTujuan = 'Rektor';
        } elseif ($tipe == 'universitas') {
            $htmlTujuan = '<span class="badge bg-info text-dark">Universitas</span>';
            $modalTujuan = 'Universitas';
        } elseif ($tipe == 'satker') {
            $htmlTujuan = '<span class="badge bg-success">Satker</span><div class="small text-muted mt-1">'.$detailTujuan.'</div>';
            $modalTujuan = 'Satker: ' . $detailTujuan;
        } elseif ($tipe == 'pegawai') {
            $htmlTujuan = '<span class="badge bg-secondary">Pegawai</span><div class="small text-muted mt-1">'.$detailTujuan.'</div>';
            $modalTujuan = 'Pegawai: ' . $detailTujuan;
        } elseif ($tipe == 'edaran_semua_satker') {
            $htmlTujuan = '<span class="badge bg-dark">Edaran (Semua)</span>';
            $modalTujuan = 'Surat Edaran (Semua Satker)';
        }

        // --- LOGIKA STATUS ---
        $statusBadge = '';
        if ($surat->status == 'baru_di_bau') {
            $statusBadge = '<span class="badge bg-danger">Baru (Draft)</span>';
        } elseif ($surat->status == 'di_admin_rektor') {
            $statusBadge = '<span class="badge bg-warning text-dark">Di Admin Rektor</span>';
        } else {
            $statusBersih = ucwords(str_replace('_', ' ', $surat->status));
            $statusBadge = '<span class="badge bg-secondary">'.$statusBersih.'</span>';
        }
    @endphp

    <tr>
        {{-- GANTI $loop->iteration DENGAN $nomor++ --}}
        <td class="text-center fw-bold">{{ $nomor++ }}</td>
        
        <td>
            <span class="fw-bold text-dark">{{ $surat->surat_dari }}</span>
            <br>
            <small class="text-muted"><i class="bi bi-calendar-event me-1"></i>{{ $surat->diterima_tanggal ? $surat->diterima_tanggal->format('d M Y') : '-' }}</small>
        </td>
        <td>
            {{ Str::limit($surat->perihal, 50) }}
            <br>
            <small class="text-muted">No: {{ $surat->nomor_surat }}</small>
        </td>
        <td>{!! $htmlTujuan !!}</td>
        <td>{!! $statusBadge !!}</td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                
                {{-- LIHAT DETAIL --}}
                <button type="button" class="btn btn-sm btn-info text-white btn-icon" 
                    title="Lihat Detail"
                    data-bs-toggle="modal" 
                    data-bs-target="#detailSuratModal"
                    data-no-agenda="{{ $surat->no_agenda }}"
                    data-perihal="{{ $surat->perihal }}"
                    data-asal-surat="{{ $surat->surat_dari }}"
                    data-tujuan-lengkap="{{ $modalTujuan }}"
                    data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                    data-tanggal-diterima="{{ $surat->diterima_tanggal ? $surat->diterima_tanggal->isoFormat('D MMMM YYYY') : '-' }}"
                    data-file-url="{{ Storage::url($surat->file_surat) }}">
                    <i class="bi bi-eye-fill"></i>
                </button>

                {{-- HANYA TAMPILKAN TOMBOL EDIT/HAPUS/TERUSKAN JIKA MASIH BARU --}}
                @if ($surat->status == 'baru_di_bau')
                    
                    {{-- EDIT --}}
                    <a href="{{ route('bau.surat.edit', $surat->id) }}" class="btn btn-sm btn-warning text-white btn-icon" title="Edit">
                        <i class="bi bi-pencil-fill"></i>
                    </a>

                    {{-- HAPUS --}}
                    <form action="{{ route('bau.surat.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus surat ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Hapus">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>

                    {{-- TERUSKAN --}}
                    @if ($tipe == 'rektor' || $tipe == 'universitas')
                        <form action="{{ route('bau.surat.forwardToRektor', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Teruskan surat ini ke Admin Rektor?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary btn-icon" title="Teruskan ke Rektor">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </form>
                    @else
                        <form action="{{ route('bau.surat.forwardToSatker', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Kirim surat langsung ke penerima?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success btn-icon" title="Kirim Langsung">
                                <i class="bi bi-send-check-fill"></i>
                            </button>
                        </form>
                    @endif

                @else
                    {{-- STATUS TERKUNCI --}}
                    <span class="text-muted small fst-italic ms-1" title="Surat sedang diproses"><i class="bi bi-lock-fill"></i> Terkunci</span>
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

{{-- MODAL DETAIL (SAMA SEPERTI SEBELUMNYA) --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-envelope-open me-2"></i>Detail Surat Masuk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-lg-4 border-end bg-light p-4">
                        <h6 class="text-uppercase text-muted fw-bold mb-3 small">Informasi Surat</h6>
                        <h5 class="mb-3 fw-bold text-dark" id="modal-perihal"></h5>
                        
                        <div class="mb-3 pb-3 border-bottom">
                            <label class="small text-secondary fw-bold d-block">Asal Surat</label>
                            <span id="modal-asal-surat" class="fw-medium"></span>
                        </div>
                        <div class="mb-3 pb-3 border-bottom">
                            <label class="small text-secondary fw-bold d-block">Tujuan</label>
                            <span id="modal-tujuan" class="badge bg-success fs-6 fw-normal"></span>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="small text-secondary fw-bold d-block">No. Agenda</label>
                                <span id="modal-no-agenda" class="fw-medium font-monospace"></span>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="small text-secondary fw-bold d-block">Tgl. Surat</label>
                                <span id="modal-tanggal-surat" class="fw-medium"></span>
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <a href="#" id="modal-download-button" class="btn btn-primary" download>
                                <i class="bi bi-cloud-arrow-down-fill me-2"></i> Download Dokumen
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-8 bg-secondary bg-opacity-10 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-uppercase text-muted fw-bold small m-0">Preview Dokumen</h6>
                        </div>
                        <div id="modal-file-preview-wrapper" class="bg-white rounded shadow-sm d-flex align-items-center justify-content-center border" style="height: 500px; overflow: hidden;">
                            {{-- Preview content via JS --}}
                        </div>
                    </div>
                </div>
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
        // PERBAIKAN: Menulis language object secara langsung (Hardcode)
        // agar tidak terkena error loading i18n file (CORS/Network)
        $('#tabelSuratMasuk').DataTable({
            pagingType: 'simple_numbers',
            ordering: false, // Matikan sorting default agar 'No' urut sesuai iterasi view
            language: { 
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ surat",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                zeroRecords: "Tidak ada data yang cocok",
                paginate: {
                    next: "Lanjut",
                    previous: "Kembali"
                }
            }
        });
        
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Set Data Teks
            detailSuratModal.querySelector('#modal-perihal').textContent = button.getAttribute('data-perihal');
            detailSuratModal.querySelector('#modal-no-agenda').textContent = button.getAttribute('data-no-agenda');
            detailSuratModal.querySelector('#modal-asal-surat').textContent = button.getAttribute('data-asal-surat');
            detailSuratModal.querySelector('#modal-tujuan').textContent = button.getAttribute('data-tujuan-lengkap');
            detailSuratModal.querySelector('#modal-tanggal-surat').textContent = button.getAttribute('data-tanggal-surat');
            
            // Set Download
            var fileUrl = button.getAttribute('data-file-url');
            var btnDownload = detailSuratModal.querySelector('#modal-download-button');
            btnDownload.href = fileUrl;
            
            // Preview Logic
            var wrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            wrapper.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
            
            if(fileUrl) {
                var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
                setTimeout(() => {
                    if(ext === 'pdf') {
                        wrapper.innerHTML = '<iframe src="'+fileUrl+'" width="100%" height="100%" style="border:none;"></iframe>';
                    } else if(['jpg', 'jpeg', 'png'].includes(ext)) {
                        wrapper.innerHTML = '<img src="'+fileUrl+'" class="img-fluid" style="max-height: 100%;">';
                    } else {
                        wrapper.innerHTML = '<div class="text-center text-muted"><i class="bi bi-file-earmark-x h1"></i><p>Preview tidak tersedia.</p></div>';
                    }
                }, 300);
            } else {
                wrapper.innerHTML = '<div class="text-center text-danger">File tidak ditemukan.</div>';
            }
        });
    });
</script>
@endpush