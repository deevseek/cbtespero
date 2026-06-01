<?php

namespace Tests\Unit;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Services\ExamStatusService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExamStatusServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_normalizes_dot_and_colon_time_formats(): void
    {
        $service = new ExamStatusService();

        $this->assertSame('10:48:00', $service->normalizeTime('10:48'));
        $this->assertSame('10:48:00', $service->normalizeTime('10:48:00'));
        $this->assertSame('10:48:00', $service->normalizeTime('10.48'));
        $this->assertSame('10:48:00', $service->normalizeTime('10.48.00'));
        $this->assertSame('06:00:00', $service->normalizeTime('06.00.00'));
    }

    #[Test]
    public function active_admin_status_overrides_student_upcoming_schedule(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 10, 0, 0, 'Asia/Jakarta'));

        $status = (new ExamStatusService())->getStudentStatus($this->exam([
            'status' => 'berlangsung',
            'tanggal_ujian' => '2026-06-01',
            'jam_mulai' => '10.48',
            'jam_selesai' => '11.45',
            'token' => 'ABCDE',
            'questions_count' => 40,
        ]), new Student());

        $this->assertSame('available', $status['key']);
        $this->assertSame('Bisa Dikerjakan', $status['label']);
        $this->assertSame('enter_token', $status['action']);
        $this->assertFalse($status['disabled']);
    }

    #[Test]
    public function scheduled_exam_uses_jakarta_time_window(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 10, 49, 0, 'Asia/Jakarta'));

        $status = (new ExamStatusService())->getStudentStatus($this->exam([
            'status' => 'terjadwal',
            'tanggal_ujian' => '2026-06-01',
            'jam_mulai' => '10:48',
            'jam_selesai' => '11:45',
            'questions_count' => 40,
        ]), new Student());

        $this->assertSame('available', $status['key']);
        $this->assertSame('start_exam', $status['action']);
    }

    #[Test]
    public function submitted_and_in_progress_results_take_priority(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 9, 0, 0, 'Asia/Jakarta'));
        $service = new ExamStatusService();
        $exam = $this->exam([
            'status' => 'terjadwal',
            'tanggal_ujian' => '2026-06-02',
            'jam_mulai' => '10:48',
            'jam_selesai' => '11:45',
            'questions_count' => 40,
        ]);

        $submitted = new ExamResult(['status' => 'selesai', 'submitted_at' => Carbon::now('Asia/Jakarta')]);
        $inProgress = new ExamResult(['status' => 'sedang_mengerjakan', 'started_at' => Carbon::now('Asia/Jakarta')]);

        $this->assertSame('view_result', $service->getStudentStatus($exam, new Student(), $submitted)['action']);
        $this->assertSame('continue_exam', $service->getStudentStatus($exam, new Student(), $inProgress)['action']);
    }

    #[Test]
    public function it_marks_future_past_and_empty_question_exams(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 10, 0, 0, 'Asia/Jakarta'));
        $service = new ExamStatusService();

        $this->assertSame('upcoming', $service->getStudentStatus($this->exam([
            'status' => 'terjadwal',
            'tanggal_ujian' => '2026-06-02',
            'jam_mulai' => '10:48',
            'jam_selesai' => '11:45',
            'questions_count' => 40,
        ]), new Student())['key']);

        $this->assertSame('missed', $service->getStudentStatus($this->exam([
            'status' => 'terjadwal',
            'tanggal_ujian' => '2026-06-01',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'questions_count' => 40,
        ]), new Student())['key']);

        $this->assertSame('not_ready', $service->getStudentStatus($this->exam([
            'status' => 'berlangsung',
            'tanggal_ujian' => '2026-06-01',
            'jam_mulai' => '08:00',
            'jam_selesai' => '11:00',
            'questions_count' => 0,
        ]), new Student())['key']);
    }

    private function exam(array $attributes): Exam
    {
        $exam = new Exam($attributes);

        foreach ($attributes as $key => $value) {
            $exam->setAttribute($key, $value);
        }

        return $exam;
    }
}
