<?php

namespace App\Services\QuestionImport;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WordQuestionImportService
{
    private const WORD_UNRECOGNIZED_MESSAGE = 'File Word berhasil dibaca, tetapi format soal belum dikenali. Pastikan soal memiliki 5 opsi dan kunci jawaban A-E, atau gunakan format tabel 3 kolom: Nomor | Soal & Opsi | Kunci.';

    public function __construct(private readonly QuestionTextParser $parser = new QuestionTextParser()) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseFile(string $path): array
    {
        try {
            $document = $this->extractDocument($path);
            $tableResult = $this->parseTables($document['tables']);
            $questions = array_merge(
                $this->parser->parse($document['text']),
                $tableResult['questions'],
                $this->parser->parse($tableResult['fallback_text']),
            );

            $this->logImportSummary($document, $tableResult, $questions);
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException('File DOCX tidak dapat dibaca. Pastikan file tidak rusak dan berformat .docx.', previous: $exception);
        }

        if ($questions === []) {
            throw new RuntimeException(self::WORD_UNRECOGNIZED_MESSAGE);
        }

        return $questions;
    }

    /**
     * @return array{text: string, tables: array<int, array<int, array<int, string>>>}
     */
    private function extractDocument(string $path): array
    {
        if (! class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            throw new RuntimeException('Library pembaca Word belum tersedia. Jalankan composer require phpoffice/phpword.');
        }

        $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
        $parts = [];
        $tables = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($this->isTableElement($element)) {
                    $tables[] = $this->extractTableRows($element);

                    continue;
                }

                $text = $this->extractTextFromElement($element);
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return [
            'text' => trim(implode("\n", $parts)),
            'tables' => $tables,
        ];
    }

    private function extractTextFromElement(object $element): string
    {
        if ($this->isTableElement($element)) {
            $rows = [];
            foreach ($this->extractTableRows($element) as $row) {
                $rows[] = implode("\n", array_filter($row, fn (string $cell): bool => $cell !== ''));
            }

            return trim(implode("\n", array_filter($rows)));
        }

        if (method_exists($element, 'getRows')) {
            $rows = [];
            foreach ($element->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cells[] = $this->extractTextFromElement($cell);
                }

                $rows[] = implode("\n", array_filter($cells, fn (string $cell): bool => $cell !== ''));
            }

            return trim(implode("\n", array_filter($rows)));
        }

        $parts = [];

        if (method_exists($element, 'getText')) {
            $text = $element->getText();

            if (is_string($text) || is_numeric($text)) {
                $parts[] = (string) $text;
            } elseif (is_object($text)) {
                $extractedText = $this->extractTextFromElement($text);
                if ($extractedText !== '') {
                    $parts[] = $extractedText;
                }
            }
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                if (! is_object($childElement)) {
                    continue;
                }

                $childText = $this->extractTextFromElement($childElement);
                if ($childText !== '') {
                    $parts[] = $childText;
                }
            }
        }

        return trim(implode("\n", array_filter($parts, fn (string $part): bool => trim($part) !== '')));
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function extractTableRows(object $table): array
    {
        $rows = [];

        if (! method_exists($table, 'getRows')) {
            return $rows;
        }

        foreach ($table->getRows() as $row) {
            if (! method_exists($row, 'getCells')) {
                continue;
            }

            $cells = [];
            foreach ($row->getCells() as $cell) {
                $cells[] = $this->extractTextFromElement($cell);
            }

            $rows[] = $cells;
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, array<int, string>>> $tables
     * @return array{questions: array<int, array<string, mixed>>, fallback_text: string, parsed_rows: int, failed_rows: int}
     */
    private function parseTables(array $tables): array
    {
        $questions = [];
        $fallbackRows = [];
        $parsedRows = 0;
        $failedRows = 0;

        foreach ($tables as $table) {
            foreach ($table as $row) {
                $parsed = $this->parseThreeColumnRow($row);

                if ($parsed !== null) {
                    $questions[] = $parsed;
                    $parsedRows++;

                    continue;
                }

                $rowText = trim(implode("\n", array_filter($row, fn (string $cell): bool => trim($cell) !== '')));
                if ($rowText !== '') {
                    $fallbackRows[] = $rowText;
                    $failedRows++;
                }
            }
        }

        return [
            'questions' => $questions,
            'fallback_text' => trim(implode("\n\n", $fallbackRows)),
            'parsed_rows' => $parsedRows,
            'failed_rows' => $failedRows,
        ];
    }

    /**
     * @param array<int, string> $row
     * @return array<string, mixed>|null
     */
    private function parseThreeColumnRow(array $row): ?array
    {
        if (count($row) < 3) {
            return null;
        }

        $answerCell = trim((string) end($row));
        $questionCell = trim((string) ($row[1] ?? ''));

        if ($questionCell === '' || ! preg_match('/^(?:Jawaban|Kunci|Answer)?\s*[:\-]?\s*([A-Ea-e])\b/u', $answerCell, $matches)) {
            return null;
        }

        return $this->parser->parseQuestionBlock($questionCell, strtoupper($matches[1]));
    }

    /**
     * @param array{text: string, tables: array<int, array<int, array<int, string>>>} $document
     * @param array{questions: array<int, array<string, mixed>>, fallback_text: string, parsed_rows: int, failed_rows: int} $tableResult
     * @param array<int, array<string, mixed>> $questions
     */
    private function logImportSummary(array $document, array $tableResult, array $questions): void
    {
        $rowCount = array_sum(array_map('count', $document['tables']));
        $firstRow = null;

        foreach ($document['tables'] as $table) {
            if ($table !== []) {
                $firstRow = array_map(fn (string $cell): string => mb_substr($cell, 0, 120), $table[0]);
                break;
            }
        }

        Log::debug('[DOCX IMPORT] tables='.$this->countTables($document['tables'])
            .' rows='.$rowCount
            .' parsed='.count($questions)
            .' failed='.$tableResult['failed_rows'], [
                'table_rows_parsed' => $tableResult['parsed_rows'],
                'first_row' => $firstRow,
            ]);
    }

    /**
     * @param array<int, array<int, array<int, string>>> $tables
     */
    private function countTables(array $tables): int
    {
        return count($tables);
    }

    private function isTableElement(object $element): bool
    {
        return $element instanceof \PhpOffice\PhpWord\Element\Table;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $options
     * @return array{created: int, review: int, failed: int, errors: array<int, string>}
     */
    public function importToDatabase(array $questions, array $options): array
    {
        $options['source_type'] = 'word';

        return app(QuestionImportPersister::class)->import($questions, $options);
    }
}
