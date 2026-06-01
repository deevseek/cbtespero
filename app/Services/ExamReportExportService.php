<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamLog;
use App\Models\ExamResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZipArchive;

class ExamReportExportService
{
    /** @param array<string, mixed> $filters */
    public function download(array $filters = [], ?Exam $exam = null)
    {
        $results = $this->query($filters, $exam)->get();
        $examForName = $exam ?: $results->first()?->exam;
        $fileName = 'laporan-ujian-'.Str::slug($examForName?->nama_ujian ?: 'semua-ujian').'-'.now()->format('Y-m-d').'.xlsx';
        $path = storage_path('app/private/exports/'.$fileName);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $this->writeXlsx($path, [
            'Rekap Ujian' => $this->rekapRows($results),
            'Daftar Nilai' => $this->nilaiRows($results),
            'Log Pelanggaran' => $this->logRows($results, $exam),
            'Analisis Jawaban' => $this->analysisRows($results),
        ]);

        return response()->download($path, $fileName)->deleteFileAfterSend(true);
    }

    /** @param array<string, mixed> $filters */
    public function query(array $filters = [], ?Exam $exam = null): Builder
    {
        return ExamResult::query()
            ->with(['exam', 'student'])
            ->withCount(['logs as violation_logs_count' => fn (Builder $query) => $query->whereIn('activity_type', $this->violationTypes())])
            ->when($exam, fn (Builder $query) => $query->where('exam_id', $exam->id))
            ->when($filters['exam_id'] ?? null, fn (Builder $query, $value) => $query->where('exam_id', $value))
            ->when($filters['student_id'] ?? null, fn (Builder $query, $value) => $query->where('student_id', $value))
            ->when($filters['status'] ?? null, fn (Builder $query, $value) => $query->where('status', $value))
            ->when($filters['kelas'] ?? null, fn (Builder $query, $value) => $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('kelas', $value)))
            ->when($filters['tanggal'] ?? null, fn (Builder $query, $value) => $query->whereHas('exam', fn (Builder $examQuery) => $examQuery->whereDate('tanggal_ujian', $value)))
            ->orderBy('exam_id')
            ->orderBy('student_id');
    }

    private function nilaiRows(Collection $results): array
    {
        $rows = [[
            'No', 'Nama Siswa', 'NIS', 'Username', 'Kelas', 'Nama Ujian', 'Mata Pelajaran', 'Tanggal Ujian',
            'Waktu Mulai', 'Waktu Submit', 'Durasi', 'Jumlah Soal', 'Terjawab', 'Benar', 'Salah', 'Tidak Dijawab',
            'Nilai Akhir', 'Status Kelulusan', 'Jumlah Pelanggaran', 'Status Ujian', 'Keterangan',
        ]];

        foreach ($results->values() as $index => $result) {
            $rows[] = [
                $index + 1,
                $result->student?->nama ?: '-',
                $result->student?->nis ?: '-',
                $result->student?->username ?: '-',
                $result->student?->kelas ?: '-',
                $result->exam?->nama_ujian ?: '-',
                $result->exam?->mata_pelajaran ?: '-',
                (string) ($result->exam?->tanggal_ujian ?: '-'),
                $result->started_at?->format('Y-m-d H:i:s') ?: '-',
                $result->submitted_at?->format('Y-m-d H:i:s') ?: '-',
                $this->formatDuration($result->duration_seconds ?: ($result->started_at && $result->submitted_at ? $result->started_at->diffInSeconds($result->submitted_at) : null)),
                $this->totalQuestions($result),
                $this->answeredQuestions($result),
                $this->correctCount($result),
                $this->wrongCount($result),
                $this->unansweredCount($result),
                (float) $result->nilai,
                '-',
                (int) ($result->violation_logs_count ?? 0),
                $this->statusLabel($result->status),
                $result->submit_reason ?: $result->lock_reason ?: '-',
            ];
        }

        return $rows;
    }

    private function rekapRows(Collection $results): array
    {
        $rows = [[
            'Nama Ujian', 'Mata Pelajaran', 'Kelas', 'Tanggal', 'Jam Mulai', 'Jam Selesai', 'Durasi',
            'Jumlah Peserta', 'Sudah Submit', 'Belum Submit', 'Nilai Tertinggi', 'Nilai Terendah', 'Rata-rata Nilai', 'Jumlah Pelanggaran',
        ]];

        foreach ($results->groupBy('exam_id') as $group) {
            $exam = $group->first()?->exam;
            $submitted = $group->whereIn('status', ['selesai', 'auto_submit']);
            $scores = $submitted->pluck('nilai')->filter(fn ($score) => $score !== null);
            $rows[] = [
                $exam?->nama_ujian ?: '-',
                $exam?->mata_pelajaran ?: '-',
                $exam?->kelas ?: '-',
                (string) ($exam?->tanggal_ujian ?: '-'),
                (string) ($exam?->jam_mulai ?: '-'),
                (string) ($exam?->jam_selesai ?: '-'),
                ((int) ($exam?->durasi ?? 0)).' menit',
                $group->count(),
                $submitted->count(),
                $group->count() - $submitted->count(),
                $scores->isEmpty() ? '-' : round($scores->max(), 2),
                $scores->isEmpty() ? '-' : round($scores->min(), 2),
                $scores->isEmpty() ? '-' : round($scores->avg(), 2),
                $group->sum(fn ($result) => (int) ($result->violation_logs_count ?? 0)),
            ];
        }

        return $rows;
    }

    private function logRows(Collection $results, ?Exam $exam = null): array
    {
        $rows = [['No', 'Nama Siswa', 'NIS', 'Kelas', 'Nama Ujian', 'Jenis Pelanggaran', 'Pesan', 'IP Address', 'User Agent', 'Waktu', 'Jumlah Pelanggaran Siswa']];
        $resultIds = $results->pluck('id');
        $logs = ExamLog::query()
            ->with(['student', 'exam', 'result'])
            ->whereIn('activity_type', $this->violationTypes())
            ->when($exam, fn (Builder $query) => $query->where('exam_id', $exam->id))
            ->when($resultIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('exam_result_id', $resultIds))
            ->latest('logged_at')
            ->get();

        foreach ($logs->values() as $index => $log) {
            $rows[] = [
                $index + 1,
                $log->student?->nama ?: '-',
                $log->student?->nis ?: '-',
                $log->student?->kelas ?: '-',
                $log->exam?->nama_ujian ?: '-',
                $log->activity_type,
                $log->description ?: '-',
                $log->ip_address ?: '-',
                $log->user_agent ?: '-',
                $log->logged_at?->format('Y-m-d H:i:s') ?: '-',
                $log->result ? $log->result->logs()->whereIn('activity_type', $this->violationTypes())->count() : 0,
            ];
        }

        return $rows;
    }

    private function analysisRows(Collection $results): array
    {
        $rows = [['Nomor Soal', 'Jumlah Menjawab A', 'Jumlah Menjawab B', 'Jumlah Menjawab C', 'Jumlah Menjawab D', 'Jumlah Menjawab E', 'Kunci Jawaban', 'Jumlah Benar', 'Persentase Benar']];
        $answers = $results->loadMissing('answers.question')->pluck('answers')->flatten();

        foreach ($answers->groupBy('question_id')->values() as $index => $group) {
            $question = $group->first()?->question;
            $answered = max(1, $group->whereNotNull('jawaban_siswa')->count());
            $correct = $group->where('is_correct', true)->count();
            $rows[] = [
                $index + 1,
                $group->where('jawaban_siswa', 'a')->count(),
                $group->where('jawaban_siswa', 'b')->count(),
                $group->where('jawaban_siswa', 'c')->count(),
                $group->where('jawaban_siswa', 'd')->count(),
                $group->where('jawaban_siswa', 'e')->count(),
                strtoupper((string) ($question?->jawaban_benar ?: '-')),
                $correct,
                round(($correct / $answered) * 100, 2).'%',
            ];
        }

        return $rows;
    }

    /** @param array<string, array<int, array<int, mixed>>> $sheets */
    private function writeXlsx(string $path, array $sheets): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml(array_keys($sheets)));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        $index = 1;
        foreach ($sheets as $rows) {
            $zip->addFromString("xl/worksheets/sheet{$index}.xml", $this->sheetXml($rows));
            $index++;
        }
        $zip->close();
    }

    private function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $r => $row) {
            $rowNumber = $r + 1;
            $xml .= '<row r="'.$rowNumber.'">';
            foreach (array_values($row) as $c => $value) {
                $cell = $this->cellName($c + 1).$rowNumber;
                if (is_numeric($value) && $value !== '') {
                    $xml .= '<c r="'.$cell.'"><v>'.$value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        return $xml.'</sheetData></worksheet>';
    }

    private function cellName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }
        return $name;
    }

    private function workbookXml(array $names): string
    {
        $sheets = '';
        foreach ($names as $index => $name) {
            $id = $index + 1;
            $sheets .= '<sheet name="'.htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8').'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$sheets.'</sheets></workbook>';
    }

    private function workbookRels(int $count): string
    {
        $rels = '';
        for ($i = 1; $i <= $count; $i++) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }
        $rels .= '<Relationship Id="rId'.($count + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels.'</Relationships>';
    }

    private function contentTypes(int $count): string
    {
        $overrides = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $count; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'.$overrides.'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="1"><xf xfId="0"/></cellXfs></styleSheet>';
    }

    private function totalQuestions(ExamResult $result): int { return (int) ($result->total_questions ?: $result->answers()->count()); }
    private function answeredQuestions(ExamResult $result): int { return (int) ($result->answered_questions ?: $result->answers()->whereNotNull('jawaban_siswa')->count()); }
    private function correctCount(ExamResult $result): int { return (int) ($result->correct_count ?: $result->answers()->where('is_correct', true)->count()); }
    private function wrongCount(ExamResult $result): int { return (int) ($result->wrong_count ?: max(0, $this->answeredQuestions($result) - $this->correctCount($result))); }
    private function unansweredCount(ExamResult $result): int { return (int) ($result->unanswered_count ?: max(0, $this->totalQuestions($result) - $this->answeredQuestions($result))); }

    private function formatDuration(?int $seconds): string
    {
        return $seconds === null ? '-' : gmdate('H:i:s', max(0, $seconds));
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'sedang_mengerjakan' => 'Mengerjakan',
            'selesai' => 'Selesai',
            'auto_submit' => 'Auto Submit',
            'terkunci' => 'Terindikasi Pelanggaran',
            'belum_mulai' => 'Belum Mulai',
            default => $status ?: '-',
        };
    }

    private function violationTypes(): array
    {
        return ['exit_fullscreen', 'tab_switch', 'window_blur', 'forbidden_shortcut', 'right_click', 'clipboard', 'devtools', 'page_reload', 'idle', 'connection_lost', 'heartbeat_missed', 'fullscreen_exit'];
    }
}
