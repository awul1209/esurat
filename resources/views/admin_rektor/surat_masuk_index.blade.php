@extends('layouts.app')

{{-- 
  ====================================================
  TAMBAHAN 1: CSS DataTables & CSS Font Kecil
  ====================================================
--}}
@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">

<style>
    /* 1. Set font tabel menjadi 13px dan komponen lainnya */
    #tabelSuratMasukAdmin,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        font-size: 13px !important; 
    }

    /* 2. Mengecilkan pagination */
    .dataTables_wrapper .dataTables_paginate .page-link {
        font-size: 0.85rem !important;
        padding: 0.3rem 0.6rem !important;
    }
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 0.5rem !important;
    }

    /* 3. Merapikan ikon sorting */
    table.dataTable thead > tr > th.sorting::before,
    table.dataTable thead > tr > th.sorting_asc::before,
    table.dataTable thead > tr > th.sorting_desc::before,
    table.dataTable thead > tr > th.sorting::after,
    table.dataTable thead > tr > th.sorting_asc::after,
    table.dataTable thead > tr > th.sorting_desc::after {
        font-size: 0.8em !important; 
        bottom: 0.6em !important;    
        opacity: 0.4 !important;     
    }
    table.dataTable thead > tr > th.sorting_asc::before,
    table.dataTable thead > tr > th.sorting_desc::after {
        opacity: 1 !important;
    }
    
    .info-modal-label { width: 130px; font-weight: 600; }
</style>
@endpush


@section('content')
<div class="container-fluid px-3">

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
            <h6 class="m-0 fw-bold text-primary">Daftar Surat Masuk (Perlu Tindak Lanjut Rektor)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
              <table id="tabelSuratMasukAdmin" class="table table-hover align-middle table-sm">
    <thead class="table-light">
        <tr>
            <th scope="col" class="text-center" width="5%">No</th> {{-- Ubah label jadi No (karena isinya 1,2,3..) --}}
            <th scope="col" width="15%">No. Surat</th> {{-- KOLOM BARU --}}
            <th scope="col" width="25%">Perihal</th>
            <th scope="col" width="20%">Asal Surat</th>
            <th scope="col" width="15%">Tujuan</th> 
            <th scope="col" width="10%">Tanggal Diterima</th>
            <th scope="col" class="text-center" width="10%">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($semuaSurat as $surat)
        @php
            // --- LOGIKA TIPE & TUJUAN ---
            $tipe = $surat->tujuan_tipe;
            $detailTujuan = '-';

            if (empty($tipe)) {
                if ($surat->tujuan_satker_id) { $tipe = 'satker'; }
                elseif ($surat->tujuan_user_id) { $tipe = 'pegawai'; }
                else { $tipe = 'universitas'; }
            }

            if ($tipe == 'satker') {
                $detailTujuan = $surat->tujuanSatker->nama_satker ?? 'Satker Tidak Ditemukan';
            } elseif ($tipe == 'pegawai') {
                $detailTujuan = $surat->tujuanUser->name ?? 'Pegawai Tidak Ditemukan';
            }

            $htmlTujuanTabel = '';
            $textTujuanModal = '';

            if ($tipe == 'rektor') {
                $htmlTujuanTabel = '<span class="badge bg-primary">Rektor</span>';
                $textTujuanModal = 'Rektor';
            } elseif ($tipe == 'universitas') {
                $htmlTujuanTabel = '<span class="badge bg-primary">Universitas</span>';
                $textTujuanModal = 'Universitas';
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
            {{-- KOLOM NO (Placeholder JS) --}}
            <td class="text-center">{{ $loop->iteration }}</td>

            {{-- KOLOM NO. SURAT (BARU) --}}
            <td class="fw-bold text-primary">{{ $surat->nomor_surat }}</td>

            <td>{{ $surat->perihal }}</td>
            <td>{{ $surat->surat_dari }}</td>
            <td>{!! $htmlTujuanTabel !!}</td>
            <td>{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}</td>
            
            <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                    {{-- Tombol Lihat --}}
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

                    {{-- Tombol Proses --}}
                    <a href="{{ route('adminrektor.disposisi.show', $surat->id) }}" class="btn btn-primary btn-sm">
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

{{-- 
  ====================================================
  BAGIAN 4: Modal Box untuk "Lihat"
  ====================================================
--}}
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
                            <tr>
                                <td class="info-modal-label">Tujuan</td>
                                <td>: <span id="modal-tujuan" class="fw-bold text-primary"></span></td>
                            </tr>
                            <tr>
                                <td class="info-modal-label">No. Agenda</td>
                                <td>: <span id="modal-no-agenda"></span></td>
                            </tr>
                            <tr>
                                <td class="info-modal-label">Asal Surat</td>
                                <td>: <span id="modal-asal-surat"></span></td>
                            </tr>
                            <tr>
                                <td class="info-modal-label">Tanggal Surat</td>
                                <td>: <span id="modal-tanggal-surat"></span></td>
                            </tr>
                            <tr>
                                <td class="info-modal-label">Tanggal Diterima</td>
                                <td>: <span id="modal-tanggal-diterima"></span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-7">
                        <div id="modal-file-preview-wrapper" style="height: 70vh; border: 1px solid #dee2e6; border-radius: .375rem;">
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
        
       // 1. Simpan DataTable ke variabel 't'
    var t = $('#tabelSuratMasukAdmin').DataTable({
        pagingType: 'simple_numbers',
        
        // PENTING: Matikan sorting di kolom 'No' (index 0) dan 'Aksi' (terakhir)
        columnDefs: [
            { searchable: false, orderable: false, targets: 0 },
            { orderable: false, targets: -1 }
        ],

        // PENTING: Kosongkan order agar urutan mengikuti Controller (Tanggal Surat terbaru)
        order: [], 

        language: {
            search: "Cari:",
            lengthMenu: "_MENU_", 
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: { next: ">", previous: "<" },
            zeroRecords: "Tidak ada data yang cocok ditemukan"
        }
    });

    // 2. LOGIKA AUTO NOMOR (1, 2, 3...)
    // Script ini memaksa kolom pertama selalu urut meski disortir
    t.on('order.dt search.dt', function () {
        let i = 1;
        t.column(0, {search: 'applied', order: 'applied'}).nodes().each(function (cell, k) {
            cell.innerHTML = i++;
        });
    }).draw();

        // --- Script untuk Modal Box ---
        var detailSuratModal = document.getElementById('detailSuratModal');
        detailSuratModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Ambil data langsung dari atribut tombol
            var noAgenda = button.getAttribute('data-no-agenda');
            var perihal = button.getAttribute('data-perihal');
            var asalSurat = button.getAttribute('data-asal-surat');
            var tanggalSurat = button.getAttribute('data-tanggal-surat');
            var tanggalDiterima = button.getAttribute('data-tanggal-diterima');
            var tujuanLengkap = button.getAttribute('data-tujuan-lengkap'); // <-- Data sudah matang dari PHP
            var fileUrl = button.getAttribute('data-file-url');

            // Set elemen modal
            detailSuratModal.querySelector('#modal-perihal').textContent = perihal;
            detailSuratModal.querySelector('#modal-no-agenda').textContent = noAgenda;
            detailSuratModal.querySelector('#modal-asal-surat').textContent = asalSurat;
            detailSuratModal.querySelector('#modal-tanggal-surat').textContent = tanggalSurat;
            detailSuratModal.querySelector('#modal-tanggal-diterima').textContent = tanggalDiterima;
            
            // Set Tujuan (Langsung pakai string dari PHP)
            detailSuratModal.querySelector('#modal-tujuan').textContent = tujuanLengkap;

            // Handle File Preview & Download
            var modalDownloadButton = detailSuratModal.querySelector('#modal-download-button');
            var modalFileWrapper = detailSuratModal.querySelector('#modal-file-preview-wrapper');
            
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