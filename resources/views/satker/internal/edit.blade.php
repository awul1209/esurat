@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3 bg-light border-0">
            <h6 class="m-0 fw-bold text-primary">Edit Surat Keluar Internal</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('satker.surat-keluar.internal.update', $surat->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT') {{-- PENTING: Method PUT untuk Update --}}
                
                <div class="mb-3">
                    <label class="form-label">Nomor Surat</label>
                    <input type="text" name="nomor_surat" class="form-control" value="{{ $surat->nomor_surat }}" required>
                </div>
                
                {{-- MULTI SELECT DENGAN PRE-SELECTED --}}
                <div class="mb-3">
                    <label class="form-label">Satker Tujuan</label>
                    <select name="tujuan_satker_ids[]" class="form-select select2" multiple="multiple" required>
                        @foreach($daftarSatker as $satker)
                            <option value="{{ $satker->id }}" 
                                {{ in_array($satker->id, $selectedSatkerIds) ? 'selected' : '' }}>
                                {{ $satker->nama_satker }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Perihal</label>
                    <input type="text" name="perihal" class="form-control" value="{{ $surat->perihal }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Surat</label>
                    <input type="date" name="tanggal_surat" class="form-control" value="{{ $surat->tanggal_surat->format('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">File Surat (Opsional)</label>
                    <input type="file" name="file_surat" class="form-control">
                    <div class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah file.</div>
                    <div class="mt-2">
                        <small>File saat ini: <a href="{{ Storage::url($surat->file_surat) }}" target="_blank">Lihat File</a></small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-warning text-white"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
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
            placeholder: 'Pilih Satker Tujuan...',
            allowClear: true
        });
    });
</script>
@endpush