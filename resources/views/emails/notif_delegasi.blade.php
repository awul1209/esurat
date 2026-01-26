<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Notifikasi Disposisi Surat</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #5e72e4;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
            background-color: #ffffff;
        }
        .footer {
            background-color: #f8f9fe;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #8898aa;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #5e72e4;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
        }
        .info-box {
            background-color: #f1f3f9;
            padding: 15px;
            border-left: 4px solid #5e72e4;
            margin: 20px 0;
        }
        .label {
            font-weight: bold;
            color: #525f7f;
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            text-uppercase: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0;">Disposisi Surat Baru</h2>
        </div>
        <div class="content">
            <p>Halo, <strong>{{ $targetUser->name }}</strong>,</p>
            <p>Anda menerima disposisi/delegasi surat baru dari Admin Satker untuk segera ditindaklanjuti.</p>
            
            <div class="info-box">
                <span class="label">Perihal Surat:</span>
                <div style="font-size: 16px; font-weight: bold; color: #32325d;">{{ $perihal }}</div>
                
                <span class="label" style="margin-top: 15px;">Instruksi / Klasifikasi:</span>
                <div style="color: #f5365c; font-weight: bold;">{{ $instruksi }}</div>
            </div>

            @if($catatan)
            <p><strong>Catatan Tambahan:</strong><br>
            <i style="color: #525f7f;">"{{ $catatan }}"</i></p>
            @endif

            <p>Silakan klik tombol di bawah ini untuk melihat detail surat dan memberikan laporan progres pada sistem E-Surat.</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/pegawai/dashboard') }}" class="btn">Buka Dashboard Pegawai</a>
            </div>
        </div>
        <div class="footer">
            <p>Sistem Informasi E-Surat &copy; {{ date('Y') }}<br>
            Harap tidak membalas email otomatis ini.</p>
        </div>
    </div>
</body>
</html>