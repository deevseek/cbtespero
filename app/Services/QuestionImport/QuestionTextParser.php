<?php

namespace App\Services\QuestionImport;

class QuestionTextParser
{
    /**
     * @return array<int, array{question_text: string, options: array<string, string>, correct_answer: ?string, type: string, needs_review: bool}>
     */
    public function parse(string $text): array
    {
        $text = $this->normalizeText($text);

        if ($text === '') {
            return [];
        }

        $blocks = $this->splitIntoQuestionBlocks($text);
        $questions = [];

        foreach ($blocks as $block) {
            $parsed = $this->parseQuestionBlock($block);

            if ($parsed !== null) {
                $questions[] = $parsed;
            }
        }

        return $questions;
    }

    /**
     * @return array{question_text: string, options: array<string, string>, correct_answer: ?string, type: string, needs_review: bool}|null
     */
    public function parseQuestionBlock(string $block, ?string $correctAnswer = null): ?array
    {
        $block = $this->normalizeText($block);
        $block = preg_replace('/^\s*\d+\s*[\.)]\s*/u', '', $block) ?? $block;

        if ($block === '') {
            return null;
        }

        $answerFromBlock = $this->extractCorrectAnswer($block);
        if ($correctAnswer === null) {
            $correctAnswer = $answerFromBlock['answer'];
        }

        $block = $answerFromBlock['text'];
        $lines = $this->nonEmptyLines($block);
        $options = [];
        $questionLines = [];

        foreach ($this->parseLabelledLines($lines) as $parsedLine) {
            if ($parsedLine['kind'] === 'question') {
                $questionLines[] = $parsedLine['text'];

                continue;
            }

            $options[$parsedLine['key']] = $parsedLine['text'];
        }

        if (count($options) < 2 && $correctAnswer !== null && count($lines) >= 6) {
            $fallback = $this->parseUnlabelledOptions($lines);

            if ($fallback !== null) {
                $questionLines = $fallback['question_lines'];
                $options = $fallback['options'];
            }
        }

        $questionText = $this->cleanMultilineText(implode("\n", $questionLines));

        if ($questionText === '') {
            return null;
        }

        $correctAnswer = $correctAnswer !== null ? strtoupper($correctAnswer) : null;
        $isMultipleChoice = count(array_filter($options, fn (string $option): bool => $option !== '')) >= 2;

        return [
            'question_text' => $questionText,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'type' => $isMultipleChoice ? 'multiple_choice' : 'essay',
            'needs_review' => ! $isMultipleChoice || $correctAnswer === null || count($options) < 5,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function splitIntoQuestionBlocks(string $text): array
    {
        if (! preg_match_all('/^\s*\d+\s*[\.)]\s+/mu', $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [$text];
        }

        $blocks = [];
        $firstOffset = $matches[0][0][1];
        $sharedPassage = trim(substr($text, 0, $firstOffset));
        $matchCount = count($matches[0]);

        for ($index = 0; $index < $matchCount; $index++) {
            $start = $matches[0][$index][1];
            $end = $index + 1 < $matchCount ? $matches[0][$index + 1][1] : strlen($text);
            $block = trim(substr($text, $start, $end - $start));

            if ($block === '') {
                continue;
            }

            $blocks[] = $sharedPassage !== '' ? $sharedPassage."\n".$block : $block;
        }

        return $blocks;
    }

    /**
     * @return array{text: string, answer: ?string}
     */
    private function extractCorrectAnswer(string $block): array
    {
        $lines = preg_split('/\n/u', $block) ?: [];

        for ($index = count($lines) - 1; $index >= 0; $index--) {
            $line = trim($lines[$index]);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^(?:Jawaban|Kunci|Answer)\s*[:\-]?\s*([A-Ea-e])\b.*$/u', $line, $matches)
                || preg_match('/^([A-Ea-e])$/u', $line, $matches)) {
                array_splice($lines, $index, 1);

                return [
                    'text' => trim(implode("\n", $lines)),
                    'answer' => strtoupper($matches[1]),
                ];
            }

            break;
        }

        return ['text' => $block, 'answer' => null];
    }

    /**
     * @param array<int, string> $lines
     * @return array<int, array{kind: 'question'|'option', key?: string, text: string}>
     */
    private function parseLabelledLines(array $lines): array
    {
        $result = [];
        $currentOption = null;

        foreach ($lines as $line) {
            if (preg_match('/^([A-Ea-e])(?:[\.)]|\s+)\s*(\S.*)$/u', $line, $matches)) {
                $key = strtoupper($matches[1]);
                $text = trim($matches[2]);
                $currentOption = $key;
                $result[] = ['kind' => 'option', 'key' => $key, 'text' => $text];

                continue;
            }

            if ($currentOption !== null) {
                $lastIndex = array_key_last($result);
                $result[$lastIndex]['text'] = trim($result[$lastIndex]['text'].' '.$line);

                continue;
            }

            $result[] = ['kind' => 'question', 'text' => $line];
        }

        foreach ($result as $index => $item) {
            $result[$index]['text'] = $this->cleanInlineText($item['text']);
        }

        return $result;
    }

    /**
     * @param array<int, string> $lines
     * @return array{question_lines: array<int, string>, options: array<string, string>}|null
     */
    private function parseUnlabelledOptions(array $lines): ?array
    {
        $optionLines = array_slice($lines, -5);
        $questionLines = array_slice($lines, 0, -5);

        if (count($optionLines) !== 5 || $questionLines === []) {
            return null;
        }

        $options = [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $index => $key) {
            $option = $this->cleanInlineText($optionLines[$index] ?? '');
            if ($option === '') {
                return null;
            }

            $options[$key] = $option;
        }

        return [
            'question_lines' => $questionLines,
            'options' => $options,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function nonEmptyLines(string $text): array
    {
        $lines = preg_split('/\n/u', $text) ?: [];

        return array_values(array_filter(array_map(fn (string $line): string => trim($line), $lines), fn (string $line): bool => $line !== ''));
    }

    private function cleanMultilineText(string $text): string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function cleanInlineText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\x{00a0}/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
