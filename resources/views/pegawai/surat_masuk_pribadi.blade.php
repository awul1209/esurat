@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/b-3.1.1/b-html5-3.1.1/datatables.min.css" rel="stylesheet">
<style>
    #tabelSuratMasuk_wrapper { font-size: 13px; }
    .badge-menunggu { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    {{-- Alert Flash Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <div class="d-flex">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>
                    <strong>Berhasil!</strong> {{ session('success') }}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <div class="d-flex">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>
                    <strong>Gagal!</strong> {{ session('error') }}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('pegawai.surat.pribadi') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold">Tipe Surat</label>
                    <select name="tipe" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="internal" {{ request('tipe') == 'internal' ? 'selected' : '' }}>Internal</option>
                        <option value="eksternal" {{ request('tipe') == 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Dari</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Sampai</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-filter"></i> Filter</button>
                    <a href="{{ route('pegawai.surat.pribadi') }}" class="btn btn-sm btn-light border px-3">Reset</a>
                    <a href="{{ route('pegawai.surat.export', request()->all()) }}" class="btn btn-sm btn-success px-3">
                        <i class="bi bi-file-earmark-excel"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table id="tabelSuratMasuk" class="table table-hover align-middle table-sm w-100">
                <thead>
                    <tr class="table-light">
                        <th>No</th>
                        <th>Tipe Surat</th>
                        <th>Asal Surat / Nomor</th>
                        <th>Perihal</th>
                        <th>Tgl. Terima</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
          @foreach ($suratUntukSaya as $index => $surat)
    @php
        $isInternal = ($surat->tipe_label == 'Internal');
        $logPribadi = $surat->riwayats->where('penerima_id', Auth::id())->first();
        $catatan = $logPribadi->catatan ?? '-';
        $statusPenerimaan = $logPribadi ? $logPribadi->is_read : 0; 
        $riwayatId = $logPribadi ? $logPribadi->id : null;
        
        // Kondisi dari Controller
        $isPerluTerima = $surat->is_perlu_terima;
    @endphp

    <tr>
        <td>{{ $index + 1 }}</td>
        <td><span class="badge {{ $isInternal ? 'bg-info' : 'bg-warning text-dark' }}">{{ $surat->tipe_label }}</span></td>
        <td>
            <div class="fw-bold">{{ $surat->surat_dari_display }}</div>
            <small class="text-muted">{{ $surat->nomor_surat }}</small>
        </td>
        <td>{{ $surat->perihal }}</td>
        <td>{{ \Carbon\Carbon::parse($surat->tgl_display)->isoFormat('D MMM Y') }}</td>
        <td>
            @if($isPerluTerima)
                {{-- Surat Langsung: Ada status Menunggu/Selesai --}}
                <span class="badge {{ $statusPenerimaan == 2 ? 'bg-success-subtle text-success border border-success' : 'bg-warning-subtle text-warning border border-warning' }} ">
                    <i class="bi {{ $statusPenerimaan == 2 ? 'bi-check-circle-fill' : 'bi-clock-history' }} me-1"></i>
                    {{ $statusPenerimaan == 2 ? 'Selesai' : 'Menunggu' }}
                </span>
            @else
                {{-- Surat Hasil Disposisi: Status Delegasi --}}
                <span class="badge bg-primary-subtle text-primary border border-primary">
                    <i class="bi bi-person-check-fill me-1"></i> Disposisi
                </span>
            @endif
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                {{-- TOMBOL TERIMA: Hanya muncul jika Surat Langsung DAN statusnya masih 0 --}}
                @if($isPerluTerima && $statusPenerimaan == 0 && $riwayatId)
                    <form action="{{ route('pegawai.surat.terima-langsung', $riwayatId) }}" method="POST" onsubmit="return confirm('Konfirmasi terima surat?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success shadow-sm">
                            <i class="bi bi-check-lg"></i> Terima
                        </button>
                    </form>
                @endif
                
                {{-- TOMBOL LIHAT: Selalu muncul untuk semua --}}
                <button type="button" class="btn btn-sm btn-info text-white btn-show-detail" 
                    data-bs-toggle="modal" data-bs-target="#detailSuratModal"
                    data-tipe="{{ $surat->tipe_label }}" 
                    data-nomor="{{ $surat->nomor_surat }}"
                    data-perihal="{{ $surat->perihal }}" 
                    data-asal="{{ $surat->surat_dari_display }}"
                    data-tgl-surat="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->isoFormat('D MMMM YYYY') }}" 
                    data-tgl-terima="{{ \Carbon\Carbon::parse($surat->tgl_display)->isoFormat('D MMMM YYYY') }}"
                    data-catatan="{{ $catatan }}" 
                    data-file="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '#' }}">
                    <i class="bi bi-eye-fill"></i> Lihat
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

{{-- MODAL DETAIL --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Detail Surat <span id="view-tipe"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-lg-5 p-4 border-end bg-light">
                        <h4 class="fw-bold text-primary mb-3" id="view-perihal"></h4>
                        <hr>
                        <div class="mb-3"><label class="text-muted small d-block">Nomor</label><span id="view-nomor" class="fw-bold"></span></div>
                        <div class="mb-3"><label class="text-muted small d-block">Asal</label><span id="view-asal" class="fw-bold"></span></div>
                        <div class="row mb-3">
                            <div class="col-6"><label class="text-muted small d-block">Tgl Surat</label><span id="view-tgl-surat" class="fw-bold"></span></div>
                            <div class="col-6"><label class="text-muted small d-block">Tgl Terima</label><span id="view-tgl-terima" class="fw-bold"></span></div>
                        </div>
                        <div class="p-3 bg-white border rounded shadow-sm mb-3">
                            <label class="text-primary small fw-bold d-block mb-1">Catatan</label>
                            <p id="view-catatan" class="mb-0 text-dark fst-italic"></p>
                        </div>
                        <div class="d-grid mt-4">
                            <a href="#" id="view-download" class="btn btn-outline-primary rounded-pill" download><i class="bi bi-download me-2"></i>Download</a>
                        </div>
                    </div>
                    <div class="col-lg-7 bg-dark d-flex align-items-center justify-content-center" style="min-height: 500px;">
                        <div id="view-file-wrapper" class="w-100 h-100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.0/b-3.1.1/b-html5-3.1.1/datatables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tabelSuratMasuk').DataTable({ language: { search: "Cari:" }, order: [[0, 'asc']] });
        $('.btn-show-detail').on('click', function() {
            const d = $(this).data();
            $('#view-tipe').text('(' + d.tipe + ')'); $('#view-perihal').text(d.perihal);
            $('#view-nomor').text(d.nomor); $('#view-asal').text(d.asal);
            $('#view-tgl-surat').text(d.tglSurat); $('#view-tgl-terima').text(d.tglTerima);
            $('#view-catatan').text(d.catatan); $('#view-download').attr('href', d.file);
            const w = $('#view-file-wrapper').empty();
            if (d.file && d.file !== '#' && d.file !== '/storage/') {
                const ext = d.file.split('.').pop().toLowerCase();
                if (ext === 'pdf') w.html(`<iframe src="${d.file}" width="100%" height="600px" style="border:none;"></iframe>`);
                else if (['jpg', 'jpeg', 'png'].includes(ext)) w.html(`<img src="${d.file}" class="img-fluid" style="max-height: 600px; object-fit: contain;">`);
            } else w.html('<div class="text-white text-center p-5"><p>File tidak tersedia.</p></div>');
        });
    });
</script>
@endpush