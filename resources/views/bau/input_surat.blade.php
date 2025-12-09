@extends('layouts.app')

@push('styles')
<style>
    /* Style 13px (dari file Anda sebelumnya) */
    .card-body .form-label,
    .card-body .form-control,
    .card-body .form-select {
        font-size: 13px;
    }
    .card-body .form-control,
    .card-body .form-select {
         padding: 0.3rem 0.6rem; 
    }

    /* BARU: Sembunyikan dropdown Satker/Pegawai by default */
    #kolomTujuanSatker,
    #kolomTujuanPegawai {
        display: none;
    }
</style>
@endpush


@section('content')
<div class="container-fluid px-4">
    
    <div class="card shadow-sm border-0">
        <div class="card-header py-3 bg-light border-0">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-envelope-plus-fill me-2"></i> Input Surat Masuk Baru</h6>
                <a href="{{ route('bau.surat.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            
            <form action="{{ route('bau.surat.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <h6 class="text-muted mb-3 fw-bold">Informasi Surat</h6>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="surat_dari" class="form-label">Surat dari: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="surat_dari" name="surat_dari" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tipe_surat" class="form-label">Tipe Surat: <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipe_surat" name="tipe_surat" required>
                            <option value="eksternal">Eksternal</option>
                            <option value="internal">Internal</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nomor_surat" class="form-label">Nomor Surat: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nomor_surat" name="nomor_surat" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal_surat" class="form-label">Tanggal Surat: <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="perihal" class="form-label">Perihal: <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="perihal" name="perihal" rows="3" required></textarea>
                </div>
                

                {{-- 
                  ====================================================
                  PERUBAHAN BESAR: Dropdown Tujuan Dinamis
                  ====================================================
                --}}
                
                <h6 class="text-muted mb-3 mt-4 fw-bold">Tujuan Surat</h6>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="tujuan_tipe" class="form-label">Tipe Tujuan: <span class="text-danger">*</span></label>
                            <select class="form-select" id="tujuan_tipe" name="tujuan_tipe" required>
                                <option value="">-- Pilih Tipe Tujuan --</option>
                                <option value="rektor">Rektor</option>
                                <option value="satker">Satker (Perlu Disposisi)</option>
                                <option value="pegawai">Pegawai (Langsung ke Ybs)</option>
                                <option value="edaran_semua_satker">Edaran (Ke Semua Satker)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Kolom Tujuan Satker (Muncul jika 'satker' dipilih) --}}
                    <div class="col-md-8" id="kolomTujuanSatker">
                        <div class="mb-3">
                            <label for="tujuan_satker_id" class="form-label">Pilih Satker: <span class="text-danger">*</span></label>
                            <select class="form-select" id="tujuan_satker_id" name="tujuan_satker_id">
                                <option value="">-- Pilih Satuan Kerja --</option>
                                @foreach ($daftarSatker as $satker)
                                    <option value="{{ $satker->id }}">{{ $satker->nama_satker }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Kolom Tujuan Pegawai (Muncul jika 'pegawai' dipilih) --}}
                    <div class="col-md-8" id="kolomTujuanPegawai">
                        <div class="mb-3">
                            <label for="tujuan_user_id" class="form-label">Pilih Pegawai: <span class="text-danger">*</span></label>
                            <select class="form-select" id="tujuan_user_id" name="tujuan_user_id">
                                <option value="">-- Pilih Pegawai --</option>
                                @foreach ($daftarPegawai as $pegawai)
                                    <option value="{{ $pegawai->id }}">
                                        {{ $pegawai->name }} 
                                        ({{ $pegawai->satker->singkatan ?? 'Belum ada Satker' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- ================= END PERUBAHAN ================= --}}

                <hr class="my-4">

                <h6 class="text-muted mb-3 fw-bold">Informasi Agenda & File</h6>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="no_agenda" class="form-label">No. Agenda: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="no_agenda" name="no_agenda" required>
                    </div>
                    <div class="col-md-4">
                        <label for="diterima_tanggal" class="form-label">Diterima Tanggal: <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="diterima_tanggal" name="diterima_tanggal" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="sifat" class="form-label">Sifat: <span class="text-danger">*</span></label>
                        <select class="form-select" id="sifat" name="sifat" required>
                            <option value="Asli">Asli</option>
                            <option value="Tembusan">Tembusan</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="file_surat" class="form-label">Upload Scan Surat (PDF/JPG): <span class="text-danger">*</span></label>
                    <input class="form-control" type="file" id="file_surat" name="file_surat" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                {{-- 
                  ====================================================
                  PERUBAHAN: Tombol Aksi
                  Hanya ada satu tombol "Simpan". 
                  Logika "Teruskan" vs "Draft" dihapus dan diganti 
                  logika "Tujuan" di Controller.
                  ====================================================
                --}}
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-save-fill me-2"></i> Simpan Surat
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- 
  ====================================================
  SCRIPT BARU: Untuk menampilkan dropdown dinamis
  ====================================================
--}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipeTujuan = document.getElementById('tujuan_tipe');
        const kolomSatker = document.getElementById('kolomTujuanSatker');
        const kolomPegawai = document.getElementById('kolomTujuanPegawai');
        const selectSatker = document.getElementById('tujuan_satker_id');
        const selectPegawai = document.getElementById('tujuan_user_id');

        tipeTujuan.addEventListener('change', function() {
            // Ambil nilai yang dipilih
            const pilihan = this.value;

            // Sembunyikan semua & reset 'required'
            kolomSatker.style.display = 'none';
            selectSatker.required = false;
            kolomPegawai.style.display = 'none';
            selectPegawai.required = false;

            if (pilihan === 'satker') {
                // Tampilkan Satker
                kolomSatker.style.display = 'block';
                selectSatker.required = true;
            } else if (pilihan === 'pegawai') {
                // Tampilkan Pegawai
                kolomPegawai.style.display = 'block';
                selectPegawai.required = true;
            }
            // Jika 'rektor', tidak ada yang ditampilkan (sudah benar)
        });
    });
</script>
@endpush