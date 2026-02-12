<?php

namespace App\Helpers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;

class BarcodeHelper
{
    public static function generateQrWithLabel(string $data, ?string $logoPath, string $savePath, string $label, int $logoSize = 40, bool $isLegal = true): void
    {
        // 1. Generate QR Code dasar (Sama seperti asli)
        $builder = new Builder(
            writer: new PngWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            backgroundColor: new Color(255, 255, 255)
        );

        $result = $builder->build();
        $qrImage = imagecreatefromstring($result->getString());
        $white = imagecolorallocate($qrImage, 255, 255, 255);

        // --- PUNCHOUT (Sama seperti asli) ---
        $holeSize = 55; 
        imagefilledrectangle($qrImage, 150 - ($holeSize/2), 150 - ($holeSize/2), 150 + ($holeSize/2), 150 + ($holeSize/2), $white);

        // --- TEMPEL LOGO (Sama seperti asli) ---
        if ($logoPath && file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoRaw = @imagecreatefromstring($logoData);
            if ($logoRaw) {
                imagealphablending($qrImage, true);
                imagesavealpha($qrImage, true);
                $logoW = imagesx($logoRaw);
                $logoH = imagesy($logoRaw);
                imagecopyresampled($qrImage, $logoRaw, 150 - ($logoSize/2), 150 - ($logoSize/2), 0, 0, $logoSize, $logoSize, $logoW, $logoH);
                imagedestroy($logoRaw);
            }
        }

        // 2. CANVAS SETTING (Layout memanjang ke samping sesuai Screenshot 104958)
        // Kita buat canvas yang lebar (1000px) tapi pendek (350px) agar teks muat satu baris
        $canvasW = $isLegal ? 1000 : 300; 
        $canvasH = $isLegal ? 350 : 300; 
        $finalImg = imagecreatetruecolor($canvasW, $canvasH);

        $whiteFinal = imagecolorallocate($finalImg, 255, 255, 255);
        imagefill($finalImg, 0, 0, $whiteFinal); 
        
        // Tetap tempel QR di pojok kiri atas (0,0)
        imagecopy($finalImg, $qrImage, 0, 0, 0, 0, 300, 300);

        // 3. TEKS URL DI BAWAH (Satu baris memanjang)
        if ($isLegal) {
            $gray = imagecolorallocate($finalImg, 80, 80, 80); // Warna abu-abu tua agar elegan
            $fontPath = public_path('fonts/times.ttf'); 
            
            // Gunakan ukuran font yang lebih kecil (12-14) agar tidak memakan tempat
            $textToPrint = "Digital Verification: " . $label;

            if (file_exists($fontPath)) {
                // Posisi teks di bawah barcode (Y = 330) mulai dari pinggir kiri (X = 10)
                imagettftext($finalImg, 18, 0, 10, 335, $gray, $fontPath, $textToPrint);
            } else {
                imagestring($finalImg, 5, 10, 315, $textToPrint, $gray);
            }
        }

        imagepng($finalImg, $savePath);
        imagedestroy($qrImage);
        imagedestroy($finalImg);
    }
}