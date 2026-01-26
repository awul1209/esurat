@extends('layouts.app')

@push('styles')
{{-- DataTables Bootstrap 5 --}}
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    body { font-family: 'Nunito', sans-serif; background-color: #f8f9fc; }
    #tabelInternal, .btn-sm { font-size: 13px !important; }
    .info-modal-label { width: 140px; font-weight: 600; color: #4e73df; }
    .preview-container {
        height: 550px;
        background: #f1f1f1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ddd;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 pt-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">
                <i class="bi bi-envelope-paper-fill me-2"></i>Verifikasi Surat Internal Rektor
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
               <table class="table table-hover align-middle border w-100" id="tabelInternal">
    <thead class="table-light text-uppercase small">
        <tr>
            <th width="5%">No</th>
            <th>Info Surat</th>
            <th>Tujuan Satker</th>
            <th>Waktu Masuk</th> {{-- Kolom Baru --}}
            <th class="text-center">Status</th>
            <th class="text-center" width="12%">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($surat as $row)
        <tr>
            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
            <td>
                <strong class="text-primary">{{ $row->nomor_surat }}</strong><br>
                <div class="text-muted small mb-1">{{ Str::limit($row->perihal, 40) }}</div>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="bi bi-calendar3 me-1"></i>Tgl Surat: {{ $row->tanggal_surat->format('d/m/Y') }}
                </span>
            </td>
            <td>
                @foreach($row->penerimaInternal as $satker)
                    <span class="badge bg-info text-white mb-1" style="font-size: 10px;">
                        {{ $satker->nama_satker }}
                    </span>
                @endforeach
            </td>
            {{-- Kolom Waktu Masuk --}}
            <td>
                <div class="small fw-bold text-dark">{{ $row->created_at->isoFormat('D MMM YYYY') }}</div>
                <div class="small text-muted"><i class="bi bi-clock me-1"></i>{{ $row->created_at->format('H:i') }} WIB</div>
            </td>
            <td class="text-center">
                @if($row->status == 'pending')
                    <span class="badge bg-secondary px-3 py-2">Pending</span>
                @else
                    <span class="badge bg-warning text-dark px-3 py-2">Proses</span>
                @endif
            </td>
            <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                    @if($row->status == 'pending')
                        <form action="{{ route('bau.surat-internal-rektor.proses', $row->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm shadow-sm" title="Proses Surat">
                                <i class="bi bi-gear-fill"></i>
                            </button>
                        </form>
                    @else
                        <form action="{{ route('bau.surat-internal-rektor.teruskan', $row->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm shadow-sm" onclick="return confirm('Teruskan surat ini ke Satker tujuan?')">
                                <i class="bi bi-send-check-fill"></i>
                            </button>
                        </form>
                    @endif
                    
                    <button type="button" class="btn btn-primary btn-sm shadow-sm" 
                        data-bs-toggle="modal" 
                        data-bs-target="#previewModal"
                        data-nomor="{{ $row->nomor_surat }}"
                        data-perihal="{{ $row->perihal }}"
                        data-tgl="{{ $row->tanggal_surat->format('d M Y') }}"
                        data-masuk="{{ $row->created_at->isoFormat('D MMMM Y, HH:mm') }} WIB" {{-- Kirim data waktu masuk ke modal --}}
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

{{-- MODAL PREVIEW DINAMIS --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-pdf me-2"></i>Preview Surat Internal</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card border-0 bg-light mb-3">
                            <div class="card-body p-3">
                              {{-- Di dalam Modal Body bagian table --}}
<table class="table table-sm table-borderless small mb-0">
    <tr>
        <td class="info-modal-label">Nomor Surat</td>
        <td>: <span id="m-nomor" class="fw-bold text-dark"></span></td>
    </tr>
    <tr>
        <td class="info-modal-label">Tanggal Surat</td>
        <td>: <span id="m-tgl"></span></td>
    </tr>
    <tr>
        <td class="info-modal-label">Waktu Masuk</td>
        <td>: <span id="m-masuk" class="text-danger fw-bold"></span></td> {{-- Field Baru --}}
    </tr>
    <tr>
        <td class="info-modal-label">Perihal</td>
        <td>: <span id="m-perihal"></span></td>
    </tr>
</table>
                            </div>
                        </div>
                        <div class="alert alert-info small border-0 shadow-sm">
                            <i class="bi bi-info-circle-fill me-2"></i>Pastikan dokumen sudah sesuai sebelum menekan tombol <strong>Teruskan</strong>.
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div id="m-preview-wrapper" class="preview-container">
                            {{-- Preview diisi via JS --}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="m-download" class="btn btn-primary btn-sm" download>
                    <i class="bi bi-download me-1"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- JQuery & DataTables JS --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>

<script>
$(document).ready(function () {
    // 1. Inisialisasi DataTable
    $('#tabelInternal').DataTable({
        "ordering": false,
        "language": {
            "search": "Cari Surat:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "zeroRecords": "Tidak ada surat internal yang ditemukan",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Data tidak tersedia",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Lanjut",
                "previous": "Kembali"
            }
        }
    });

    // 2. Logika Preview Modal Dinamis
    const previewModal = document.getElementById('previewModal');
    if (previewModal) {
        previewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            // Di dalam eventListener 'show.bs.modal'
const masuk = button.getAttribute('data-masuk'); // Tangkap data
document.getElementById('m-masuk').textContent = masuk; // Tampilkan
            // Ambil data dari atribut button
            const nomor = button.getAttribute('data-nomor');
            const perihal = button.getAttribute('data-perihal');
            const tgl = button.getAttribute('data-tgl');
            const fileUrl = button.getAttribute('data-file-url');

            // Update isi modal
            document.getElementById('m-nomor').textContent = nomor;
            document.getElementById('m-perihal').textContent = perihal;
            document.getElementById('m-tgl').textContent = tgl;
            document.getElementById('m-download').href = fileUrl;

            // Handle Preview File (PDF/Gambar)
            const wrapper = document.getElementById('m-preview-wrapper');
            wrapper.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
            
            const extension = fileUrl.split('.').pop().toLowerCase();
            
            setTimeout(() => {
                if (extension === 'pdf') {
                    wrapper.innerHTML = `<iframe src="${fileUrl}#toolbar=0" width="100%" height="100%" style="border:none;"></iframe>`;
                } else if (['jpg', 'jpeg', 'png'].includes(extension)) {
                    wrapper.innerHTML = `<img src="${fileUrl}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
                } else {
                    wrapper.innerHTML = `<div class="text-center p-4"><i class="bi bi-file-earmark-zip fs-1"></i><br>Preview tidak tersedia untuk tipe file ini. Silakan download.</div>`;
                }
            }, 300);
        });
    }
});
</script>
@endpush