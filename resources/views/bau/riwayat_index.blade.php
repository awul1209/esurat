@extends('layouts.app')

@push('styles')
{{-- DataTables & Select2 CSS --}}
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* CSS 13px untuk konsistensi */
    #tabelRiwayat, .dataTables_wrapper, .form-label, .form-control, .form-select, .btn-sm { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    
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
<div class="container-fluid px-3">
    
    {{-- CARD UTAMA --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-table me-1"></i> Data & Rekap Surat</h6>
        </div>
        
        <div class="card-body">
            
            {{-- 1. BAGIAN FILTER --}}
            <div class="p-3 mb-3 bg-light rounded border border-light">
                <form method="GET" action="" id="filterForm">
                    <div class="row align-items-end">
                        
                        {{-- Filter Tanggal --}}
                        <div class="col-md-3 mb-2">
                            <label class="form-label small fw-bold">Dari Tanggal</label>
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
                                <button type="submit" formaction="{{ route('bau.riwayat.index') }}" class="btn btn-primary btn-sm px-3">
                                    <i class="bi bi-search me-1"></i> Cari
                                </button>
                                
                                <a href="{{ route('bau.riwayat.index') }}" class="btn btn-secondary btn-sm px-3">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>

                                <button type="submit" formaction="{{ route('bau.riwayat.export') }}" class="btn btn-success btn-sm px-3 text-white">
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
                <table id="tabelRiwayat" class="table table-hover align-middle table-sm table-bordered" style="width:100%">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>No Agenda/Surat</th>
                            <th>Tipe</th> {{-- KOLOM TIPE SURAT --}}
                            <th>Perihal</th>
                            <th>Asal Surat</th>
                            <th>Status</th>
                            <th>Tujuan Akhir</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratSelesai as $surat) 
                        @php
                            // LOGIKA TUJUAN
                            $tipe = $surat->tujuan_tipe;
                            $tujuanAkhirHTML = '-';
                            $tujuanModalText = '-';

                            if (empty($tipe)) {
                                if ($surat->tujuan_satker_id) { $tipe = 'satker'; } 
                                elseif ($surat->tujuan_user_id) { $tipe = 'pegawai'; } 
                                else { $tipe = 'universitas'; }
                            }

                            if ($tipe == 'rektor') {
                                $tujuanAkhirHTML = '<span class="fw-bold text-primary">Rektor</span>';
                                $tujuanModalText = 'Rektor';
                            } elseif ($tipe == 'universitas') {
                                $listDisposisi = $surat->disposisis;
                                $namaTujuansHTML = []; $namaTujuansText = [];
                                foreach($listDisposisi as $d) {
                                    if ($d->tujuanSatker) {
                                        $namaTujuansHTML[] = '<span class="text-primary fw-bold">' . $d->tujuanSatker->nama_satker . '</span>';
                                        $namaTujuansText[] = $d->tujuanSatker->nama_satker;
                                    } elseif ($d->disposisi_lain) {
                                        $namaTujuansHTML[] = '<span class="text-dark fst-italic">' . $d->disposisi_lain . '</span>';
                                        $namaTujuansText[] = $d->disposisi_lain;
                                    }
                                }
                                if (count($namaTujuansHTML) > 0) {
                                    $penerimaHTML = implode(', ', $namaTujuansHTML);
                                    $penerimaText = implode(', ', $namaTujuansText);
                                    $tujuanAkhirHTML = '<span class="fw-bold text-primary">Universitas</span><br><small class="text-muted">Disp: ' . $penerimaHTML . '</small>';
                                    $tujuanModalText = 'Universitas (Disp: ' . $penerimaText . ')';
                                } else {
                                    $tujuanAkhirHTML = '<span class="fw-bold text-primary">Universitas</span><br><small class="text-muted">Arsip Rektor</small>';
                                    $tujuanModalText = 'Universitas (Arsip Rektor)';
                                }
                            } elseif ($tipe == 'satker') {
                                $nama = $surat->tujuanSatker->nama_satker ?? 'Satker Tidak Ditemukan';
                                $tujuanAkhirHTML = '<span class="fw-bold text-success">' . $nama . '</span> <small>(Lgsg)</small>';
                                $tujuanModalText = $nama;
                            } elseif ($tipe == 'pegawai') {
                                $nama = $surat->tujuanUser->name ?? 'Pegawai Tidak Ditemukan';
                                $tujuanAkhirHTML = '<span class="fw-bold text-info">' . $nama . '</span> <small>(Ybs)</small>';
                                $tujuanModalText = $nama;
                            }
                        @endphp

                        <tr>
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ $surat->no_agenda }} <br> {{ $surat->nomor_surat }}</td>
                            
                            {{-- Kolom Tipe --}}
                            <td class="text-center">
                                @if($surat->tipe_surat == 'internal')
                                    <span class="badge text-bg-info text-white">Internal</span>
                                @else
                                    <span class="badge text-bg-warning text-dark">Eksternal</span>
                                @endif
                            </td>

                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->surat_dari }}</td>
                            <td class="text-center">
                                @if(in_array($surat->status, ['selesai_edaran', 'selesai', 'arsip_satker', 'disimpan', 'diarsipkan', 'di_satker']))
                                    <span class="badge text-bg-success">Selesai</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $surat->status }}</span>
                                @endif
                            </td>
                            <td style="font-size: 12px;">{!! $tujuanAkhirHTML !!}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- View --}}
                                    <button type="button" class="btn btn-sm btn-info text-white" 
                                        data-bs-toggle="modal" data-bs-target="#detailSuratModal"
                                        data-no-agenda="{{ $surat->no_agenda }}"
                                        data-perihal="{{ $surat->perihal }}"
                                        data-asal-surat="{{ $surat->surat_dari }}"
                                        data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                        data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                        data-tujuan="{{ $tujuanModalText }}"
                                        data-file-url="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    {{-- Cetak --}}
                                    <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak Disposisi">
                                        <i class="bi bi-printer-fill"></i>
                                    </a>
                                    
                                    {{-- Timeline --}}
                                    <button type="button" class="btn btn-sm btn-secondary" 
                                        data-bs-toggle="modal" data-bs-target="#riwayatModal"
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
                        <h4 class="mb-3" id="modal-perihal"></h4>
                        <table class="table table-borderless table-sm small">
                           <tr><td class="info-modal-label">No. Agenda</td><td>: <span id="modal-no-agenda"></span></td></tr>
                           <tr><td class="info-modal-label">Asal Surat</td><td>: <span id="modal-asal-surat"></span></td></tr>
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

{{-- MODAL 2: Riwayat LOG --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="riwayatModalLabel">Riwayat Lengkap Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="riwayatModalBody" style="font-size: 13px;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    function formatTanggal(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        }) + ' WIB';
    }

    $(document).ready(function () {
        $('#tabelRiwayat').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
            ordering: false 
        });

        // Detail Modal
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            detailSuratModal.querySelector('#modal-perihal').textContent = button.getAttribute('data-perihal');
            detailSuratModal.querySelector('#modal-no-agenda').textContent = button.getAttribute('data-no-agenda');
            detailSuratModal.querySelector('#modal-asal-surat').textContent = button.getAttribute('data-asal-surat');
            detailSuratModal.querySelector('#modal-tujuan').textContent = button.getAttribute('data-tujuan'); 
            detailSuratModal.querySelector('#modal-tanggal-surat').textContent = button.getAttribute('data-tanggal-surat');
            detailSuratModal.querySelector('#modal-tanggal-diterima').textContent = button.getAttribute('data-tanggal-diterima');
            
            var fileUrl = button.getAttribute('data-file-url');
            var btnDl = detailSuratModal.querySelector('#modal-download-button');
            btnDl.href = fileUrl;
            
            var extension = fileUrl.split('.').pop().toLowerCase().split('?')[0]; 
            var html = (extension == 'pdf') 
                ? '<iframe src="' + fileUrl + '" width="100%" height="100%" frameborder="0"></iframe>' 
                : '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 70vh; object-fit: contain; width: 100%;">';
            
            detailSuratModal.querySelector('#modal-file-preview-wrapper').innerHTML = html;
        });

      // Riwayat Modal
var riwayatModal = document.getElementById('riwayatModal');
riwayatModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var dataUrl = button.getAttribute('data-url');
    var modalBody = riwayatModal.querySelector('#riwayatModalBody');
    var modalLabel = riwayatModal.querySelector('#riwayatModalLabel');

    modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-2">Memuat...</p></div>';
    
    fetch(dataUrl)
        .then(response => response.json())
        .then(surat => {
            modalLabel.textContent = 'Riwayat Surat: ' + surat.perihal;
            var html = '<ul class="timeline">';
            
            // Tambahkan filter .filter() sebelum .forEach() sebagai pengaman tambahan
            surat.riwayats.filter(item => {
                const aksi = item.status_aksi.toLowerCase();
                // HANYA tampilkan jika BUKAN informasi umum atau delegasi pegawai
        return !aksi.includes('informasi umum') && !aksi.includes('delegasi') && !aksi.includes('diarsipkan/selesai di satker');
            }).forEach((item) => {
                var badge = 'primary'; var icon = 'bi-check';
                
                if (item.status_aksi.includes('Selesai')) badge = 'success';
                else if (item.status_aksi.includes('Diteruskan')) badge = 'warning';
                
                if (item.status_aksi.includes('Input')) icon = 'bi-pencil-fill';
                else if (item.status_aksi.includes('Rektor')) icon = 'bi-person-workspace';
                else if (item.status_aksi.includes('Dikirim ke Satker')) icon = 'bi-send-check-fill'; // Ikon tambahan untuk estetika

                html += `<li>
                    <div class="timeline-badge ${badge}"><i class="bi ${icon}"></i></div>
                    <div class="timeline-panel">
                        <div class="timeline-heading">
                            <h6 class="timeline-title">${item.status_aksi}</h6>
                            <p class="mb-0">
                                <small class="text-muted">
                                    <i class="bi bi-clock-fill"></i> 
                                    ${item.tanggal_f ? item.tanggal_f : item.created_at}
                                    <br>
                                    <i class="bi bi-person-fill"></i> ${item.user ? item.user.name : 'Sistem'}
                                </small>
                            </p>
                        </div>
                        <div class="timeline-body mt-2">
                            <p class="mb-0 text-dark">${item.catatan ?? '-'}</p>
                        </div>
                    </div>
                </li>`;
            });
            
            html += '</ul>';
            modalBody.innerHTML = html;
        });
});
    });
</script>
@endpush