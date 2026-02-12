@extends('layouts.guest') {{-- Gunakan layout tanpa sidebar --}}

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="bi bi-patch-check-fill text-success" style="font-size: 4rem;"></i>
                        <h3 class="fw-bold mt-2">Dokumen Terverifikasi</h3>
                        <p class="text-muted">Sistem e-Surat Universitas Wiraraja</p>
                    </div>
                    
                    <table class="table table-borderless text-start small">
                        <tr>
                            <th width="35%">Nomor Surat</th>
                            <td>: {{ $surat->nomor_surat }}</td>
                        </tr>
                        <tr>
                            <th>Perihal</th>
                            <td>: {{ $surat->perihal }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Terbit</th>
                            <td>: {{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <th>Penandatangan</th>
                            <td>: {{ $surat->user->name }}</td>
                        </tr>
                        <tr>
                            <th>Jabatan</th>
                            <td>: {{ $surat->user->jabatan->nama_jabatan ?? '-' }}</td>
                        </tr>
                    </table>

                    <div class="mt-4">
                        <a href="{{ asset('storage/' . $surat->file_surat) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Lihat Dokumen Asli
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-success text-white py-2">
                    <small><i class="bi bi-shield-check me-1"></i> Dokumen ini sah dan tercatat secara digital.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection