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
            {{-- Form Filter --}}
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

                            {{-- TOMBOL EXPORT EXCEL --}}
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
                            <th width="25%">Perihal</th>
                            <th width="15%">Tgl Surat</th>
                            {{-- KOLOM BARU: STATUS --}}
                            <th width="10%" class="text-center">Status</th>
                            <th width="15%" class="text-center">Aksi</th>
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
    <td>{{ Str::limit($surat->perihal, 50) }}</td>
    <td>
        <div class="small fw-bold">{{ \Carbon\Carbon::parse($surat->tanggal_surat)->isoFormat('D MMM Y') }}</div>
        <div class="small text-muted" style="font-size: 0.75rem">
            Diterima: {{ $surat->diterima_tanggal ? \Carbon\Carbon::parse($surat->created_at)->isoFormat('D MMM Y') : '-' }}
        </div>
    </td>

{{-- KOLOM STATUS (PERBAIKAN PRIORITAS LOGIKA) --}}
<td class="text-center">
    @php
        // Ambil riwayat terakhir yang dilakukan oleh user BAU ini
        $lastActionBau = $surat->riwayats->where('user_id', Auth::id())->last();
        $statusAksi = $lastActionBau ? strtolower($lastActionBau->status_aksi) : '';
    @endphp

    {{-- 1. Cek Riwayat Aksi Terlebih Dahulu (Prioritas Utama) --}}
    @if(str_contains($statusAksi, 'delegasi'))
        <span class="badge bg-info text-white">
            <i class="bi bi-person-check-fill me-1"></i>Didelegasikan
        </span>
    @elseif(str_contains($statusAksi, 'informasi umum'))
        <span class="badge bg-primary text-white">
            <i class="bi bi-megaphone-fill me-1"></i>Disebar
        </span>
    
    {{-- 2. Cek Status 'Selesai' Hanya Jika Tidak Ada Riwayat Delegasi/Sebar --}}
    @elseif($surat->status == 'Selesai di BAU' || $surat->status == 'arsip_satker')
        <span class="badge bg-success">
            <i class="bi bi-check-circle-fill me-1"></i>Diarsipkan
        </span>

    {{-- 3. Cek Status Baru Masuk --}}
    @elseif($surat->status == 'Terkirim' || $surat->status == 'terkirim' || $surat->status == 'di_satker') 
        <span class="badge bg-warning text-dark">
            <i class="bi bi-envelope-fill me-1"></i>Baru Masuk
        </span>
    @else
        <span class="badge bg-secondary">
            {{ $surat->status ?? 'Menunggu' }}
        </span>
    @endif
</td>

 {{-- KOLOM AKSI --}}
<td class="text-center">
    <div class="btn-group" role="group">
        
        {{-- 1. TOMBOL LIHAT FILE --}}
        @php
            // Ambil riwayat delegasi spesifik (bukan informasi umum/arsip)
            $delegasiLogs = $surat->riwayats->filter(function($r) {
                return str_contains(strtolower($r->status_aksi), 'delegasi');
            })->map(function($r) {
                return [
                    'penerima' => $r->penerima ? $r->penerima->name : 'Pegawai',
                    'aksi' => $r->status_aksi,
                    'catatan' => $r->catatan ?? '-'
                ];
            })->values();
        @endphp

        <button type="button" class="btn btn-sm btn-info text-white me-2" 
            title="Lihat File"
            data-bs-toggle="modal" 
            data-bs-target="#previewFileModal"
            data-title="{{ $surat->perihal }}"
            data-file="{{ Storage::url($surat->file_surat) }}"
            data-delegasi="{{ json_encode($delegasiLogs) }}">
            <i class="bi bi-eye"></i>
        </button>

        {{-- 2. TOMBOL LOG RIWAYAT --}}
        <button type="button" class="btn btn-sm btn-secondary text-white" 
                title="Riwayat Surat"
                data-bs-toggle="modal" 
                data-bs-target="#riwayatModal" 
                data-url="{{ route('bau.riwayat.detail', $surat->id) }}">
            <i class="bi bi-clock-history"></i>
        </button>

        {{-- 3. TOMBOL CETAK DISPOSISI --}}
        @php
            $riwayatList = $surat->riwayats ?? collect([]); 
            $isDisposisiRektor = $riwayatList->where('status_aksi', 'Disposisi Rektor')->isNotEmpty();
            
            // Cek apakah BAU sudah pernah mendelegasikan atau menyebar
            $isAlreadyProcessed = $surat->riwayats->where('user_id', Auth::id())->filter(function($r) {
                $aksi = strtolower($r->status_aksi);
                return str_contains($aksi, 'delegasi') || str_contains($aksi, 'informasi');
            })->isNotEmpty();
        @endphp

        @if($isDisposisiRektor)
            <a href="{{ route('cetak.disposisi', $surat->id) }}" target="_blank" class="btn btn-sm btn-purple text-white ms-1" title="Cetak Disposisi" style="background-color: #6f42c1; border-color: #6f42c1;">
                <i class="bi bi-printer"></i>
            </a>
        @endif

        {{-- 4. EDIT/HAPUS (KHUSUS MANUAL) --}}
        @if($surat->is_manual && $surat->user_id == Auth::id())
            {{-- TOMBOL EDIT: Hanya muncul jika BELUM didelegasi/disebar --}}
            @if(!$isAlreadyProcessed)
<button class="btn btn-sm btn-warning text-white ms-1" 
        data-bs-toggle="modal" 
        data-bs-target="#modalEditInbox{{ $surat->id }}"
        title="Edit">
        <i class="bi bi-pencil"></i>
    </button>
            @endif

            {{-- TOMBOL HAPUS: Tetap muncul untuk surat manual milik sendiri --}}
{{-- TOMBOL HAPUS --}}
@if($surat->is_manual && $surat->user_id == Auth::id() && !$isAlreadyProcessed)
    <form action="{{ route('bau.inbox.destroy', $surat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Pindahkan surat ini ke tempat sampah?');">
        @csrf 
        @method('DELETE')
        <button class="btn btn-sm btn-danger ms-1" title="Hapus">
            <i class="bi bi-trash"></i>
        </button>
    </form>
@endif
        @endif
        
        {{-- 5. TOMBOL DELEGASI & ARSIPKAN --}}
        @if($surat->status != 'Selesai di BAU' && !$isAlreadyProcessed)
            <button type="button" class="btn btn-sm btn-primary text-white ms-1" 
                title="Delegasikan ke Pegawai"
                data-bs-toggle="modal" 
                data-bs-target="#delegasiModalBAU" 
                data-id="{{ $surat->id }}" 
                data-perihal="{{ $surat->perihal }}" 
                data-tabel="{{ $surat->is_manual ? 'surat' : 'surat_keluar' }}">
                <i class="bi bi-share-fill"></i>
            </button>

            <form action="{{ route('bau.inbox.arsipkan', $surat->id) }}" method="POST" onsubmit="return confirm('Tandai sebagai Diterima/Diarsipkan oleh BAU?');">
                @csrf 
                <button type="submit" class="ms-1 btn btn-sm btn-secondary" title="Terima & Arsipkan">
                    <i class="bi bi-clipboard-check-fill"></i>
                </button>
            </form>
        @endif

    </div>
</td>

        </div>
    </td>
</tr>

    {{-- MODAL EDIT (Include di dalam loop agar ID unik) --}}
   {{-- MODAL EDIT --}}
@if($surat->is_manual && $surat->user_id == Auth::id())
<div class="modal fade" id="modalEditInbox{{ $surat->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('bau.inbox.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header bg-warning text-white py-3">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Data Surat Masuk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4 pb-5">
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Identitas Surat</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Tipe Surat <span class="text-danger">*</span></label>
                            {{-- Setiap modal punya onchange ke function yang unik berdasarkan ID --}}
                            <select name="tipe_surat" id="tipe_edit_{{ $surat->id }}" class="form-select shadow-sm" required onchange="toggleEditSumber({{ $surat->id }})">
                                <option value="eksternal" {{ $surat->tipe_surat == 'eksternal' ? 'selected' : '' }}>Eksternal (Luar Kampus)</option>
                                <option value="internal" {{ $surat->tipe_surat == 'internal' ? 'selected' : '' }}>Internal (Manual / Satker)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_surat" class="form-control shadow-sm" value="{{ $surat->nomor_surat }}" required>
                        </div>
                    </div>

                    {{-- Kontainer Asal Eksternal --}}
                    <div id="group_eksternal_edit_{{ $surat->id }}" class="mb-3" style="{{ $surat->tipe_surat == 'internal' ? 'display:none' : '' }}">
                        <label class="form-label small fw-bold">Asal Surat / Pengirim <span class="text-danger">*</span></label>
                        <input type="text" name="surat_dari" id="input_eksternal_edit_{{ $surat->id }}" class="form-control shadow-sm" value="{{ $surat->surat_dari }}" {{ $surat->tipe_surat == 'eksternal' ? 'required' : '' }}>
                    </div>

                    {{-- Kontainer Asal Internal (Daftar Satker) --}}
                    <div id="group_internal_edit_{{ $surat->id }}" class="mb-3" style="{{ $surat->tipe_surat == 'eksternal' ? 'display:none' : '' }}">
                        <label class="form-label small fw-bold">Pilih Satker Pengirim <span class="text-danger">*</span></label>
                        <select name="satker_asal_id" id="select_internal_edit_{{ $surat->id }}" class="form-select shadow-sm" {{ $surat->tipe_surat == 'internal' ? 'required' : '' }}>
                            <option value="">-- Pilih Satker --</option>
                            @foreach($satkers as $s)
                                <option value="{{ $s->id }}" {{ $surat->surat_dari == $s->nama_satker ? 'selected' : '' }}>{{ $s->nama_satker }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Perihal <span class="text-danger">*</span></label>
                        <textarea name="perihal" class="form-control shadow-sm" rows="2" required>{{ $surat->perihal }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control shadow-sm" value="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Diterima</label>
                            <input type="date" name="diterima_tanggal" class="form-control shadow-sm" value="{{ \Carbon\Carbon::parse($surat->diterima_tanggal)->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">File (Kosongkan jika tidak ganti)</label>
                            <input type="file" name="file_surat" class="form-control shadow-sm" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted" style="font-size: 0.7rem">File saat ini: <a href="{{ Storage::url($surat->file_surat) }}" target="_blank">Lihat File</a></small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold text-white shadow-sm">Simpan Perubahan</button>
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

{{-- MODAL RIWAYAT --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="riwayatModalLabel">Riwayat Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="riwayatModalBody" style="max-height: 70vh; overflow-y: auto;">
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


{{-- MODAL TAMBAH INBOX MANUAL BAU --}}
{{-- MODAL TAMBAH INBOX MANUAL BAU --}}
<div class="modal fade" id="modalTambahInbox" tabindex="-1" aria-hidden="true">
    {{-- 1. Tambahkan class modal-dialog-scrollable di sini --}}
    <div class="modal-dialog modal-lg modal-dialog-scrollable"> 
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('bau.inbox.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title"><i class="bi bi-journal-plus me-2"></i>Catat Surat Masuk Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                {{-- 2. Berikan style overflow dan padding extra di bawah --}}
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    
                    {{-- SECTION 1: DATA SURAT --}}
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-file-earmark-text me-1"></i> Identitas Surat
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Tipe Surat <span class="text-danger">*</span></label>
                            <select name="tipe_surat" id="tipe_surat_manual" class="form-select shadow-sm" required onchange="toggleSumberSurat()">
                                <option value="eksternal">Eksternal (Luar Kampus)</option>
                                <option value="internal">Internal (Manual / Satker)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_surat" class="form-control shadow-sm" placeholder="Nomor surat resmi" required>
                        </div>
                    </div>

                    <div id="group_asal_eksternal" class="mb-3">
                        <label class="form-label small fw-bold">Asal Surat / Pengirim <span class="text-danger">*</span></label>
                        <input type="text" name="surat_dari" id="input_asal_eksternal" class="form-control shadow-sm" placeholder="Instansi Pengirim" required>
                    </div>

                    <div id="group_asal_internal" class="mb-3" style="display: none;">
                        <label class="form-label small fw-bold">Pilih Satker Pengirim <span class="text-danger">*</span></label>
                        <select name="satker_asal_id" id="select_satker_internal" class="form-select shadow-sm">
                            <option value="">-- Pilih Satker --</option>
                            @foreach($satkers as $s)
                                <option value="{{ $s->id }}">{{ $s->nama_satker }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Perihal <span class="text-danger">*</span></label>
                        <textarea name="perihal" class="form-control shadow-sm" rows="2" placeholder="Isi perihal surat" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Tgl Diterima</label>
                            <input type="date" name="diterima_tanggal" class="form-control shadow-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">File Surat <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat" class="form-control shadow-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>

                    {{-- SECTION 2: TINDAK LANJUT --}}
                    <h6 class="fw-bold text-success border-bottom pb-2 mt-4 mb-3">
                        <i class="bi bi-send-check me-1"></i> Tindak Lanjuti
                    </h6>
                    
                    <div class="bg-light p-3 rounded-3 border mb-4"> {{-- Tambahkan mb-4 agar tidak mepet bawah --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-success">Pilih Alur Surat:</label>
                            <select name="target_tipe" id="target_tipe_manual" class="form-select shadow-sm border-success" onchange="toggleManualDelegasi()">
                                <option value="arsip">Simpan & Arsipkan Saja (Hanya BAU)</option>
                                <option value="pribadi">Delegasikan ke Pegawai Tertentu (Pribadi)</option>
                                <option value="semua">Sebarluaskan ke Semua Pegawai BAU (Umum)</option>
                            </select>
                        </div>

                        <div id="group_pegawai_manual" style="display: none;" class="mb-3">
                            <label class="form-label small fw-bold">Pilih Pegawai BAU:</label>
                            <div class="p-2 bg-white rounded border shadow-sm" style="max-height: 200px; overflow-y: auto;">
                                @foreach ($pegawaiList as $pegawai)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="delegasi_user_ids[]" value="{{ $pegawai->id }}" id="manual_pegawai_{{ $pegawai->id }}">
                                        <label class="form-check-label small" for="manual_pegawai_{{ $pegawai->id }}">{{ $pegawai->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div id="group_catatan_manual" style="display: none;" class="mb-2">
                            <label class="form-label small fw-bold">Catatan / Instruksi:</label>
                            <textarea name="catatan_delegasi" id="catatan_manual" class="form-control shadow-sm" rows="2" placeholder="Instruksi khusus..."></textarea>
                        </div>
                    </div>

                    {{-- Extra space di paling bawah agar scroll tidak mepet tombol --}}
                    <div style="height: 20px;"></div>
                </div>

                <div class="modal-footer bg-light border-top py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-all me-1"></i> Simpan & Selesaikan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW FILE --}}
<div class="modal fade" id="previewFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="previewTitle">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                {{-- AREA INFO DELEGASI --}}
                <div id="delegasiInfo" class="bg-warning bg-opacity-10 p-3 border-bottom d-none">
                    <h6 class="fw-bold mb-2 small text-uppercase"><i class="bi bi-person-check-fill me-1"></i> Informasi Delegasi Pegawai:</h6>
                    <div id="delegasiList" class="row g-2"></div>
                </div>

                <div id="fileContainer" class="d-flex justify-content-center align-items-center bg-dark bg-opacity-10" style="height: 70vh; width: 100%; overflow: hidden;">
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


<!-- modal delegasi BAU -->
<div class="modal fade" id="delegasiModalBAU" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('bau.inbox.delegate') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="delegasi_surat_id">
            <input type="hidden" name="asal_tabel" id="delegasi_asal_tabel">

            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-up me-2"></i> Delegasikan Surat (BAU)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold">Perihal Surat</label>
                        <p id="text_perihal" class="text-muted small italic"></p>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Opsi Delegasi</label>
                        <select name="target_tipe" id="target_tipe" class="form-select shadow-sm" required onchange="togglePegawaiBAU()">
                            <option value="pribadi">Pegawai Spesifik (Disposisi)</option>
                            <option value="semua">Sebar ke Semua Pegawai (Informasi)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="group_pegawai">
                        <label class="small fw-bold">Pilih Pegawai (Bisa lebih dari satu)</label>
                        <select name="user_id[]" id="select_pegawai" class="form-select shadow-sm select2-bau" multiple="multiple">
                            @foreach($pegawaiList as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="group_instruksi">
                        <div class="mb-3">
                            <label class="small fw-bold">Klasifikasi / Instruksi</label>
                            <input type="text" name="klasifikasi" id="input_klasifikasi" class="form-control shadow-sm" placeholder="Contoh: Segera tindak lanjuti" required>
                        </div>
                        <div class="mb-0">
                            <label class="small fw-bold">Catatan Tambahan</label>
                            <textarea name="catatan" id="input_catatan" class="form-control shadow-sm" rows="3" placeholder="Masukkan instruksi khusus..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Delegasi</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    /* CSS Timeline */
    ul.timeline { list-style-type: none; padding: 0; position: relative; margin-bottom: 0; }
    ul.timeline:before { content: ' '; background: #dee2e6; display: inline-block; position: absolute; left: 16px; width: 2px; height: 100%; z-index: 0; top: 10px; }
    ul.timeline > li { z-index: 2; position: relative; margin-bottom: 20px; }
    ul.timeline > li:last-child { margin-bottom: 0; }
    ul.timeline > li:last-child:before { content: ''; position: absolute; background: white; width: 4px; height: 100%; left: 15px; top: 40px; }
</style>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>
{{-- 2. Load Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    {{-- 3. Load DataTables --}}
    <script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Inisialisasi Select2
    function initSelect2() {
        $('.select2-bau').select2({
            theme: 'bootstrap-5',
            placeholder: "-- Pilih Satu atau Lebih Pegawai --",
            allowClear: true,
            dropdownParent: $('#delegasiModalBAU'),
            width: '100%'
        });
    }

    // 2. Passing data ke modal
    $('#delegasiModalBAU').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); 
        var id = button.data('id');
        var perihal = button.data('perihal');
        var tabel = button.data('tabel'); 

        $(this).find('form')[0].reset();
        
        // Bersihkan select2 lama jika ada
        if ($('.select2-bau').data('select2')) {
            $('.select2-bau').val(null).trigger('change');
        }

        $('#delegasi_surat_id').val(id);
        $('#delegasi_asal_tabel').val(tabel);
        $('#text_perihal').text(perihal);
        
        togglePegawaiBAU();
    });

    // Jalankan init saat modal ditampilkan sempurna
    $('#delegasiModalBAU').on('shown.bs.modal', function () {
        initSelect2();
    });
});

// 3. Fungsi Toggle
function togglePegawaiBAU() {
    const tipe = document.getElementById('target_tipe').value;
    const groupPegawai = document.getElementById('group_pegawai');
    const groupInstruksi = document.getElementById('group_instruksi');
    const inputKlasifikasi = document.getElementById('input_klasifikasi');
    const selectPegawai = document.getElementById('select_pegawai');

    if (tipe === 'semua') {
        groupPegawai.style.display = 'none';
        groupInstruksi.style.display = 'none';
        inputKlasifikasi.removeAttribute('required');
        selectPegawai.removeAttribute('required');
        $('.select2-bau').val(null).trigger('change');
    } else {
        groupPegawai.style.display = 'block';
        groupInstruksi.style.display = 'block';
        inputKlasifikasi.setAttribute('required', 'required');
    }
}
</script>
<script>
    $(document).ready(function() {
        var previewModal = document.getElementById('previewFileModal');
        previewModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileUrl = button.getAttribute('data-file');
            var title = button.getAttribute('data-title');
            var delegasiData = JSON.parse(button.getAttribute('data-delegasi') || '[]');
            
            var modalTitle = previewModal.querySelector('#previewTitle');
            var downloadBtn = previewModal.querySelector('#downloadBtn');
            var container = previewModal.querySelector('#fileContainer');
            var delegasiInfo = previewModal.querySelector('#delegasiInfo');
            var delegasiList = previewModal.querySelector('#delegasiList');

            modalTitle.textContent = title;
            downloadBtn.href = fileUrl;
            container.innerHTML = ''; 
            delegasiList.innerHTML = '';

            // LOGIKA TAMPILKAN DELEGASI
            if (delegasiData.length > 0) {
                delegasiInfo.classList.remove('d-none');
                delegasiData.forEach(function(item) {
                    var card = `
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body p-2" style="font-size: 0.8rem;">
                                    <div class="fw-bold text-primary">${item.penerima}</div>
                                    <div class="text-muted small mb-1">${item.aksi}</div>
                                    <div class="fst-italic text-dark border-top mt-1 pt-1">${item.catatan}</div>
                                </div>
                            </div>
                        </div>`;
                    delegasiList.insertAdjacentHTML('beforeend', card);
                });
            } else {
                delegasiInfo.classList.add('d-none');
            }

            // LOGIKA PREVIEW FILE
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
            previewModal.querySelector('#delegasiList').innerHTML = '';
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

                // Reset Loading
                modalLabel.textContent = 'Memuat Data...';
                modalBody.innerHTML = `
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Sedang mengambil data...</p>
                    </div>`;
                
                // Fetch
                fetch(dataUrl)
                    .then(async response => {
                        if (!response.ok) { 
                            const errData = await response.json().catch(() => ({}));
                            throw new Error(errData.message || 'Error ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'error') throw new Error(data.message);

                        modalLabel.textContent = 'Riwayat: ' + (data.nomor_surat || 'Surat Masuk');
                        var html = '<ul class="timeline">';
                        
                        if (data.riwayats && data.riwayats.length > 0) {
                            data.riwayats.forEach((item) => {
                                var badge = 'primary'; 
                                var icon = 'bi-circle-fill';
                                var status = item.status_aksi || '';

                                if (status.includes('Selesai') || status.includes('Arsip')) {
                                    badge = 'success'; icon = 'bi-check-lg';
                                } else if (status.includes('Disposisi') || status.includes('Teruskan')) {
                                    badge = 'warning'; icon = 'bi-arrow-return-right';
                                } else if (status.includes('Masuk') || status.includes('Input')) {
                                    badge = 'info'; icon = 'bi-envelope';
                                }

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
                                                    <span class="badge bg-white text-muted border rounded-pill fw-normal small px-2">
                                                        ${item.tanggal_f}
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
                            html += '<li class="text-center p-4 text-muted">Belum ada riwayat aktivitas.</li>';
                        }
                        
                        html += '</ul>';
                        modalBody.innerHTML = html;
                    })
                    .catch(err => {
                        console.error("JS Error:", err);
                        modalBody.innerHTML = `<div class="alert alert-danger text-center m-3">${err.message}</div>`;
                    });
            });
        }
    });
</script>

<!-- tambah manual -->

<script>
function toggleSumberSurat() {
    const tipe = document.getElementById('tipe_surat_manual').value;
    const groupEksternal = document.getElementById('group_asal_eksternal');
    const groupInternal = document.getElementById('group_asal_internal');
    const inputEksternal = document.getElementById('input_asal_eksternal');
    const selectInternal = document.getElementById('select_satker_internal');

    if (tipe === 'internal') {
        groupEksternal.style.display = 'none';
        groupInternal.style.display = 'block';
        
        inputEksternal.removeAttribute('required');
        selectInternal.setAttribute('required', 'required');
    } else {
        groupEksternal.style.display = 'block';
        groupInternal.style.display = 'none';
        
        inputEksternal.setAttribute('required', 'required');
        selectInternal.removeAttribute('required');
    }
}

function toggleManualDelegasi() {
    const tipe = document.getElementById('target_tipe_manual').value;
    const groupPegawai = document.getElementById('group_pegawai_manual');
    const groupCatatan = document.getElementById('group_catatan_manual');

    if (tipe === 'arsip') {
        $(groupPegawai).slideUp();
        $(groupCatatan).slideUp();
    } else if (tipe === 'semua') {
        $(groupPegawai).slideUp();
        $(groupCatatan).slideDown();
    } else {
        $(groupPegawai).slideDown();
        $(groupCatatan).slideDown();
    }
}

// Inisialisasi awal saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    toggleSumberSurat();
    toggleManualDelegasi();
});
</script>

<!-- edit -->
<script>
function toggleEditSumber(id) {
    const tipe = document.getElementById('tipe_edit_' + id).value;
    const groupEks = document.getElementById('group_eksternal_edit_' + id);
    const groupInt = document.getElementById('group_internal_edit_' + id);
    const inputEks = document.getElementById('input_eksternal_edit_' + id);
    const selectInt = document.getElementById('select_internal_edit_' + id);

    if (tipe === 'internal') {
        groupEks.style.display = 'none';
        groupInt.style.display = 'block';
        
        inputEks.removeAttribute('required');
        selectInt.setAttribute('required', 'required');
    } else {
        groupEks.style.display = 'block';
        groupInt.style.display = 'none';
        
        inputEks.setAttribute('required', 'required');
        selectInt.removeAttribute('required');
    }
}
</script>

@endpush