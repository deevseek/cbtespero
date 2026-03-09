<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = ['nama_ujian','mata_pelajaran','kelas','tanggal_ujian','jam_mulai','jam_selesai','durasi','jumlah_soal','status','token','acak_soal','acak_jawaban'];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ExamToken::class);
    }
}
