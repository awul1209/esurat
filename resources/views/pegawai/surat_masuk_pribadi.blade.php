@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/b-3.1.1/b-html5-3.1.1/datatables.min.css" rel="stylesheet">
<style>
    /* Styling Tabel & Font */
    #tabelSuratMasuk { font-size: 0.875rem; border-collapse: separate; border-spacing: 0 8px; }
    #tabelSuratMasuk thead th { border: none; background-color: #f8f9fa; color: #495057; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; padding: 12px; }
    #tabelSuratMasuk tbody tr { background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s ease; border-radius: 8px; }
    #tabelSuratMasuk tbody tr:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.05); background-color: #fcfcfc; }
    #tabelSuratMasuk tbody td { border: none; padding: 12px; vertical-align: middle; }
    #tabelSuratMasuk tbody td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
    #tabelSuratMasuk tbody td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }

    /* Custom Badges */
    .badge-status { font-weight: 500; letter-spacing: 0.3px; border-radius: 6px; padding: 6px 12px; }
    .badge-tipe { border-radius: 50px; padding: 4px 12px; font-weight: 600; font-size: 10px; }
    
    /* Layout */
    .filter-card { border-radius: 12px; background: #fff; }
    /* .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0; } */
</style>
@endpush

@section('content')
<div class="container-fluid px-2 py-3">

    {{-- Alert Section --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-left: 4px solid #198754 !important;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card filter-card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('pegawai.surat.pribadi') }}" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-bold text-secondary">Rentang Tanggal</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="from" value="{{ request('from') }}" class="form-control border-end-0" title="Dari Tanggal">
                        <span class="input-group-text bg-white border-start-0 border-end-0 text-muted">-</span>
                        <input type="date" name="to" value="{{ request('to') }}" class="form-control border-start-0" title="Sampai Tanggal">
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-secondary">Tipe Surat</label>
                    <select name="tipe" class="form-select form-select-sm border-secondary-subtle">
                        <option value="">Semua Tipe</option>
                        <option value="internal" {{ request('tipe') == 'internal' ? 'selected' : '' }}>Internal</option>
                        <option value="eksternal" {{ request('tipe') == 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                    </select>
                </div>
                <div class="col-lg-7 col-md-12 text-md-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm">
                            <i class="bi bi-search me-1"></i> Terapkan Filter
                        </button>
                        <a href="{{ route('pegawai.surat.pribadi') }}" class="btn btn-sm btn-outline-secondary px-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                        <a href="{{ route('pegawai.surat.export', request()->all()) }}" class="btn btn-sm btn-success px-3 shadow-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export .csv
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive p-4">
                <table id="tabelSuratMasuk" class="table align-middle w-100">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th width="100">Kategori</th>
                            <th width="250">Asal & Nomor Surat</th>
                            <th>Perihal</th>
                            <th width="120">Tgl. Masuk</th>
                            <th width="150">Status</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratUntukSaya as $index => $surat)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <span class="badge badge-tipe {{ $surat->tipe_label == 'Internal' ? 'bg-info-subtle text-info border border-info' : 'bg-warning-subtle text-warning-emphasis border border-warning' }}">
                                    {{ strtoupper($surat->tipe_label) }}
                                </span>
                            </td>
                            <td>
                                <div class="text-dark fw-bold mb-1" style="font-size: 0.9rem;">{{ $surat->surat_dari_display }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-hash me-1"></i>{{ $surat->nomor_surat }}</div>
                            </td>
                            <td>
                                <span class="d-inline-block text-truncate fw-medium" style="max-width: 300px;" title="{{ $surat->perihal }}">
                                    {{ $surat->perihal }}
                                </span>
                            </td>
                            <td class="text-secondary small">
                                {{ \Carbon\Carbon::parse($surat->tgl_tampil)->isoFormat('DD MMM YYYY') }}
                            </td>
                            <td>
                                @if($surat->is_read_fix == 2)
                                    <span class="badge badge-status bg-success-subtle text-success border border-success w-100">
                                        <i class="bi bi-check2-all me-1"></i> Selesai
                                    </span>
                                @elseif($surat->is_perlu_terima)
                                    <span class="badge badge-status bg-warning-subtle text-warning-emphasis border border-warning w-100">
                                        <i class="bi bi-hourglass-split me-1"></i> Menunggu
                                    </span>
                                @else
                                    <span class="badge badge-status bg-info-subtle text-info border border-info w-100">
                                        <i class="bi bi-arrow-right-short me-1"></i> Delegasi
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    @if($surat->is_perlu_terima && $surat->is_read_fix < 2 && $surat->riwayat_id_fix)
                                        <form action="{{ route('pegawai.surat.terima-langsung', $surat->riwayat_id_fix) }}" method="POST" onsubmit="return confirm('Konfirmasi penerimaan surat?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-icon btn-success shadow-sm" title="Terima Surat">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    <button type="button" class="btn btn-sm btn-icon btn-primary shadow-sm btn-show-detail" 
                                        data-bs-toggle="modal" data-bs-target="#detailSuratModal"
                                        data-file="{{ $surat->file_surat ? Storage::url($surat->file_surat) : '#' }}"
                                        title="Pratinjau Surat">
                                        <i class="bi bi-eye"></i>
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
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered shadow-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white p-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-pdf me-2"></i>Pratinjau Dokumen Surat</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-secondary-subtle">
                <div id="view-file-wrapper" style="width: 100%; height: 80vh;">
                    {{-- Iframe loader --}}
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
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
        // DataTable Styling
        const table = $('#tabelSuratMasuk').DataTable({ 
            language: { 
                search: "_INPUT_",
                searchPlaceholder: "Cari nomor atau perihal...",
                lengthMenu: "_MENU_ data per halaman",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ surat",
                paginate: {
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            },
            dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            pageLength: 10
        });

        // PDF Preview Handler
        $('.btn-show-detail').on('click', function() {
            const d = $(this).data();
            const w = $('#view-file-wrapper').empty();
            
            if (d.file && d.file !== '#' && d.file !== '/storage/') {
                w.html(`<iframe src="${d.file}" width="100%" height="100%" style="border:none;"></iframe>`);
            } else {
                w.html(`
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                        <div class="bg-white p-5 rounded-circle shadow-sm mb-3">
                            <i class="bi bi-file-earmark-x display-4 text-danger"></i>
                        </div>
                        <h6 class="fw-bold">Berkas Tidak Ditemukan</h6>
                        <p class="small text-center px-4">Maaf, berkas PDF untuk surat ini tidak tersedia di server atau tautan sudah kadaluarsa.</p>
                    </div>
                `);
            }
        });
    });
</script>
@endpush