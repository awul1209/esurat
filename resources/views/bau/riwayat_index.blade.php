@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px */
    #tabelRiwayat, .dataTables_wrapper { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .dataTables_wrapper .dataTables_paginate { margin-top: 0.5rem !important; }
    
    /* CSS Timeline */
    .timeline { list-style: none; padding: 0; position: relative; }
    .timeline:before {
        top: 0; bottom: 0; position: absolute; content: " "; width: 3px;
        background-color: #eeeeee; left: 30px; margin-left: -1.5px;
    }
    .timeline > li { margin-bottom: 20px; position: relative; }
    .timeline > li:after { clear: both; }
    .timeline > li > .timeline-panel {
        width: calc(100% - 75px); float: right; padding: 15px;
        border: 1px solid #d4d4d4; border-radius: 5px;
        position: relative; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .timeline > li > .timeline-badge {
        color: #fff; width: 50px; height: 50px; line-height: 50px;
        font-size: 1.4em; text-align: center; position: absolute;
        top: 16px; left: 15px; margin-left: -10px;
        background-color: #999999; z-index: 100;
        border-radius: 50%;
    }
    .timeline > li > .timeline-badge.primary { background-color: #0d6efd !important; }
    .timeline > li > .timeline-badge.success { background-color: #198754 !important; }
    .timeline > li > .timeline-badge.warning { background-color: #ffc107 !important; }
    .timeline-heading h6 { margin-top: 0; font-weight: bold; }
    .timeline-body > p, .timeline-body > ul { margin-bottom: 0; }

    .info-modal-label { width: 150px; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Riwayat Surat Selesai Diteruskan</h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelRiwayat" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">No. Agenda</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Asal Surat</th>
                            <th scope="col">Status</th>
                            <th scope="col">Tujuan Akhir</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratSelesai as $surat) 
                        
                        @php
                            // LOGIKA PENENTUAN TUJUAN AKHIR UNTUK RIWAYAT
                            $tipe = $surat->tujuan_tipe;
                            $tujuanAkhirHTML = '-'; // Versi HTML untuk Tabel
                            $tujuanModalText = '-'; // Versi Teks untuk Modal

                            // 1. Fallback Detection (jika tipe kosong di DB lama)
                            if (empty($tipe)) {
                                if ($surat->tujuan_satker_id) { $tipe = 'satker'; } 
                                elseif ($surat->tujuan_user_id) { $tipe = 'pegawai'; } 
                                else { $tipe = 'universitas'; }
                            }

                            // 2. Generate String Tujuan berdasarkan Tipe
                            if ($tipe == 'rektor') {
                                $tujuanAkhirHTML = '<span class="fw-bold text-primary">Rektor</span>';
                                $tujuanModalText = 'Rektor';
                            } 
                            elseif ($tipe == 'universitas') {
                                // PERBAIKAN PENTING DI SINI:
                                // Mengambil SEMUA disposisi, baik Satker maupun Lainnya
                                $listDisposisi = $surat->disposisis;
                                $namaTujuansHTML = []; // Array untuk tampilan HTML
                                $namaTujuansText = []; // Array untuk tampilan Teks Modal

                                foreach($listDisposisi as $d) {
                                    // A. Cek Satker Internal
                                    if ($d->tujuanSatker) {
                                        $namaTujuansHTML[] = '<span class="text-primary fw-bold">' . $d->tujuanSatker->nama_satker . '</span>';
                                        $namaTujuansText[] = $d->tujuanSatker->nama_satker;
                                    }
                                    // B. Cek Disposisi Lain (BEM/Ormawa)
                                    elseif ($d->disposisi_lain) {
                                        $namaTujuansHTML[] = '<span class="text-dark fst-italic">' . $d->disposisi_lain . ' (Non-Satker)</span>';
                                        $namaTujuansText[] = $d->disposisi_lain . ' (Non-Satker)';
                                    }
                                }

                                if (count($namaTujuansHTML) > 0) {
                                    // Gabungkan nama tujuan
                                    $penerimaHTML = implode(', ', $namaTujuansHTML);
                                    $penerimaText = implode(', ', $namaTujuansText);
                                    
                                    $tujuanAkhirHTML = '<span class="fw-bold text-primary">Universitas</span><br><small class="text-muted">Disposisi ke: ' . $penerimaHTML . '</small>';
                                    $tujuanModalText = 'Universitas (Disposisi ke: ' . $penerimaText . ')';
                                } else {
                                    // Jika benar-benar kosong (Rektor langsung klik selesai tanpa memilih tujuan apapun)
                                    $tujuanAkhirHTML = '<span class="fw-bold text-primary">Universitas</span><br><small class="text-muted">Arsip Rektor (Tanpa Disposisi)</small>';
                                    $tujuanModalText = 'Universitas (Arsip Rektor)';
                                }
                            } 
                            elseif ($tipe == 'satker') {
                                $nama = $surat->tujuanSatker->nama_satker ?? 'Satker Tidak Ditemukan';
                                $tujuanAkhirHTML = '<span class="fw-bold text-success">' . $nama . '</span> <small>(Langsung)</small>';
                                $tujuanModalText = $nama . ' (Satker Langsung)';
                            } 
                            elseif ($tipe == 'pegawai') {
                                $nama = $surat->tujuanUser->name ?? 'Pegawai Tidak Ditemukan';
                                $tujuanAkhirHTML = '<span class="fw-bold text-info">' . $nama . '</span> <small>(Ybs)</small>';
                                $tujuanModalText = $nama . ' (Pegawai/Ybs)';
                            } 
                            elseif ($tipe == 'edaran_semua_satker' || $surat->status == 'selesai_edaran') {
                                $tujuanAkhirHTML = '<span class="fw-bold text-secondary">Semua Satker (Edaran)</span>';
                                $tujuanModalText = 'Semua Satker (Edaran)';
                            }
                        @endphp

                        <tr>
                            <th scope="row" class="text-center">{{ $surat->no_agenda }}</th>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->surat_dari }}</td>
                            <td>
                                @if(in_array($surat->status, ['selesai_edaran', 'selesai', 'arsip_satker', 'disimpan', 'diarsipkan', 'di_satker']))
                                    <span class="badge text-bg-success">Selesai / Diarsipkan</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $surat->status }}</span>
                                @endif
                            </td>
                            <td>
                                {{-- Tampilkan Tujuan Akhir (HTML) --}}
                                {!! $tujuanAkhirHTML !!}
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- Tombol Lihat Detail --}}
                                    <button type="button" class="btn btn-sm btn-info" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailSuratModal"
                                        data-no-agenda="{{ $surat->no_agenda }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-asal-surat="{{ $surat->surat_dari }}"
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                        data-tujuan="{{ $tujuanModalText }}"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    {{-- Tombol Cetak --}}
                                    <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak Lembar Disposisi">
                                        <i class="bi bi-printer-fill"></i>
                                    </a>
                                    
                                    {{-- Tombol Timeline Riwayat --}}
                                    <button type="button" class="btn btn-sm btn-secondary" 
                                        title="Lihat Riwayat Lengkap"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#riwayatModal"
                                        data-url="{{ route('bau.riwayat.detail', $surat->id) }}">
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

{{-- MODAL 1: Detail Surat --}}
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
                        <table class="table table-borderless table-sm small" style="font-size: 13px;">
                           <tr><td class="info-modal-label">No. Agenda</td><td>: <span id="modal-no-agenda"></span></td></tr>
                           <tr><td class="info-modal-label">Asal Surat</td><td>: <span id="modal-asal-surat"></span></td></tr>
                           
                           {{-- Baris Tujuan di Modal --}}
                           <tr><td class="info-modal-label">Tujuan</td><td>: <span id="modal-tujuan" class="fw-bold text-primary"></span></td></tr>
                           
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

{{-- MODAL 2: Riwayat (Timeline) --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-labelledby="riwayatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="riwayatModalLabel">Riwayat Lengkap Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="riwayatModalBody" style="font-size: 13px;">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat riwayat...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
<script>
    function formatTanggal(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        }) + ' WIB';
    }

    $(document).ready(function () {
        
        // DataTables
        new DataTable('#tabelRiwayat', {
            pagingType: 'simple_numbers',
            order: [[ 0, 'desc' ]], 
            language: {
                search: "Cari:", lengthMenu: "_MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: { next: "Selanjutnya", previous: "Sebelumnya" },
                zeroRecords: "Tidak ada riwayat surat."
            }
        });
        
        // Script Modal Detail (Modal 1)
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var noAgenda = button.getAttribute('data-no-agenda');
            var perihal = button.getAttribute('data-perihal');
            var asalSurat = button.getAttribute('data-asal-surat');
            var tujuan = button.getAttribute('data-tujuan'); 
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
            modalTujuan.textContent = tujuan; 
            modalTanggalSurat.textContent = tanggalSurat;
            modalTanggalDiterima.textContent = tanggalDiterima;
            
            modalDownloadButton.href = fileUrl;
            modalDownloadButton.setAttribute('download', 'Surat - ' + perihal + '.pdf');
            
            var fileHtml = '';
            var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0]; 
            
            if (extension == 'pdf') {
                fileHtml = '<iframe src="' + fileUrl + '" width="100%" height="100%" frameborder="0"></iframe>';
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                fileHtml = '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 70vh; object-fit: contain; width: 100%;">';
            } else {
                 fileHtml = '<div class="text-center p-5"><i class="bi bi-file-earmark-text h1 text-muted"></i><p class="mt-3">Preview tidak didukung.</p></div>';
            }
            modalFileWrapper.innerHTML = fileHtml;
        });

        // Script Modal Riwayat (Modal 2)
        var riwayatModal = document.getElementById('riwayatModal');
        riwayatModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var dataUrl = button.getAttribute('data-url');
            var modalBody = riwayatModal.querySelector('#riwayatModalBody');
            var modalLabel = riwayatModal.querySelector('#riwayatModalLabel');

            modalBody.innerHTML = `<div class="text-center p-4">
                                     <div class="spinner-border text-primary" role="status"></div>
                                     <p class="mt-2 text-muted">Memuat riwayat...</p>
                                   </div>`;
            modalLabel.textContent = "Riwayat Lengkap Surat";

            fetch(dataUrl)
                .then(response => response.json())
                .then(surat => {
                    modalLabel.textContent = 'Riwayat Surat: ' + surat.perihal;
                    
                    var html = '<ul class="timeline">';
                    surat.riwayats.forEach((item, index) => {
                        var badgeClass = 'primary'; 
                        if (item.status_aksi.includes('Selesai')) {
                            badgeClass = 'success';
                        } else if (item.status_aksi.includes('Diteruskan')) {
                            badgeClass = 'warning';
                        }
                        
                        var icon = 'bi-check';
                        if (item.status_aksi.includes('Input')) icon = 'bi-pencil-fill';
                        else if (item.status_aksi.includes('Rektor')) icon = 'bi-person-workspace';
                        else if (item.status_aksi.includes('Satker')) icon = 'bi-send-check-fill';

                        html += `<li>
                                    <div class="timeline-badge ${badgeClass}"><i class="bi ${icon}"></i></div>
                                    <div class="timeline-panel">
                                        <div class="timeline-heading">
                                            <h6 class="timeline-title">${item.status_aksi}</h6>
                                            <p><small class="text-muted">
                                                <i class="bi bi-clock-fill"></i> ${formatTanggal(item.created_at)}
                                                <br>
                                                <i class="bi bi-person-fill"></i> ${item.user.name}
                                            </small></p>
                                        </div>
                                        <div class="timeline-body">
                                            <p>${item.catatan}</p>
                                        </div>
                                    </div>
                                </li>`;
                    });
                    html += '</ul>';
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Gagal memuat riwayat.</div>';
                });
        });
    });
</script>
@endpush