<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'mata_pelajaran','soal','image_path','pilihan_a','pilihan_b','pilihan_c','pilihan_d','pilihan_e','jawaban_benar','bobot_nilai','tingkat_kesulitan'
    ];
}
