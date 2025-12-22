<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surat extends Model
{
    use HasFactory;

    protected $fillable = [
        'surat_dari',
        'tipe_surat',
        'nomor_surat',
        'tanggal_surat',
        'perihal',
        'no_agenda',
        'diterima_tanggal',
        'sifat',
        'file_surat',
        'status',
        'user_id',
        'tujuan_tipe',  
        'tujuan_satker_id',
        'tujuan_user_id', // <-- TAMBAHAN BARU
    ];

    
    protected $casts = [
        'tanggal_surat' => 'datetime',
        'diterima_tanggal' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi
    |--------------------------------------------------------------------------
    */
    public function disposisis()
    {
        return $this->hasMany(Disposisi::class);
    }

    public function tujuanSatker()
    {
        return $this->belongsTo(Satker::class, 'tujuan_satker_id');
    }

    /**
     * ====================================================
     * RELASI BARU: Ke User (Pegawai) yang Dituju
     * ====================================================
     */
    public function tujuanUser()
    {
        return $this->belongsTo(User::class, 'tujuan_user_id');
    }

    public function riwayats()
    {
        return $this->hasMany(RiwayatSurat::class)->with('user')->orderBy('created_at', 'asc');
    }

    public function satkerPenerima()
    {
        return $this->belongsToMany(Satker::class, 'surat_edaran_satker')
                    ->withPivot('status')
                    ->withTimestamps();
    }
    public function delegasiPegawai()
    {
        return $this->belongsToMany(User::class, 'surat_delegasi', 'surat_id', 'user_id')
                    ->withPivot('status', 'catatan')
                    ->withTimestamps();
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // Relasi Many-to-Many ke Klasifikasi
public function klasifikasis()
{
    // Parameter 2: nama tabel pivot yg tadi dibuat
    return $this->belongsToMany(Klasifikasi::class, 'klasifikasi_surat');
}
}