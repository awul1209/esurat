@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    body { font-family: 'Nunito', sans-serif; background-color: #f8f9fc; }
    #tabelArsip, .dataTables_wrapper, .form-control, .btn-sm { font-size: 13px !important; }
    .info-modal-label { width: 140px; font-weight: 600; color: #4e73df; }
    
    /* CSS Timeline Konsisten */
    .timeline { list-style: none; padding: 0; position: relative; }
    .timeline:before {
        top: 0; bottom: 0; position: absolute; content: " "; width: 3px;
        background-color: #eeeeee; left: 30px; margin-left: -1.5px;
    }
    .timeline > li { margin-bottom: 20px; position: relative; }
    .timeline > li > .timeline-panel {
        width: calc(100% - 75px); float: right; padding: 15px;
        border: 1px solid #d4d4d4; border-radius: 5px;
        position: relative; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); background: #fff;
    }
    .timeline > li > .timeline-badge {
        color: #fff; width: 50px; height: 50px; line-height: 50px;
        font-size: 1.2em; text-align: center; position: absolute;
        top: 16px; left: 15px; margin-left: -10px; z-index: 100; border-radius: 50%;
    }
    .timeline-badge.info { background-color: #0dcaf0 !important; }
    .timeline-badge.success { background-color: #198754 !important; }
    .timeline-badge.primary { background-color: #0d6efd !important; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 pt-2">
    
    {{-- 1. SECTION FILTER & EXPORT --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <form action="{{ route('bau.arsip-rektor.eksternal') }}" method="GET">
                <div class="row align-items-end g-2">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Cari</button>
                            <a href="{{ route('bau.arsip-rektor.eksternal') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="submit" formaction="{{ route('bau.arsip-rektor.eksternal.export') }}" class="btn btn-success btn-sm fw-bold shadow-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export Arsip
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. SECTION TABEL --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-success"><i class="bi bi-archive-fill me-2"></i>Arsip Surat Keluar Rektor (Selesai)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelArsip" class="table table-hover align-middle table-bordered w-100">
                    <thead class="table-light text-center text-uppercase">
                        <tr>
                            <th width="5%">No</th>
                            <th>No. Surat & Perihal</th>
                            <th>Tujuan / Via</th>
                            <th>Waktu Selesai</th>
                            <th width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($arsip as $row)
                        <tr>
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            <td>
                                <span class="text-primary fw-bold">{{ $row->nomor_surat }}</span>
                                <div class="text-muted small">{{ Str::limit($row->perihal, 50) }}</div>
                            </td>
                            <td>
                                <strong>{{ $row->tujuan_luar }}</strong><br>
                                <span class="badge bg-light text-muted border small"><i class="bi bi-truck me-1"></i>{{ $row->via ?? 'Lainnya' }}</span>
                            </td>
                            <td class="text-center text-muted">
                                <small>{{ $row->updated_at->isoFormat('D MMM Y') }}</small><br>
                                <strong class="text-dark">{{ $row->updated_at->isoFormat('HH.mm') }} WIB</strong>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- Log Riwayat --}}
<button type="button" class="btn btn-info btn-sm text-white shadow-sm" 
    title="Lihat Riwayat"
    data-bs-toggle="modal" 
    data-bs-target="#riwayatModal"
    data-url="{{ route('bau.arsip-rektor.log', $row->id) }}">
    <i class="bi bi-clock-history"></i>
</button>

                                    {{-- Detail Preview --}}
                                    <button type="button" class="btn btn-primary btn-sm shadow-sm" title="Detail & Preview"
                                        data-bs-toggle="modal" data-bs-target="#detailModal" 
                                        data-nomor="{{ $row->nomor_surat }}" 
                                        data-perihal="{{ $row->perihal }}" 
                                        data-tujuan="{{ $row->tujuan_luar }}"
                                        data-via="{{ $row->via ?? '-' }}" 
                                        data-tgl-surat="{{ \Carbon\Carbon::parse($row->tanggal_surat)->isoFormat('D MMMM Y') }}"
                                        data-tgl-input="{{ $row->created_at->isoFormat('D MMMM Y, HH.mm') }} WIB" 
                                        data-file-url="{{ Storage::url($row->file_surat) }}">
                                        <i class="bi bi-eye"></i>
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

{{-- MODAL 1: PREVIEW DETAIL --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Detail Arsip Surat Rektor</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-4">
                        <table class="table table-sm table-borderless small">
                            <tr><td class="info-modal-label">Nomor Surat</td><td>: <span id="m-nomor" class="fw-bold text-dark"></span></td></tr>
                            <tr><td class="info-modal-label">Tujuan</td><td>: <span id="m-tujuan"></span></td></tr>
                            <tr><td class="info-modal-label">Kirim Via</td><td>: <span id="m-via" class="badge bg-light text-dark border"></span></td></tr>
                            <tr><td class="info-modal-label">Perihal</td><td>: <span id="m-perihal"></span></td></tr>
                            <tr><td class="info-modal-label">Tanggal Surat</td><td>: <span id="m-tgl-surat"></span></td></tr>
                            <tr><td class="info-modal-label">Waktu Masuk</td><td>: <span id="m-tgl-input"></span></td></tr>
                        </table>
                    </div>
                    <div class="col-lg-8">
                        <div id="m-preview" style="height: 500px; background: #eee; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="m-download" class="btn btn-primary btn-sm" download><i class="bi bi-download me-1"></i> Download File</a>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 2: RIWAYAT (TIMELINE) --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Riwayat Log Surat</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light" id="riwayatModalBody" style="max-height: 500px; overflow-y: auto;">
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
    $('#tabelArsip').DataTable({ 
        ordering: false,
        language: { search: "Cari Cepat:", lengthMenu: "Tampilkan _MENU_ data" }
    });

   // --- LOGIKA RIWAYAT ARSIP ---
var riwayatModal = document.getElementById('riwayatModal');
riwayatModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var dataUrl = button.getAttribute('data-url');
    var modalBody = riwayatModal.querySelector('#riwayatModalBody');

    // Menampilkan loading spinner
    modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat riwayat...</p></div>';

    fetch(dataUrl)
        .then(response => response.json())
        .then(data => {
            var html = '<ul class="timeline">';
            
         // Ganti bagian looping di JS Anda menjadi ini:
data.riwayats.forEach((item) => {
    // Gunakan langsung item.tanggal_f dari PHP
    var dateStr = item.tanggal_f; 

    html += `
        <li>
            <div class="timeline-badge info"><i class="bi bi-clock"></i></div>
            <div class="timeline-panel shadow-sm">
                <div class="timeline-heading">
                    <h6 class="text-primary fw-bold">${item.status_aksi}</h6>
                    <p class="mb-0 small text-muted">
                        <i class="bi bi-clock"></i> ${dateStr} &bull; Oleh: <strong>${item.user_name}</strong>
                    </p>
                </div>
                <div class="timeline-body mt-2 small text-dark">${item.catatan}</div>
            </div>
        </li>`;
});

            html += '</ul>';
            modalBody.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<div class="alert alert-danger">Gagal memuat data riwayat.</div>';
        });
});

    // --- LOGIKA DETAIL & PREVIEW ---
    var detailModal = document.getElementById('detailModal');
    detailModal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        var fileUrl = btn.getAttribute('data-file-url');
        
        document.getElementById('m-nomor').textContent = btn.getAttribute('data-nomor');
        document.getElementById('m-tujuan').textContent = btn.getAttribute('data-tujuan');
        document.getElementById('m-perihal').textContent = btn.getAttribute('data-perihal');
        document.getElementById('m-via').textContent = btn.getAttribute('data-via');
        document.getElementById('m-tgl-surat').textContent = btn.getAttribute('data-tgl-surat');
        document.getElementById('m-tgl-input').textContent = btn.getAttribute('data-tgl-input');
        document.getElementById('m-download').href = fileUrl;

        var wrapper = document.getElementById('m-preview');
        wrapper.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
        
        var ext = fileUrl.split('.').pop().toLowerCase();
        setTimeout(() => {
            if(ext === 'pdf'){
                wrapper.innerHTML = `<iframe src="${fileUrl}" width="100%" height="100%" style="border:none;"></iframe>`;
            } else if(['jpg','jpeg','png'].includes(ext)) {
                wrapper.innerHTML = `<img src="${fileUrl}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
            } else {
                wrapper.innerHTML = '<div class="text-center text-muted small">Preview tidak tersedia</div>';
            }
        }, 300);
    });
});
</script>
@endpush