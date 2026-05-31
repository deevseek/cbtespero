<?php

namespace App\Services\QuestionImport;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class GoogleFormImportService
{
    public const ACCESS_ERROR_MESSAGE = 'Google Form tidak bisa diakses. Pastikan form bersifat publik atau integrasi Google API sudah dikonfigurasi.';

    private const IDENTITY_TITLES = [
        'NAME',
        'NAMA',
        'NAMA SISWA',
        'STUDENT NAME',
        'KELAS',
        'CLASS',
        'NO ABSEN',
        'NOMOR ABSEN',
        'EMAIL',
        'ALAMAT EMAIL',
        'USERNAME',
    ];

    private const QUESTION_TYPES = [
        0 => 'short_answer',
        1 => 'paragraph',
        2 => 'multiple_choice',
        3 => 'dropdown',
        4 => 'checkbox',
    ];

    public function extractFormIdFromUrl(string $url): string
    {
        $url = trim($url);
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        $path = parse_url($url, PHP_URL_PATH) ?: '';

        if (! in_array($host, ['docs.google.com', 'www.docs.google.com'], true)) {
            throw new InvalidArgumentException('URL harus berasal dari docs.google.com/forms.');
        }

        if (! str_starts_with($path, '/forms/')) {
            throw new InvalidArgumentException('URL harus berasal dari docs.google.com/forms.');
        }

        if (preg_match('#/forms/d/(?:e/)?([A-Za-z0-9_-]+)(?:/|$)#', $path, $matches)) {
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
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CBT-Julia-Importer/1.0)',
                ])
                ->get("https://docs.google.com/forms/d/{$formId}/viewform");

            if (! $response->successful()) {
                throw new RuntimeException(self::ACCESS_ERROR_MESSAGE);
            }

            $html = $response->body();
            $payload = $this->extractPublicLoadData($html);
            $items = $payload[1][1] ?? [];

            if (! is_array($items) || $items === []) {
                throw new RuntimeException('Google Form berhasil dibaca, tetapi soal belum ditemukan. Pastikan soal berada di form yang sama dan bukan hanya data identitas.');
            }

            return $items;
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            report($exception);
            throw new RuntimeException('Google Form gagal diproses. Pastikan URL benar, form dapat diakses publik, lalu coba lagi.', previous: $exception);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rawItems
     * @return array{questions: array<int, array<string, mixed>>, ignored_identity: int, failed: int}
     */
    public function normalizeQuestionsWithSummary(array $rawItems): array
    {
        $questions = [];
        $ignoredIdentity = 0;
        $failed = 0;

        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                $failed++;
                continue;
            }

            $title = $this->cleanText((string) ($item[1] ?? ''));
            if ($title === '') {
                continue;
            }

            $options = $this->extractOptions($item);
            if ($this->isIdentityField($title, $options)) {
                $ignoredIdentity++;
                continue;
            }

            $type = $this->resolveQuestionType($item, $options);
            if (! $this->isQuestionItem($title, $options, $type)) {
                $failed++;
                continue;
            }

            $questions[] = [
                'question_text' => $title,
                'options' => $options,
                'correct_answer' => $this->extractCorrectAnswer($item, $options),
                'type' => $type,
                'needs_review' => true,
            ];
        }

        return [
            'questions' => $questions,
            'ignored_identity' => $ignoredIdentity,
            'failed' => $failed,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rawItems
     * @return array<int, array<string, mixed>>
     */
    public function normalizeQuestions(array $rawItems): array
    {
        return $this->normalizeQuestionsWithSummary($rawItems)['questions'];
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $options
     * @return array{created: int, review: int, failed: int, errors: array<int, string>}
     */
    public function importToDatabase(array $questions, array $options): array
    {
        $options['source_type'] = 'google_form';
        $options['allow_missing_correct_answer'] = true;
        $options['allow_partial_options'] = true;
        $options['status'] = 'draft';

        return app(QuestionImportPersister::class)->import($questions, $options);
    }

    /**
     * @return array<int, mixed>
     */
    private function extractPublicLoadData(string $html): array
    {
        if (! preg_match('/FB_PUBLIC_LOAD_DATA_\s*=\s*(.*?);\s*(?:<\/script>|\n)/s', $html, $matches)) {
            throw new RuntimeException(self::ACCESS_ERROR_MESSAGE);
        }

        $payload = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new RuntimeException(self::ACCESS_ERROR_MESSAGE);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, string>
     */
    private function extractOptions(array $item): array
    {
        $options = [];
        $optionItems = $item[4][0][1] ?? [];

        if (! is_array($optionItems)) {
            return $options;
        }

        $letterIndex = 0;
        foreach ($optionItems as $optionItem) {
            $label = is_array($optionItem) ? (string) ($optionItem[0] ?? '') : '';
            $label = $this->cleanText($label);
            if ($label === '') {
                continue;
            }

            $options[chr(ord('A') + $letterIndex)] = $label;
            $letterIndex++;

            if ($letterIndex >= 26) {
                break;
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, string> $options
     */
    private function extractCorrectAnswer(array $item, array $options): ?string
    {
        // Public Google Form HTML usually does not expose answer keys. Keep imported
        // questions as draft/review instead of failing the whole import.
        return null;
    }

    /**
     * @param array<string, string> $options
     */
    private function isIdentityField(string $title, array $options): bool
    {
        $normalizedTitle = $this->normalizeTitle($title);

        if (in_array($normalizedTitle, self::IDENTITY_TITLES, true)) {
            return true;
        }

        if (in_array($normalizedTitle, ['NAME', 'NAMA'], true) && $this->mostlyLooksLikePersonNames($options)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, string> $options
     */
    private function mostlyLooksLikePersonNames(array $options): bool
    {
        if (count($options) < 5) {
            return false;
        }

        $nameLike = 0;
        foreach ($options as $option) {
            if (preg_match('/^[\p{L}\s\'.-]{3,}$/u', $option) && str_word_count($option) <= 5) {
                $nameLike++;
            }
        }

        return ($nameLike / max(count($options), 1)) >= 0.6;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, string> $options
     */
    private function resolveQuestionType(array $item, array $options): string
    {
        $typeCode = $item[4][0][3] ?? $item[3] ?? null;
        $typeCode = is_numeric($typeCode) ? (int) $typeCode : null;

        if ($typeCode !== null && isset(self::QUESTION_TYPES[$typeCode])) {
            return self::QUESTION_TYPES[$typeCode];
        }

        return $options === [] ? 'short_answer' : 'multiple_choice';
    }

    /**
     * @param array<string, string> $options
     */
    private function isQuestionItem(string $title, array $options, string $type): bool
    {
        if (in_array($type, ['multiple_choice', 'checkbox', 'short_answer', 'paragraph', 'dropdown'], true)) {
            return mb_strlen($title) >= 2 || $options !== [];
        }

        return mb_strlen($title) >= 12 || $options !== [];
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function normalizeTitle(string $title): string
    {
        $title = $this->cleanText($title);
        $title = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $title) ?? $title;
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

        return mb_strtoupper(trim($title));
    }
}
