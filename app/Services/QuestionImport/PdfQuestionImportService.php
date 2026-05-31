<?php

namespace App\Services\QuestionImport;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PdfQuestionImportService
{
    private const PDF_UNREADABLE_MESSAGE = 'File PDF tidak dapat dibaca. Pastikan file bukan hasil scan penuh atau gunakan Word/DOCX untuk hasil lebih akurat.';

    private const PDF_UNRECOGNIZED_MESSAGE = 'Format soal PDF belum dikenali. Pastikan soal memiliki nomor, opsi A-E, atau gunakan template import yang disediakan.';

    public function __construct(private readonly QuestionTextParser $parser = new QuestionTextParser()) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseFile(string $path): array
    {
        try {
            $document = $this->extractDocument($path);
            $cleanText = $this->cleanNonQuestionText($document['text']);
            $questions = $this->parser->parse($cleanText);

            $this->logImportSummary($document['pages'], $cleanText, $questions);
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
     * @return array{pages: int, text: string}
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
     */
    private function logImportSummary(int $pages, string $text, array $questions): void
    {
        $questionNumbers = preg_match_all('/^\s*\d+\s*[\.)]\s+/mu', $text);
        $optionLabels = preg_match_all('/^\s*[A-E]\s*(?:[\.)]|\s+)\s*\S/mu', $text);
        $answersFound = count(array_filter($questions, fn (array $question): bool => filled($question['correct_answer'] ?? null)));
        $savedAsDraft = count(array_filter($questions, fn (array $question): bool => (bool) ($question['needs_review'] ?? false)));
        $failed = max(0, $questionNumbers - count($questions));

        Log::debug('[PDF IMPORT] pages='.$pages.' text_length='.mb_strlen($text).' parsed='.count($questions).' failed='.$failed, [
            'first_1000_chars' => mb_substr($text, 0, 1000),
            'question_numbers' => $questionNumbers,
            'option_labels' => $optionLabels,
            'answers_found' => $answersFound,
            'saved_as_draft' => $savedAsDraft,
            'highlight_note' => 'Jawaban yang hanya ditandai highlight pada PDF tidak dapat dibaca oleh parser teks. Soal disimpan sebagai Draft untuk direview.',
        ]);

        Log::debug('[PDF IMPORT] pages='.$pages);
        Log::debug('[PDF IMPORT] question_numbers='.$questionNumbers);
        Log::debug('[PDF IMPORT] parsed_questions='.count($questions));
        Log::debug('[PDF IMPORT] answers_found='.$answersFound);
        Log::debug('[PDF IMPORT] saved_as_draft='.$savedAsDraft);
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $options
     * @return array{created: int, review: int, failed: int, errors: array<int, string>}
     */
    public function importToDatabase(array $questions, array $options): array
    {
        $options['allow_missing_correct_answer'] = true;

        return app(QuestionImportPersister::class)->import($questions, $options);
    }
}
