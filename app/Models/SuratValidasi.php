<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratValidasi extends Model
{
    protected $fillable = ['surat_keluar_id', 'pimpinan_id', 'status', 'catatan'];

    public function suratKeluar() {
        return $this->belongsTo(SuratKeluar::class);
    }

    public function pimpinan() {
        return $this->belongsTo(User::class, 'pimpinan_id');
    }
}