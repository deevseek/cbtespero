<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamLog extends Model
{
    use HasFactory;

    protected $fillable = ['exam_result_id','student_id','exam_id','activity_type','description','ip_address','logged_at'];
}
