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
<div class="container-fluid px-4">

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

    <div class="card shadow-sm border-0 mb-4 mt-4">
        <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">
                <i class="bi bi-inbox-fill me-2"></i>Daftar Surat Masuk (Perlu Diproses)
            </h6>
            <a href="{{ route('bau.surat.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Input Surat Baru
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratMasuk" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="20%">Asal Surat</th>
                            <th width="25%">Perihal</th>
                            <th width="20%">Tujuan</th>
                            <th width="15%">Status</th>
                            <th class="text-center" width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($semuaSurat as $surat)
                        
                        @php
                            // --- 1. LOGIKA TUJUAN ---
                            $tipe = $surat->tujuan_tipe;
                            $detailTujuan = '-';
                            
                            // Fallback jika tipe kosong
                            if (empty($tipe)) {
                                if ($surat->tujuan_satker_id) { $tipe = 'satker'; }
                                elseif ($surat->tujuan_user_id) { $tipe = 'pegawai'; }
                                else { $tipe = 'universitas'; }
                            }

                            // Ambil nama detail
                            if ($tipe == 'satker') {
                                $detailTujuan = $surat->tujuanSatker->nama_satker ?? 'Satker Tidak Ditemukan';
                            } elseif ($tipe == 'pegawai') {
                                $detailTujuan = $surat->tujuanUser->name ?? 'Pegawai Tidak Ditemukan';
                            }

                            // HTML Badge Tujuan
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

                            // --- 2. LOGIKA STATUS ---
                            $statusBadge = '';
                            if ($surat->status == 'baru_di_bau') {
                                $statusBadge = '<span class="badge bg-danger">Baru (Draft)</span>';
                            } elseif ($surat->status == 'di_admin_rektor') {
                                $statusBadge = '<span class="badge bg-warning text-dark">Di Admin Rektor</span>';
                            } else {
                                $statusBadge = '<span class="badge bg-secondary">'.$surat->status.'</span>';
                            }
                        @endphp

                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <span class="fw-bold text-dark">{{ $surat->surat_dari }}</span>
                                <br>
                                <small class="text-muted"><i class="bi bi-calendar-event"></i> {{ $surat->diterima_tanggal->format('d M Y') }}</small>
                            </td>
                            <td>
                                {{ $surat->perihal }}
                                <br>
                                <small class="text-muted">No: {{ $surat->nomor_surat }}</small>
                            </td>
                            <td>{!! $htmlTujuan !!}</td>
                            <td>{!! $statusBadge !!}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    
                                    {{-- TOMBOL LIHAT DETAIL (Selalu Muncul) --}}
                                    <button type="button" class="btn btn-sm btn-info text-white btn-icon" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailSuratModal"
                                        data-no-agenda="{{ $surat->no_agenda }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-asal-surat="{{ $surat->surat_dari }}"
                                        data-tujuan-lengkap="{{ $modalTujuan }}"
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                 
                                        <a href="{{ route('bau.surat.edit', $surat->id) }}" class="btn btn-sm btn-warning text-white btn-icon" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <form action="{{ route('bau.surat.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus surat ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Hapus">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                 

                                    {{-- TOMBOL TERUSKAN / KIRIM --}}
                                    @if ($surat->status == 'baru_di_bau')
                                        
                                        @if ($tipe == 'rektor' || $tipe == 'universitas')
                                            {{-- Kasus 1: Ke Rektor --}}
                                            <form action="{{ route('bau.surat.forwardToRektor', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Teruskan surat ini ke Admin Rektor?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary btn-icon" title="Teruskan ke Rektor">
                                                    <i class="bi bi-send-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            {{-- Kasus 2: Ke Satker Langsung --}}
                                            <form action="{{ route('bau.surat.forwardToSatker', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Kirim surat langsung ke penerima?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success btn-icon" title="Kirim Langsung">
                                                    <i class="bi bi-send-check-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                    @elseif ($surat->status == 'di_admin_rektor')
                                      
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
                    {{-- Kolom Kiri: Informasi Teks --}}
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded border">
                            <h5 class="mb-3 text-primary fw-bold" id="modal-perihal"></h5>
                            
                            <table class="table table-borderless table-sm mb-0">
                                <tr><td class="info-modal-label">No. Agenda</td><td>: <span id="modal-no-agenda"></span></td></tr>
                                <tr><td class="info-modal-label">Asal Surat</td><td>: <span id="modal-asal-surat"></span></td></tr>
                                <tr>
                                    <td class="info-modal-label">Tujuan</td>
                                    <td>: <span id="modal-tujuan" class="fw-bold text-success"></span></td>
                                </tr>
                                <tr><td class="info-modal-label">Tgl. Surat</td><td>: <span id="modal-tanggal-surat"></span></td></tr>
                                <tr><td class="info-modal-label">Tgl. Diterima</td><td>: <span id="modal-tanggal-diterima"></span></td></tr>
                            </table>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Preview File --}}
                    <div class="col-md-7">
                        <div id="modal-file-preview-wrapper" class="bg-dark bg-opacity-10 d-flex align-items-center justify-content-center rounded border" style="height: 70vh;">
                            {{-- Konten preview akan diisi JS --}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="modal-download-button" class="btn btn-primary" download>
                    <i class="bi bi-download me-2"></i> Download File
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
        // Init DataTable
        $('#tabelSuratMasuk').DataTable({
            pagingType: 'simple_numbers',
            order: [[ 0, 'desc' ]], // Urut berdasarkan No (terbaru)
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ surat",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: { next: "Next", previous: "Prev" }
            }
        });
        
        // Modal Detail Logic
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Ambil data
            var noAgenda = button.getAttribute('data-no-agenda');
            var perihal = button.getAttribute('data-perihal');
            var asalSurat = button.getAttribute('data-asal-surat');
            var tujuan = button.getAttribute('data-tujuan-lengkap');
            var tglSurat = button.getAttribute('data-tanggal-surat');
            var tglTerima = button.getAttribute('data-tanggal-diterima');
            var fileUrl = button.getAttribute('data-file-url');

            // Set text
            detailSuratModal.querySelector('#modal-perihal').textContent = perihal;
            detailSuratModal.querySelector('#modal-no-agenda').textContent = noAgenda;
            detailSuratModal.querySelector('#modal-asal-surat').textContent = asalSurat;
            detailSuratModal.querySelector('#modal-tujuan').textContent = tujuan;
            detailSuratModal.querySelector('#modal-tanggal-surat').textContent = tglSurat;
            detailSuratModal.querySelector('#modal-tanggal-diterima').textContent = tglTerima;

            // Set Download Link
            var btnDownload = detailSuratModal.querySelector('#modal-download-button');
            btnDownload.href = fileUrl;
            btnDownload.setAttribute('download', perihal + '.pdf');

            // Preview File Logic
            var wrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            wrapper.innerHTML = '<div class="spinner-border text-primary" role="status"></div>'; // Loading

            if(fileUrl && fileUrl.length > 5) {
                var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
                
                setTimeout(() => {
                    if(ext === 'pdf') {
                        wrapper.innerHTML = '<iframe src="'+fileUrl+'" width="100%" height="100%" style="border:none;"></iframe>';
                    } else if(['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                        wrapper.innerHTML = '<img src="'+fileUrl+'" class="img-fluid" style="max-height: 100%; object-fit: contain;">';
                    } else {
                        wrapper.innerHTML = '<div class="text-center p-5"><i class="bi bi-file-earmark-text h1 text-secondary"></i><p class="mt-3">Preview tidak tersedia.<br>Silakan download file.</p></div>';
                    }
                }, 500);
            } else {
                wrapper.innerHTML = '<div class="text-center text-danger"><i class="bi bi-exclamation-circle h2"></i><p>File tidak ditemukan.</p></div>';
            }
        });
    });
</script>
@endpush