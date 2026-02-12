@extends('layouts.app')

@push('styles')
<link href="https://cdn.datatables.net/v/bs5/dt-2.1.0/datatables.min.css" rel="stylesheet">
<style>
    #tabelValidasi, .dataTables_wrapper { font-size: 13px !important; }
    /* Memastikan modal tampil maksimal */
    .modal-xl { max-width: 95%; }
    #pdf-viewer { background-color: #525659; }
    /* Animasi transisi textarea */
    #box-catatan { display: none; transition: all 0.3s ease; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3">
    <div class="card shadow-sm border-0 mb-4 mt-2">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-primary">
                <i class="bi bi-shield-check-fill me-2 text-warning"></i> Antrean Validasi Mengetahui
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tabelValidasi" class="table table-hover align-middle w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" width="5%">No</th>
                            <th width="35%">No. Surat & Perihal</th>
                            <th width="30%">Tujuan</th>
                            <th width="15%">Tanggal Masuk</th>
                            <th width="10%">Status</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($validasiSurat as $v)
                        @php
                            $surat = $v->suratKeluar; 
                            $totalValidasi = $surat->validasis->count();
                            $sudahValidasi = $surat->validasis->where('status', 'approved')->count();
                            $statusDisplay = ($v->status == 'pending') ? 'Menunggu Anda' : 'Selesai';
                            $badgeColor = ($v->status == 'pending') ? 'warning' : 'success';
                        @endphp

                        <tr>
                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                            
                            {{-- NO SURAT & PERIHAL --}}
                            <td>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fw-bold text-primary">{{ $surat->nomor_surat }}</span>
                                    @if($surat->sifat)
                                        <span class="badge bg-info p-1" style="font-size: 9px;">{{ $surat->sifat }}</span>
                                    @endif
                                </div>
                                <span class="text-muted small d-block" style="line-height: 1.2;">
                                    {{ Str::limit($surat->perihal, 80) }}
                                </span>
                                <small class="text-primary d-block mt-1" style="font-size: 10px;">
                                    <i class="bi bi-person-circle"></i> Pengirim: {{ $surat->user->name }} ({{ $surat->user->satker->nama_satker ?? '-' }})
                                </small>
                            </td>

                            {{-- TUJUAN & PROGRESS --}}
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="fw-bold text-dark" style="font-size: 11px;">
                                        <i class="bi bi-people-fill me-1 text-primary"></i> {{ $surat->tujuan_surat }}
                                    </span>
                                    <div class="mt-1 border-top pt-1">
                                        @foreach($surat->validasis as $val)
                                            <span class="badge {{ $val->status == 'approved' ? 'bg-success' : 'bg-light text-muted border' }} mb-1" style="font-size: 9px;">
                                                <i class="bi {{ $val->status == 'approved' ? 'bi-check-circle-fill' : 'bi-hourglass-split' }} me-1"></i>
                                                {{ $val->pimpinan->name ?? 'Pimpinan' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </td>

                            {{-- TANGGAL --}}
                            <td>
                                <small class="text-muted d-block">Surat: {{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d/m/y') }}</small>
                                <small class="text-muted d-block">Masuk: {{ \Carbon\Carbon::parse($v->created_at)->format('d/m/y H:i') }}</small>
                            </td>

                            {{-- STATUS BADGE --}}
                            <td class="text-center">
                                <span class="badge bg-{{ $badgeColor }} shadow-sm" style="font-size: 0.75rem;">
                                    {{ $statusDisplay }}
                                </span>
                            </td>

                            {{-- AKSI --}}
                            <td class="text-center">
                                <button type="button" 
                                    class="btn btn-sm btn-primary shadow-sm btn-proses"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalValidasi"
                                    data-id="{{ $v->id }}"
                                    data-file="{{ asset('storage/' . $surat->file_surat) }}"
                                    data-nomor="{{ $surat->nomor_surat }}"
                                    data-perihal="{{ $surat->perihal }}"> Proses
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL VALIDASI & PREVIEW --}}
<div class="modal fade" id="modalValidasi" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom">
                <h6 class="modal-title fw-bold text-primary"><i class="bi bi-file-earmark-pdf me-2"></i>Validasi Dokumen Digital</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 d-flex flex-column flex-lg-row" style="height: 80vh;">
                {{-- Preview Area --}}
                <div class="flex-grow-1 bg-dark">
                    <iframe id="pdf-viewer" src="" width="100%" height="100%" style="border:none;"></iframe>
                </div>
                
                {{-- Action Area --}}
                <div class="p-4 bg-white border-start shadow-sm" style="width: 380px; overflow-y: auto;">
                    <div class="alert alert-info py-2 mb-3" style="font-size: 11px;">
                        <i class="bi bi-info-circle me-1"></i> Periksa dokumen dengan teliti sebelum memberikan keputusan.
                    </div>

                    <form id="form-validasi" action="" method="POST">
                        @csrf
                        {{-- Dropdown Keputusan --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Keputusan Validasi <span class="text-danger">*</span></label>
                            <select name="status" id="select-keputusan" class="form-select" required>
                                <option value="" selected disabled>-- Pilih Keputusan --</option>
                                <option value="approved" class="text-success">✅ Setujui Dokumen</option>
                                <option value="rejected" class="text-danger">❌ Minta Revisi (Tolak)</option>
                            </select>
                        </div>

                        {{-- Textarea Catatan (Hanya muncul jika rejected) --}}
                        <div id="box-catatan" class="mb-4">
                            <label class="form-label small fw-bold text-danger">Alasan Revisi / Catatan <span class="text-danger">*</span></label>
                            <textarea name="catatan" id="textarea-catatan" class="form-control" rows="5" placeholder="Berikan alasan mengapa dokumen memerlukan revisi..."></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 border-top pt-3">
                            <button type="submit" class="btn btn-primary" id="btn-submit-validasi">
                                <i class="bi bi-send-check me-2"></i> Kirim Keputusan
                            </button>
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        </div>
                    </form>
                    
                    <div class="mt-4 p-3 bg-light rounded border border-info">
                        <h6 class="fw-bold small text-info mb-2 border-bottom pb-1">Detail Dokumen:</h6>
                        <p class="small mb-0 text-dark" id="info-surat" style="font-size: 11px;"></p>
                    </div>
                </div>
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
        $('#tabelValidasi').DataTable({
            ordering: false,
            language: { search: "Cari Surat:", lengthMenu: "_MENU_", info: "Menampilkan _TOTAL_ surat" }
        });

        // Script untuk mengisi data ke Modal
        $('.btn-proses').on('click', function () {
            let id = $(this).data('id');
            let file = $(this).data('file');
            let nomor = $(this).data('nomor');
            let perihal = $(this).data('perihal');

            $('#pdf-viewer').attr('src', file + "#toolbar=0"); // Mencegah download di beberapa browser
            $('#info-surat').html("<strong>No:</strong> " + nomor + "<br><strong>Hal:</strong> " + perihal);
            
            // SESUAIKAN RUTE ASLI ANDA DI SINI
            $('#form-validasi').attr('action', '/pimpinan/validasi-surat/' + id + '/proses');
            
            // Reset Form saat buka modal
            $('#select-keputusan').val('');
            $('#box-catatan').hide();
            $('#textarea-catatan').removeAttr('required');
        });

        // LOGIKA DROPDOWN: Munculkan catatan hanya jika Minta Revisi
        $('#select-keputusan').on('change', function() {
            let keputusan = $(this).val();
            if (keputusan === 'rejected') {
                $('#box-catatan').slideDown();
                $('#textarea-catatan').attr('required', 'required').focus();
            } else {
                $('#box-catatan').slideUp();
                $('#textarea-catatan').removeAttr('required').val('');
            }
        });

        // Konfirmasi sebelum submit
        $('#form-validasi').on('submit', function(e) {
            let keputusan = $('#select-keputusan').val();
            let pesan = keputusan === 'approved' ? "Setujui dokumen ini?" : "Tolak dokumen untuk revisi?";
            if(!confirm("Apakah Anda yakin ingin: " + pesan)) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush