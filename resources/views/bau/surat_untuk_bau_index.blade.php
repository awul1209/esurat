@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4 mt-4">
        <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">Inbox BAU (Surat Masuk)</h6>
            
            {{-- TOMBOL INPUT MANUAL (CRUD) --}}
            <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahInbox">
                <i class="bi bi-plus-lg me-1"></i> Catat Surat Manual
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratBau" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Tipe</th>
                            <th>Asal Surat</th>
                            <th>Perihal</th>
                            <th>Tgl Diterima/Surat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratUntukBau as $surat)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                @if($surat->jenis_surat == 'Internal')
                                    <span class="badge bg-primary">Internal</span>
                                @else
                                    <span class="badge bg-success">Eksternal</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold">{{ $surat->surat_dari }}</span><br>
                                <small class="text-muted">{{ $surat->nomor_surat }}</small>
                            </td>
                            <td>{{ $surat->perihal }}</td>
                            <td>
                                {{ $surat->diterima_tanggal ? \Carbon\Carbon::parse($surat->diterima_tanggal)->format('d M Y') : '-' }}
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    {{-- TOMBOL LIHAT (MODAL PREVIEW) --}}
                                    <button type="button" class="btn btn-sm btn-info text-white " 
                                            title="Lihat File"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#previewFileModal"
                                            data-title="{{ $surat->perihal }}"
                                            data-file="{{ Storage::url($surat->file_surat) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    {{-- CRUD: HANYA JIKA INPUTAN MANUAL --}}
                                    @if($surat->is_manual && $surat->user_id == Auth::id())
                                        {{-- Edit --}}
                                        <button class="btn btn-sm btn-warning text-white ms-1 me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditInbox{{ $surat->id }}"
                                                title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        {{-- Hapus --}}
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

                        {{-- MODAL EDIT (Looping) --}}
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
                                        <div class="modal-body">
                                            <div class="mb-2"><label>Nomor Surat</label><input type="text" name="nomor_surat" class="form-control" value="{{ $surat->nomor_surat }}" required></div>
                                            <div class="mb-2"><label>Asal Surat</label><input type="text" name="surat_dari" class="form-control" value="{{ $surat->surat_dari }}" required></div>
                                            <div class="mb-2"><label>Perihal</label><input type="text" name="perihal" class="form-control" value="{{ $surat->perihal }}" required></div>
                                            <div class="row">
                                                <div class="col-6 mb-2">
                                                    <label>Tgl Surat</label>
                                                    <input type="date" name="tanggal_surat" class="form-control" value="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('Y-m-d') }}" required>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label>Tgl Diterima</label>
                                                    <input type="date" name="diterima_tanggal" class="form-control" value="{{ \Carbon\Carbon::parse($surat->diterima_tanggal)->format('Y-m-d') }}" required>
                                                </div>
                                            </div>
                                            <div class="mb-2"><label>File (Opsional)</label><input type="file" name="file_surat" class="form-control"></div>
                                        </div>
                                        <div class="modal-footer">
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

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambahInbox" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('bau.inbox.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Catat Surat Masuk (Manual)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    
                    {{-- TAMBAHAN: TIPE SURAT --}}
                    <div class="mb-3">
                        <label>Tipe Surat <span class="text-danger">*</span></label>
                        <select name="tipe_surat" class="form-select" required>
                            <option value="eksternal">Eksternal (Instansi Luar/Pusat)</option>
                            <option value="internal">Internal (Satker/Unit Dalam)</option>
                        </select>
                        <div class="form-text text-muted small">Pilih "Internal" jika surat fisik dari Satker sendiri.</div>
                    </div>

                    <div class="mb-3">
                        <label>Nomor Surat <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_surat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Asal Surat <span class="text-danger">*</span></label>
                        <input type="text" name="surat_dari" class="form-control" placeholder="Instansi / Pengirim" required>
                    </div>
                    <div class="mb-3">
                        <label>Perihal <span class="text-danger">*</span></label>
                        <input type="text" name="perihal" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Tgl Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tgl Diterima</label>
                            <input type="date" name="diterima_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>File Surat <span class="text-danger">*</span></label>
                        <input type="file" name="file_surat" class="form-control" accept=".pdf,.jpg,.png" required>
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
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="downloadBtn" class="btn btn-primary" download>
                    <i class="bi bi-download me-1"></i> Download File
                </a>
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

            container.innerHTML = ''; // Bersihkan

            if (fileUrl) {
                var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];

                if (ext === 'pdf') {
                    container.innerHTML = '<iframe src="' + fileUrl + '" width="100%" height="100%" style="border:none;"></iframe>';
                } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) {
                    container.innerHTML = '<img src="' + fileUrl + '" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">';
                } else {
                    container.innerHTML = `
                        <div class="text-center p-5">
                            <i class="bi bi-file-earmark-text h1 text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3">Preview tidak tersedia untuk format ini.</h5>
                            <p class="text-muted">Silakan klik tombol download di bawah.</p>
                        </div>
                    `;
                }
            } else {
                container.innerHTML = '<div class="text-center p-5 text-danger">File tidak ditemukan.</div>';
            }
        });

        // Bersihkan saat ditutup
        previewModal.addEventListener('hidden.bs.modal', function () {
            var container = previewModal.querySelector('#fileContainer');
            container.innerHTML = ''; 
        });
    });
</script>
@endpush