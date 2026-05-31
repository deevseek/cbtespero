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

        if (count($options) < 4 && count($lines) >= 5) {
            $fallback = $this->parseUnlabelledOptions($lines);

            if ($fallback !== null) {
                $questionLines = $fallback['question_lines'];
                $options = $fallback['options'];
            }
        }

        $questionText = $this->cleanMultilineText(implode("\n", $questionLines));
        $questionText = $this->stripQuestionNumberMarkers($questionText);
        $options = array_filter(
            array_map(fn (string $option): string => $this->cleanInlineText($option), $options),
            fn (string $option): bool => $option !== ''
        );

        if ($questionText === '') {
            return null;
        }

        $correctAnswer = $correctAnswer !== null ? strtoupper($correctAnswer) : null;
        $filledOptions = count($options);
        $isMultipleChoice = $filledOptions >= 4;

        return [
            'question_text' => $questionText,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'type' => $isMultipleChoice ? 'multiple_choice' : 'essay',
            'needs_review' => ! $isMultipleChoice || $correctAnswer === null || $filledOptions < 5,
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
        $pendingPassage = trim(substr($text, 0, $matches[0][0][1]));
        $matchCount = count($matches[0]);

        for ($index = 0; $index < $matchCount; $index++) {
            $start = $matches[0][$index][1];
            $end = $index + 1 < $matchCount ? $matches[0][$index + 1][1] : strlen($text);
            $chunk = trim(substr($text, $start, $end - $start));

            if ($chunk === '') {
                continue;
            }

            $split = $this->splitTrailingPassage($chunk);
            $block = $pendingPassage !== '' ? $pendingPassage."\n".$split['question'] : $split['question'];
            $block = trim($block);

            if ($block !== '') {
                $blocks[] = $block;
            }

            $pendingPassage = $split['passage'];
        }

        return $blocks;
    }

    /**
     * @return array{question: string, passage: string}
     */
    private function splitTrailingPassage(string $block): array
    {
        $lines = preg_split('/\n/u', $block) ?: [];
        $optionLabelsSeen = [];
        $lastOptionLine = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*([A-Ea-e])(?:[\.)]\s*|\s+)(?:\S.*)?$/u', trim($line), $matches)) {
                $label = strtoupper($matches[1]);

                if ($label === 'A' || $optionLabelsSeen !== []) {
                    $optionLabelsSeen[$label] = true;
                    $lastOptionLine = $index;
                }
            }
        }

        if ($lastOptionLine === null || count($optionLabelsSeen) < 4 || $lastOptionLine >= count($lines) - 1) {
            return ['question' => $block, 'passage' => ''];
        }

        if (preg_match('/^\s*[A-Ea-e]\s*[\.)]\s*$/u', trim($lines[$lastOptionLine]))) {
            return ['question' => $block, 'passage' => ''];
        }

        $tailLines = array_slice($lines, $lastOptionLine + 1);
        $tailText = trim(implode("\n", $tailLines));

        if ($tailText === '') {
            return ['question' => $block, 'passage' => ''];
        }

        $questionLines = array_slice($lines, 0, $lastOptionLine + 1);
        $firstTailLine = trim((string) ($tailLines[0] ?? ''));

        if ($this->looksLikeAnswerText($firstTailLine)) {
            $questionLines[] = $firstTailLine;
            $tailLines = array_slice($tailLines, 1);
            $tailText = trim(implode("\n", $tailLines));

            if ($tailText === '') {
                return ['question' => $block, 'passage' => ''];
            }
        }

        return [
            'question' => trim(implode("\n", $questionLines)),
            'passage' => $tailText,
        ];
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

            if (preg_match('/^(?:Jawaban|Kunci|Answer|ANS|Correct\s+Answer)\s*[:\-]?\s*([A-Ea-e])\b.*$/iu', $line, $matches)) {
                array_splice($lines, $index, 1);

                return [
                    'text' => trim(implode("\n", $lines)),
                    'answer' => strtoupper($matches[1]),
                ];
            }

            if (preg_match('/^([A-Ea-e])$/u', $line, $matches)) {
                $textBeforeAnswer = trim(implode("\n", array_slice($lines, 0, $index)));

                if (preg_match('/^\s*E\s*(?:[\.)]|\s+)\s*/miu', $textBeforeAnswer)) {
                    array_splice($lines, $index, 1);

                    return [
                        'text' => trim(implode("\n", $lines)),
                        'answer' => strtoupper($matches[1]),
                    ];
                }
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
        $pendingOption = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^([A-Ea-e])\s*[\.)]\s*$/u', $line, $matches)) {
                $pendingOption = strtoupper($matches[1]);
                $currentOption = null;

                continue;
            }

            if (preg_match('/^([A-Ea-e])[\.)]\s*(\S.*)$/u', $line, $matches)) {
                $key = strtoupper($matches[1]);
                $text = trim($matches[2]);
                $currentOption = $key;
                $pendingOption = null;
                $result[] = ['kind' => 'option', 'key' => $key, 'text' => $text];

                continue;
            }

            if (preg_match('/^([A-Ea-e])\s+(\S.*)$/u', $line, $matches)) {
                $key = strtoupper($matches[1]);

                if ($key !== 'A' || $this->nextLineStartsWithLabel($lines, $index, 'B')) {
                    $currentOption = $key;
                    $pendingOption = null;
                    $result[] = ['kind' => 'option', 'key' => $key, 'text' => trim($matches[2])];

                    continue;
                }
            }

            if ($pendingOption !== null) {
                $currentOption = $pendingOption;
                $pendingOption = null;
                $result[] = ['kind' => 'option', 'key' => $currentOption, 'text' => $line];

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
     */
    private function nextLineStartsWithLabel(array $lines, int $currentIndex, string $expectedLabel): bool
    {
        for ($index = $currentIndex + 1; $index < count($lines); $index++) {
            $line = trim($lines[$index]);

            if ($line === '') {
                continue;
            }

            return (bool) preg_match('/^'.preg_quote($expectedLabel, '/').'\s*(?:[\.)]|\s+)\s*\S/iu', $line)
                || (bool) preg_match('/^'.preg_quote($expectedLabel, '/').'\s*[\.)]\s*$/iu', $line);
        }

        return false;
    }

    /**
     * @param array<int, string> $lines
     * @return array{question_lines: array<int, string>, options: array<string, string>}|null
     */
    private function parseUnlabelledOptions(array $lines): ?array
    {
        $optionCount = min(5, count($lines) - 1);

        if ($optionCount < 4) {
            return null;
        }

        $optionLines = array_slice($lines, -$optionCount);
        $questionLines = array_slice($lines, 0, -$optionCount);

        if ($questionLines === []) {
            return null;
        }

        $options = [];
        foreach (array_slice(['A', 'B', 'C', 'D', 'E'], 0, $optionCount) as $index => $key) {
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

    private function stripQuestionNumberMarkers(string $text): string
    {
        $text = preg_replace('/(^|\n)\s*\d+\s*[\.)]\s*/u', '$1', $text) ?? $text;

        return trim($text);
    }

    private function looksLikeAnswerText(string $text): bool
    {
        return (bool) preg_match('/^(?:Jawaban|Kunci|Answer|ANS|Correct\s+Answer)\s*[:\-]?\s*[A-E]\b|^[A-E]$/iu', trim($text));
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
