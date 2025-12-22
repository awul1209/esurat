@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- CARD UTAMA --}}
    <div class="card shadow border-0 mb-4 rounded-3">
        
        {{-- CARD HEADER: FILTER & ACTIONS --}}
        <div class="card-header bg-white py-4">
            {{-- Form Filter ini membungkus semua input dan tombol action agar filter terbawa saat Export --}}
            <form action="{{ route('bau.inbox') }}" method="GET"> 
                <div class="row g-3 align-items-end">
                    
                    {{-- 1. BAGIAN FILTER (KIRI) --}}
                    <div class="col-lg-7">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted mb-1">Tipe Surat</label>
                                <select name="tipe_surat" class="form-select form-select-sm">
                                    <option value="">-- Semua --</option>
                                    <option value="Internal" {{ request('tipe_surat') == 'Internal' ? 'selected' : '' }}>Internal</option>
                                    <option value="Eksternal" {{ request('tipe_surat') == 'Eksternal' ? 'selected' : '' }}>Eksternal</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 2. BAGIAN TOMBOL AKSI (KANAN) --}}
                    <div class="col-lg-5 text-lg-end">
                        <div class="d-flex gap-2 justify-content-lg-end flex-wrap">
                            
                            {{-- Tombol Cari (Filter) --}}
                            <button type="submit" class="btn btn-sm btn-primary" title="Terapkan Filter">
                                <i class="bi bi-search"></i> Cari
                            </button>
                            
                            {{-- Tombol Reset --}}
                            <a href="{{ route('bau.inbox') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>

                            {{-- TOMBOL EXPORT EXCEL (BARU) --}}
                            {{-- Menggunakan formaction agar mengirim data filter ke route export --}}
                            <button type="submit" formaction="{{ route('bau.inbox.export') }}" class="btn btn-sm btn-success text-white shadow-sm">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i> Excel
                            </button>

                            {{-- TOMBOL CATAT SURAT (MODAL) --}}
                            <button type="button" class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahInbox">
                                <i class="bi bi-plus-lg me-1"></i> Catat Surat
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        {{-- CARD BODY: TABEL --}}
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratBau" class="table table-hover align-middle table-sm w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="10%">Tipe</th>
                            <th width="20%">Asal Surat</th>
                            <th width="30%">Perihal</th>
                            <th width="15%">Tgl Surat</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratUntukBau as $surat)
                        <tr>
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            <td>
                                @if($surat->jenis_surat == 'Internal')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2">Internal</span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2">Eksternal</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $surat->surat_dari }}</div>
                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i>{{ $surat->nomor_surat }}</div>
                            </td>
                            <td>{{ $surat->perihal }}</td>
                            <td>
                                <div class="small fw-bold">{{ \Carbon\Carbon::parse($surat->tanggal_surat)->isoFormat('D MMM Y') }}</div>
                                <div class="small text-muted" style="font-size: 0.75rem">
                                    Diterima: {{ $surat->diterima_tanggal ? \Carbon\Carbon::parse($surat->diterima_tanggal)->isoFormat('D MMM Y') : '-' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    {{-- Lihat --}}
                                    <button type="button" class="btn btn-sm btn-info text-white" 
                                        title="Lihat File"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#previewFileModal"
                                        data-title="{{ $surat->perihal }}"
                                        data-file="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    {{-- Cetak (Khusus Disposisi) --}}
@php
    // Gunakan '?? collect([])' agar jika null dianggap array kosong
    $riwayatList = $surat->riwayats ?? collect([]); 
    $isDisposisiRektor = $riwayatList->where('status_aksi', 'Disposisi Rektor')->isNotEmpty();
@endphp

                                    @if($isDisposisiRektor)
                                        <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" class="btn btn-sm btn-purple text-white" title="Cetak Disposisi" style="background-color: #6f42c1; border-color: #6f42c1;">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    @endif

                                    {{-- Edit/Hapus (Khusus Inputan Manual User Ini) --}}
                                    @if($surat->is_manual && $surat->user_id == Auth::id())
                                        <button class="btn btn-sm btn-warning text-white" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditInbox{{ $surat->id }}"
                                            title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('bau.inbox.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus surat ini?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- MODAL EDIT --}}
                        @if($surat->is_manual && $surat->user_id == Auth::id())
                        <div class="modal fade" id="modalEditInbox{{ $surat->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('bau.inbox.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Surat Masuk</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <div class="mb-2"><label class="fw-bold small">Nomor Surat</label><input type="text" name="nomor_surat" class="form-control" value="{{ $surat->nomor_surat }}" required></div>
                                            <div class="mb-2"><label class="fw-bold small">Asal Surat</label><input type="text" name="surat_dari" class="form-control" value="{{ $surat->surat_dari }}" required></div>
                                            <div class="mb-2"><label class="fw-bold small">Perihal</label><input type="text" name="perihal" class="form-control" value="{{ $surat->perihal }}" required></div>
                                            <div class="row">
                                                <div class="col-6 mb-2">
                                                    <label class="fw-bold small">Tgl Surat</label>
                                                    <input type="date" name="tanggal_surat" class="form-control" value="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('Y-m-d') }}" required>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="fw-bold small">Tgl Diterima</label>
                                                    <input type="date" name="diterima_tanggal" class="form-control" value="{{ \Carbon\Carbon::parse($surat->diterima_tanggal)->format('Y-m-d') }}" required>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="fw-bold small">File (Opsional)</label>
                                                <input type="file" name="file_surat" class="form-control">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH (SAMA SEPERTI SEBELUMNYA) --}}
<div class="modal fade" id="modalTambahInbox" tabindex="-1">
    <div class="modal-dialog modal-lg"> 
        <div class="modal-content">
            <form action="{{ route('bau.inbox.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Catat Surat Masuk (Manual)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Data Surat</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Tipe Surat <span class="text-danger">*</span></label>
                            <select name="tipe_surat" class="form-select" required>
                                <option value="eksternal">Eksternal</option>
                                <option value="internal">Internal</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_surat" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Asal Surat <span class="text-danger">*</span></label>
                        <input type="text" name="surat_dari" class="form-control" placeholder="Instansi / Pengirim" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Perihal <span class="text-danger">*</span></label>
                        <input type="text" name="perihal" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Diterima</label>
                            <input type="date" name="diterima_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">File Surat <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" class="form-control" accept=".pdf,.jpg,.png" required>
                        </div>
                    </div>
                    <h6 class="fw-bold text-success border-bottom pb-2 mt-3 mb-3">
                        Delegasi ke Pegawai <small class="text-muted fw-normal">(Opsional)</small>
                    </h6>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Pegawai:</label>
                        <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: 5px; background: #f8f9fa;">
                            @if(isset($daftarPegawai) && $daftarPegawai->isNotEmpty())
                                @foreach ($daftarPegawai as $pegawai)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="delegasi_user_ids[]" value="{{ $pegawai->id }}" id="pegawai_{{ $pegawai->id }}">
                                        <label class="form-check-label" for="pegawai_{{ $pegawai->id }}">{{ $pegawai->name }}</label>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted text-center small my-1">Tidak ada pegawai terdaftar di BAU.</p>
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Catatan Delegasi:</label>
                        <textarea name="catatan_delegasi" class="form-control" rows="2" placeholder="Instruksi untuk pegawai..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW FILE --}}
<div class="modal fade" id="previewFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="fileContainer" class="d-flex justify-content-center align-items-center bg-light" style="height: 75vh; width: 100%; overflow: hidden;">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="downloadBtn" class="btn btn-primary" download><i class="bi bi-download me-1"></i> Download File</a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        var previewModal = document.getElementById('previewFileModal');
        previewModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file');
            var title = button.getAttribute('data-title');
            var modalTitle = previewModal.querySelector('#previewTitle');
            var downloadBtn = previewModal.querySelector('#downloadBtn');
            var container = previewModal.querySelector('#fileContainer');

            modalTitle.textContent = title;
            downloadBtn.href = fileUrl;
            container.innerHTML = ''; 

            if (fileUrl) {
                var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
                if (ext === 'pdf') {
                    container.innerHTML = '<iframe src="' + fileUrl + '" width="100%" height="100%" style="border:none;"></iframe>';
                } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) {
                    container.innerHTML = '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">';
                } else {
                    container.innerHTML = `<div class="text-center p-5"><i class="bi bi-file-earmark-text h1 text-muted" style="font-size: 4rem;"></i><h5 class="mt-3">Preview tidak tersedia.</h5></div>`;
                }
            } else {
                container.innerHTML = '<div class="text-center p-5 text-danger">File tidak ditemukan.</div>';
            }
        });
        previewModal.addEventListener('hidden.bs.modal', function () {
            previewModal.querySelector('#fileContainer').innerHTML = ''; 
        });
    });
</script>
@endpush