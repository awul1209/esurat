@extends('layouts.app')

@push('styles')
<style>
    /* Samakan font-size dengan form input BAU */
    .form-label, .form-control, .form-select, .form-text {
        font-size: 13px;
    }
    .form-control, .form-select {
         padding: 0.3rem 0.6rem; 
    }
    .disposisi-rektor-icon{
        font-size:14px;
    }
    .disposisi-rektor-btn{
        font-size:14px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        
        {{-- KOLOM KIRI: DETAIL SURAT & PREVIEW FILE --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary">Detail Surat: {{ $surat->perihal }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td style="width: 150px;"><strong>No. Agenda</strong></td>
                            <td>: {{ $surat->no_agenda }}</td>
                        </tr>
                        <tr>
                            <td><strong>Asal Surat</strong></td>
                            <td>: {{ $surat->surat_dari }}</td>
                        </tr>
                        <tr>
                            <td><strong>Perihal</strong></td>
                            <td>: {{ $surat->perihal }}</td>
                        </tr>
                        <!-- <tr>
                            <td><strong>Tipe Tujuan</strong></td>
                            <td>: 
                                @if($surat->tujuan_tipe == 'rektor')
                                    <span class="badge bg-primary">Rektor (Langsung)</span>
                                @elseif($surat->tujuan_tipe == 'satker')
                                    <span class="badge bg-warning text-dark">Disposisi ke Satker</span>
                                @else
                                    {{ ucfirst($surat->tujuan_tipe) }}
                                @endif
                            </td>
                        </tr> -->
                        <tr>
                            <td><strong>Tanggal Diterima</strong></td>
                            <td>: {{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tujuan Awal</strong></td>
                            <td>: {{ $surat->tujuanSatker->nama_satker ?? '(Tujuan Rektor)' }}</td>
                        </tr>
                    </table>

                    <hr>

                    {{-- Bagian File & Download --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                         <h6 class="m-0 fw-bold text-primary">File Surat</h6>
                         <a href="{{ Storage::url($surat->file_surat) }}" class="btn btn-sm btn-outline-primary" download>
                            <i class="bi bi-download me-1"></i> Download File
                        </a>
                    </div>
                    
                    @php
                        $fileUrl = Storage::url($surat->file_surat);
                        $extension = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
                    @endphp

                    <div class="file-preview-wrapper" style="height: 75vh; max-height: 800px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: .375rem;">
                        @if ($extension == 'pdf')
                            <iframe src="{{ $fileUrl }}" width="100%" height="100%" style="border: none;"></iframe>
                        @elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']))
                            <img src="{{ $fileUrl }}" style="width: 100%; height: 100%; object-fit: contain; padding: 1rem;">
                        @else
                            <div class="text-center p-5">
                                <i class="bi bi-file-earmark-text h1 text-muted"></i>
                                <p class="mt-3">Preview tidak didukung untuk tipe file ini.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: FORM DISPOSISI / PERSETUJUAN --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="m-0 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i> Tindak Lanjut Rektor</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('adminrektor.disposisi.store', $surat->id) }}" method="POST">
                        @csrf
                        
                        {{-- 
                           ====================================================
                           LOGIKA TAMPILAN (DIPERBARUI)
                           Jika tipe 'rektor' ATAU tujuan_satker_id KOSONG -> Anggap Tujuan Rektor
                           ====================================================
                        --}}

                        @if($surat->tujuan_tipe == 'rektor' || !$surat->tujuan_satker_id)
                            {{-- 
                                KASUS 1: SURAT TUJUAN REKTOR (LANGSUNG) 
                                - Tidak ada input form sama sekali.
                                - Langsung tombol aksi.
                            --}}
                            
                            <div class="alert alert-info py-4 mb-4 text-center">
                                <i class="bi bi-info-circle-fill h1 d-block mb-3 text-primary"></i>
                                <h6 class="fw-bold">Surat untuk Rektor</h6>
                                <p class="mb-0 small text-muted">Surat ini ditujukan langsung kepada Rektor. Tidak perlu input disposisi, klasifikasi, atau catatan.</p>
                                <hr class="my-3">
                                <p class="mb-0 fw-bold">Klik tombol di bawah untuk menyetujui dan mengarsipkan.</p>
                            </div>

                            {{-- Input Hidden kosong (opsional, untuk memastikan field terkirim null) --}}
                            <input type="hidden" name="tujuan_satker_id" value="">

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg disposisi-rektor-btn py-3">
                                    <i class="bi bi-check-circle-fill me-2 disposisi-rektor-icon"></i> Setuju & Arsipkan (Selesai)
                                </button>
                            </div>

                        @else
                            {{-- 
                                KASUS 2: SURAT TUJUAN SATKER (PERLU DISPOSISI) 
                                - Menampilkan form lengkap (Klasifikasi, Tujuan, Catatan, dll).
                            --}}
                            
                            <div class="alert alert-warning py-2 mb-3" style="font-size: 12px;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> 
                                Surat ini ditujukan untuk <strong>Satker/Fakultas</strong>. Harap lengkapi form disposisi di bawah ini untuk meneruskannya.
                            </div>

                            <div class="mb-3">
                                <label for="klasifikasi_id" class="form-label">Klasifikasi Arsip:</label>
                                <select class="form-select" id="klasifikasi_id" name="klasifikasi_id">
                                    <option value="">-- Pilih Klasifikasi --</option>
                                    @foreach($daftarKlasifikasi as $klasifikasi)
                                        <option value="{{ $klasifikasi->id }}">
                                            [{{ $klasifikasi->kode_klasifikasi }}] - {{ $klasifikasi->nama_klasifikasi }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="tujuan_satker_id" class="form-label">Diteruskan Kepada (Satker): <span class="text-danger">*</span></label>
                                <select class="form-select" id="tujuan_satker_id" name="tujuan_satker_id" required>
                                    <option value="">-- Pilih Satker Tujuan --</option>
                                    @foreach($daftarSatker as $satker)
                                        {{-- Jika data dari BAU sudah ada tujuan_satker_id, otomatis terpilih --}}
                                        <option value="{{ $satker->id }}" {{ $surat->tujuan_satker_id == $satker->id ? 'selected' : '' }}>
                                            {{ $satker->nama_satker }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="catatan_rektor" class="form-label">Catatan Rektor (Disposisi):</label>
                                <textarea class="form-control" id="catatan_rektor" name="catatan_rektor" rows="4" placeholder="Misal: 'Setuju, harap proses lebih lanjut'"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="disposisi_lain" class="form-label">Disposisi Lain (Tembusan):</label>
                                <textarea class="form-control" id="disposisi_lain" name="disposisi_lain" rows="3" placeholder="Misal: 'Tembusan untuk Wakil Rektor I'"></textarea>
                            </div>

                            <hr>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg disposisi-rektor-btn">
                                    <i class="bi bi-send-check-fill me-2 disposisi-rektor-icon"></i> Simpan & Teruskan Disposisi
                                </button>
                            </div>
                        @endif

                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection