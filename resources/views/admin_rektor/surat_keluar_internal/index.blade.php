@extends('layouts.app')
<!-- memunculkan btn hapus -->
@push('styles')
{{-- DataTables Bootstrap 5 CSS --}}
<link href="https://cdn.datatables.net/v/bs5/dt-1.13.6/datatables.min.css" rel="stylesheet">
<style>
    /* Global Styles */
    body { font-family: 'Nunito', sans-serif; background-color: #f8f9fc; }
    
    /* Header & Filter */
    .page-title { color: #0d6efd; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; }
    .filter-card { border: 1px solid #e3e6f0; border-radius: 0.35rem; background: #fff; margin-bottom: 20px; }
    .filter-body { padding: 1.2rem; }
    .form-label-custom { font-size: 0.85rem; font-weight: 600; color: #5a5c69; margin-bottom: 0.3rem; }
    
    /* Table Styling */
    .table-custom thead th {
        background-color: #f8f9fc; color: #3a3b45; font-weight: 700; font-size: 0.85rem;
        border-bottom: 2px solid #e3e6f0; padding: 12px;
    }
    .table-custom tbody td {
        font-size: 0.9rem; vertical-align: middle; padding: 10px; color: #5a5c69; border-bottom: 1px solid #e3e6f0;
    }
    
    /* Specific Items */
    .text-no-surat { color: #0d6efd; font-weight: 700; text-decoration: none; }
    .text-no-surat:hover { text-decoration: underline; }
    .badge-via {
        background-color: #f8f9fa; border: 1px solid #e3e6f0; color: #858796;
        font-size: 0.7rem; padding: 3px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;
    }
     /* .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; } */ */ */
    .btn-file { background-color: #fff; border: 1px solid #d1d3e2; color: #0d6efd; transition: all 0.2s; }
    .btn-file:hover { background-color: #0d6efd; color: #fff; border-color: #0d6efd; }

    /* Modal Preview */
    #file-viewer-container {
        min-height: 400px; max-height: 75vh; overflow: auto;
        display: flex; align-items: center; justify-content: center; background-color: #f8f9fc;
        border-radius: 0.3rem; border: 1px solid #dee2e6;
    }
    #preview-image { max-width: 100%; height: auto; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); }
    
    /* DATATABLES CUSTOMIZATION (PENTING) */
    .dataTables_wrapper .dataTables_length select {
        padding-right: 30px; 
        font-size: 0.85rem;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #d1d3e2;
        border-radius: 4px;
        padding: 5px 10px;
    }
    .dataTables_wrapper .dataTables_paginate .page-item .page-link {
        color: #0d6efd;
        font-size: 0.85rem;
    }
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
   
    /* CSS Timeline Sederhana */
    ul.timeline {
        list-style-type: none;
        padding: 0;
        position: relative;
        margin-bottom: 0;
    }
    /* Garis Vertikal */
    ul.timeline:before {
        content: ' ';
        background: #dee2e6;
        display: inline-block;
        position: absolute;
        left: 16px; /* Posisi garis di tengah icon */
        width: 2px;
        height: 100%;
        z-index: 0;
        top: 10px;
    }
    ul.timeline > li {
        z-index: 2;
        position: relative;
        margin-bottom: 20px;
    }
    /* Hapus margin item terakhir */
    ul.timeline > li:last-child {
        margin-bottom: 0;
    }
    /* Hapus garis lebih pada item terakhir */
    ul.timeline > li:last-child:before {
        content: '';
        position: absolute;
        background: white; /* Tutup garis */
        width: 4px;
        height: 100%;
        left: 15px;
        top: 40px;
    }

</style>
<style>
    .timeline { list-style: none; padding: 20px 0 20px; position: relative; }
    .timeline:before {
        top: 0; bottom: 0; position: absolute; content: " "; width: 2px;
        background-color: #e9ecef; left: 17px; margin-left: 0;
    }
    .timeline > li { margin-bottom: 20px; position: relative; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 pt-1">


    {{-- SECTION FILTER (Tetap Dipertahankan) --}}
    <div class="filter-card shadow-sm">
        <div class="filter-body">
            <form action="{{ route('adminrektor.surat-keluar-internal.index') }}" method="GET">
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label-custom">Dari Tanggal</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-custom">Sampai Tanggal</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary text-white fw-bold w-100"><i class="bi bi-search me-1"></i> </button>
                            <a href="{{ route('adminrektor.surat-keluar-internal.index') }}" class="btn btn-sm  btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="{{ route('adminrektor.surat-keluar-internal.export', request()->query()) }}" class="btn btn-sm btn-success text-white fw-bold me-2">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('adminrektor.surat-keluar-internal.create') }}" class="btn btn-primary text-white fw-bold btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Buat Surat Baru
                        </a>
                    </div>
                </div>
            </form>
        </div>
    
        {{-- SECTION TABEL --}}
        <div class="border-top p-3">
            <div class="table-responsive">
                <table id="tabelSurat" class="table table-custom w-100">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">Tujuan Surat</th>
                            <th width="25%">No. Surat & Perihal</th>
                            <th width="15%">Tanggal Kirim</th>
                            <th width="15%">Tanggal Surat</th>
                            <th width="5%" class="text-center">File</th>
                            <th width="5%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                  <tbody>
    @foreach($suratKeluar as $index => $surat)
 @php
    // 1. Ambil Data Dasar dari Tabel Utama (BAU)
    $statusUtama = $surat->status; // 'pending', 'proses', 'selesai'
    
    // 2. Hitung Interaksi Satker (Tabel Pivot)
    $totalPenerima = $surat->penerimaInternal->count();
    $jumlahArsip = $surat->penerimaInternal->where('pivot.is_read', 2)->count();
    $jumlahBaca = $surat->penerimaInternal->where('pivot.is_read', 1)->count();

    // 3. Tentukan Status & Warna Badge
    $isLocked = false;
    $statusDisplay = '';
    $badgeColor = '';

    // --- LOGIKA STATUS BERJENJANG ---

    if ($statusUtama == 'pending') {
        $statusDisplay = 'Menunggu Verifikasi BAU';
        $badgeColor = 'secondary';
        $isLocked = false; // Masih bisa edit/hapus selama belum diproses BAU
        
    } elseif ($statusUtama == 'proses') {
        $statusDisplay = 'Sedang Diproses BAU';
        $badgeColor = 'info';
        $isLocked = true; // Kunci: BAU sedang bekerja
        
    } elseif ($statusUtama == 'selesai') {
        // Jika sudah selesai di BAU, cek interaksi Satker
        if ($totalPenerima > 0) {
            if ($jumlahArsip == $totalPenerima) {
                $statusDisplay = 'Selesai / Diarsipkan';
                $badgeColor = 'success';
                $isLocked = true;
            } elseif ($jumlahArsip > 0) {
                $statusDisplay = 'Diterima Sebagian (' . $jumlahArsip . '/' . $totalPenerima . ')';
                $badgeColor = 'primary';
                $isLocked = true;
            } elseif ($jumlahBaca > 0) {
                $statusDisplay = 'Dibaca (' . $jumlahBaca . '/' . $totalPenerima . ')';
                $badgeColor = 'info';
                $isLocked = true;
            } else {
                $statusDisplay = 'Diteruskan ke Satker';
                $badgeColor = 'warning text-dark';
                $isLocked = true;
            }
        } else {
            $statusDisplay = 'Terkirim';
            $badgeColor = 'success';
            $isLocked = true;
        }
    }
@endphp

    <tr>
        <td class="text-center fw-bold">{{ $loop->iteration }}</td>
        
        {{-- KOLOM TUJUAN --}}
        <td>
            <div class="text-dark">
                @if($surat->penerimaInternal->count() > 0)
                    @foreach($surat->penerimaInternal as $penerima)
                        <div class="fw-bold mb-1" style="font-size: 13px;">
                            <i class="bi bi-dot"></i> {{ $penerima->nama_satker }}
                        </div>
                    @endforeach
                @else
                    -
                @endif
            </div>
            <div class="badge-via mt-2 text-muted small">
                <i class="bi bi-arrow-right-circle me-1"></i> Via Internal
            </div>
        </td>

        {{-- KOLOM NOMOR & PERIHAL --}}
        <td>
            <span class="text-no-surat d-block mb-1 fw-bold text-primary">{{ $surat->nomor_surat }}</span>
            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($surat->perihal, 40) }}</div>
        </td>

        {{-- TANGGAL --}}
        <td><div class="text-muted"><i class="bi bi-calendar4-week me-2"></i>{{ $surat->created_at->format('d/m/Y') }}</div></td>
        <td><div class="text-muted"><i class="bi bi-calendar4-week me-2"></i>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d/m/Y') }}</div></td>
        
        {{-- FILE --}}
        <td class="text-center">
            @if($surat->file_surat)
                <button type="button" class="btn btn-action btn-file shadow-sm" 
                    title="Lihat File"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalPreview"
                    data-file-url="{{ Storage::url($surat->file_surat) }}"
                    data-file-name="{{ basename($surat->file_surat) }}"
                    data-title="{{ $surat->perihal }}">
                    <i class="bi bi-file-earmark-text"></i>
                </button>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>

        {{-- KOLOM STATUS (BARU) --}}
        <td class="text-center">
            <span class="badge bg-{{ $badgeColor }} text-wrap shadow-sm" style="font-size: 0.75rem;">
                {{ $statusDisplay }}
            </span>
        </td>

        {{-- KOLOM AKSI & LOG --}}
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                
                {{-- 1. TOMBOL RIWAYAT (LOG) --}}
                <button class="btn btn-sm btn-action btn-secondary text-white shadow-sm" 
                        title="Riwayat Surat"
                        data-bs-toggle="modal" 
                        data-bs-target="#riwayatModal" 
                        data-url="{{ route('adminrektor.surat-keluar-internal.riwayat', $surat->id) }}">
                    <i class="bi bi-clock-history" style="font-size: 12px;"></i>
                </button>
                                    <form action="{{ route('adminrektor.surat-keluar-internal.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-action btn-danger text-white shadow-sm" title="Hapus">
                            <i class="bi bi-trash-fill" style="font-size: 12px;"></i>
                        </button>
                    </form>

                {{-- 2. TOMBOL EDIT & HAPUS (HANYA JIKA BELUM LOCKED) --}}
                @if(!$isLocked)
                    <a href="{{ route('adminrektor.surat-keluar-internal.edit', $surat->id) }}" 
                       class="btn btn-sm btn-action btn-warning text-white shadow-sm" 
                       title="Edit">
                        <i class="bi bi-pencil-fill" style="font-size: 12px;"></i>
                    </a>
                    
                    <!-- <form action="{{ route('adminrektor.surat-keluar-internal.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-action btn-danger text-white shadow-sm" title="Hapus">
                            <i class="bi bi-trash-fill" style="font-size: 12px;"></i>
                        </button>
                    </form> -->
                @endif
            </div>
        </td>
    </tr>
    @endforeach
</tbody>
                </table>
            </div>
            {{-- TIDAK ADA LAGI {{ $suratKeluar->links() }} KARENA SUDAH DITANGANI DATATABLES --}}
        </div>
    </div>
</div>

{{-- MODAL RIWAYAT (LOG AKTIVITAS) --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="riwayatModalLabel">Riwayat Perjalanan Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="riwayatModalBody" style="max-height: 70vh; overflow-y: auto;">
                {{-- Loading Spinner Default --}}
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Sedang memuat data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW (SAMA SEPERTI SEBELUMNYA) --}}
<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <div class="d-flex align-items-center overflow-hidden">
                    <i class="bi bi-file-earmark-richtext fs-4 me-2"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-truncate" id="previewTitle" style="max-width: 600px;">Preview File</h6>
                        <small class="text-white-50 d-block" id="previewFilename">nama_file.pdf</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="file-viewer-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <div class="mt-2 text-muted">Memuat preview...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnDownload" class="btn btn-success px-4" download target="_blank">
                    <i class="bi bi-download me-2"></i> Download File
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- Script DataTables --}}
<script src="https://cdn.datatables.net/v/bs5/dt-1.13.6/datatables.min.js"></script>
<script>
    $(document).ready(function() {
        // --- KONFIGURASI DATATABLES (AGAR ADA PAGINATION & SHOW ENTRIES) ---
        $('#tabelSurat').DataTable({
            "paging": true,        // AKTIFKAN PAGINATION
            "lengthChange": true,  // AKTIFKAN DROPDOWN "TAMPILKAN 10 DATA"
            "searching": true,     // AKTIFKAN PENCARIAN
            "ordering": false,     // MATIKAN SORTING CLIENT-SIDE (Agar urutan Controller 'latest' tidak berubah)
            "info": true,          // TAMPILKAN "Menampilkan 1 sampai 10..."
            "autoWidth": false,
            "pageLength": 10,      // DEFAULT 10 DATA
            "lengthMenu": [ [5, 10, 25, 50, -1], [5, 10, 25, 50, "Semua"] ], // PILIHAN DATA
            "language": {
                "search": "Cari Cepat:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "zeroRecords": "Data tidak ditemukan",
                "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 s/d 0 dari 0 data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": {
                    "first": "Awal",
                    "last": "Akhir",
                    "next": "&raquo;",
                    "previous": "&laquo;"
                }
            },
            // Layout Bootstrap 5 yang rapi
            "dom": "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center'l><'col-sm-12 col-md-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        // --- LOGIKA MODAL PREVIEW (SAMA) ---
        var modalPreview = document.getElementById('modalPreview');
        modalPreview.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file-url');
            var fileName = button.getAttribute('data-file-name');
            var title = button.getAttribute('data-title');

            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewFilename').textContent = fileName;
            document.getElementById('btnDownload').href = fileUrl;

            var container = document.getElementById('file-viewer-container');
            var ext = fileName.split('.').pop().toLowerCase();

            container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>`;

            setTimeout(function() {
                if (ext === 'pdf') {
                    container.innerHTML = `<iframe src="${fileUrl}" width="100%" height="600px" style="border:none;"></iframe>`;
                } else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                    container.innerHTML = `<img src="${fileUrl}" id="preview-image" alt="Preview Gambar">`;
                } else {
                    container.innerHTML = `<div class="text-center p-5"><i class="bi bi-file-earmark-x fs-1 text-muted"></i><h5 class="mt-3">Preview tidak tersedia</h5><p class="text-muted">Unduh file untuk melihatnya.</p></div>`;
                }
            }, 300);
        });

        modalPreview.addEventListener('hidden.bs.modal', function () {
            document.getElementById('file-viewer-container').innerHTML = '';
        });

        
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var riwayatModal = document.getElementById('riwayatModal');
        
        if (riwayatModal) {
            riwayatModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;

                var dataUrl = button.getAttribute('data-url');
                var modalBody = riwayatModal.querySelector('#riwayatModalBody');
                var modalLabel = riwayatModal.querySelector('#riwayatModalLabel');

                // 1. Reset Tampilan (Loading)
                modalLabel.textContent = 'Memuat Data...';
                modalBody.innerHTML = `
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Sedang mengambil riwayat surat...</p>
                    </div>`;
                
                // 2. Fetch Data
                fetch(dataUrl)
                    .then(async response => {
                        // Handle Error Server (500, 404, dll)
                        if (!response.ok) { 
                            const errData = await response.json().catch(() => ({}));
                            throw new Error(errData.message || 'Gagal memuat data (Status: ' + response.status + ')');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Cek status logic dari controller
                        if (data.status === 'error') {
                            throw new Error(data.message);
                        }

                        // 3. Update Judul
                        modalLabel.textContent = 'Riwayat: ' + (data.nomor_surat || 'Surat Keluar');

                        // 4. Render Timeline
                        var html = '<ul class="timeline">';
                        
                        if (data.riwayats && data.riwayats.length > 0) {
                            data.riwayats.forEach((item) => {
                                // Tentukan Warna & Icon
                                var badge = 'primary'; 
                                var icon = 'bi-circle-fill';
                                var status = item.status_aksi || '';

                                if (status.includes('Selesai') || status.includes('Arsip') || status.includes('Diterima')) {
                                    badge = 'success'; icon = 'bi-check-lg';
                                } else if (status.includes('Dibaca')) {
                                    badge = 'info'; icon = 'bi-eye'; // Icon mata untuk dibaca
                                } else if (status.includes('Dikirim')) {
                                    badge = 'primary'; icon = 'bi-send';
                                }

                                // Nama User (Safety Check)
                                var userName = (item.user && item.user.name) ? item.user.name : 'Sistem';

                                html += `
                                    <li class="position-relative ps-5">
                                        <div class="position-absolute start-0 top-0">
                                            <span class="badge rounded-circle bg-${badge} border border-light shadow-sm" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi ${icon}" style="font-size: 1rem;"></i>
                                            </span>
                                        </div>
                                        <div class="card border-0 shadow-sm bg-light mb-0">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="fw-bold mb-0 text-${badge}">${status}</h6>
                                                    <span class="badge bg-white text-muted border rounded-pill fw-normal">
                                                        <i class="bi bi-clock me-1"></i>${item.tanggal_f}
                                                    </span>
                                                </div>
                                                <p class="mb-2 small text-dark">${item.catatan || '-'}</p>
                                                <div class="text-end border-top pt-2">
                                                    <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                                                        <i class="bi bi-person-circle me-1"></i> ${userName}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </li>`;
                            });
                        } else {
                            html += `
                                <li class="text-center p-4">
                                    <i class="bi bi-info-circle text-muted fs-1"></i>
                                    <p class="text-muted mt-2">Belum ada riwayat aktivitas tercatat.</p>
                                </li>`;
                        }
                        
                        html += '</ul>';
                        modalBody.innerHTML = html;
                    })
                    .catch(err => {
                        console.error("JS Error:", err);
                        modalBody.innerHTML = `
                            <div class="alert alert-danger text-center m-3 shadow-sm border-0">
                                <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-2"></i><br>
                                <strong class="fs-5">Terjadi Kesalahan</strong>
                                <p class="small text-muted mt-2">${err.message}</p>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" data-bs-dismiss="modal">Tutup</button>
                            </div>`;
                    });
            });
        }
    });
</script>


@endpush