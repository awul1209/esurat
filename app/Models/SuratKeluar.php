<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    use HasFactory;

    // Tentukan nama tabel jika berbeda dari 'surat_keluars'
    // protected $table = 'surat_keluars';

    protected $fillable = [
        'nomor_surat',
        'tanggal_surat',
        'tujuan_surat',
        'perihal',
        'file_surat',
        'user_id',
    ];

    /**
     * Tipe data untuk kolom tanggal
     */
    protected $casts = [
        'tanggal_surat' => 'datetime',
    ];

    /**
     * Relasi ke user (Admin BAU) yang membuat surat ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}