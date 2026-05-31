<?php

namespace App\Services\QuestionImport;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class GoogleFormImportService
{
    public const ACCESS_ERROR_MESSAGE = 'Google Form tidak bisa diakses. Pastikan form bersifat publik atau integrasi Google API sudah dikonfigurasi.';

    public function extractFormIdFromUrl(string $url): string
    {
        $url = trim($url);
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $path = parse_url($url, PHP_URL_PATH) ?: '';

        if ($host !== 'docs.google.com') {
            throw new InvalidArgumentException('URL harus berasal dari docs.google.com/forms.');
        }

        if (! str_starts_with($path, '/forms/')) {
            throw new InvalidArgumentException('URL harus berasal dari docs.google.com/forms.');
        }

        if (preg_match('#/forms/d/([A-Za-z0-9_-]+)/#', $path, $matches)) {
            return $matches[1];
        }

        throw new InvalidArgumentException('Form ID Google Form tidak valid atau tidak dapat diekstrak dari URL.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchFormQuestions(string $formId): array
    {
        try {
            $response = Http::timeout(15)->get("https://docs.google.com/forms/d/{$formId}/viewform");

            if (! $response->successful()) {
                throw new RuntimeException(self::ACCESS_ERROR_MESSAGE);
            }

            $html = $response->body();
            if (! preg_match('/FB_PUBLIC_LOAD_DATA_\s*=\s*(.+?);\s*<\/script>/s', $html, $matches)) {
                throw new RuntimeException(self::ACCESS_ERROR_MESSAGE);
            }

            $payload = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
            $items = $payload[1][1] ?? [];

            if (! is_array($items) || $items === []) {
                throw new RuntimeException(self::ACCESS_ERROR_MESSAGE);
            }

            return $items;
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException && $exception->getMessage() === self::ACCESS_ERROR_MESSAGE) {
                throw $exception;
            }

            report($exception);
            throw new RuntimeException(self::ACCESS_ERROR_MESSAGE, previous: $exception);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rawItems
     * @return array<int, array<string, mixed>>
     */
    public function normalizeQuestions(array $rawItems): array
    {
        $questions = [];

        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = trim((string) ($item[1] ?? ''));
            if ($title === '') {
                continue;
            }

            $options = [];
            $optionItems = $item[4][0][1] ?? [];

            if (is_array($optionItems)) {
                $letterIndex = 0;
                foreach ($optionItems as $optionItem) {
                    $label = is_array($optionItem) ? (string) ($optionItem[0] ?? '') : '';
                    if (trim($label) === '') {
                        continue;
                    }

                    $options[chr(ord('A') + $letterIndex)] = trim($label);
                    $letterIndex++;

                    if ($letterIndex >= 5) {
                        break;
                    }
                }
            }

            $questions[] = [
                'question_text' => $title,
                'options' => $options,
                'correct_answer' => null,
                'type' => $options === [] ? 'essay' : 'multiple_choice',
                'needs_review' => true,
            ];
        }

        return $questions;
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
