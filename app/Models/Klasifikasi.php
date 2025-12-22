<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Klasifikasi extends Model
{
    // Izinkan kolom-kolom ini untuk diisi
    protected $fillable = ['kode_klasifikasi', 'nama_klasifikasi', 'deskripsi'];
    public function surats()
{
    return $this->belongsToMany(Surat::class, 'klasifikasi_surat');
}
}
