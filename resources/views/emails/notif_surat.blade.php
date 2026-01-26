<!DOCTYPE html>
<html>
<head>
    <title>{{ $details['subject'] }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
        <div style="background: #0d6efd; color: #ffffff; padding: 20px; text-align: center;">
            <h2 style="margin: 0;">Sistem Informasi Arsip</h2>
        </div>
        <div style="padding: 20px;">
            <h3>{{ $details['greeting'] }}</h3>
            <p>{{ $details['body'] }}</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $details['actionurl'] }}" 
                   style="background: #198754; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                   {{ $details['actiontext'] }}
                </a>
            </div>
            @if(isset($details['file_url']))
                <div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border: 1px dashed #ccc; text-align: center;">
                    <p style="margin-bottom: 10px; font-size: 0.9em;">Lampiran Dokumen:</p>
                    <a href="{{ $details['file_url'] }}" 
                    style="color: #0d6efd; font-weight: bold; text-decoration: underline;">
                    <i class="bi bi-download"></i> Klik Disini untuk Download File Surat
                    </a>
                </div>
            @endif
            
            <p style="font-size: 0.9em; color: #666;">
                Ini adalah pesan otomatis dari sistem. Mohon tidak membalas email ini.
            </p>
        </div>
    </div>
</body>
</html>