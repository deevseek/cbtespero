<?php

namespace App\Services\QuestionImport;

use RuntimeException;
use Throwable;

class PdfQuestionImportService
{
    public function __construct(private readonly QuestionTextParser $parser = new QuestionTextParser()) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseFile(string $path): array
    {
        try {
            $text = $this->extractText($path);
            $questions = $this->parser->parse($text);
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException('File PDF tidak dapat dibaca atau format soal tidak dikenali.', previous: $exception);
        }

        if ($questions === []) {
            throw new RuntimeException('File PDF tidak dapat dibaca atau format soal tidak dikenali.');
        }

        return $questions;
    }

    private function extractText(string $path): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new RuntimeException('Library pembaca PDF belum tersedia. Jalankan composer require smalot/pdfparser.');
        }

        $parser = new \Smalot\PdfParser\Parser();

        return trim($parser->parseFile($path)->getText());
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $options
     * @return array{created: int, review: int, failed: int, errors: array<int, string>}
     */
    public function importToDatabase(array $questions, array $options): array
    {
        return app(QuestionImportPersister::class)->import($questions, $options);
    }
}
