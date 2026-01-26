@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* Style tabel agar rapi */
    #tabelSuratInternal, .dataTables_wrapper { font-size: 13px !important; }
    .btn-icon { padding: 0.25rem 0.5rem; }
    .info-modal-label { width: 130px; font-weight: 600; color: #555; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">

    {{-- Alert Success/Error --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4 mt-2">
        <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">
                <i class="bi bi-arrow-down-left-square-fill me-2"></i>Daftar Surat Masuk Internal (Dari Satker)
            </h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratInternal" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="25%">Asal Satker</th>
                            <th width="35%">Perihal</th>
                            <th width="20%">Tanggal Diterima</th>
                            <th class="text-center" width="15%">Aksi</th>
                        </tr>
                    </thead>
                   <tbody>
    {{-- 1. Inisialisasi Nomor Manual --}}
    @php $nomor = 1; @endphp

    @foreach ($suratInternal as $surat)
    <tr>
        {{-- 2. Gunakan variabel nomor manual lalu tambah 1 ($nomor++) --}}
        <td class="text-center">{{ $nomor++ }}</td>
        
        <td>
            <span class="fw-bold text-dark">{{ $surat->surat_dari }}</span>
            <br>
            <small class="text-muted">No: {{ $surat->nomor_surat }}</small>
        </td>
        <td>{{ $surat->perihal }}</td>
        <td>
            {{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}
            <br>
            <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Menunggu Disposisi</span>
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                {{-- Tombol Lihat Detail --}}
                <button type="button" class="btn btn-sm btn-info text-white btn-icon" 
                    title="Lihat Detail"
                    data-bs-toggle="modal" 
                    data-bs-target="#detailInternalModal"
                    data-no-agenda="{{ $surat->no_agenda }}"
                    data-perihal="{{ $surat->perihal }}"
                    data-asal-surat="{{ $surat->surat_dari }}"
                    data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                    data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                    data-file-url="{{ Storage::url($surat->file_surat) }}">
                    <i class="bi bi-eye-fill"></i>
                </button>

                {{-- Tombol Tindak Lanjuti (Disposisi) --}}
                <a href="{{ route('adminrektor.disposisi.show', $surat->id) }}" class="btn btn-primary btn-sm btn-icon" title="Proses Disposisi">
                    <i class="bi bi-pencil-square"></i> Proses
                </a>
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
<div class="modal fade" id="detailInternalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Surat Internal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    {{-- Kolom Kiri: Info --}}
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded border mb-3">
                            <h5 class="text-primary fw-bold mb-3" id="modal-perihal"></h5>
                            <table class="table table-borderless table-sm mb-0">
                                <tr><td class="info-modal-label">Asal Satker</td><td>: <span id="modal-asal-surat" class="fw-bold"></span></td></tr>
                                <tr><td class="info-modal-label">No. Agenda</td><td>: <span id="modal-no-agenda"></span></td></tr>
                                <tr><td class="info-modal-label">Tgl. Surat</td><td>: <span id="modal-tanggal-surat"></span></td></tr>
                                <tr><td class="info-modal-label">Tgl. Diterima</td><td>: <span id="modal-tanggal-diterima"></span></td></tr>
                            </table>
                        </div>
                    </div>
                    {{-- Kolom Kanan: File Preview --}}
                    <div class="col-md-7">
                        <div id="modal-file-preview-wrapper" class="bg-dark bg-opacity-10 d-flex align-items-center justify-content-center rounded border" style="height: 60vh;">
                            {{-- Preview diisi JS --}}
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
    $(document).ready(function() {
      // 1. Simpan DataTable ke variabel 't' agar bisa dimanipulasi
    var t = $('#tabelSuratInternal').DataTable({
        // Konfigurasi Kolom
        columnDefs: [
            {
                searchable: false,
                orderable: false,
                targets: 0 // Kolom "No" (index 0) JANGAN disortir user
            },
            {
                orderable: false,
                targets: -1 // Kolom "Aksi" (index terakhir) JANGAN disortir user
            }
        ],
        
        // Urutan Default: Tetap gunakan preferensi Anda (Kolom ke-3 desc)
        order: [], 

        // Bahasa Indonesia (Sesuai kode Anda)
        language: {
            "emptyTable": "Tidak ada data yang tersedia pada tabel ini",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
            "infoFiltered": "(disaring dari _MAX_ total entri)",
            "lengthMenu": "Tampilkan _MENU_ entri",
            "loadingRecords": "Sedang memuat...",
            "processing": "Sedang memproses...",
            "search": "Cari:",
            "zeroRecords": "Tidak ditemukan data yang sesuai",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        }
    });

    // 2. LOGIKA AUTO NOMOR (INI SOLUSINYA)
    // Script ini akan berjalan setiap kali tabel di-sort atau di-search.
    // Dia akan menimpa kolom pertama (No) dengan angka 1, 2, 3... berurutan.
    t.on('order.dt search.dt', function () {
        let i = 1;
        t.column(0, {search: 'applied', order: 'applied'}).nodes().each(function (cell, k) {
            cell.innerHTML = i++;
        });
    }).draw();

        // Init Modal Detail
        var detailModal = document.getElementById('detailInternalModal');
        detailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Set Text Data
            detailModal.querySelector('#modal-perihal').textContent = button.getAttribute('data-perihal');
            detailModal.querySelector('#modal-no-agenda').textContent = button.getAttribute('data-no-agenda');
            detailModal.querySelector('#modal-asal-surat').textContent = button.getAttribute('data-asal-surat');
            detailModal.querySelector('#modal-tanggal-surat').textContent = button.getAttribute('data-tanggal-surat');
            detailModal.querySelector('#modal-tanggal-diterima').textContent = button.getAttribute('data-tanggal-diterima');

            // Set Download Link
            var fileUrl = button.getAttribute('data-file-url');
            var btnDownload = detailModal.querySelector('#modal-download-button');
            btnDownload.href = fileUrl;

            // Preview File Logic
            var wrapper = detailModal.querySelector('#modal-file-preview-wrapper');
            wrapper.innerHTML = '<div class="spinner-border text-primary" role="status"></div>'; 

            if(fileUrl && fileUrl.length > 5) {
                var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
                setTimeout(() => {
                    if(ext === 'pdf') {
                        wrapper.innerHTML = '<iframe src="'+fileUrl+'" width="100%" height="100%" style="border:none;"></iframe>';
                    } else if(['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                        wrapper.innerHTML = '<img src="'+fileUrl+'" class="img-fluid" style="max-height: 100%; object-fit: contain;">';
                    } else {
                        wrapper.innerHTML = '<div class="text-center p-5 text-muted"><i class="bi bi-file-earmark-text h2"></i><p class="mt-2">Preview tidak tersedia.<br>Silakan download.</p></div>';
                    }
                }, 300);
            } else {
                wrapper.innerHTML = '<p class="text-danger">File tidak ditemukan</p>';
            }
        });
    });
</script>
@endpush