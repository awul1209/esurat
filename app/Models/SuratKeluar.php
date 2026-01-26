<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuratKeluar extends Model
{
    use HasFactory;
use SoftDeletes;
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
        'status',
        'via',
        'tanggal_terusan', // Tambahkan ini
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
    // Pastikan parameter ke-2 adalah nama tabel pivot Anda yang benar
    return $this->belongsToMany(Satker::class, 'surat_keluar_internal_penerima', 'surat_keluar_id', 'satker_id')
                ->withPivot('is_read', 'dibaca_pada') // <-- PENTING: Load kolom ini
                ->withTimestamps();
                
}
    
    // Helper untuk mengambil list nama penerima sebagai string
    public function getListPenerimaAttribute()
    {
        return $this->penerimaInternal->pluck('nama_satker')->implode(', ');
    }

    public function tujuanSatkers()
{
    return $this->belongsToMany(Satker::class, 'surat_keluar_rektor_tujuan', 'surat_keluar_id', 'satker_id');
}
public function riwayats() {
    return $this->hasMany(RiwayatSurat::class, 'surat_keluar_id');
}

    

}