<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_import_id',
        'mata_pelajaran',
        'kelas',
        'soal',
        'image_path',
        'pilihan_a',
        'pilihan_b',
        'pilihan_c',
        'pilihan_d',
        'pilihan_e',
        'jawaban_benar',
        'bobot_nilai',
        'tingkat_kesulitan',
        'status',
        'needs_review',
    ];

    protected $casts = [
        'needs_review' => 'boolean',
        'bobot_nilai' => 'integer',
    ];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(QuestionImport::class, 'question_import_id');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot(['order_number', 'score'])
            ->withTimestamps();
    }
}
