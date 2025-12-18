<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'no_hp',
        'role',      // <-- TAMBAHKAN INI
        'satker_id', // <-- TAMBAHKAN INI
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke Satker
     */
    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }

    public function suratDibuat()
    {
        return $this->hasMany(Surat::class, 'user_id');
    }

    /**
     * Relasi disposisi yang dibuat oleh user ini (Admin Rektor)
     */
    public function disposisiDibuat()
    {
        return $this->hasMany(Disposisi::class, 'user_id');
    }

    /**
     * ====================================================
     * PENTING: INI ADALAH PERBAIKAN ERROR ANDA
     * ====================================================
     *
     * Surat yang ditujukan ke user ini (sebagai pegawai)
     * Ini terhubung ke kolom 'tujuan_user_id' di tabel 'surats'.
     */
    public function suratUntukSaya()
    {
        return $this->hasMany(Surat::class, 'tujuan_user_id');
    }
}