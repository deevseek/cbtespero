<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_name',
        'original_filename',
        'source_url',
        'subject',
        'class_level',
        'difficulty',
        'default_weight',
        'total_questions',
        'imported_questions',
        'failed_questions',
        'needs_review_count',
        'status',
        'imported_by',
        'imported_at',
        'meta',
    ];

    protected $casts = [
        'default_weight' => 'integer',
        'total_questions' => 'integer',
        'imported_questions' => 'integer',
        'failed_questions' => 'integer',
        'needs_review_count' => 'integer',
        'imported_at' => 'datetime',
        'meta' => 'array',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->source_name ?: $this->original_filename ?: 'Batch Import #'.$this->id;
    }
}
