<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = ['exam_id','student_id','nilai','status','started_at','submitted_at','tab_switch_count','fullscreen_exit_count'];

    public function exam(): BelongsTo { return $this->belongsTo(Exam::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function answers(): HasMany { return $this->hasMany(ExamAnswer::class); }
}
