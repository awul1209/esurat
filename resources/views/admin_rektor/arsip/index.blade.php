@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* CSS 13px untuk konsistensi */
    #tabelArsip, .dataTables_wrapper, .form-label, .form-control, .form-select, .btn-sm { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .table td, .table th { vertical-align: middle; }
    
    /* CSS Timeline (Sama dengan Riwayat Disposisi) */
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
<div class="container-fluid px-3">
    
    {{-- CARD UTAMA --}}
    <div class="card shadow-sm border-0 mb-4 mt-2">
        <div class="card-header py-3 bg-light d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-archive-fill me-2"></i>Data Arsip (Tidak Didisposisi)</h6>
        </div>

        <div class="card-body">
            
            {{-- 1. BAGIAN FILTER & EXPORT --}}
            <div class="p-3 mb-3 bg-light rounded border border-light">
                <form method="GET" action="">
                    <div class="row align-items-end">
                        
                        {{-- Filter Tanggal --}}
                        <div class="col-md-3 mb-2">
                            <label class="form-label small fw-bold">Dari Tanggal (Surat)</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small fw-bold">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>

                        {{-- Filter Tipe Surat (BARU) --}}
                        <div class="col-md-2 mb-2">
                            <label class="form-label small fw-bold">Tipe Surat</label>
                            <select name="tipe_surat" class="form-select">
                                <option value="semua" {{ request('tipe_surat') == 'semua' ? 'selected' : '' }}>Semua</option>
                                <option value="internal" {{ request('tipe_surat') == 'internal' ? 'selected' : '' }}>Internal</option>
                                <option value="eksternal" {{ request('tipe_surat') == 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                            </select>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="col-md-4 mb-2">
                            <div class="d-flex gap-2">
                                {{-- Tombol Cari --}}
                                <button type="submit" formaction="{{ route('adminrektor.arsip.index') }}" class="btn btn-primary btn-sm px-3">
                                    <i class="bi bi-search me-1"></i> Cari
                                </button>
                                
                                {{-- Tombol Reset --}}
                                <a href="{{ route('adminrektor.arsip.index') }}" class="btn btn-secondary btn-sm px-3">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>

                                {{-- Tombol Export --}}
                                <button type="submit" formaction="{{ route('adminrektor.arsip.export') }}" class="btn btn-success btn-sm px-3 text-white">
                                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Excel
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <hr class="text-muted my-3">

            {{-- 2. BAGIAN TABEL --}}
            <div class="table-responsive">
                <table id="tabelArsip" class="table table-hover align-middle table-sm table-bordered" style="width:100%">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>No Agenda/Surat</th>
                            <th>Tipe</th>
                            <th>Perihal</th>
                            <th>Asal Surat</th>
                            <th>Status</th>
                            <th>Tgl Terima</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($arsipSurat as $surat)
                        <tr>
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $surat->no_agenda ?? '-' }}</span><br>
                                <span class="fw-bold text-primary small">{{ $surat->nomor_surat }}</span>
                            </td>
                            
                            <td class="text-center">
                                @if($surat->tipe_surat == 'internal')
                                    <span class="badge bg-info text-white">Internal</span>
                                @else
                                    <span class="badge bg-warning text-dark">Eksternal</span>
                                @endif
                            </td>

                            <td>{{ Str::limit($surat->perihal, 50) }}</td>
                            <td>{{ $surat->surat_dari }}</td>
                            
                            <td class="text-center">
                                <span class="badge bg-success">Arsip Rektor</span>
                            </td>

                            <td class="text-center">{{ \Carbon\Carbon::parse($surat->diterima_tanggal)->format('d/m/Y') }}</td>
                            
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- TOMBOL LIHAT DETAIL --}}
                                    <button type="button" class="btn btn-info btn-sm text-white shadow-sm" 
                                        title="Lihat Detail"
                                        data-bs-toggle="modal" data-bs-target="#detailSuratModal"
                                        data-no-agenda="{{ $surat->no_agenda }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-asal-surat="{{ $surat->surat_dari }}"
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                        data-tujuan="Arsip Rektor (Tidak Disposisi)"
                                        data-file-url="{{ $surat->file_surat ? Storage::url($surat->file_surat) : 'null' }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                {{-- Timeline Riwayat --}}
                                <button type="button" class="btn btn-sm btn-secondary" 
                                    data-bs-toggle="modal" data-bs-target="#riwayatModal"
                                    data-url="{{ route('adminrektor.arsip.riwayat.detail', $surat->id) }}">
                                    <i class="bi bi-clock-history"></i>
                                </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        {{-- Data kosong ditangani DataTables, tapi fallback manual --}}
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 1: Detail Surat --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Surat Arsip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <h4 class="mb-3" id="modal-perihal"></h4>
                        <table class="table table-borderless table-sm small">
                           <tr><td class="info-modal-label">No. Agenda</td><td>: <span id="modal-no-agenda"></span></td></tr>
                           <tr><td class="info-modal-label">Asal Surat</td><td>: <span id="modal-asal-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Tujuan</td><td>: <span id="modal-tujuan" class="fw-bold text-success"></span></td></tr>
                           <tr><td class="info-modal-label">Tanggal Surat</td><td>: <span id="modal-tanggal-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Tanggal Diterima</td><td>: <span id="modal-tanggal-diterima"></span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-7">
                        <div id="modal-file-preview-wrapper" style="height: 70vh; border: 1px solid #dee2e6; border-radius: .375rem; display: flex; align-items: center; justify-content: center;"></div>
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
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="riwayatModalLabel">Riwayat Lengkap Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="riwayatModalBody" style="font-size: 13px;">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status"></div>
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
        // Init DataTable
        new DataTable('#tabelArsip', {
            pagingType: 'simple_numbers',
            searching: false, // Kita pakai search manual controller
            lengthChange: true,
            info: true,
            ordering: false,
            language: {
                search: "Cari:", lengthMenu: "Tampilkan _MENU_ data",
                zeroRecords: "Data tidak ditemukan",
                info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 s/d 0 dari 0 data",
                paginate: { first: "Awal", last: "Akhir", next: "&raquo;", previous: "&laquo;" }
            }
        });

        // Detail Modal Logic
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            // Isi Data Teks
            detailSuratModal.querySelector('#modal-perihal').textContent = button.getAttribute('data-perihal');
            detailSuratModal.querySelector('#modal-no-agenda').textContent = button.getAttribute('data-no-agenda');
            detailSuratModal.querySelector('#modal-asal-surat').textContent = button.getAttribute('data-asal-surat');
            detailSuratModal.querySelector('#modal-tujuan').textContent = button.getAttribute('data-tujuan'); 
            detailSuratModal.querySelector('#modal-tanggal-surat').textContent = button.getAttribute('data-tanggal-surat');
            detailSuratModal.querySelector('#modal-tanggal-diterima').textContent = button.getAttribute('data-tanggal-diterima');
            
            // Setup Download & Preview
            var fileUrl = button.getAttribute('data-file-url');
            var btnDl = detailSuratModal.querySelector('#modal-download-button');
            var previewWrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            
            // Reset Preview
            previewWrapper.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';

            if (fileUrl && fileUrl !== 'null') {
                btnDl.href = fileUrl;
                btnDl.classList.remove('disabled');
                
                var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0]; 
                
                setTimeout(() => {
                    if (extension == 'pdf') {
                        previewWrapper.innerHTML = '<iframe src="' + fileUrl + '" width="100%" height="100%" frameborder="0"></iframe>';
                    } else if (['jpg', 'jpeg', 'png', 'bmp'].includes(extension)) {
                        previewWrapper.innerHTML = '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 70vh; object-fit: contain; width: 100%;">';
                    } else {
                        previewWrapper.innerHTML = '<div class="text-center text-muted"><i class="bi bi-file-earmark-x fs-1"></i><p>Preview tidak tersedia.</p></div>';
                    }
                }, 300);
            } else {
                btnDl.href = '#';
                btnDl.classList.add('disabled');
                previewWrapper.innerHTML = '<div class="text-muted fst-italic">Tidak ada file lampiran.</div>';
            }
        });

       // --- LOGIKA RIWAYAT MODAL (PERBAIKAN) ---
var riwayatModal = document.getElementById('riwayatModal');
riwayatModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
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

    // 2. Fetch Data dari Controller
    fetch(dataUrl)
        .then(response => {
            if (!response.ok) { throw new Error('Network response was not ok'); }
            return response.json();
        })
        .then(surat => {
            // Update Judul Modal
            modalLabel.textContent = 'Riwayat: ' + surat.nomor_surat;

            // 3. Render HTML Timeline
            var html = '<ul class="timeline">';
            
            if (surat.riwayats && surat.riwayats.length > 0) {
                surat.riwayats.forEach((item) => {
                    // Tentukan Warna Badge & Icon
                    var badgeColor = 'primary'; 
                    var iconClass = 'bi-check';

                    if (item.status_aksi.includes('Selesai') || item.status_aksi.includes('Arsip')) {
                        badgeColor = 'success'; iconClass = 'bi-archive-fill';
                    } else if (item.status_aksi.includes('Disposisi') || item.status_aksi.includes('Diteruskan')) {
                        badgeColor = 'warning'; iconClass = 'bi-arrow-right';
                    } else if (item.status_aksi.includes('Masuk') || item.status_aksi.includes('Input')) {
                        badgeColor = 'info'; iconClass = 'bi-envelope-plus';
                    }

                    // Format Tanggal (Helper Function)
                    var dateObj = new Date(item.created_at);
                    var dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

                    // Nama User (Cek null safety)
                    var userName = item.user ? item.user.name : 'Sistem';

                    // Susun HTML Item
                    html += `
                        <li>
                            <div class="timeline-badge ${badgeColor}"><i class="bi ${iconClass}"></i></div>
                            <div class="timeline-panel">
                                <div class="timeline-heading">
                                    <h6 class="timeline-title">${item.status_aksi}</h6>
                                    <p class="mb-0"><small class="text-muted"><i class="bi bi-clock"></i> ${dateStr} &bull; Oleh: <strong>${userName}</strong></small></p>
                                </div>
                                <div class="timeline-body mt-2">
                                    <p class="mb-0 text-dark">${item.catatan ?? '-'}</p>
                                </div>
                            </div>
                        </li>
                    `;
                });
            } else {
                html += '<li class="text-center text-muted p-3">Belum ada riwayat aktivitas untuk surat ini.</li>';
            }

            html += '</ul>';
            modalBody.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<div class="alert alert-danger">Gagal memuat data riwayat. Silakan coba lagi.</div>';
        });
});
    });
</script>
@endpush