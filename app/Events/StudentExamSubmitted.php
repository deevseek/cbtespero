<?php

namespace App\Events;

use App\Models\ExamResult;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentExamSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public ExamResult $result) {}

    public function broadcastOn(): PrivateChannel { return new PrivateChannel('exam.'.$this->result->exam_id); }
    public function broadcastAs(): string { return 'StudentExamSubmitted'; }
    public function broadcastWith(): array
    {
        return [
            'exam_id' => $this->result->exam_id,
            'student_id' => $this->result->student_id,
            'score' => (float) $this->result->nilai,
            'final_score' => (float) $this->result->nilai,
            'status' => $this->result->status,
            'submitted_at' => $this->result->submitted_at?->toIso8601String(),
            'correct_count' => (int) $this->result->correct_count,
            'wrong_count' => (int) $this->result->wrong_count,
            'unanswered_count' => (int) $this->result->unanswered_count,
        ];
    }
}
