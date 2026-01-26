@extends('layouts.app')

@push('styles')
{{-- DataTables Bootstrap 5 CSS --}}
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* Konsistensi Font & Global */
    body { font-family: 'Nunito', sans-serif; background-color: #f8f9fc; }
    #tabelSurat, .dataTables_wrapper, .form-label, .btn-sm, .form-control { font-size: 13px !important; }
    
    /* Table Styling */
    .table-custom thead th {
        background-color: #f8f9fc; color: #3a3b45; font-weight: 700;
        border-bottom: 2px solid #e3e6f0; padding: 12px;
    }
    
    .badge-via {
        background-color: #fff3cd; border: 1px solid #ffecb5; color: #856404;
        font-size: 11px; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;
    }

    /* Timeline CSS (Konsisten dengan Disposisi/Arsip) */
    .timeline { list-style: none; padding: 0; position: relative; }
    .timeline:before {
        top: 0; bottom: 0; position: absolute; content: " "; width: 3px;
        background-color: #eeeeee; left: 30px; margin-left: -1.5px;
    }
    .timeline > li { margin-bottom: 20px; position: relative; }
    .timeline > li > .timeline-panel {
        width: calc(100% - 75px); float: right; padding: 15px;
        border: 1px solid #d4d4d4; border-radius: 5px;
        position: relative; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        background: #fff;
    }
    .timeline > li > .timeline-badge {
        color: #fff; width: 50px; height: 50px; line-height: 50px;
        font-size: 1.2em; text-align: center; position: absolute;
        top: 16px; left: 15px; margin-left: -10px;
        z-index: 100; border-radius: 50%;
    }
    .timeline-badge.primary { background-color: #0d6efd !important; }
    .timeline-badge.success { background-color: #198754 !important; }
    .timeline-badge.info { background-color: #0dcaf0 !important; }
    .timeline-badge.warning { background-color: #ffc107 !important; }
    .timeline-heading h6 { font-weight: bold; font-size: 14px; margin-bottom: 5px; }

    /* Detail Modal Label */
    .info-modal-label { width: 140px; font-weight: 600; color: #4e73df; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 pt-2">

    {{-- 1. SECTION FILTER & EXPORT --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <form action="{{ route('adminrektor.surat-keluar-eksternal.index') }}" method="GET">
                <div class="row align-items-end g-2">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Cari</button>
                            <a href="{{ route('adminrektor.surat-keluar-eksternal.index') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('adminrektor.surat-keluar-eksternal.export', request()->query()) }}" class="btn btn-success btn-sm text-white fw-bold">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('adminrektor.surat-keluar-eksternal.create') }}" class="btn btn-primary btn-sm text-white fw-bold ms-1">
                            <i class="bi bi-plus-lg me-1"></i> Buat Surat
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. SECTION TABEL --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSurat" class="table table-hover align-middle table-bordered w-100">
                    <thead class="table-light text-center text-uppercase">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Tujuan Eksternal</th>
                            <th width="25%">No. Surat & Perihal</th>
                            <th width="15%">Status</th>
                            <th width="15%">Waktu Pengajuan</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                   <tbody>
    @foreach($suratKeluar as $surat)
    <tr>
        <td class="text-center fw-bold">{{ $loop->iteration }}</td>
        <td>
            <div class="fw-bold text-dark">{{ $surat->tujuan_luar ?? '-' }}</div>
            <div class="badge-via"><i class="bi bi-globe me-1"></i> Eksternal</div>
            {{-- Menampilkan via pengiriman jika ada --}}
            @if($surat->via)
                <br><small class="text-muted"><i class="bi bi-truck me-1"></i> Via: {{ $surat->via }}</small>
            @endif
        </td>
        <td>
            <span class="text-primary fw-bold">{{ $surat->nomor_surat }}</span>
            <div class="text-muted small">{{ Str::limit($surat->perihal, 35) }}</div>
        </td>
        <td class="text-center">
            @if($surat->status == 'pending')
                <span class="badge bg-warning text-dark shadow-sm">
                    <i class="bi bi-clock-history me-1"></i> Menunggu BAU
                </span>
            @elseif($surat->status == 'proses')
                <span class="badge bg-info text-white shadow-sm">
                    <i class="bi bi-gear-wide-connected me-1"></i> Diproses BAU
                </span>
            @elseif($surat->status == 'selesai')
                <span class="badge bg-success text-white shadow-sm">
                    <i class="bi bi-check-circle-fill me-1"></i> Selesai
                </span>
            @endif
        </td>
        <td class="text-center small">
            {{ $surat->created_at->isoFormat('D MMM Y') }}<br>
            <strong class="text-dark">{{ $surat->created_at->isoFormat('HH.mm') }} WIB</strong>
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                {{-- TOMBOL LIHAT DETAIL --}}
                <button type="button" class="btn btn-primary btn-sm text-white shadow-sm" 
                    title="Lihat Detail" data-bs-toggle="modal" data-bs-target="#detailSuratModal"
                    data-nomor="{{ $surat->nomor_surat }}"
                    data-perihal="{{ $surat->perihal }}"
                    data-tujuan="{{ $surat->tujuan_luar }}"
                    data-via="{{ $surat->via ?? '-' }}"
                    data-tgl-surat="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->isoFormat('D MMMM Y') }}"
                    data-tgl-input="{{ $surat->created_at->isoFormat('D MMMM Y, hh.mm') }} WIB"
                    data-file-url="{{ Storage::url($surat->file_surat) }}">
                    <i class="bi bi-eye-fill"></i>
                </button>

                {{-- TOMBOL RIWAYAT --}}
                <button type="button" class="btn btn-info btn-sm text-white shadow-sm" 
                    title="Lihat Riwayat" data-bs-toggle="modal" data-bs-target="#riwayatModal" 
                    data-url="{{ route('adminrektor.surat-keluar-eksternal.log', $surat->id) }}">
                    <i class="bi bi-clock-history"></i>
                </button>

                {{-- Logika Tombol Edit/Hapus: Hanya muncul jika status masih PENDING --}}
                @if($surat->status == 'pending')
                    <a href="{{ route('adminrektor.surat-keluar-eksternal.edit', $surat->id) }}" class="btn btn-warning btn-sm text-white shadow-sm" title="Edit">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                    <form action="{{ route('adminrektor.surat-keluar-eksternal.destroy', $surat->id) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm shadow-sm" title="Hapus" onclick="return confirm('Hapus permohonan surat ini?')">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>
                @else
                    {{-- Tombol Terkunci jika sudah diproses atau selesai --}}
                    <button class="btn btn-secondary btn-sm disabled shadow-sm" title="Data sudah diproses, tidak bisa diubah">
                        <i class="bi bi-lock-fill"></i>
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

{{-- MODAL 1: DETAIL SURAT (KONSISTEN) --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Detail Permohonan Surat</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-5">
                        <h5 class="fw-bold text-primary mb-3" id="modal-perihal"></h5>
                        <hr>
                        <table class="table table-sm table-borderless">
                            <tr><td class="info-modal-label">Nomor Surat</td><td>: <span id="modal-nomor" class="fw-bold"></span></td></tr>
                            <tr><td class="info-modal-label">Tujuan</td><td>: <span id="modal-tujuan"></span></td></tr>
                            <tr><td class="info-modal-label">Tanggal Surat</td><td>: <span id="modal-tgl-surat"></span></td></tr>
                            <tr><td class="info-modal-label">Waktu Input</td><td>: <span id="modal-tgl-input"></span></td></tr>
                        </table>
                    </div>
                    <div class="col-lg-7">
                        <div id="modal-preview-wrapper" style="height: 500px; background: #eee; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            {{-- Preview Loaded Here --}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="modal-download-link" class="btn btn-primary btn-sm" download><i class="bi bi-download me-1"></i> Unduh File</a>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 2: RIWAYAT TIMELINE (KONSISTEN) --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title fw-bold" id="riwayatModalLabel"><i class="bi bi-clock-history me-2"></i>Riwayat Aktivitas</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light" id="riwayatModalBody" style="max-height: 500px; overflow-y: auto;">
                {{-- Content Loaded via AJAX --}}
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
    // 1. Init DataTable
    $('#tabelSurat').DataTable({ ordering: false, language: { search: "Cari Cepat:", lengthMenu: "Tampilkan _MENU_ data" }});

    // 2. Logic Modal Detail & Preview
    var detailModal = document.getElementById('detailSuratModal');
    detailModal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        var fileUrl = btn.getAttribute('data-file-url');
        
        document.getElementById('modal-perihal').textContent = btn.getAttribute('data-perihal');
        document.getElementById('modal-nomor').textContent = btn.getAttribute('data-nomor');
        document.getElementById('modal-tujuan').textContent = btn.getAttribute('data-tujuan');
        document.getElementById('modal-tgl-surat').textContent = btn.getAttribute('data-tgl-surat');
        document.getElementById('modal-tgl-input').textContent = btn.getAttribute('data-tgl-input');
        document.getElementById('modal-download-link').href = fileUrl;
        if(document.getElementById('modal-via')) {
    document.getElementById('modal-via').textContent = btn.getAttribute('data-via');
}

        var wrapper = document.getElementById('modal-preview-wrapper');
        wrapper.innerHTML = '<div class="spinner-border text-primary"></div>';
        
        var ext = fileUrl.split('.').pop().toLowerCase();
        setTimeout(() => {
            if(ext === 'pdf'){
                wrapper.innerHTML = `<iframe src="${fileUrl}" width="100%" height="100%" style="border:none;"></iframe>`;
            } else if(['jpg','jpeg','png'].includes(ext)){
                wrapper.innerHTML = `<img src="${fileUrl}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
            } else {
                wrapper.innerHTML = '<div class="text-muted small">Preview tidak tersedia untuk format ini.</div>';
            }
        }, 300);
    });

    // 3. Logic Modal Riwayat Timeline
    var riwayatModal = document.getElementById('riwayatModal');
    riwayatModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var dataUrl = button.getAttribute('data-url');
        var modalBody = riwayatModal.querySelector('#riwayatModalBody');

        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';

        fetch(dataUrl)
            .then(response => response.json())
            .then(data => {
              // Di dalam fetch(dataUrl).then(data => { ...
var html = '<ul class="timeline">';
data.riwayats.forEach((item) => {
    // Tentukan warna dan ikon berdasarkan teks status
    var badgeColor = 'info';
    var icon = 'bi-plus-lg';
    
    var statusText = item.status_aksi.toLowerCase();
    
    if (statusText.includes('proses')) {
        badgeColor = 'warning';
        icon = 'bi-gear-fill';
    } else if (statusText.includes('selesai') || statusText.includes('arsip')) {
        badgeColor = 'success';
        icon = 'bi-check-all';
    } else if (statusText.includes('buat') || statusText.includes('permohonan')) {
        badgeColor = 'primary';
        icon = 'bi-file-earmark-text';
    }

    var dateObj = new Date(item.created_at);
var dateStr = item.tanggal_f;

    html += `
        <li>
            <div class="timeline-badge ${badgeColor}"><i class="bi ${icon}"></i></div>
            <div class="timeline-panel shadow-sm">
                <div class="timeline-heading">
                    <h6 class="text-primary fw-bold">${item.status_aksi}</h6>
                    <p class="mb-0 small text-muted">
                        <i class="bi bi-clock me-1"></i> ${dateStr} &bull; Oleh: <strong>${item.user_name}</strong>
                    </p>
                </div>
                <div class="timeline-body mt-2 text-dark small">
                    ${item.catatan ?? '-'}
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