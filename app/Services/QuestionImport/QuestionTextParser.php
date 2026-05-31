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

        $blocks = preg_split('/(?=^\s*\d+\s*[\.)]\s+)/m', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $questions = [];

        foreach ($blocks as $block) {
            $parsed = $this->parseBlock($block);

            if ($parsed !== null) {
                $questions[] = $parsed;
            }
        }

        return $questions;
    }

    private function parseBlock(string $block): ?array
    {
        $block = trim($block);
        $block = preg_replace('/^\s*\d+\s*[\.)]\s*/', '', $block) ?? $block;

        if ($block === '') {
            return null;
        }

        $correctAnswer = null;
        if (preg_match('/(?:Jawaban|Kunci|Answer)\s*:\s*([A-Ea-e])\b/u', $block, $matches)) {
            $correctAnswer = strtoupper($matches[1]);
            $block = preg_replace('/(?:Jawaban|Kunci|Answer)\s*:\s*[A-Ea-e]\b.*$/imu', '', $block) ?? $block;
        }

        $options = [];
        if (preg_match_all('/^\s*([A-Ea-e])\s*[\.)]\s*(.+?)(?=^\s*[A-Ea-e]\s*[\.)]\s+|\z)/msu', $block, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = strtoupper($match[1]);
                $options[$key] = trim(preg_replace('/\s+/', ' ', $match[2]) ?? $match[2]);
            }
        }

        $questionText = $block;
        if ($options !== [] && preg_match('/^(.+?)^\s*[A-Ea-e]\s*[\.)]\s+/msu', $block, $matches)) {
            $questionText = $matches[1];
        }

        $questionText = trim(preg_replace('/\s+/', ' ', $questionText) ?? $questionText);

        if ($questionText === '') {
            return null;
        }

        $isMultipleChoice = count($options) >= 2;

        return [
            'question_text' => $questionText,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'type' => $isMultipleChoice ? 'multiple_choice' : 'essay',
            'needs_review' => ! $isMultipleChoice || $correctAnswer === null,
        ];
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
