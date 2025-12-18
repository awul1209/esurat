@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Kirim Surat Internal / Ke Rektor</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('satker.surat-keluar.internal.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nomor Surat</label>
                    <input type="text" name="nomor_surat" class="form-control" required>
                </div>
                
                {{-- MULTI SELECT --}}
                <div class="mb-3">
                    <label class="form-label">Tujuan Surat</label>
                    <select name="tujuan_satker_ids[]" class="form-select select2" multiple="multiple" required>
                        {{-- OPSI KHUSUS UNTUK JALUR BAU -> REKTOR --}}
                        <optgroup label="Pimpinan (Via BAU)">
                            <option value="universitas">Rektor/Universitas</option>
                            <!-- <option value="universitas">Universitas</option> -->
                        </optgroup>

                        {{-- OPSI SATKER LAIN (LANGSUNG) --}}
                        <optgroup label="Satuan Kerja (Langsung)">
                            @foreach($daftarSatker as $satker)
                                <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <div class="form-text text-muted">
                        <small>
                            <i class="bi bi-info-circle"></i> 
                            Jika memilih <strong>Rektor/Universitas</strong>, surat akan masuk ke <strong>BAU</strong> terlebih dahulu untuk diteruskan.
                            Jika memilih <strong>Satker</strong>, surat langsung masuk ke akun Satker tujuan.
                        </small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Perihal</label>
                    <input type="text" name="perihal" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Surat</label>
                    <input type="date" name="tanggal_surat" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">File Surat (PDF/Gambar)</label>
                    <input type="file" name="file_surat" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i> Kirim Surat</button>
                <a href="{{ route('satker.surat-keluar.internal') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Tujuan...',
            allowClear: true
        });
    });
</script>
@endpush