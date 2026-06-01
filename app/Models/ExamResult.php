<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamResult extends Model
{
    use HasFactory;

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'server_started_at' => 'datetime',
        'server_ends_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'locked_at' => 'datetime',
        'auto_submitted_at' => 'datetime',
        'fullscreen_status' => 'boolean',
        'duration_seconds' => 'integer',
    ];

    protected $fillable = ['exam_id','student_id','nilai','status','started_at','submitted_at','tab_switch_count','fullscreen_exit_count','session_uuid','device_id','device_name','platform','app_version','server_started_at','server_ends_at','last_heartbeat_at','locked_at','lock_reason','auto_submitted_at','app_exit_count','heartbeat_missed_count','relogin_count','ip_address','user_agent','current_question_id','remaining_time_seconds','fullscreen_status','visibility_state','total_questions','answered_questions','correct_count','wrong_count','unanswered_count','duration_seconds','submit_reason'];

    public function exam(): BelongsTo { return $this->belongsTo(Exam::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function answers(): HasMany { return $this->hasMany(ExamAnswer::class); }
    public function logs(): HasMany { return $this->hasMany(ExamLog::class); }
    public function questionOrders(): HasMany { return $this->hasMany(ExamQuestionOrder::class); }
}
