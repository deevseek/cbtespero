<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttendance extends Model
{
    use HasFactory;

    protected $table = 'exam_attendance';

    protected $fillable = ['exam_id','student_id','tanggal_ujian','kelas','mata_pelajaran','pengawas','status_hadir','berita_acara'];
}
