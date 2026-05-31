<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestionOrder extends Model
{
    use HasFactory;
    protected $fillable = ['exam_result_id', 'question_id', 'position'];
    public function result(): BelongsTo { return $this->belongsTo(ExamResult::class, 'exam_result_id'); }
    public function question(): BelongsTo { return $this->belongsTo(Question::class); }
}
