<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSecuritySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id','require_fullscreen','block_screenshot','device_binding','auto_submit_on_cheat',
        'max_app_exit','max_fullscreen_exit','max_heartbeat_missed','connection_tolerance_seconds',
        'show_result_after_exam','randomize_questions','randomize_answers','allow_reentry','max_relogin','orientation',
    ];

    protected function casts(): array
    {
        return [
            'require_fullscreen' => 'boolean', 'block_screenshot' => 'boolean', 'device_binding' => 'boolean',
            'auto_submit_on_cheat' => 'boolean', 'show_result_after_exam' => 'boolean',
            'randomize_questions' => 'boolean', 'randomize_answers' => 'boolean', 'allow_reentry' => 'boolean',
        ];
    }

    public function exam(): BelongsTo { return $this->belongsTo(Exam::class); }
}
