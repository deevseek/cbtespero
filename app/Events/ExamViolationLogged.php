<?php

namespace App\Events;

use App\Models\ExamLog;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamViolationLogged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public ExamLog $log, public int $violationCount = 0) {}

    public function broadcastOn(): PrivateChannel { return new PrivateChannel('exam.'.$this->log->exam_id); }
    public function broadcastAs(): string { return 'ExamViolationLogged'; }
    public function broadcastWith(): array
    {
        return [
            'exam_id' => $this->log->exam_id,
            'student_id' => $this->log->student_id,
            'type' => $this->log->activity_type,
            'message' => $this->log->description,
            'violation_count' => $this->violationCount,
            'occurred_at' => $this->log->logged_at?->toIso8601String(),
        ];
    }
}
