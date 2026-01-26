<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use App\Mail\NotifSurat;
use App\Models\User;

class EmailHelper
{
    /**
     * Kirim Notifikasi ke User (Email 1 & Email 2)
     *
     * @param int|array $userIds ID User atau Array ID User penerima
     * @param array $details Isi email (subject, greeting, body, actiontext, actionurl)
     */
    public static function kirimNotif($userIds, $details)
    {
        // Pastikan input berupa array agar bisa menampung banyak user sekaligus
        $ids = is_array($userIds) ? $userIds : [$userIds];

        // Ambil semua user terkait
        $users = User::whereIn('id', $ids)->get();

        foreach ($users as $user) {
            $recipients = [];

            // Masukkan email utama jika ada
            if (!empty($user->email)) {
                $recipients[] = $user->email;
            }

            // Masukkan email kedua jika ada
            if (!empty($user->email2)) {
                $recipients[] = $user->email2;
            }

            // Jika ada alamat email, kirim!
            if (!empty($recipients)) {
                try {
                    Mail::to($recipients)->send(new NotifSurat($details));
                } catch (\Exception $e) {
                    // Log error jika pengiriman gagal agar sistem tidak crash
                    \Log::error("Gagal kirim email ke User ID {$user->id}: " . $e->getMessage());
                    // dd($e->getMessage());
                }
            }
        }
    }
}