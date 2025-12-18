<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lembar Disposisi - {{ $surat->perihal }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 10pt; color: #000; }
        .logo { position: absolute; top: 0px; left: 0px; width: 70px; }
        .header { text-align: center; margin-bottom: 10px; margin-left: 70px; }
        .header h1 { font-size: 16pt; font-weight: bold; margin: 0; padding: 0; text-transform: uppercase; }
        .header h2 { font-size: 14pt; font-weight: bold; margin: 0; padding: 0; text-transform: uppercase; }
        .header h3 { font-size: 12pt; font-weight: bold; text-decoration: underline; margin-top: 5px; text-transform: uppercase; }
        
        .info-table { width: 100%; border-collapse: collapse; border: 1px solid #000; margin-top: 15px; }
        .info-table td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        .label { width: 18%; font-weight: bold; }
        .colon { width: 2%; text-align: center; }
        
        .checklist-table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid #000; }
        .checklist-table th { text-align: center; font-weight: bold; background: #e0e0e0; padding: 5px; border: 1px solid #000; font-size: 10pt; }
        .checklist-table td { vertical-align: top; padding: 5px; border: 1px solid #000; font-size: 9pt; }
        
        /* CSS Checkbox */
        .checkbox {
            font-family: 'DejaVu Sans', sans-serif; /* Wajib font ini agar centang muncul */
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            line-height: 11px; 
            font-size: 11px; 
            margin-right: 4px;
            vertical-align: middle;
        }
        
        .faculty-table { width: 100%; border: none; }
        .faculty-table td { border: none; padding: 2px 0; }
        .faculty-name { font-weight: bold; }
        
        .flow-table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid #000; }
        .flow-table td { border: 1px solid #000; padding: 5px 8px; vertical-align: top; }
        .catatan-box { font-style: italic; margin-top: 2px; min-height: 40px; }
        
        .footer { margin-top: 30px; width: 300px; float: right; text-align: center; }
        .footer .signature-space { height: 70px; }
        .footer .name { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>

    @php
        // ====================================================================
        // FUNGSI CENTANG PINTAR (Case Insensitive & Trim Spasi)
        // ====================================================================
        function centang($nama_target, $list_terpilih, $force_check = false) {
            $isi = ' '; 
            $isFound = false;

            // 1. Jika dipaksa centang (misal: Disposisi Lain yang manual)
            if ($force_check) {
                $isFound = true;
            } 
            // 2. Cek Array dengan Case Insensitive
            else if (is_array($list_terpilih)) {
                foreach ($list_terpilih as $item) {
                    // Bandingkan string tanpa peduli besar/kecil dan spasi
                    // strcasecmp return 0 jika sama
                    if (strcasecmp(trim($item), trim($nama_target)) == 0) {
                        $isFound = true;
                        break;
                    }
                }
            }

            if ($isFound) {
                $isi = '✓'; 
            }
            return '<span class="checkbox">' . ($isi === ' ' ? '&nbsp;' : $isi) . '</span>';
        }

        // ====================================================================
        // DATA PROCESSING
        // ====================================================================
        $disposisiRektorPertama = $disposisis->first();
        $catatanRektorUtama = $disposisiRektorPertama->catatan_rektor ?? '-';
        
        // Logika Tipe Tujuan untuk Header
        $tipe_tujuan_fix = $surat->tujuan_tipe;
        if (empty($tipe_tujuan_fix)) {
            if ($surat->tujuan_satker_id) $tipe_tujuan_fix = 'satker';
            elseif ($surat->tujuan_user_id) $tipe_tujuan_fix = 'pegawai';
            else $tipe_tujuan_fix = 'rektor';
        }
    @endphp

    <img src="{{ resource_path('images/unija.jpg') }}" alt="Logo" class="logo">

    {{-- Header --}}
    <div class="header">
        <h1>UNIVERSITAS WIRARAJA</h1>
        <h2>REKTOR</h2>
        <h3>LEMBAR DISPOSISI</h3>
    </div>

    {{-- Info Surat --}}
    <table class="info-table">
        <tr>
            <td class="label">Surat dari</td><td class="colon">:</td>
            <td style="width: 30%;">{{ $surat->surat_dari }}</td>
            <td class="label">Diterima tanggal</td><td class="colon">:</td>
            <td>{{ $surat->diterima_tanggal->isoFormat('D MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Surat</td><td class="colon">:</td>
            <td>{{ $surat->tanggal_surat->isoFormat('D MMMM YYYY') }}</td>
            <td class="label">No. Agenda</td><td class="colon">:</td>
            <td>{{ $surat->no_agenda }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Surat</td><td class="colon">:</td>
            <td>{{ $surat->nomor_surat }}</td>
            <td class="label">Sifat</td><td class="colon">:</td>
            <td>{{ $surat->sifat }}</td>
        </tr>
        <tr>
            <td class="label">Perihal</td><td class="colon">:</td>
            @if($tipe_tujuan_fix == 'rektor')
                <td style="font-weight: bold;">{{ $surat->perihal }}</td>
                <td class="label">Tujuan</td><td class="colon">:</td>
                <td style="font-weight: bold; text-transform: uppercase;">Rektor</td>
            @else
                <td colspan="4" style="font-weight: bold;">{{ $surat->perihal }}</td>
            @endif
        </tr>
    </table>

    {{-- Checklist Disposisi --}}
    <table class="checklist-table">
        <tr>
            <th style="width: 55%;">DITERUSKAN KEPADA</th>
            <th style="width: 45%;">INSTRUKSI / KLASIFIKASI</th>
        </tr>
        <tr>
            {{-- KOLOM KIRI: Daftar Pimpinan, Fakultas & LAINNYA --}}
            <td style="line-height: 1.3;">
                
                Wakil Rektor I {!! centang('Wakil Rektor I', $satkerTujuanList) !!} &nbsp;
                II {!! centang('Wakil Rektor II', $satkerTujuanList) !!} &nbsp;
                III {!! centang('Wakil Rektor III', $satkerTujuanList) !!}
                <br>
                <div style="margin: 3px 0; border-bottom: 1px dashed #ccc;"></div>
                
                <table class="faculty-table">
                    <tr>
                        <td>Dekan</td><td>{!! centang('Dekan FP', $satkerTujuanList) !!}</td>
                        <td>Wadek I</td><td>{!! centang('Wadek I FP', $satkerTujuanList) !!}</td>
                        <td>II</td><td>{!! centang('Wadek II FP', $satkerTujuanList) !!}</td>
                        <td class="faculty-name">FP</td>
                    </tr>
                    <tr>
                        <td>Dekan</td><td>{!! centang('Dekan FH', $satkerTujuanList) !!}</td>
                        <td>Wadek I</td><td>{!! centang('Wadek I FH', $satkerTujuanList) !!}</td>
                        <td>II</td><td>{!! centang('Wadek II FH', $satkerTujuanList) !!}</td>
                        <td class="faculty-name">FH</td>
                    </tr>
                    <tr>
                        <td>Dekan</td><td>{!! centang('Dekan FEB', $satkerTujuanList) !!}</td>
                        <td>Wadek I</td><td>{!! centang('Wadek I FEB', $satkerTujuanList) !!}</td>
                        <td>II</td><td>{!! centang('Wadek II FEB', $satkerTujuanList) !!}</td>
                        <td class="faculty-name">FEB</td>
                    </tr>
                    <tr>
                        <td>Dekan</td><td>{!! centang('Dekan FISIP', $satkerTujuanList) !!}</td>
                        <td>Wadek I</td><td>{!! centang('Wadek I FISIP', $satkerTujuanList) !!}</td>
                        <td>II</td><td>{!! centang('Wadek II FISIP', $satkerTujuanList) !!}</td>
                        <td class="faculty-name">FISIP</td>
                    </tr>
                    <tr>
                        <td>Dekan</td><td>{!! centang('Dekan FT', $satkerTujuanList) !!}</td>
                        <td>Wadek I</td><td>{!! centang('Wadek I FT', $satkerTujuanList) !!}</td>
                        <td>II</td><td>{!! centang('Wadek II FT', $satkerTujuanList) !!}</td>
                        <td class="faculty-name">FT</td>
                    </tr>
                    <tr>
                        <td>Dekan</td><td>{!! centang('Dekan FIK', $satkerTujuanList) !!}</td>
                        <td>Wadek I</td><td>{!! centang('Wadek I FIK', $satkerTujuanList) !!}</td>
                        <td>II</td><td>{!! centang('Wadek II FIK', $satkerTujuanList) !!}</td>
                        <td class="faculty-name">FIK</td>
                    </tr>
                    <tr>
                        <td>Dekan</td><td>{!! centang('Dekan FKIP', $satkerTujuanList) !!}</td>
                        <td>Wadek I</td><td>{!! centang('Wadek I FKIP', $satkerTujuanList) !!}</td>
                        <td>II</td><td>{!! centang('Wadek II FKIP', $satkerTujuanList) !!}</td>
                        <td class="faculty-name">FKIP</td>
                    </tr>
                    <tr>
                        <td>Direktur</td><td>{!! centang('Direktur PASCASARJANA', $satkerTujuanList) !!}</td>
                        <td>Wadek I</td><td>{!! centang('Wadek I PASCASARJANA', $satkerTujuanList) !!}</td>
                        <td>II</td><td>{!! centang('Wadek II PASCASARJANA', $satkerTujuanList) !!}</td>
                        <td class="faculty-name">PASCASARJANA</td>
                    </tr>
                </table>
                
                <div style="margin: 3px 0; border-bottom: 1px dashed #ccc;"></div>

                {!! centang('Ketua Pusat Jaminan Mutu', $satkerTujuanList) !!} Ketua Pusat Jaminan Mutu <br>
                {!! centang('Ketua Satuan Pengendali Internal', $satkerTujuanList) !!} Ketua Satuan Pengendali Internal <br>
                {!! centang('Ka. LPPM', $satkerTujuanList) !!} Ka. LPPM <br>
                {!! centang('Ka. LBH', $satkerTujuanList) !!} Ka. LBH <br>
                {!! centang('Ketua Badan Pengelola Usaha', $satkerTujuanList) !!} Ketua Badan Pengelola Usaha <br>
                {!! centang('Ka. Biro Adm. Akademik & Kemahasiswaan', $satkerTujuanList) !!} Ka. Biro Adm. Akademik & Kemahasiswaan <br>
                {!! centang('Ka. Biro Adm. Umum', $satkerTujuanList) !!} Ka. Biro Adm. Umum <br>
                {!! centang('Ka. Biro Adm. Keuangan', $satkerTujuanList) !!} Ka. Biro Adm. Keuangan <br>
                {!! centang('Ka. Biro Adm. Perencanaan', $satkerTujuanList) !!} Ka. Biro Adm. Perencanaan, SIM & PD <br>
                {!! centang('Kepala UPT Perpustakaan', $satkerTujuanList) !!} Kepala UPT Perpustakaan <br>
                {!! centang('Kepala UPT Laboratorium/Studio', $satkerTujuanList) !!} Kepala UPT Laboratorium/Studio <br>
                {!! centang('Kepala UPT Pusat Bahasa', $satkerTujuanList) !!} Kepala UPT Pusat Bahasa <br>
                {!! centang('Kepala UPT Pusat Layanan Karier', $satkerTujuanList) !!} Ka. UPT Pusat Layanan Karier & Konseling <br>
                {!! centang('Kepala UPT Pusat Layanan Kesehatan', $satkerTujuanList) !!} Ka. UPT Pusat Layanan Kesehatan <br>
                {!! centang('Kepala UPT Penerimaan Mahasiswa Baru', $satkerTujuanList) !!} Ka. UPT Penerimaan Mahasiswa Baru <br>
                {!! centang('Kepala Sekretarian', $satkerTujuanList) !!} Kepala Sekretarian <br>
                {!! centang('Sekretaris Rektor', $satkerTujuanList) !!} Sekretaris Rektor <br>

                {{-- MENAMPILKAN DISPOSISI LAIN (Manual Input) --}}
                @if(!empty($disposisiLain))
                    <div style="margin-top: 2px;">
                        {!! centang($disposisiLain, [], true) !!} <strong>{{ $disposisiLain }}</strong>
                    </div>
                @endif

            </td>
            
            {{-- KOLOM KANAN: INSTRUKSI (SEKARANG LEBIH PINTAR DETEKSI TEXT) --}}
            <td style="line-height: 1.4;">
                {!! centang('Segera', $klasifikasiList) !!} Segera<br>
                {!! centang('Untuk Disposisi', $klasifikasiList) !!} Untuk Disposisi<br>
                {!! centang('Mohon Tindak Lanjut', $klasifikasiList) !!} Mohon Tindak Lanjut<br>
                {!! centang('Selesaikan', $klasifikasiList) !!} Selesaikan<br>
                {!! centang('Pedomani', $klasifikasiList) !!} Pedomani<br>
                {!! centang('Sarankan', $klasifikasiList) !!} Sarankan<br>
                {!! centang('Untuk Diketahui', $klasifikasiList) !!} Untuk Diketahui<br>
                {!! centang('Untuk Diproses', $klasifikasiList) !!} Untuk Diproses<br>
                {!! centang('Siapkan Bahan', $klasifikasiList) !!} Siapkan Bahan<br>
                {!! centang('Sampaikan Kpd Ybs', $klasifikasiList) !!} Sampaikan Kpd Ybs<br>
                {!! centang('Pertimbangkan', $klasifikasiList) !!} Pertimbangkan<br>
                {!! centang('Agar Menghadap Saya', $klasifikasiList) !!} Agar Menghadap Saya<br>
                {!! centang('Agendakan', $klasifikasiList) !!} Agendakan<br>
                {!! centang('Laporkan Hasilnya', $klasifikasiList) !!} Laporkan Hasilnya<br>
                {!! centang('Untuk Diwakili', $klasifikasiList) !!} Untuk Diwakili<br>
            </td>
        </tr>
    </table>

    {{-- ALUR DISPOSISI BAWAH (FULL WIDTH UNTUK CATATAN REKTOR) --}}
    <table class="flow-table">
        <tr>
            <td colspan="3" style="min-height: 100px;">
                <strong>Catatan Rektor / Kepala:</strong><br>
                <div class="catatan-box">
                    {{ $catatanRektorUtama }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        Sumenep, {{ \Carbon\Carbon::parse($disposisiRektorPertama ? $disposisiRektorPertama->created_at : $surat->created_at)->isoFormat('D MMMM YYYY') }}<br>
        REKTOR,<br>
        <div class="signature-space"></div>
        <div class="name">Dr. Sjaifurrachman, S.H., C.N., M.H.</div>
        NIDN. 0722086203
    </div>

</body>
</html>