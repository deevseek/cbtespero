<?php

namespace App\Events;

use App\Models\ExamResult;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentAnswerSaved implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public ExamResult $result, public ?int $currentQuestion = null) {}

    public function broadcastOn(): PrivateChannel { return new PrivateChannel('exam.'.$this->result->exam_id); }
    public function broadcastAs(): string { return 'StudentAnswerSaved'; }
    public function broadcastWith(): array
    {
        $total = max(1, (int) ($this->result->total_questions ?: $this->result->answers()->count()));
        $answered = (int) ($this->result->answered_questions ?: $this->result->answers()->whereNotNull('jawaban_siswa')->count());
        return [
            'exam_id' => $this->result->exam_id,
            'student_id' => $this->result->student_id,
            'answered_questions' => $answered,
            'total_questions' => $total,
            'progress_percent' => (int) round(($answered / $total) * 100),
            'current_question' => $this->currentQuestion,
            'last_seen_at' => now()->toIso8601String(),
        ];
    }
}
