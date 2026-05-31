<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswerOrder extends Model
{
    use HasFactory;
    protected $fillable = ['exam_result_id', 'question_id', 'option_order'];
    protected function casts(): array { return ['option_order' => 'array']; }
    public function result(): BelongsTo { return $this->belongsTo(ExamResult::class, 'exam_result_id'); }
    public function question(): BelongsTo { return $this->belongsTo(Question::class); }
}
