<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_surat',
        'tipe_kirim',
        'tanggal_surat',
        'tujuan_surat',
        'tujuan_satker_id', // Ini tetap ada tapi nanti bisa null
        'perihal',
        'file_surat',
        'user_id',
        'tujuan_luar',
    ];

    protected $casts = [
        'tanggal_surat' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELASI BARU: Ke Banyak Satker Penerima (Pivot)
     */
    public function penerimaInternal()
    {
        return $this->belongsToMany(Satker::class, 'surat_keluar_internal_penerima', 'surat_keluar_id', 'satker_id')
                    ->withPivot('dibaca_pada')
                    ->withTimestamps();
    }
    
    // Helper untuk mengambil list nama penerima sebagai string
    public function getListPenerimaAttribute()
    {
        return $this->penerimaInternal->pluck('nama_satker')->implode(', ');
    }
}