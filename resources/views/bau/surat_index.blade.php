@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px */
    #tabelSuratMasuk, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .dataTables_wrapper .dataTables_paginate { margin-top: 0.5rem !important; }
    table.dataTable thead > tr > th.sorting::before, table.dataTable thead > tr > th.sorting_asc::before, table.dataTable thead > tr > th.sorting_desc::before, table.dataTable thead > tr > th.sorting::after, table.dataTable thead > tr > th.sorting_asc::after, table.dataTable thead > tr > th.sorting_desc::after { font-size: 0.8em !important; bottom: 0.6em !important; opacity: 0.4 !important; }
    table.dataTable thead > tr > th.sorting_asc::before, table.dataTable thead > tr > th.sorting_desc::after { opacity: 1 !important; }
    
    /* Tambahan style untuk modal */
    .info-modal-label { width: 130px; font-weight: 600; }
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
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Daftar Surat Masuk (Baru & Menunggu Disposisi)</h6>
                <a href="{{ route('bau.surat.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-plus-circle-fill me-2"></i> Tambah Surat Masuk
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratMasuk" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">No. Agenda</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Asal Surat</th>
                            <th scope="col">Tujuan</th>
                            <th scope="col">Tanggal Diterima</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($semuaSurat as $surat)
                        
                        {{-- 
                            LOGIKA DETEKSI TIPE & TUJUAN (Diambil dari Admin Rektor)
                            Ini memperbaiki masalah kolom Tujuan yang kosong.
                        --}}
                        @php
                            // 1. Logika Deteksi Tipe (Fallback)
                            $tipe = $surat->tujuan_tipe;
                            $detailTujuan = '-';

                            if (empty($tipe)) {
                                if ($surat->tujuan_satker_id) {
                                    $tipe = 'satker';
                                } elseif ($surat->tujuan_user_id) {
                                    $tipe = 'pegawai';
                                } else {
                                    // Default jika tidak ada data spesifik, asumsikan ke Rektor
                                    $tipe = 'rektor';
                                }
                            }

                            // 2. Ambil Nama Detail Tujuan (Relasi Eloquent)
                            if ($tipe == 'satker') {
                                $detailTujuan = $surat->tujuanSatker->nama_satker ?? 'Satker Tidak Ditemukan';
                            } elseif ($tipe == 'pegawai') {
                                $detailTujuan = $surat->tujuanUser->name ?? 'Pegawai Tidak Ditemukan';
                            }

                            // 3. Siapkan HTML untuk Tampilan Tabel & Modal
                            $htmlTujuanTabel = '';
                            $textTujuanModal = '';

                            if ($tipe == 'rektor') {
                                $htmlTujuanTabel = '<span class="badge bg-primary">Rektor</span>';
                                $textTujuanModal = 'Rektor';
                            } elseif ($tipe == 'satker') {
                                $htmlTujuanTabel = '<span class="badge bg-warning text-dark">Satker</span><br><small class="text-muted">'.$detailTujuan.'</small>';
                                $textTujuanModal = 'Satker (' . $detailTujuan . ')';
                            } elseif ($tipe == 'pegawai') {
                                $htmlTujuanTabel = '<span class="badge bg-info text-dark">Pegawai</span><br><small class="text-muted">'.$detailTujuan.'</small>';
                                $textTujuanModal = 'Pegawai (' . $detailTujuan . ')';
                            } elseif ($tipe == 'edaran_semua_satker') {
                                $htmlTujuanTabel = '<span class="badge bg-secondary">Semua Satker</span><br><small class="text-muted">Surat Edaran</small>';
                                $textTujuanModal = 'Semua Satker (Surat Edaran)';
                            } else {
                                $htmlTujuanTabel = '<span class="badge bg-light text-dark border">'.ucfirst($tipe).'</span>';
                                $textTujuanModal = ucfirst($tipe);
                            }
                        @endphp

                        <tr>
                            <th scope="row" class="text-center">{{ $surat->no_agenda }}</th>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->surat_dari }}</td>
                            
                            {{-- TAMPILKAN TUJUAN SURAT (Hasil Logika di atas) --}}
                            <td>{!! $htmlTujuanTabel !!}</td>

                            <td>{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}</td>
                            <td>
                                {{-- 
                                    LOGIKA STATUS BERDASARKAN TIPE TUJUAN
                                    1. Tipe Rektor -> Menunggu Persetujuan Rektor
                                    2. Tipe Satker/Pegawai/Edaran -> Menunggu Disposisi Rektor
                                --}}
                                @if ($tipe == 'rektor')
                                    <span class="badge text-bg-warning">
                                        <i class="bi bi-clock-history me-1"></i> Menunggu Persetujuan Rektor
                                    </span>
                                
                                @elseif (in_array($tipe, ['satker', 'pegawai', 'edaran_semua_satker']) || $surat->butuh_disposisi)
                                    <span class="badge text-bg-primary">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Menunggu Disposisi Rektor
                                    </span>
                                
                                @else
                                    {{-- Fallback status asli jika logika tipe tidak terpenuhi --}}
                                    @if ($surat->status == 'baru_di_bau')
                                        <span class="badge text-bg-info">Baru di BAU</span>
                                    @elseif ($surat->status == 'di_admin_rektor')
                                        <span class="badge text-bg-primary">Menunggu Disposisi Rektor</span>
                                    @else
                                        <span class="badge text-bg-secondary">{{ $surat->status }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Tombol Lihat (Modal) -->
                                    <button type="button" class="btn btn-sm btn-info" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailSuratModal"
                                        data-no-agenda="{{ $surat->no_agenda }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-asal-surat="{{ $surat->surat_dari }}"
                                        data-tujuan-lengkap="{{ $textTujuanModal }}" 
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    
                                    @if ($surat->status == 'baru_di_bau')
                                        <!-- Tombol Edit -->
                                        <a href="{{ route('bau.surat.edit', $surat->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <!-- Tombol Delete -->
                                        <form action="{{ route('bau.surat.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus surat ini? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>

                                        <!-- Tombol "Teruskan ke Rektor" -->
                                        <form action="{{ route('bau.surat.forwardToRektor', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin meneruskan surat ini ke Rektor?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" title="Teruskan ke Rektor">
                                                <i class="bi bi-send-arrow-up-fill"></i>
                                            </button>
                                        </form>
                                        
                                    @elseif ($surat->status == 'di_admin_rektor')
                                        <!-- Tombol Edit -->
                                        <a href="{{ route('bau.surat.edit', $surat->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <!-- Tombol Delete -->
                                        <form action="{{ route('bau.surat.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus surat ini? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>

                                        <!-- Icon Menunggu (Jam Pasir) -->
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Sedang diproses Rektor">
                                            <i class="bi bi-hourglass-split"></i>
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
                    <div class="col-md-5">
                        <h4 class="mb-3" id="modal-perihal">Perihal Surat</h4>
                        <table class="table table-borderless table-sm small">
                            <tr><td class="info-modal-label">No. Agenda</td><td>: <span id="modal-no-agenda"></span></td></tr>
                            <tr><td class="info-modal-label">Asal Surat</td><td>: <span id="modal-asal-surat"></span></td></tr>
                            <tr>
                                <td class="info-modal-label">Tujuan</td>
                                <td>: <span id="modal-tujuan" class="fw-bold text-primary"></span></td>
                            </tr>
                            <tr><td class="info-modal-label">Tanggal Surat</td><td>: <span id="modal-tanggal-surat"></span></td></tr>
                            <tr><td class="info-modal-label">Tanggal Diterima</td><td>: <span id="modal-tanggal-diterima"></span></td></tr>
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
            order: [[ 4, 'desc' ]], 
            language: {
                search: "Cari:",
                lengthMenu: "_MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: { next: "Selanjutnya", previous: "Sebelumnya" },
                zeroRecords: "Tidak ada surat masuk baru."
            }
        });
        
        // Init Modal
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            var noAgenda = button.getAttribute('data-no-agenda');
            var perihal = button.getAttribute('data-perihal');
            var asalSurat = button.getAttribute('data-asal-surat');
            var tujuanLengkap = button.getAttribute('data-tujuan-lengkap'); // Data baru dari PHP
            var tanggalSurat = button.getAttribute('data-tanggal-surat');
            var tanggalDiterima = button.getAttribute('data-tanggal-diterima');
            var fileUrl = button.getAttribute('data-file-url');

            var modalPerihal = detailSuratModal.querySelector('#modal-perihal');
            var modalNoAgenda = detailSuratModal.querySelector('#modal-no-agenda');
            var modalAsalSurat = detailSuratModal.querySelector('#modal-asal-surat');
            var modalTujuan = detailSuratModal.querySelector('#modal-tujuan');
            var modalTanggalSurat = detailSuratModal.querySelector('#modal-tanggal-surat');
            var modalTanggalDiterima = detailSuratModal.querySelector('#modal-tanggal-diterima');
            var modalFileWrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            var modalDownloadButton = detailSuratModal.querySelector('#modal-download-button');

            modalPerihal.textContent = perihal;
            modalNoAgenda.textContent = noAgenda;
            modalAsalSurat.textContent = asalSurat;
            
            // Set Tujuan langsung dari atribut data-tujuan-lengkap
            modalTujuan.textContent = tujuanLengkap;

            modalTanggalSurat.textContent = tanggalSurat;
            modalTanggalDiterima.textContent = tanggalDiterima;
            modalDownloadButton.href = fileUrl;
            modalDownloadButton.setAttribute('download', perihal + '.pdf');

            var fileHtml = '';
            var extension = fileUrl.split('.').pop().toLowerCase();
            if (extension == 'pdf') {
                fileHtml = '<iframe src="' + fileUrl + '" width="100%" height="100%" frameborder="0"></iframe>';
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                fileHtml = '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 70vh; object-fit: contain; width: 100%;">';
            } else {
                 fileHtml = '<div class="text-center p-5"><i class="bi bi-file-earmark-text h1 text-muted"></i><p class="mt-3">Preview tidak didukung.</p></div>';
            }
            modalFileWrapper.innerHTML = fileHtml;
        });
    });
</script>
@endpush