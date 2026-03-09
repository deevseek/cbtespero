<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_aplikasi','logo_sekolah','nama_sekolah','alamat_sekolah','tahun_ajaran','acak_soal','acak_jawaban','tampilkan_nilai_setelah_ujian'
    ];
}
