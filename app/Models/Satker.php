<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satker extends Model
{
    protected $fillable = [
    'nama_satker',
    'kode_satker',
    'stempel_image', 
    'logo_satker',
    'token_code',
];
    public function suratEdaran()
    {
        return $this->belongsToMany(Surat::class, 'surat_edaran_satker')
                    ->withPivot('status')
                    ->withTimestamps()
                    ->orderBy('pivot_created_at', 'desc');
    }
    public function users()
{
    // Relasi satu Satker memiliki banyak User
    return $this->hasMany(User::class, 'satker_id');
}
}
