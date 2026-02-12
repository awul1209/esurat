<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratTembusan extends Model
{
    protected $fillable = [
        'surat_keluar_id', 
        'satker_id', 
        'user_id'
    ];

public function suratKeluar()
{
    // Pastikan foreign key 'surat_keluar_id' sesuai dengan tabel Anda
    return $this->belongsTo(\App\Models\SuratKeluar::class, 'surat_keluar_id');
}

    public function satker() {
        return $this->belongsTo(Satker::class);
    }
}