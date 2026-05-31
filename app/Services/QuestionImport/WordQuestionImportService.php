<?php

namespace App\Services\QuestionImport;

use RuntimeException;
use Throwable;

class WordQuestionImportService
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

            throw new RuntimeException('File DOCX tidak dapat dibaca atau format soal tidak dikenali.', previous: $exception);
        }

        if ($questions === []) {
            throw new RuntimeException('File DOCX tidak dapat dibaca atau format soal tidak dikenali.');
        }

        return $questions;
    }

    private function extractText(string $path): string
    {
        if (! class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            throw new RuntimeException('Library pembaca Word belum tersedia. Jalankan composer require phpoffice/phpword.');
        }

        $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
        $parts = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $this->appendElementText($element, $parts);
            }
        }

        return trim(implode("\n", array_filter($parts)));
    }

    private function appendElementText(object $element, array &$parts): void
    {
        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text)) {
                $parts[] = $text;
            }
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $this->appendElementText($childElement, $parts);
            }
        }

        if (method_exists($element, 'getRows')) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $this->appendElementText($cellElement, $parts);
                    }
                }
            }
        }
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
