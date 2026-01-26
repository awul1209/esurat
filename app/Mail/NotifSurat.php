<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class NotifSurat extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // Gunakan subjek dinamis dari $details
            subject: $this->details['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            // DI SINI HARUS DIGANTI:
            view: 'emails.notif_surat', 
        );
    }

public function attachments(): array
    {
        $attachments = [];

        // Cek apakah ada key 'file_path' di dalam $details
        if (isset($this->details['file_path']) && !empty($this->details['file_path'])) {
            $path = public_path('storage/' . $this->details['file_path']);
            
            // Cek apakah filenya benar-benar ada di folder storage
            if (file_exists($path)) {
                $attachments[] = Attachment::fromPath($path);
            }
        }

        return $attachments;
    }
}