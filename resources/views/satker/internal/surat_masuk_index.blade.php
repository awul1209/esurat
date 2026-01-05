@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    #tabelSuratMasuk, .dataTables_wrapper, .modal-body { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    .table td, .table th { vertical-align: middle; }
    .btn-icon { width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
    
    /* Style Checkbox Delegasi */
    .checklist-container {
        max-height: 150px; 
        overflow-y: auto; 
        border: 1px solid #ced4da; 
        padding: 10px; 
        border-radius: 5px;
        background: #f8f9fa;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">

    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" style="font-size: 13px;">
            <i class="bi bi-exclamation-circle-fill me-2"></i> Gagal menyimpan. Mohon periksa form input kembali.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- CARD UTAMA --}}
    <div class="card shadow-sm border-0 mb-4 mt-2">
        
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-inbox me-2"></i>Daftar Surat Masuk (Internal)</h6>

    
        </div>

        {{-- FILTER & EXPORT --}}
        <div class="card-body bg-light border-bottom py-3">
            <form action="{{ route('satker.surat-masuk.internal') }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Cari</button>
                        <a href="{{ route('satker.surat-masuk.internal') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                    </div>
                    <div class="col-md ms-auto text-md-end mt-2 mt-md-0">
                        <a href="{{ route('satker.surat-masuk.internal.export', request()->query()) }}" class="btn btn-success btn-sm shadow-sm">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                        </a>
                                {{-- TOMBOL TAMBAH MANUAL --}}
   {{-- PERHATIKAN: Ada tambahan type="button" --}}
        <button type="button" class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahSuratModal">
            <i class="bi bi-plus-lg me-1"></i> Input Manual
        </button>
                    </div>
                    
                </div>
            </form>
        </div>

        {{-- TABEL DATA --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tabelSuratMasuk" class="table table-hover align-middle table-sm w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" width="5%">No</th>
                            <th width="25%">Pengirim (Satker)</th>
                            <th width="35%">No. Surat & Perihal</th>
                            <th width="15%">Tanggal Surat</th>
                            <th width="15%">Tanggal Terima</th>
                            <th class="text-center" width="20%">Aksi</th>
                        </tr>
                    </thead>
                <tbody>
    @foreach($suratMasuk as $surat)
    @php
        // 1. Cek apakah ini Inputan Manual
        $isManual = ($surat instanceof \App\Models\Surat);
        $isMyInput = ($surat->user_id == Auth::id() && $isManual);

        // 2. Tentukan Nama Satker & Penginput
        if ($isManual) {
            $namaSatker = $surat->surat_dari; 
            $penginput = 'Saya (Manual)';
            
            // --- PERBAIKAN UTAMA DISINI ---
            // Cari ID Satker berdasarkan Nama yang tersimpan agar Dropdown Edit terpilih otomatis
            $satkerIdAsal = '';
            $matchSatker = $daftarSatker->firstWhere('nama_satker', $surat->surat_dari);
            if ($matchSatker) {
                $satkerIdAsal = $matchSatker->id;
            }
        } else {
            $namaSatker = $surat->satker->nama_satker ?? ($surat->user->satker->nama_satker ?? 'Rektorat');
            $penginput = $surat->user->name ?? '-';
            $satkerIdAsal = ''; // Tidak dipakai untuk surat otomatis
        }

        $isDisposisiRektor = stripos($namaSatker, 'rektor') !== false;
    @endphp

    <tr>
        <td class="text-center fw-bold">{{ $loop->iteration }}</td>
        <td>
            @if($isMyInput)
                <span class="badge bg-info text-dark mb-1"><i class="bi bi-keyboard"></i> Input Manual</span><br>
                <span class="fw-bold text-dark">{{ $namaSatker }}</span>
            @else
                <span class="fw-bold text-dark">{{ $namaSatker }}</span><br>
                <small class="text-muted"><i class="bi bi-person-circle"></i> {{ $penginput }}</small>
            @endif
        </td>
        <td>
            <div class="fw-bold text-dark">{{ $surat->nomor_surat }}</div>
            <div class="text-muted small text-truncate" style="max-width: 300px;">{{ $surat->perihal }}</div>
        </td>
        <td>
            <div class="">
                <i class="bi bi-calendar-event me-2"></i>{{ $surat->tanggal_surat->format('d/m/Y') }}
            </div>
        </td>
     {{-- JIKA ADA tanggal terima, format. JIKA TIDAK ADA, pakai strip (-) atau pakai tanggal surat --}}
<td>
    @if($surat->diterima_tanggal)
       <i class="bi bi-calendar-event me-2"></i>  {{ $surat->diterima_tanggal->format('d/m/Y') }}
    @else
        {{-- Opsi 1: Tampilkan strip jika kosong --}}
        - 
        
        {{-- Opsi 2 (Alternatif): Gunakan Tanggal Surat sebagai fallback --}}
       <i class="bi bi-calendar-event me-2"></i>  {{-- {{ $surat->tanggal_surat->format('d/m/Y') }} --}}
    @endif
</td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                
                {{-- TOMBOL PREVIEW --}}
                <button type="button" class="btn btn-info btn-sm btn-icon text-white shadow-sm" 
                    title="Lihat File"
                    data-bs-toggle="modal" data-bs-target="#filePreviewModal"
                    data-title="{{ $surat->perihal }}" 
                    data-file-url="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '' }}">
                    <i class="bi bi-eye-fill"></i>
                </button>

                {{-- TOMBOL CETAK (Disposisi Rektor) --}}
                @if($isDisposisiRektor && !$isMyInput)
                    <!-- <a href="#" class="btn btn-dark btn-sm btn-icon shadow-sm" onclick="alert('Fitur cetak...')">
                        <i class="bi bi-printer-fill"></i> -->
                    </a>
                @endif

                {{-- TOMBOL EDIT & HAPUS (Manual Sendiri) --}}
                @if($isMyInput)
                    <button type="button" class="btn btn-warning btn-sm btn-icon text-white shadow-sm btn-edit-manual" 
                        title="Edit"
                        data-bs-toggle="modal" 
                        data-bs-target="#editSuratModal"
                        data-id="{{ $surat->id }}"
                        data-nomor="{{ $surat->nomor_surat }}"
                        
                        {{-- ID HASIL PENCARIAN TADI DIMASUKKAN KESINI --}}
                        data-satker="{{ $satkerIdAsal }}" 
                        
                        data-perihal="{{ $surat->perihal }}"
                        data-tanggal="{{ $surat->tanggal_surat->format('Y-m-d') }}"
                        data-file-url="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '' }}"
                        data-file-name="{{ $surat->file_surat ? basename($surat->file_surat) : '' }}"
                        data-url="{{ route('satker.surat-masuk.internal.update', $surat->id) }}">
                        <i class="bi bi-pencil-fill"></i>
                    </button>

                    <form action="{{ route('satker.surat-masuk.internal.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Hapus permanen?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm btn-icon shadow-sm" title="Hapus">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>
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

{{-- ====================== MODAL TAMBAH ====================== --}}
<div class="modal fade" id="tambahSuratModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('satker.surat-masuk.internal.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Input Surat Masuk (Manual)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Data Surat</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_surat" class="form-control @error('nomor_surat') is-invalid @enderror" value="{{ old('nomor_surat') }}" required>
                            @error('nomor_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Asal Surat (Satker) <span class="text-danger">*</span></label>
                            <select name="asal_satker_id" class="form-select @error('asal_satker_id') is-invalid @enderror" required>
                                <option value="" selected disabled>-- Pilih Satker --</option>
                                @foreach($daftarSatker as $satker)
                                    <option value="{{ $satker->id }}" {{ old('asal_satker_id') == $satker->id ? 'selected' : '' }}>
                                        {{ $satker->nama_satker }}
                                    </option>
                                @endforeach
                            </select>
                            @error('asal_satker_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Perihal <span class="text-danger">*</span></label>
                        <input type="text" name="perihal" class="form-control" value="{{ old('perihal') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Tgl Surat <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Tgl Diterima</label>
                            <input type="date" name="diterima_tanggal" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>File (PDF/Gambar) <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" class="form-control @error('file_surat') is-invalid @enderror" accept=".pdf,.jpg,.png" required>
                            @error('file_surat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mt-4 mb-3">
                        Delegasi ke Pegawai <small class="text-muted fw-normal">(Opsional)</small>
                    </h6>
                    <div class="mb-3">
                        <label class="form-label">Pilih Pegawai:</label>
                        <div class="checklist-container">
                            @if($daftarPegawai->isEmpty())
                                <p class="text-muted text-center small my-1">Tidak ada pegawai.</p>
                            @else
                                @foreach ($daftarPegawai as $pegawai)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="delegasi_user_ids[]" value="{{ $pegawai->id }}" id="tambah_pegawai_{{ $pegawai->id }}">
                                        <label class="form-check-label" for="tambah_pegawai_{{ $pegawai->id }}">{{ $pegawai->name }}</label>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Catatan Delegasi:</label>
                        <textarea name="catatan_delegasi" class="form-control" rows="2" placeholder="Catatan untuk pegawai..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====================== MODAL EDIT ====================== --}}
{{-- ====================== MODAL EDIT ====================== --}}
<div class="modal fade" id="editSuratModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditManual" action="" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Surat Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Input Lainnya (Nomor, Satker, Perihal, Tgl) Tetap Sama --}}
                    <div class="mb-3">
                        <label>Nomor Surat <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_surat" id="edit_nomor_surat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Asal Surat (Satker) <span class="text-danger">*</span></label>
                        <select name="asal_satker_id" id="edit_asal_satker_id" class="form-select" required>
                            <option value="" disabled>-- Pilih Satker --</option>
                            @foreach($daftarSatker as $satker)
                                <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Perihal <span class="text-danger">*</span></label>
                        <input type="text" name="perihal" id="edit_perihal" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Tgl Surat <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_surat" id="edit_tanggal_surat" class="form-control" required>
                    </div>

                    {{-- BAGIAN FILE (YANG DIUPDATE) --}}
                    <div class="mb-3">
                        <label class="d-block mb-1 fw-bold">File Saat Ini:</label>
                        
                        {{-- Area untuk menampilkan info file lama --}}
                        <div id="edit_file_info" class="p-2 border rounded bg-light mb-2">
                            {{-- Isi akan di-inject lewat Javascript --}}
                        </div>

                        <label>Ganti File (Opsional)</label>
                        <input type="file" name="file_surat" class="form-control" accept=".pdf,.jpg,.png">
                        <div class="form-text small">Biarkan kosong jika tidak ingin mengubah file.</div>
                    </div>
                    {{-- AKHIR BAGIAN FILE --}}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW (Standard) --}}
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="file-viewer-container" class="bg-light d-flex align-items-center justify-content-center" style="height: 75vh;">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <span class="me-auto text-muted small" id="preview-filename"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btn-download-file" class="btn btn-primary" download>Download</a>
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
        // Init DataTable dengan Bahasa Indonesia (Hardcoded untuk menghindari CORS)
        $('#tabelSuratMasuk').DataTable({
            pagingType: 'simple_numbers',
            order: [[ 3, 'desc' ]],
            language: {
                "emptyTable": "Tidak ada data yang tersedia pada tabel ini",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                "infoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
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

        // --- LOGIKA MODAL EDIT (Populate Data) ---
        $(document).on('click', '.btn-edit-manual', function() {
            // Ambil data dari atribut tombol
            var urlUpdate = $(this).data('url');
            var nomor = $(this).data('nomor');
            var satkerId = $(this).data('satker');
            var perihal = $(this).data('perihal');
            var tanggal = $(this).data('tanggal');
            
            // AMBIL DATA FILE
            var fileUrl = $(this).data('file-url');
            var fileName = $(this).data('file-name'); // Ambil nama file langsung

            // Isi Form Edit
            $('#formEditManual').attr('action', urlUpdate);
            $('#edit_nomor_surat').val(nomor);
            $('#edit_asal_satker_id').val(satkerId);
            $('#edit_perihal').val(perihal);
            $('#edit_tanggal_surat').val(tanggal);

            // --- LOGIKA TAMPILKAN FILE LAMA DI KOTAK ---
            var infoHtml = '';

            // Cek apakah fileName ada isinya (tidak kosong)
            if (fileName && fileName !== "") {
                infoHtml = `
                    <div class="d-flex align-items-center p-2 border rounded bg-white">
                        <div class="me-3 text-success">
                            <i class="bi bi-file-earmark-pdf-fill fs-3"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <span class="d-block fw-bold text-dark small">File Terlampir:</span>
                            <span class="d-block text-muted small text-truncate" style="max-width: 250px;">
                                ${fileName}
                            </span>
                        </div>
                        <div class="ms-2">
                            <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat File">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </div>
                `;
            } else {
                // JIKA KOSONG
                infoHtml = `
                    <div class="p-2 border rounded bg-light text-center text-muted small fst-italic">
                        <i class="bi bi-x-circle me-1"></i> Tidak ada file lampiran sebelumnya.
                    </div>
                `;
            }

            // Masukkan HTML ke dalam div di Modal
            $('#edit_file_info').html(infoHtml);
        });

        // --- LOGIKA MODAL PREVIEW ---
        var fileModal = document.getElementById('filePreviewModal');
        if (fileModal) {
            fileModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var fileUrl = button.getAttribute('data-file-url');
                var title = button.getAttribute('data-title');
                
                fileModal.querySelector('.modal-title').textContent = "Preview: " + title;
                fileModal.querySelector('#btn-download-file').href = fileUrl;
                
                // Ambil nama file dari URL jika perlu
                var filename = fileUrl.split('/').pop();
                var previewFilenameEl = fileModal.querySelector('#preview-filename');
                if(previewFilenameEl) previewFilenameEl.textContent = filename;
                
                var container = fileModal.querySelector('#file-viewer-container');
                container.innerHTML = '<div class="spinner-border text-primary"></div>';

                var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
                setTimeout(function() {
                    if (ext === 'pdf') {
                        container.innerHTML = `<iframe src="${fileUrl}" width="100%" height="100%" style="border:none;"></iframe>`;
                    } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
                        container.innerHTML = `<img src="${fileUrl}" class="img-fluid" style="max-height: 100%;">`;
                    } else {
                        container.innerHTML = `<div class="text-center p-5">Preview tidak tersedia.</div>`;
                    }
                }, 300);
            });
        }
        
        // --- RE-OPEN MODAL JIKA ERROR VALIDASI ---
        @if ($errors->any())
            @if(session('error_code') == 'create')
                var createModalEl = document.getElementById('tambahSuratModal');
                if(createModalEl) {
                    new bootstrap.Modal(createModalEl).show();
                }
            @elseif(session('error_code') == 'edit')
                var editId = "{{ session('edit_id') }}";
                // Cari tombol edit dengan ID tersebut lalu klik otomatis agar modal terbuka dengan data benar
                $('.btn-edit-manual[data-id="'+editId+'"]').click();
            @endif
        @endif
    });
</script>
@endpush