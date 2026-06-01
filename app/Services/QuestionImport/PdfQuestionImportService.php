<?php

namespace App\Services\QuestionImport;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PdfQuestionImportService
{
    private const PDF_UNREADABLE_MESSAGE = 'File PDF tidak dapat dibaca. Pastikan file bukan hasil scan penuh atau gunakan Word/DOCX untuk hasil lebih akurat.';

    private const PDF_UNRECOGNIZED_MESSAGE = 'Format soal PDF belum dikenali. Pastikan soal memiliki nomor, opsi A-E, atau gunakan template import yang disediakan.';

    public function __construct(
        private readonly QuestionTextParser $parser = new QuestionTextParser(),
        private readonly PdfHighlightAnswerDetector $highlightDetector = new PdfHighlightAnswerDetector(),
    ) {}

    /** @var array<string, mixed> */
    private array $lastHighlightSummary = [];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseFile(string $path): array
    {
        try {
            $document = $this->extractDocument($path);
            $cleanText = $this->cleanNonQuestionText($document['text']);
            $questions = $this->parser->parse($cleanText);
            $highlightAnswers = $this->highlightDetector->detectAnswers($path, $questions, $document['page_texts']);
            $this->lastHighlightSummary = $this->highlightDetector->getLastSummary();
            $questions = $this->applyHighlightAnswers($questions, $highlightAnswers);
            $questions = $this->normalizeReviewState($questions);

            $this->logImportSummary($document['pages'], $cleanText, $questions, $this->lastHighlightSummary);
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException(self::PDF_UNREADABLE_MESSAGE, previous: $exception);
        }

        if ($questions === []) {
            throw new RuntimeException(self::PDF_UNRECOGNIZED_MESSAGE);
        }

        return $questions;
    }

    /**
     * @return array{pages: int, text: string, page_texts: array<int, string>}
     */
    private function extractDocument(string $path): array
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new RuntimeException('Library pembaca PDF belum tersedia. Jalankan composer require smalot/pdfparser.');
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($path);
        $pageTexts = [];

        foreach ($pdf->getPages() as $page) {
            $text = trim((string) $page->getText());

            if ($text !== '') {
                $pageTexts[] = $text;
            }
        }

        if ($pageTexts === []) {
            $pageTexts[] = trim((string) $pdf->getText());
        }

        return [
            'pages' => count($pdf->getPages()),
            'text' => trim(implode("\n\n", $pageTexts)),
            'page_texts' => $pageTexts,
        ];
    }

    private function cleanNonQuestionText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\x{00a0}/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        $patterns = [
            '/^\s*PEMERINTAH\s+PROVINSI\s+JAWA\s+TENGAH\b.*$/imu',
            '/^\s*DINAS\s+PENDIDIKAN\s+DAN\s+KEBUDAYAAN\b.*$/imu',
            '/^\s*SEKOLAH\s+MENENGAH\s+ATAS\b.*$/imu',
            '/^\s*SMA\b.*$/imu',
            '/^\s*Alamat\b.*$/imu',
            '/^\s*(?:Website|E-?mail|Email)\b.*$/imu',
            '/^\s*MATA\s+PELAJARAN\b.*$/imu',
            '/^\s*KELAS\s*\/\s*PROGRAM\b.*$/imu',
            '/^\s*HARI\s*\/\s*TANGGAL\b.*$/imu',
            '/^\s*WAKTU\b.*$/imu',
            '/^\s*ASESMEN\s+SUMATIF\b.*$/imu',
            '/^\s*TAHUN\s+AJARAN\b.*$/imu',
            '/^\s*Direction\s*:\s*Choose\s+the\s+best\s+option\b.*$/imu',
            '/^\s*Halaman\s+\d+\s*(?:dari\s+\d+)?\s*$/imu',
            '/^\s*Page\s+\d+\s*(?:of\s+\d+)?\s*$/imu',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text) ?? $text;
        }

        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $highlightSummary
     */
    private function logImportSummary(int $pages, string $text, array $questions, array $highlightSummary = []): void
    {
        $questionNumbers = preg_match_all('/^\s*\d+\s*[\.)]\s+/mu', $text);
        $optionLabels = preg_match_all('/^\s*[A-E]\s*(?:[\.)]|\s+)\s*\S/mu', $text);
        $answersFound = count(array_filter($questions, fn (array $question): bool => filled($question['correct_answer'] ?? null)));
        $highlightAnswersFound = (int) ($highlightSummary['answers_found'] ?? 0);
        $savedAsDraft = count(array_filter($questions, fn (array $question): bool => (bool) ($question['needs_review'] ?? false)));
        $failed = max(0, $questionNumbers - count($questions));

        Log::debug('[PDF IMPORT] pages='.$pages.' text_length='.mb_strlen($text).' parsed='.count($questions).' failed='.$failed, [
            'first_1000_chars' => mb_substr($text, 0, 1000),
            'question_numbers' => $questionNumbers,
            'option_labels' => $optionLabels,
            'answers_found' => $answersFound,
            'highlight_answers_found' => $highlightAnswersFound,
            'saved_as_draft' => $savedAsDraft,
            'highlight_summary' => $highlightSummary,
        ]);

        Log::debug('[PDF IMPORT] pages='.$pages);
        Log::debug('[PDF IMPORT] question_numbers='.$questionNumbers);
        Log::debug('[PDF IMPORT] parsed_questions='.count($questions));
        Log::debug('[PDF IMPORT] highlight_boxes='.(int) ($highlightSummary['highlight_boxes'] ?? 0));
        Log::debug('[PDF IMPORT] highlight_answers_found='.$highlightAnswersFound);
        Log::debug('[PDF IMPORT] answers='.$this->formatAnswersForLog((array) ($highlightSummary['answers'] ?? [])));
        Log::debug('[PDF IMPORT] answers_found='.$answersFound);
        Log::debug('[PDF IMPORT] saved_as_draft='.$savedAsDraft);
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastHighlightSummary(): array
    {
        return $this->lastHighlightSummary;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<int, string> $highlightAnswers
     * @return array<int, array<string, mixed>>
     */
    private function applyHighlightAnswers(array $questions, array $highlightAnswers): array
    {
        foreach ($questions as $index => $question) {
            if (filled($question['correct_answer'] ?? null)) {
                continue;
            }

            $questionNumber = $index + 1;
            $answer = strtoupper((string) ($highlightAnswers[$questionNumber] ?? ''));

            if (! in_array($answer, ['A', 'B', 'C', 'D', 'E'], true)) {
                continue;
            }

            if (blank($question['options'][$answer] ?? null)) {
                continue;
            }

            $questions[$index]['correct_answer'] = $answer;
            $questions[$index]['needs_review'] = false;
            $questions[$index]['status'] = 'aktif';
        }

        return $questions;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @return array<int, array<string, mixed>>
     */
    private function normalizeReviewState(array $questions): array
    {
        foreach ($questions as $index => $question) {
            if (filled($question['correct_answer'] ?? null)) {
                $questions[$index]['needs_review'] = false;
                $questions[$index]['status'] = $question['status'] ?? 'aktif';
            } else {
                $questions[$index]['correct_answer'] = null;
                $questions[$index]['needs_review'] = true;
                $questions[$index]['status'] = 'draft';
            }
        }

        return $questions;
    }

    /** @param array<int, string> $answers */
    private function formatAnswersForLog(array $answers): string
    {
        if ($answers === []) {
            return '{}';
        }

        ksort($answers);
        $pairs = [];

        foreach ($answers as $number => $answer) {
            $pairs[] = $number.':'.$answer;
        }

        return '{'.implode(',', $pairs).'}';
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $options
     * @return array{created: int, review: int, failed: int, errors: array<int, string>}
     */
    public function importToDatabase(array $questions, array $options): array
    {
        $options['source_type'] = 'pdf';
        $options['allow_missing_correct_answer'] = true;

        $result = app(QuestionImportPersister::class)->import($questions, $options);
        $result['highlight'] = $this->lastHighlightSummary;

        return $result;
    }
}
