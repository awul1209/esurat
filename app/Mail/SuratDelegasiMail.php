<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuratDelegasiMail extends Mailable
{
    use Queueable, SerializesModels;

    // Definisikan variabel agar bisa dibaca di Blade
    public $surat;
    public $catatan;
    public $namaAdmin;

    /**
     * Menerima data dari Controller
     */
    public function __construct($surat, $catatan, $namaAdmin)
    {
        $this->surat = $surat;
        $this->catatan = $catatan;
        $this->namaAdmin = $namaAdmin;
    }

    /**
     * Mengatur Subject Email
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Delegasi Surat Baru: ' . $this->surat->nomor_surat,
        );
    }

    /**
     * Mengatur View yang digunakan
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notif_delegasi', // Sesuaikan dengan file yang Anda buat
        );
    }

    public function attachments(): array
    {
        return [];
    }
}