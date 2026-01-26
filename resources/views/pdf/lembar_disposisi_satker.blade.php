<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Disposisi Satker - {{ $surat->perihal }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; line-height: 1.2; }
        .wrapper { border: 2px solid #000; padding: 1px; }
        .inner-border { border: 1px solid #000; padding: 10px; }
        
        /* Header / Kop Satker */
        .logo { position: absolute; top: 15px; left: 20px; width: 70px; }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 3px double #000; padding-bottom: 10px; }
        .header h1 { font-size: 14pt; margin: 0; text-transform: uppercase; }
        .header h2 { font-size: 13pt; margin: 0; text-transform: uppercase; }
        .header p { font-size: 9pt; margin: 2px 0; }

        .title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 14pt; margin: 15px 0; }

        /* Table Info Surat */
        .main-table { width: 100%; border-collapse: collapse; }
        .main-table td { border: 1px solid #000; padding: 8px; vertical-align: top; }
        
        .label { width: 110px; display: inline-block; }
        .check-box-group { margin-top: 5px; }
        .check-item { display: inline-block; margin-right: 15px; font-size: 10pt; }
        
        .checkbox {
            font-family: 'DejaVu Sans', sans-serif; 
            display: inline-block; width: 14px; height: 14px;
            border: 1px solid #000; text-align: center;
            line-height: 14px; font-size: 12pt; margin-right: 5px;
        }

        /* Section Catatan & Delegasi */
        .instruction-table { width: 100%; border-collapse: collapse; }
        .instruction-table td { border: 1px solid #000; padding: 10px; height: 120px; vertical-align: top; }

        .catatan-full { border: 1px solid #000; padding: 10px; min-height: 250px; margin-top: -1px; }
        
        .footer-sig { margin-top: 20px; float: right; width: 250px; text-align: left; }
        .sig-space { height: 60px; }

        .page-break { page-break-before: always; }
        .lampiran-img { max-width: 100%; height: auto; border: 1px solid #000; margin-top: 10px; }
    </style>
</head>
<body>

<div class="inner-border">
    {{-- KOP SURAT DINAMIS BERDASARKAN SATKER --}}
    <img src="{{ resource_path('images/unija.jpg') }}" class="logo">
    <div class="header">
        <h1>UNIVERSITAS WIRARAJA</h1>
        <h2>{{ strtoupper($unitKerja) }}</h2>
        <p>Jl. Raya Sumenep Pamekasan KM. 5 Ngampal, Sumenep</p>
    </div>

    <div class="title">LEMBAR DISPOSISI</div>

    <table class="main-table">
    <tr>
        <td width="55%">
            <div><span class="label">Surat dari</span> : {{ $surat->surat_dari ?? ($surat->user->satker->nama_satker ?? 'Internal') }}</div>
            <div style="margin-top: 5px;"><span class="label">No. Surat</span> : {{ $surat->nomor_surat }}</div>
            <div style="margin-top: 5px;">
                <span class="label">Tgl. Surat</span> : 
                {{ \Carbon\Carbon::parse($surat->tanggal_surat)->isoFormat('D MMMM YYYY') }}
            </div>
        </td>
        <td width="45%">
            <div>
                <span class="label">Diterima Tgl</span> : 
                {{-- Cek apakah kolom diterima_tanggal ada (tabel surats), jika tidak pakai created_at (tabel surat_keluars) --}}
                @if(isset($surat->diterima_tanggal) && $surat->diterima_tanggal)
                    {{ \Carbon\Carbon::parse($surat->diterima_tanggal)->isoFormat('D MMMM YYYY') }}
                @else
                    {{ \Carbon\Carbon::parse($surat->created_at)->isoFormat('D MMMM YYYY') }}
                @endif
            </div>
            <div style="margin-top: 5px;"><span class="label">No. Agenda</span> : <strong>(Kosong)</strong></div>
            <div style="margin-top: 5px;"><span class="label">Sifat</span> : {{ $surat->sifat ?? 'Biasa' }}</div>
            
            <div class="check-box-group">
                <span class="check-item"><span class="checkbox">@if(($surat->sifat ?? '') == 'Sangat Segera') ✓ @endif</span> Sangat Segera</span>
                <span class="check-item"><span class="checkbox">@if(($surat->sifat ?? '') == 'Segera') ✓ @endif</span> Segera</span>
                <span class="check-item"><span class="checkbox">@if(($surat->sifat ?? '') == 'Rahasia') ✓ @endif</span> Rahasia</span>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>Perihal :</strong><br>
            {{ $surat->perihal }}
        </td>
    </tr>
</table>

    <table class="instruction-table" style="margin-top: -1px;">
        <tr>
            <td width="50%">
                <strong>Diteruskan Kepada :</strong>
                <ol style="padding-left: 20px; margin-top: 10px;">
                    @foreach($penerimaList as $nama)
                        <li>{{ $nama }}</li>
                    @endforeach
                </ol>
            </td>
            <td width="50%">
                <strong>Dengan Hormat harap :</strong>
                <div style="margin-top: 10px; font-style: italic;">
                    {{-- status_aksi dari riwayat_surats --}}
                    @if(is_array($selectedKlasifikasi))
                        @foreach($selectedKlasifikasi as $aksi)
                            - {{ $aksi }}<br>
                        @endforeach
                    @else
                        {{ $riwayat->status_aksi ?? '-' }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="catatan-full">
        <strong>Catatan :</strong>
        <p style="margin-top: 10px; white-space: pre-wrap;">{{ $riwayat->catatan ?? '-' }}</p>
    </div>

    <div class="footer-sig">
        Sumenep, {{ \Carbon\Carbon::now()->isoFormat('D MMMM YYYY') }}<br>
        <strong>Nama Jabatan :</strong> {{ Auth::user()->role == 'satker' ? 'DEKAN / KEPALA' : 'ADMIN' }}<br>
        <div class="sig-space"></div>
        <strong>Prf & tgl :</strong> .................................
    </div>
</div>

{{-- HALAMAN LAMPIRAN (SAMA DENGAN VERSI REKTOR) --}}
@if($surat->file_surat)
    @php
        $path = storage_path('app/public/' . $surat->file_surat);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    @endphp

    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'bmp']))
        <div class="page-break"></div>
        <div style="text-align: center;">
            <h3 style="text-decoration: underline;">LAMPIRAN SURAT</h3>
            <img src="{{ $path }}" class="lampiran-img">
        </div>
    @endif
@endif

</body>
</html>