@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    body { font-family: 'Nunito', sans-serif; background-color: #f8f9fc; }
    #tabelArsip, .btn-sm { font-size: 13px !important; }
    .info-modal-label { width: 140px; font-weight: 600; color: #4e73df; }
    
    /* CSS Timeline Riwayat */
    .timeline { list-style: none; padding: 0; position: relative; }
    .timeline:before {
        top: 0; bottom: 0; position: absolute; content: " "; width: 2px;
        background-color: #e9ecef; left: 20px;
    }
    .timeline > li { margin-bottom: 15px; position: relative; padding-left: 45px; }
    .timeline > li > .timeline-badge {
        width: 30px; height: 30px; line-height: 30px; font-size: 14px;
        text-align: center; position: absolute; left: 5px; top: 0;
        border-radius: 50%; color: #fff; z-index: 10;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 pt-2">
    {{-- Filter Section --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
           <form action="{{ route('bau.arsip-rektor.internal') }}" method="GET">
    <div class="row align-items-end g-2">
        <div class="col-md-3">
            <label class="form-label fw-bold small">Dari Tanggal (Selesai)</label>
            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold small">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
        </div>
        <div class="col-md-3">
            <div class="btn-group w-100">
                {{-- TOMBOL FILTER --}}
                <button type="submit" class="btn btn-primary btn-sm me-1">
                    <i class="bi bi-search"></i> Filter
                </button>
                {{-- TOMBOL RESET --}}
                <a href="{{ route('bau.arsip-rektor.internal') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </div>
        <div class="col-md-3 text-end">
            {{-- TOMBOL EXPORT --}}
            <button type="submit" formaction="{{ route('bau.arsip-rektor.internal.export') }}" class="btn btn-success btn-sm w-100">
                <i class="bi bi-file-earmark-excel me-1"></i> Export ke Excel (.csv)
            </button>
        </div>
    </div>
</form>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-success"><i class="bi bi-archive-fill me-2"></i>Arsip Surat Keluar Internal Rektor (Selesai)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelArsip" class="table table-hover align-middle border w-100">
    <thead class="table-light text-uppercase small">
        <tr>
            <th width="5%">No</th>
            <th>Info Surat</th> {{-- Gabungan Nomor & Perihal --}}
            <th>Tujuan Satker</th>
            <th>Tgl Surat</th> {{-- Kolom Baru --}}
            <th>Waktu Masuk</th>
            <th class="text-success">Waktu Selesai</th>
            <th width="10%">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($arsip as $row)
        <tr>
            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
            <td>
                <span class="text-primary fw-bold">{{ $row->nomor_surat }}</span>
                <div class="text-muted small">{{ Str::limit($row->perihal, 45) }}</div>
            </td>
            <td>
                @foreach($row->penerimaInternal as $satker)
                    <span class="badge bg-light text-dark border mb-1" style="font-size: 10px;">{{ $satker->nama_satker }}</span>
                @endforeach
            </td>
            {{-- Kolom Tanggal Surat --}}
            <td>
                <small class="fw-bold">{{ \Carbon\Carbon::parse($row->tanggal_surat)->format('d/m/Y') }}</small>
            </td>
            {{-- Kolom Waktu Masuk (Dibuat Rektor) --}}
            <td>
                <small>{{ $row->created_at->format('d/m/Y H:i') }}</small>
            </td>
            {{-- Kolom Waktu Selesai (Diteruskan BAU) --}}
            <td class="fw-bold text-success">
                @if($row->tanggal_terusan)
                    <small>{{ \Carbon\Carbon::parse($row->tanggal_terusan)->format('d/m/Y H:i') }} WIB</small>
                @elseif($row->status == 'selesai')
                    {{-- Fallback jika tanggal_terusan null tapi status sudah selesai --}}
                    <small>{{ $row->updated_at->format('d/m/Y H:i') }} WIB*</small>
                @else
                    <small class="text-muted">-</small>
                @endif
            </td>
            <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
<button type="button" class="btn btn-info btn-sm text-white" 
    data-bs-toggle="modal" 
    data-bs-target="#riwayatModal" 
    data-url="{{ route('bau.arsip-rektor.log-internal', $row->id) }}"> {{-- Pakai log-internal --}}
    <i class="bi bi-clock-history"></i>
</button>
<button type="button" class="btn btn-primary btn-sm" 
    data-bs-toggle="modal" data-bs-target="#previewModal" 
    data-nomor="{{ $row->nomor_surat }}" 
    data-perihal="{{ $row->perihal }}"
    data-tgl="{{ \Carbon\Carbon::parse($row->tanggal_surat)->format('d F Y') }}"
    data-satker="{{ $row->penerimaInternal->pluck('nama_satker')->implode(',') }}" {{-- Data Satker --}}
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

{{-- MODAL PREVIEW FILE & DETAIL --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white p-2 px-3">
                <h6 class="modal-title fw-bold small"><i class="bi bi-file-earmark-text me-2"></i>Detail & Preview Arsip Internal</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    {{-- Sisi Kiri: Detail Surat --}}
                    <div class="col-lg-4 border-end bg-light">
                        <div class="p-4">
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Informasi Surat</h6>
                            <div class="mb-3">
                                <label class="text-muted small d-block">Nomor Surat</label>
                                <span id="det-nomor" class="fw-bold text-dark">-</span>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small d-block">Tanggal Surat</label>
                                <span id="det-tgl" class="text-dark">-</span>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small d-block">Perihal</label>
                                <p id="det-perihal" class="text-dark mb-0" style="font-size: 14px; line-height: 1.5;">-</p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small d-block">Tujuan Satker</label>
                                <div id="det-satker" class="d-flex flex-wrap gap-1 mt-1">
                                    {{-- List Satker akan diisi via JS --}}
                                </div>
                            </div>
                            <hr>
                            <div class="alert alert-warning py-2 small border-0 shadow-sm">
                                <i class="bi bi-info-circle me-1"></i> File ini merupakan arsip resmi yang telah diteruskan oleh BAU.
                            </div>
                        </div>
                    </div>
                    {{-- Sisi Kanan: Preview File --}}
                    <div class="col-lg-8 bg-secondary bg-opacity-10" id="previewBody" style="height: 600px;">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer p-2 bg-white">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btn-download" class="btn btn-primary btn-sm" download>
                    <i class="bi bi-download me-1"></i> Download Dokumen
                </a>
            </div>
        </div>
    </div>
</div>

{{-- MODAL RIWAYAT --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-info text-white p-2 px-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Log Perjalanan Surat</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light" id="riwayatBody">
                {{-- Log via JS --}}
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
    $('#tabelArsip').DataTable({ "ordering": false });

    // --- LOGIKA RIWAYAT ---
    const riwayatModal = document.getElementById('riwayatModal');
    riwayatModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const url = button.getAttribute('data-url');
        const body = document.getElementById('riwayatBody');

        body.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';

        fetch(url)
            .then(res => res.json())
           .then(data => {
    let html = '<ul class="timeline">';
    
    // Pastikan data.riwayats ada dan bukan undefined
    if (data.riwayats && data.riwayats.length > 0) {
        data.riwayats.forEach(item => {
            // Cek apakah item.tanggal_f ada, jika tidak pakai string kosong
            let waktu = item.tanggal_f ? item.tanggal_f : 'Waktu tidak tercatat';
            
            let color = item.status_aksi.includes('Selesai') ? 'success' : 'primary';
            let icon = item.status_aksi.includes('Selesai') ? 'bi-check-lg' : 'bi-info-circle';
            
           // Di dalam loop data.riwayats
html += `
    <li>
        <div class="timeline-badge bg-${color} shadow-sm"><i class="bi ${icon}"></i></div>
        <div class="bg-white p-2 border rounded shadow-sm mb-3">
            <div class="fw-bold text-primary small">${item.status_aksi}</div>
            <div class="text-muted" style="font-size:11px;">
                <i class="bi bi-clock me-1"></i>${item.tanggal_f} </div>
            <div class="mt-1 small" style="line-height:1.4;">${item.catatan}</div>
            <div class="text-end border-top mt-1 pt-1 fst-italic text-muted" style="font-size:10px;">
                Oleh: ${item.user_name}
            </div>
        </div>
    </li>`;
        });
    } else {
        html += '<li class="text-center small text-muted p-3">Data log tidak ditemukan.</li>';
    }
    
    html += '</ul>';
    body.innerHTML = html;
})
.catch(error => {
    body.innerHTML = '<div class="alert alert-danger small">Gagal memuat log. Silakan coba lagi.</div>';
    console.error('Error:', error);
});
    });

    // --- LOGIKA PREVIEW ---
const previewModal = document.getElementById('previewModal');
previewModal.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    
    // Ambil Data
    const url = btn.getAttribute('data-file-url');
    const nomor = btn.getAttribute('data-nomor');
    const perihal = btn.getAttribute('data-perihal');
    const tgl = btn.getAttribute('data-tgl');
    const satkers = btn.getAttribute('data-satker').split(',');

    // Isi Detail di Sisi Kiri
    document.getElementById('det-nomor').textContent = nomor;
    document.getElementById('det-perihal').textContent = perihal;
    document.getElementById('det-tgl').textContent = tgl;
    document.getElementById('btn-download').href = url;

    // Render Badge Satker
    const satkerWrapper = document.getElementById('det-satker');
    satkerWrapper.innerHTML = '';
    satkers.forEach(s => {
        satkerWrapper.innerHTML += `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size:11px;">${s}</span>`;
    });

    // Handle Preview Sisi Kanan
    const body = document.getElementById('previewBody');
    const ext = url.split('.').pop().toLowerCase();
    
    body.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><div class="spinner-border text-primary"></div></div>';

    setTimeout(() => {
        if (ext === 'pdf') {
            body.innerHTML = `<iframe src="${url}#toolbar=0" width="100%" height="100%" style="border:none;"></iframe>`;
        } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
            body.innerHTML = `<div class="d-flex align-items-center justify-content-center h-100 p-3"><img src="${url}" class="img-fluid rounded shadow-sm" style="max-height:100%"></div>`;
        } else {
            body.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted"><i class="bi bi-file-earmark-zip fs-1"></i><p class="mt-2">Preview tidak tersedia untuk format ini.</p></div>`;
        }
    }, 400);
});
});
</script>
@endpush