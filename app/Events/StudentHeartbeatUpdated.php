<?php

namespace App\Events;

use App\Models\ExamResult;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentHeartbeatUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public ExamResult $result, public int $violationCount = 0) {}

    public function broadcastOn(): PrivateChannel { return new PrivateChannel('exam.'.$this->result->exam_id); }
    public function broadcastAs(): string { return 'StudentHeartbeatUpdated'; }
    public function broadcastWith(): array
    {
        return [
            'exam_id' => $this->result->exam_id,
            'student_id' => $this->result->student_id,
            'remaining_seconds' => $this->result->remaining_time_seconds,
            'status' => $this->result->status,
            'last_seen_at' => $this->result->last_heartbeat_at?->toIso8601String(),
            'current_question' => $this->result->current_question_id,
            'violation_count' => $this->violationCount,
        ];
    }
}
