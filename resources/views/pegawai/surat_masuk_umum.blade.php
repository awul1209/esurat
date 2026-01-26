@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/b-3.1.1/b-html5-3.1.1/datatables.min.css" rel="stylesheet">
<style>
    #tabelSuratUmum_wrapper { font-size: 13px; }
    .badge-informasi { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-megaphone me-2"></i>Daftar Surat Umum & Informasi</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('pegawai.surat.umum') }}" class="row g-2 align-items-end">
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
                    <a href="{{ route('pegawai.surat.umum') }}" class="btn btn-sm btn-light border px-3">Reset</a>
                    <a href="{{ route('pegawai.surat.umum.export', request()->all()) }}" class="btn btn-sm btn-success px-3">
                        <i class="bi bi-file-earmark-excel"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table id="tabelSuratUmum" class="table table-hover align-middle table-sm w-100">
                <thead>
                    <tr class="table-light">
                        <th>No</th>
                        <th>Tipe</th>
                        <th>Asal Surat / Nomor</th>
                        <th>Perihal</th>
                        <th>Tgl. Surat</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suratUmum as $index => $surat)
                        @php
                            $isInternal = ($surat->tipe_label == 'Internal');
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><span class="badge {{ $isInternal ? 'bg-info' : 'bg-warning text-dark' }}">{{ $surat->tipe_label }}</span></td>
                            <td>
                                <div class="fw-bold">{{ $surat->surat_dari_display }}</div>
                                <small class="text-muted">{{ $surat->nomor_surat }}</small>
                            </td>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->isoFormat('D MMM Y') }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                                    <i class="bi bi-info-circle me-1"></i> Informasi Umum
                                </span>
                            </td>
                            <td class="text-center">
                              {{-- Bagian Button Lihat di dalam Foreach --}}
<button type="button" class="btn btn-sm btn-info text-white btn-show-detail" 
    data-bs-toggle="modal" data-bs-target="#detailSuratModal"
    data-tipe="{{ $surat->tipe_label }}" 
    data-nomor="{{ $surat->nomor_surat }}"
    data-perihal="{{ $surat->perihal }}" 
    data-asal="{{ $surat->surat_dari_display }}"
    {{-- Gunakan format Y-m-d agar mudah diparse oleh JS/Carbon --}}
    data-tgl-surat="{{ \Carbon\Carbon::parse($surat->tanggal_surat)->isoFormat('D MMMM YYYY') }}" 
    data-tgl-terima="{{ \Carbon\Carbon::parse($surat->tgl_display)->isoFormat('D MMMM YYYY') }}"
    data-catatan="Diteruskan kepada seluruh pegawai." 
    data-file="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '#' }}">
    <i class="bi bi-eye-fill"></i> Lihat
</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL DETAIL (Gunakan Modal yang sama dengan halaman pribadi) --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-megaphone me-2"></i>Detail Informasi Umum <span id="view-tipe"></span></h5>
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
                            <div class="col-6"><label class="text-muted small d-block">Tgl Terbit</label><span id="view-tgl-terima" class="fw-bold"></span></div>
                        </div>
                        <div class="p-3 bg-white border rounded shadow-sm mb-3">
                            <label class="text-primary small fw-bold d-block mb-1">Keterangan</label>
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
        $('#tabelSuratUmum').DataTable({ language: { search: "Cari:" }, order: [[0, 'asc']] });
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