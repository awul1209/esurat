<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatSurat extends Model
{
    use HasFactory; // Tambahkan ini jika belum ada
    
    protected $fillable = [
        'surat_id',
        'user_id',
        'status_aksi',
        'catatan',
    ];

    /**
     * ====================================================
     * TAMBAHAN BARU: Relasi ke User
     * ====================================================
     *
     * Ini akan mengizinkan kode kita memanggil ->user
     * untuk mendapatkan nama user yang melakukan aksi.
     * INI YANG AKAN MEMPERBAIKI ERROR 500 ANDA.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * (Opsional) Relasi kembali ke Surat
     */
    public function surat()
    {
        return $this->belongsTo(Surat::class);
    }
}