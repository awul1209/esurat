@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    #tabelTembusan { font-size: 13px !important; }
    .btn-action {
        width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 6px; transition: all 0.2s; border: none;
    }
    .badge-tujuan { font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 4px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tabelTembusan" class="table table-hover align-middle w-100">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th width="200">Asal & Penanda Tangan</th>
                            <th width="250">No. Surat & Perihal</th>
                            <th>Tujuan Utama Surat</th>
                            <th width="120">Tgl. Diterima</th>
                            <th width="80" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                  <tbody>
    @foreach($tembusanSurat as $t)
    <tr>
        <td class="text-center fw-bold"></td> <td>
            <div class="fw-bold text-dark">{{ $t->suratKeluar->user->satker->nama_satker ?? '-' }}</div>
            <small class="text-muted italic">
                <i class="bi bi-person-check me-1"></i>
                {{ $t->suratKeluar->validasis->where('status', 'disetujui')->first()->pimpinan->name ?? $t->suratKeluar->user->name }}
            </small>
        </td>
        <td>
            <div class="text-primary fw-bold mb-1">{{ $t->suratKeluar->nomor_surat ?? '-' }}</div>
            <div class="text-truncate" style="max-width: 250px;" title="{{ $t->suratKeluar->perihal }}">
                {{ $t->suratKeluar->perihal }}
            </div>
        </td>
        <td>
            @if($t->suratKeluar->tujuan_surat)
                <span class="badge bg-info-subtle text-info border border-info-subtle badge-tujuan">
                    {{ $t->suratKeluar->tujuan_surat }}
                </span>
            @else
                @foreach($t->suratKeluar->penerimaInternal as $penerima)
                    <span class="badge bg-light text-dark border badge-tujuan mb-1">
                        {{ $penerima->nama_satker }}
                    </span>
                @endforeach
            @endif
        </td>
        <td class="text-secondary">
            <i class="bi bi-calendar3 me-1"></i>
            {{ $t->created_at ? \Carbon\Carbon::parse($t->created_at)->isoFormat('D MMM Y') : '-' }}
        </td>
        <td class="text-center">
            @if($t->suratKeluar && $t->suratKeluar->file_surat)
            <button type="button" class="btn btn-info text-white btn-action shadow-sm btn-preview" 
                    data-bs-toggle="modal" data-bs-target="#modalPreview"
                    data-file="{{ asset('storage/' . $t->suratKeluar->file_surat) }}">
                <i class="bi bi-file-earmark-pdf"></i>
            </button>
            @endif
        </td>
    </tr>
    @endforeach
</tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PREVIEW --}}
<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Pratinjau Dokumen Tembusan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="height: 85vh;">
                <iframe id="frame-pdf" src="" width="100%" height="100%" style="border:none;"></iframe>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
    var table = $('#tabelTembusan').DataTable({
        language: { 
            search: "Cari data:",
            info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ tembusan",
        },
        order: [[4, 'desc']], // Urutkan berdasarkan kolom Tgl Diterima (indeks 4) terbaru
        columnDefs: [
            {
                searchable: false,
                orderable: false,
                targets: 0, // Target kolom indeks 0 (Nomor)
            },
        ],
    });

    // Logika agar nomor tetap urut 1, 2, 3... meskipun di-sort atau di-filter
    table.on('order.dt search.dt', function () {
        let i = 1;
        table.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
            this.data(i++);
        });
    }).draw();

    // Modal Preview Handler
    $('.btn-preview').on('click', function () {
        $('#frame-pdf').attr('src', $(this).data('file'));
    });

    $('#modalPreview').on('hidden.bs.modal', function () {
        $('#frame-pdf').attr('src', '');
    });
});
</script>
@endpush