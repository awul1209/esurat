<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaService
{
    public static function send($to, $message)
    {
        if (empty($to)) return false;

        // 1. FORMAT NOMOR HP (Auto Convert 08 -> 628)
        $to = preg_replace('/[^0-9]/', '', $to);
        if (substr($to, 0, 2) == '08') {
            $to = '62' . substr($to, 1);
        }

        // URL API
        $endpoint = "https://wa.apiwiraraja.com/";
        
        // Parameter Token
        $queryParams = [
            'token' => 'apiwiraraja_secret',
            'uid'   => '1'
        ];

        // Data Pesan
        $data = [
            'to'      => $to,
            'message' => $message,
        ];

        try {
            // === MODIFIKASI DISINI: withOutVerifying() ===
            $response = Http::withoutVerifying()
                            ->asForm()
                            ->post($endpoint . '?' . http_build_query($queryParams), $data);

            if ($response->successful()) {
                Log::info("WA Terkirim ke $to");
                return true;
            } else {
                Log::error("Gagal WA ke $to. Response: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error WaService: " . $e->getMessage());
            return false;
        }
    }
}