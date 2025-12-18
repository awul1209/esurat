@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    /* CSS 13px agar konsisten */
    #tabelSuratUnified, .dataTables_wrapper, .modal-body { font-size: 13px !important; }
    .dataTables_wrapper .dataTables_paginate .page-link { font-size: 0.85rem !important; padding: 0.3rem 0.6rem !important; }
    
    .info-modal-label { width: 140px; font-weight: 600; }
    .info-modal-data { word-break: break-word; }

    /* CSS Khusus Checkbox List Delegasi */
    .checklist-container {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0.5rem;
        background-color: #fff;
    }
    .form-check {
        margin-bottom: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

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
        {{-- HEADER DENGAN TOMBOL TAMBAH --}}
        <div class="card-header py-3 bg-light border-0 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">Daftar Semua Surat Masuk Eksternal</h6>
            
            {{-- TOMBOL TAMBAH DATA (Input Manual Satker) --}}
            <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahSuratModal">
                <i class="bi bi-plus-lg me-1"></i> Input Surat Manual
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSuratUnified" class="table table-hover align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">No</th>
                            <th scope="col">Asal Surat</th>
                            <th scope="col">Perihal</th>
                            <th scope="col">Tgl. Diterima</th>
                            <th scope="col">Jalur Penerimaan</th>
                            <th scope="col">Status / Posisi</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Menggabungkan Surat Disposisi dan Surat Edaran
                            $allSurat = $suratMasukSatker->merge($suratEdaran)->unique('id')->sortByDesc('diterima_tanggal');
                            $mySatkerId = Auth::user()->satker_id;
                            $myUserId = Auth::id(); // ID User Login
                        @endphp

                        @foreach ($allSurat as $index => $surat)
                            @php
                                $isEdaran = isset($surat->pivot);
                                
                                // LOGIKA 1: Apakah surat ini inputan saya sendiri?
                                $isMyInput = ($surat->user_id == $myUserId);

                                // FILTER TAMPILAN (Status)
                                $statusBolehDilihat = ['di_satker', 'arsip_satker', 'disimpan', 'selesai', 'selesai_edaran'];
                                if (!$isEdaran && !in_array($surat->status, $statusBolehDilihat)) { continue; }

                                // LOGIKA JALUR
                                $myDisposisi = $surat->disposisis->where('tujuan_satker_id', $mySatkerId)->first();
                                $isDirectToMe = ($surat->tujuan_satker_id == $mySatkerId);
                                
                                $catatanRektor = $myDisposisi ? $myDisposisi->catatan_rektor : ($surat->disposisis->last()->catatan_rektor ?? '-');

                                // LOGIKA DELEGASI
                                $myDelegations = $surat->delegasiPegawai->filter(function($pegawai) use ($mySatkerId) {
                                    return $pegawai->satker_id == $mySatkerId;
                                });
                                
                                $delegatedCount = $myDelegations->count();
                                $lastMyDelegation = $myDelegations->sortByDesc('pivot.created_at')->first();
                                $catatanSatker = $lastMyDelegation ? ($lastMyDelegation->pivot->catatan ?? '-') : '-';

                                // LOGIKA STATUS & TOMBOL
                                $isProcessed = false;
                                $statusBadge = '';
                                
                                $isLocalDone = ($myDisposisi && $myDisposisi->status_penerimaan == 'selesai');
                                $isGlobalDone = (!$myDisposisi && in_array($surat->status, ['arsip_satker', 'disimpan', 'selesai']));

                                if ($isLocalDone || $isGlobalDone) {
                                    $isProcessed = true;
                                    $statusBadge = '<span class="badge bg-secondary">Selesai (Diarsipkan)</span>';
                                    if ($delegatedCount > 0) {
                                        $firstPegawaiName = $myDelegations->first()->name;
                                        $statusText = "Delegasi: " . $firstPegawaiName;
                                        if($delegatedCount > 1) {
                                            $sisa = $delegatedCount - 1;
                                            $statusBadge = '<span class="badge bg-primary">'.$statusText.' +'.$sisa.'</span>';
                                        } else {
                                            $statusBadge = '<span class="badge bg-primary">'.$statusText.'</span>';
                                        }
                                    }
                                } elseif ($isEdaran && $surat->pivot->status == 'diteruskan_internal') {
                                    $isProcessed = true;
                                    $statusBadge = '<span class="badge bg-success">Disebarkan</span>';
                                } else {
                                    $statusBadge = '<span class="badge bg-warning text-dark">Perlu Tindak Lanjut</span>';
                                }
                            @endphp
                            
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>

                                <td>
                                    {{ $surat->surat_dari }}
                                    <br><small class="text-muted">{{ $surat->nomor_surat }}</small>
                                    @if($isEdaran) <br><span class="badge bg-info text-dark">Edaran</span> @endif
                                </td>
                                <td>{{ $surat->perihal }}</td>
                                <td>{{ $surat->diterima_tanggal->isoFormat('D MMM YYYY') }}</td>
                                
                                {{-- KOLOM JALUR PENERIMAAN --}}
                                <td>
                                    @if ($myDisposisi)
                                        <span class="text-primary fw-bold">Disposisi Rektor</span>
                                    @elseif ($isMyInput)
                                        {{-- Tanda Input Manual --}}
                                        <span class="text-info fw-bold"><i class="bi bi-keyboard"></i> Input Manual</span>
                                    @elseif ($isDirectToMe)
                                        <span class="text-success fw-bold">Langsung dari BAU</span>
                                    @elseif ($isEdaran)
                                        Edaran
                                    @endif
                                </td>

                                <td>{!! $statusBadge !!}</td>
                                
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        
                                        {{-- TOMBOL LIHAT (Semua Bisa Lihat) --}}
                                        <button type="button" class="btn btn-sm btn-info" 
                                            title="Lihat Detail"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailSuratModal"
                                            data-no-agenda="{{ $surat->no_agenda }}"
                                            data-nomor-surat="{{ $surat->nomor_surat }}"
                                            data-perihal="{{ $surat->perihal }}"
                                            data-asal-surat="{{ $surat->surat_dari }}"
                                            data-tanggal-surat="{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}"
                                            data-tanggal-diterima="{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}"
                                            data-catatan-rektor="{{ $catatanRektor }}"
                                            data-catatan-satker="{{ $catatanSatker }}"
                                            data-file-url="{{ Storage::url($surat->file_surat) }}">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>

                                        {{-- JIKA INPUTAN SENDIRI: Show Edit & Hapus --}}
                                        @if($isMyInput)
                                            {{-- EDIT --}}
                                            <button class="btn btn-sm btn-warning text-white" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editSuratModal{{ $surat->id }}"
                                                    title="Edit Data">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>

                                            {{-- HAPUS --}}
                                            <form action="{{ route('satker.surat.destroy', $surat->id) }}" method="POST" onsubmit="return confirm('Yakin hapus surat manual ini?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- ACTION LANJUTAN (Delegasi/Selesai) --}}
                                        {{-- Tetap dimunculkan agar Input Manual pun bisa diproses (Didelegasikan/Diarsipkan) --}}
                                        @if(!$isProcessed)
                                            <button class="btn btn-sm btn-primary" title="Delegasi" data-bs-toggle="modal" data-bs-target="#delegasiModal" data-id="{{ $surat->id }}" data-perihal="{{ $surat->perihal }}">
                                                <i class="bi bi-person-fill-add"></i>
                                            </button>
                                            
                                            @if($isEdaran)
                                                <form action="{{ route('satker.surat.broadcastInternal', $surat->id) }}" method="POST" onsubmit="return confirm('Sebarkan?');">
                                                    @csrf <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-people-fill"></i></button>
                                                </form>
                                            @else
                                                <form action="{{ route('satker.surat.arsipkan', $surat->id) }}" method="POST" onsubmit="return confirm('Arsipkan?');">
                                                    @csrf <button type="submit" class="btn btn-sm btn-secondary" title="Selesai"><i class="bi bi-clipboard-check-fill"></i></button>
                                                </form>
                                            @endif
                                        @endif
                                        
                                        {{-- TOMBOL CETAK (HANYA Jika Disposisi Rektor) --}}
                                        @if($isProcessed && $myDisposisi && !$isMyInput)
                                            <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak Lembar Disposisi">
                                                <i class="bi bi-printer-fill"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- MODAL EDIT (Looping inside loop) --}}
                            @if($isMyInput)
                            <div class="modal fade" id="editSuratModal{{ $surat->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('satker.surat.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Surat Manual</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2"><label>Nomor Surat</label><input type="text" name="nomor_surat" class="form-control" value="{{ $surat->nomor_surat }}" required></div>
                                                <div class="mb-2"><label>Asal Surat</label><input type="text" name="surat_dari" class="form-control" value="{{ $surat->surat_dari }}" required></div>
                                                <div class="mb-2"><label>Perihal</label><input type="text" name="perihal" class="form-control" value="{{ $surat->perihal }}" required></div>
                                                <div class="row">
                                                    <div class="col-6 mb-2"><label>Tgl Surat</label><input type="date" name="tanggal_surat" class="form-control" value="{{ $surat->tanggal_surat->format('Y-m-d') }}" required></div>
                                                    <div class="col-6 mb-2"><label>Tgl Diterima</label><input type="date" name="diterima_tanggal" class="form-control" value="{{ $surat->diterima_tanggal->format('Y-m-d') }}" required></div>
                                                </div>
                                                <div class="mb-2"><label>File (Kosongkan jika tidak ubah)</label><input type="file" name="file_surat" class="form-control"></div>
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

{{-- MODAL TAMBAH SURAT MANUAL (PLUS DELEGASI) --}}
<div class="modal fade" id="tambahSuratModal" tabindex="-1">
    <div class="modal-dialog modal-lg"> {{-- Pakai modal-lg agar lebih lebar --}}
        <div class="modal-content">
            <form action="{{ route('satker.surat.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>Input Surat Masuk (Manual)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    
                    {{-- BAGIAN 1: DATA SURAT --}}
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Data Surat</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_surat" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Asal Surat <span class="text-danger">*</span></label>
                            <input type="text" name="surat_dari" class="form-control" placeholder="Instansi Pengirim" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Perihal <span class="text-danger">*</span></label>
                        <input type="text" name="perihal" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Tgl Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Tgl Diterima</label>
                            <input type="date" name="diterima_tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>File (PDF/Gambar) <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" class="form-control" accept=".pdf,.jpg,.png" required>
                        </div>
                    </div>

                    {{-- BAGIAN 2: DELEGASI (OPSIONAL) --}}
                    <h6 class="fw-bold text-success border-bottom pb-2 mt-4 mb-3">
                        Delegasi ke Pegawai <small class="text-muted fw-normal">(Opsional)</small>
                    </h6>

                    <div class="mb-3">
                        <label class="form-label">Pilih Pegawai:</label>
                        <div class="checklist-container" style="max-height: 150px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: 5px;">
                            @if($daftarPegawai->isEmpty())
                                <p class="text-muted text-center small my-1">Tidak ada pegawai terdaftar.</p>
                            @else
                                @foreach ($daftarPegawai as $pegawai)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="delegasi_user_ids[]" value="{{ $pegawai->id }}" id="input_pegawai_{{ $pegawai->id }}">
                                        <label class="form-check-label" for="input_pegawai_{{ $pegawai->id }}">
                                            {{ $pegawai->name }}
                                        </label>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class="form-text text-muted small">Jika dikosongkan, surat hanya tersimpan di arsip Satker.</div>
                    </div>

                    <div class="mb-3">
                        <label>Catatan Delegasi:</label>
                        <textarea name="catatan_delegasi" class="form-control" rows="2" placeholder="Contoh: Segera tindak lanjuti..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 1: DETAIL SURAT --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Surat Masuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <h4 class="mb-3" id="modal-perihal"></h4>
                        <table class="table table-borderless table-sm small">
                           <tr><td class="info-modal-label">No. Agenda</td><td class="info-modal-data">: <span id="modal-no-agenda"></span></td></tr>
                           <tr><td class="info-modal-label">Nomor Surat</td><td class="info-modal-data">: <span id="modal-nomor-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Asal Surat</td><td class="info-modal-data">: <span id="modal-asal-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Tanggal Surat</td><td class="info-modal-data">: <span id="modal-tanggal-surat"></span></td></tr>
                           <tr><td class="info-modal-label">Tanggal Diterima</td><td class="info-modal-data">: <span id="modal-tanggal-diterima"></span></td></tr>
                           <tr class="border-top">
                               <td class="info-modal-label pt-2">Catatan Rektor</td>
                               <td class="info-modal-data pt-2">: <span id="modal-catatan-rektor" class="fst-italic text-muted"></span></td>
                           </tr>
                           <tr>
                               <td class="info-modal-label text-primary">Instruksi Anda</td>
                               <td class="info-modal-data">: <span id="modal-catatan-satker" class="fw-bold text-primary"></span></td>
                           </tr>
                        </table>
                    </div>
                    <div class="col-md-7">
                        <div id="modal-file-preview-wrapper" style="height: 70vh; border: 1px solid #dee2e6; border-radius: .375rem;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="modal-download-button" class="btn btn-primary" download><i class="bi bi-download me-2"></i> Download File</a>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 2: DELEGASI --}}
<div class="modal fade" id="delegasiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formDelegasi" action="" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Delegasikan Surat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Perihal Surat:</label>
                        <p id="delegasi-perihal" class="text-muted mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Pegawai:</label>
                        <div class="checklist-container">
                            @if($daftarPegawai->isEmpty())
                                <p class="text-muted text-center small my-2">Tidak ada pegawai.</p>
                            @else
                                @foreach ($daftarPegawai as $pegawai)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tujuan_user_ids[]" value="{{ $pegawai->id }}" id="pegawai_{{ $pegawai->id }}">
                                        <label class="form-check-label" for="pegawai_{{ $pegawai->id }}">{{ $pegawai->name }}</label>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="catatan_satker" class="form-label">Catatan:</label>
                        <textarea class="form-control" id="catatan_satker" name="catatan_satker" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Delegasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
<script>
    $(document).ready(function () {
        new DataTable('#tabelSuratUnified', {
            pagingType: 'simple_numbers',
            order: [[ 3, 'desc' ]],
            language: {
                search: "Cari:", lengthMenu: "_MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                paginate: { next: "Next", previous: "Prev" }
            }
        });

        // Detail Modal
        var detailModal = document.getElementById('detailSuratModal');
        detailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            // Set Data
            detailModal.querySelector('#modal-perihal').textContent = button.getAttribute('data-perihal');
            detailModal.querySelector('#modal-no-agenda').textContent = button.getAttribute('data-no-agenda') || '-';
            detailModal.querySelector('#modal-nomor-surat').textContent = button.getAttribute('data-nomor-surat');
            detailModal.querySelector('#modal-asal-surat').textContent = button.getAttribute('data-asal-surat');
            detailModal.querySelector('#modal-tanggal-surat').textContent = button.getAttribute('data-tanggal-surat');
            detailModal.querySelector('#modal-tanggal-diterima').textContent = button.getAttribute('data-tanggal-diterima');
            
            var catatanRektor = button.getAttribute('data-catatan-rektor');
            detailModal.querySelector('#modal-catatan-rektor').textContent = (catatanRektor && catatanRektor !== '-') ? catatanRektor : '(Tidak ada)';
            
            var catatanSatker = button.getAttribute('data-catatan-satker');
            detailModal.querySelector('#modal-catatan-satker').textContent = catatanSatker;

            var fileUrl = button.getAttribute('data-file-url');
            detailModal.querySelector('#modal-download-button').href = fileUrl;
            
            var wrapper = detailModal.querySelector('#modal-file-preview-wrapper');
            if(fileUrl && fileUrl.length > 5) {
                var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0]; 
                if(ext === 'pdf'){
                    wrapper.innerHTML = '<iframe src="'+fileUrl+'" width="100%" height="100%" frameborder="0"></iframe>';
                } else if(['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                    wrapper.innerHTML = '<img src="'+fileUrl+'" class="img-fluid" style="max-height: 100%; width: 100%; object-fit: contain;">';
                } else {
                    wrapper.innerHTML = '<div class="text-center p-5"><p class="mt-3">Preview tidak didukung.</p></div>';
                }
            } else {
                 wrapper.innerHTML = '<div class="text-center p-5"><p class="mt-3">File tidak ditemukan.</p></div>';
            }
        });

        // Delegasi Modal
        var delegasiModal = document.getElementById('delegasiModal');
        delegasiModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var perihal = button.getAttribute('data-perihal');
            
            delegasiModal.querySelector('#delegasi-perihal').textContent = perihal;
            var checkboxes = delegasiModal.querySelectorAll('.form-check-input');
            checkboxes.forEach(cb => cb.checked = false);
            var form = delegasiModal.querySelector('#formDelegasi');
            form.action = '/satker/surat/' + id + '/delegasi-ke-pegawai'; 
        });
    });
</script>
@endpush