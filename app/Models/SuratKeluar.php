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
        'qrcode_hash',
        'ukuran_kertas',
        'verifikasi_url',
        'pdf_password',
        'is_final'
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
// File: app/Models/SuratKeluar.php
public function riwayats()
{
    // Untuk surat internal, foreign key di riwayat_surats adalah 'surat_keluar_id'
    return $this->hasMany(RiwayatSurat::class, 'surat_keluar_id', 'id');
}

// Di dalam class SuratKeluar
public function validasis()
{
    // Mengacu pada tabel validasi yang kita bahas sebelumnya
    return $this->hasMany(SuratValidasi::class, 'surat_keluar_id');
}

public function tembusans()
{
    return $this->hasMany(SuratTembusan::class, 'surat_keluar_id');
}
public function surats()
{
    return $this->hasMany(Surat::class, 'nomor_surat', 'nomor_surat'); 
    // atau sesuaikan foreign key-nya, misal 'surat_keluar_id'
}

    

}