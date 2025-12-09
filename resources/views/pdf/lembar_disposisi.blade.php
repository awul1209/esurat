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
        
        .checkbox {
            font-family: 'DejaVu Sans', sans-serif;
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            line-height: 5px; 
            font-size: 20px;
            font-weight: bold;
            margin-right: 4px;
            vertical-align: middle;
        }
        
        .faculty-table { width: 100%; border: none; }
        .faculty-table td { border: none; padding: 2px 0; }
        .faculty-name { font-weight: bold; }
        
        .flow-table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid #000; }
        .flow-table td { border: 1px solid #000; padding: 5px 8px; vertical-align: top; }
        .flow-label { font-weight: normal; width: 25%; }
        .flow-colon { width: 2%; text-align: center; }
        .flow-value { font-weight: bold; }
        .catatan-box { font-style: italic; margin-top: 2px; }

        .footer { margin-top: 30px; width: 300px; float: right; text-align: center; }
        .footer .signature-space { height: 70px; }
        .footer .name { font-weight: bold; text-decoration: underline; }
        
        .penerima-akhir { font-size: 12pt; font-weight: bold; margin-top: 5px; text-transform: uppercase; }
    </style>
</head>
<body>

    @php
        // 1. Cek Disposisi
        $hasDisposisi = $disposisi ? true : false;
        $tujuan_id = $hasDisposisi ? $disposisi->tujuan_satker_id : null;
        $klasifikasi_id = $hasDisposisi ? $disposisi->klasifikasi_id : null;
        
        $satker_dipilih = $tujuan_id ? (\App\Models\Satker::find($tujuan_id)->nama_satker ?? '') : '';
        $klasifikasi_dipilih = $klasifikasi_id ? (\App\Models\Klasifikasi::find($klasifikasi_id)->nama_klasifikasi ?? '') : '';

        // 2. Data Delegasi
        $riwayatDelegasi = $surat->riwayats->filter(function ($item) {
            return str_contains($item->status_aksi, 'Didelegasikan') || str_contains($item->status_aksi, 'Diteruskan ke Pegawai');
        })->last();

        $catatanSatker = $riwayatDelegasi ? $riwayatDelegasi->catatan : '-';
        if ($catatanSatker !== '-' && strpos($catatanSatker, 'Catatan:') !== false) {
            $parts = explode('Catatan:', $catatanSatker);
            $catatanSatker = trim(end($parts), " \"");
        }

        // 3. Logika Centang
        function centang($nama_target, $nama_terpilih) {
            $isi = '&nbsp;';
            if ($nama_terpilih && $nama_terpilih == $nama_target) {
                $isi = '✓'; 
            }
            return '<span class="checkbox">' . $isi . '</span>';
        }

        // ====================================================
        // 4. LOGIKA PENENTUAN TIPE TUJUAN (Sama seperti Index)
        // ====================================================
        $tipe_tujuan_fix = $surat->tujuan_tipe;
        
        // Jika kosong, lakukan deteksi otomatis (fallback)
        if (empty($tipe_tujuan_fix)) {
            if ($surat->tujuan_satker_id) {
                $tipe_tujuan_fix = 'satker';
            } elseif ($surat->tujuan_user_id) {
                $tipe_tujuan_fix = 'pegawai';
            } else {
                // Default jika tidak ada tujuan spesifik => Rektor
                $tipe_tujuan_fix = 'rektor';
            }
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
        
        {{-- 
            ====================================================
            LOGIKA TAMPILAN:
            Perihal dan Tujuan berada dalam satu baris (sejajar).
            Tujuan ada di sebelah kanan (bawah Sifat), Perihal di kiri (bawah No. Surat).
            Jika bukan Rektor, Perihal mengambil lebar penuh (colspan 4).
            ====================================================
        --}}
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
            {{-- KOLOM KIRI: Daftar Pimpinan & Fakultas --}}
            <td style="line-height: 1.3;">
                
                Wakil Rektor I {!! centang('Wakil Rektor I', $satker_dipilih) !!} &nbsp;
                II {!! centang('Wakil Rektor II', $satker_dipilih) !!} &nbsp;
                III {!! centang('Wakil Rektor III', $satker_dipilih) !!}
                <br>
                
                <div style="margin: 3px 0; border-bottom: 1px dashed #ccc;"></div>
                
                {{-- Tabel layout Fakultas --}}
                <table class="faculty-table">
                    <tr>
                        <td>Dekan</td>
                        <td>{!! centang('Dekan FP', $satker_dipilih) !!}</td>
                        <td>Wadek I</td>
                        <td>{!! centang('Wadek I FP', $satker_dipilih) !!}</td>
                        <td>II</td>
                        <td>{!! centang('Wadek II FP', $satker_dipilih) !!}</td>
                        <td class="faculty-name">FP</td>
                    </tr>
                    <tr>
                        <td>Dekan</td>
                        <td>{!! centang('Dekan FH', $satker_dipilih) !!}</td>
                        <td>Wadek I</td>
                        <td>{!! centang('Wadek I FH', $satker_dipilih) !!}</td>
                        <td>II</td>
                        <td>{!! centang('Wadek II FH', $satker_dipilih) !!}</td>
                        <td class="faculty-name">FH</td>
                    </tr>
                    <tr>
                        <td>Dekan</td>
                        <td>{!! centang('Dekan FEB', $satker_dipilih) !!}</td>
                        <td>Wadek I</td>
                        <td>{!! centang('Wadek I FEB', $satker_dipilih) !!}</td>
                        <td>II</td>
                        <td>{!! centang('Wadek II FEB', $satker_dipilih) !!}</td>
                        <td class="faculty-name">FEB</td>
                    </tr>
                    <tr>
                        <td>Dekan</td>
                        <td>{!! centang('Dekan FISIP', $satker_dipilih) !!}</td>
                        <td>Wadek I</td>
                        <td>{!! centang('Wadek I FISIP', $satker_dipilih) !!}</td>
                        <td>II</td>
                        <td>{!! centang('Wadek II FISIP', $satker_dipilih) !!}</td>
                        <td class="faculty-name">FISIP</td>
                    </tr>
                    <tr>
                        <td>Dekan</td>
                        <td>{!! centang('Dekan FT', $satker_dipilih) !!}</td>
                        <td>Wadek I</td>
                        <td>{!! centang('Wadek I FT', $satker_dipilih) !!}</td>
                        <td>II</td>
                        <td>{!! centang('Wadek II FT', $satker_dipilih) !!}</td>
                        <td class="faculty-name">FT</td>
                    </tr>
                    <tr>
                        <td>Dekan</td>
                        <td>{!! centang('Dekan FIK', $satker_dipilih) !!}</td>
                        <td>Wadek I</td>
                        <td>{!! centang('Wadek I FIK', $satker_dipilih) !!}</td>
                        <td>II</td>
                        <td>{!! centang('Wadek II FIK', $satker_dipilih) !!}</td>
                        <td class="faculty-name">FIK</td>
                    </tr>
                    <tr>
                        <td>Dekan</td>
                        <td>{!! centang('Dekan FKIP', $satker_dipilih) !!}</td>
                        <td>Wadek I</td>
                        <td>{!! centang('Wadek I FKIP', $satker_dipilih) !!}</td>
                        <td>II</td>
                        <td>{!! centang('Wadek II FKIP', $satker_dipilih) !!}</td>
                        <td class="faculty-name">FKIP</td>
                    </tr>
                    <tr>
                        <td>Direktur</td>
                        <td>{!! centang('Direktur PASCASARJANA', $satker_dipilih) !!}</td>
                        <td>Wadek I</td>
                        <td>{!! centang('Wadek I PASCASARJANA', $satker_dipilih) !!}</td>
                        <td>II</td>
                        <td>{!! centang('Wadek II PASCASARJANA', $satker_dipilih) !!}</td>
                        <td class="faculty-name">PASCASARJANA</td>
                    </tr>
                </table>
                
                <div style="margin: 3px 0; border-bottom: 1px dashed #ccc;"></div>

                {{-- Lembaga, Biro, UPT --}}
                {!! centang('Ketua Pusat Jaminan Mutu', $satker_dipilih) !!} Ketua Pusat Jaminan Mutu <br>
                {!! centang('Ketua Satuan Pengendali Internal', $satker_dipilih) !!} Ketua Satuan Pengendali Internal <br>
                {!! centang('Kepala Lembaga Penelitian dan Pengamdian Kepada Masyarakat', $satker_dipilih) !!} Ka. LPPM <br>
                {!! centang('Kepala Lembaga Bantuan Hukum', $satker_dipilih) !!} Ka. LBH <br>
                {!! centang('Kepala Badan Pengelola usaha', $satker_dipilih) !!} Ketua Badan Pengelola Usaha <br>
                {!! centang('Kepala Biro Administrasi Akademik dan Kemahasiswaan', $satker_dipilih) !!} Ka. Biro Adm. Akademik & Kemahasiswaan <br>
                {!! centang('Kepala Biro Administrasi Umum', $satker_dipilih) !!} Ka. Biro Adm. Umum <br>
                {!! centang('Kepala Biro Administrasi Keuangan', $satker_dipilih) !!} Ka. Biro Adm. Keuangan <br>
                {!! centang('Kepala Biro Administrasi Perencanaan', $satker_dipilih) !!} Ka. Biro Adm. Perencanaan, SIM & PD <br>
                {!! centang('Kepala UPT Perpustakaan', $satker_dipilih) !!} Kepala UPT Perpustakaan <br>
                {!! centang('Kepala UPT Laboratorium/Studio', $satker_dipilih) !!} Kepala UPT Laboratorium/Studio <br>
                {!! centang('Kepala UPT Pusat Bahasa', $satker_dipilih) !!} Kepala UPT Pusat Bahasa <br>
                {!! centang('Kepala UPT Pusat Layanan Karier', $satker_dipilih) !!} Ka. UPT Pusat Layanan Karier & Konseling <br>
                {!! centang('Kepala UPT Pusat Layanan Kesehatan', $satker_dipilih) !!} Ka. UPT Pusat Layanan Kesehatan <br>
                {!! centang('Kepala UPT Penerimaan Mahasiswa Baru', $satker_dipilih) !!} Ka. UPT Penerimaan Mahasiswa Baru <br>
                {!! centang('Kepala Sekretarian', $satker_dipilih) !!} Kepala Sekretarian <br>
                {!! centang('Sekretaris Rektor', $satker_dipilih) !!} Sekretaris Rektor <br>
            </td>
            
            {{-- KOLOM KANAN: INSTRUKSI --}}
            <td style="line-height: 1.4;">
                {!! centang('Segera', $klasifikasi_dipilih) !!} Segera<br>
                {!! centang('Disposisi', $klasifikasi_dipilih) !!} Untuk Disposisi<br>
                {!! centang('Tindak Lanjut', $klasifikasi_dipilih) !!} Mohon Tindak Lanjut<br>
                {!! centang('Selesaikan', $klasifikasi_dipilih) !!} Selesaikan<br>
                {!! centang('Pedomani', $klasifikasi_dipilih) !!} Pedomani<br>
                {!! centang('Sarankan', $klasifikasi_dipilih) !!} Sarankan<br>
                {!! centang('Untuk Diketahui', $klasifikasi_dipilih) !!} Untuk Diketahui<br>
                {!! centang('Untuk Diproses', $klasifikasi_dipilih) !!} Untuk Diproses<br>
                {!! centang('Siapkan', $klasifikasi_dipilih) !!} Siapkan Bahan<br>
                {!! centang('Sampaikan Ybs', $klasifikasi_dipilih) !!} Sampaikan Kpd Ybs<br>
                {!! centang('Pertimbangkan', $klasifikasi_dipilih) !!} Pertimbangkan<br>
                {!! centang('Menghadap', $klasifikasi_dipilih) !!} Agar Menghadap Saya<br>
                {!! centang('Agendakan', $klasifikasi_dipilih) !!} Agendakan<br>
                {!! centang('Laporkan', $klasifikasi_dipilih) !!} Laporkan Hasilnya<br>
                {!! centang('Diwakili', $klasifikasi_dipilih) !!} Untuk Diwakili<br>
            </td>
        </tr>
    </table>

    {{-- Alur Disposisi Bawah --}}
    <table class="flow-table">
        <tr>
            <td colspan="3">
                <strong>Catatan Rektor / Kepala:</strong><br>
                <div class="catatan-box">
                    @if($hasDisposisi)
                        {{ $disposisi->catatan_rektor ?? '-' }}
                        @if($disposisi->disposisi_lain)
                            <br>({{ $disposisi->disposisi_lain }})
                        @endif
                    @else
                        - 
                    @endif
                </div>
            </td>
        </tr>
        <!-- <tr>
            <td class="flow-label">Diteruskan kepada</td>
            <td class="flow-colon">:</td>
            <td class="flow-value">
                @if ($surat->tujuanUser)
                    {{ $surat->tujuanUser->name }}
                @else
                    -
                @endif
            </td>
        </tr> -->
        <tr>
            <td class="flow-label">Pada penyelesaian</td>
            <td class="flow-colon">:</td>
            <td class="flow-value">
                @if ($surat->tujuanUser || $surat->status == 'arsip_satker')
                    {{ \Carbon\Carbon::parse($surat->updated_at)->isoFormat('D MMMM YYYY') }}
                @else
                    -
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3" style="border-top: 1px solid #000;">
                <strong>Catatan {{ $satker_dipilih ? $satker_dipilih : 'Tindak Lanjut' }}:</strong><br>
                <div class="catatan-box">
                    {{ $catatanSatker }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        Sumenep, {{ \Carbon\Carbon::parse($hasDisposisi ? $disposisi->created_at : $surat->created_at)->isoFormat('D MMMM YYYY') }}<br>
        REKTOR,<br>
        <div class="signature-space"></div>
        <div class="name">Dr. Sjaifurrachman, S.H., C.N., M.H.</div>
        NIDN. 0722086203
    </div>

</body>
</html>