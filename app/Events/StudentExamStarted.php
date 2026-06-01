<?php

namespace App\Events;

use App\Models\ExamResult;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentExamStarted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public ExamResult $result) {}

    public function broadcastOn(): PrivateChannel { return new PrivateChannel('exam.'.$this->result->exam_id); }
    public function broadcastAs(): string { return 'StudentExamStarted'; }
    public function broadcastWith(): array
    {
        $this->result->loadMissing(['student', 'exam']);
        return [
            'exam_id' => $this->result->exam_id,
            'student_id' => $this->result->student_id,
            'student_name' => $this->result->student?->nama,
            'status' => $this->result->status,
            'started_at' => $this->result->started_at?->toIso8601String(),
            'remaining_seconds' => $this->result->remaining_time_seconds ?? ($this->result->server_ends_at ? max(0, now()->diffInSeconds($this->result->server_ends_at, false)) : null),
        ];
    }
}
