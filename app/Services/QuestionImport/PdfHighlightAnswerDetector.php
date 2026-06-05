<?php

namespace App\Services\QuestionImport;

use Illuminate\Support\Facades\Log;
use Throwable;

class PdfHighlightAnswerDetector
{
    private const YELLOW_MIN_R = 180;
    private const YELLOW_MIN_G = 160;
    private const YELLOW_MAX_B = 140;
    private const MIN_BOX_WIDTH = 18;
    private const MIN_BOX_HEIGHT = 4;
    private const MIN_BOX_AREA = 180;

    /** @var array<int, string> */
    private array $pageTexts = [];

    /** @var array<int, array<string, mixed>> */
    private array $questions = [];

    /** @var array<string, mixed> */
    private array $lastSummary = [
        'enabled' => true,
        'renderer' => null,
        'unavailable_reason' => null,
        'highlight_boxes' => 0,
        'answers' => [],
        'answers_found' => 0,
    ];

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<int, string> $pageTexts
     * @return array<int, string>
     */
    public function detect(string $pdfPath, array $questions = [], array $pageTexts = []): array
    {
        $this->questions = $questions;
        $this->pageTexts = $pageTexts;
        $this->resetSummary();

        if (! (bool) config('question_import.pdf_highlight_detection', true)) {
            $this->lastSummary['enabled'] = false;
            $this->lastSummary['unavailable_reason'] = 'PDF_HIGHLIGHT_DETECTION=false';

            Log::debug('[PDF IMPORT] highlight_detection_unavailable="PDF_HIGHLIGHT_DETECTION=false"');

            return [];
        }

        try {
            $answers = $this->detectFromAnnotations($pdfPath);

            if ($answers !== []) {
                $this->lastSummary['answers'] = $answers;
                $this->lastSummary['answers_found'] = count($answers);

                return $answers;
            }
        } catch (Throwable $exception) {
            report($exception);
            Log::debug('[PDF IMPORT] highlight_annotation_detection_failed='.$exception->getMessage());
        }

        try {
            $answers = $this->detectFromRenderedPages($pdfPath, $questions);
            $this->lastSummary['answers'] = $answers;
            $this->lastSummary['answers_found'] = count($answers);

            return $answers;
        } catch (Throwable $exception) {
            report($exception);
            $this->lastSummary['unavailable_reason'] = $exception->getMessage();
            Log::debug('[PDF IMPORT] highlight_detection_unavailable="'.$exception->getMessage().'"');

            return [];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<int, string> $pageTexts
     * @return array<int, string>
     */
    public function detectAnswers(string $pdfPath, array $questions = [], array $pageTexts = []): array
    {
        return $this->detect($pdfPath, $questions, $pageTexts);
    }

    /**
     * Coba membaca annotation highlight dari struktur PDF. Smalot/pdfparser tidak menyediakan
     * API stabil untuk koordinat highlight, jadi implementasi aman ini hanya mendeteksi bahwa
     * annotation ada dan mengembalikan kosong agar fallback render image tetap berjalan.
     *
     * @return array<int, string>
     */
    public function detectFromAnnotations(string $pdfPath): array
    {
        $content = @file_get_contents($pdfPath, false, null, 0, 1024 * 1024 * 8);

        if (! is_string($content) || $content === '') {
            return [];
        }

        if (stripos($content, '/Subtype/Highlight') !== false || stripos($content, '/Subtype /Highlight') !== false) {
            $answers = $this->detectAnswersFromHighlightContents($content);

            if ($answers !== []) {
                Log::debug('[PDF IMPORT] highlight_annotations_present=1 annotation_contents_answers='.count($answers));

                return $answers;
            }

            Log::debug('[PDF IMPORT] highlight_annotations_present=1 note="annotation contents empty; rendered fallback mapping required"');
        }

        return [];
    }

    /** @return array<int, string> */
    public function detectAnswersFromAnnotations(string $pdfPath): array
    {
        return $this->detectFromAnnotations($pdfPath);
    }

    /**
     * @return array<int, string>
     */
    private function detectAnswersFromHighlightContents(string $content): array
    {
        if ($this->questions === []) {
            return [];
        }

        if (! preg_match_all('/<<(?:(?!<<|>>).)*\/Subtype\s*\/?Highlight(?:(?!<<|>>).)*>>/isu', $content, $matches)) {
            return [];
        }

        $answers = [];

        foreach ($matches[0] as $annotation) {
            $highlightText = $this->extractAnnotationContentsText($annotation);

            if ($highlightText === '') {
                continue;
            }

            $matched = $this->matchHighlightedTextToQuestionAnswer($highlightText);

            if ($matched !== null) {
                $answers[$matched['question_number']] ??= $matched['answer'];
            }
        }

        ksort($answers);

        return $answers;
    }

    private function extractAnnotationContentsText(string $annotation): string
    {
        $values = [];

        if (preg_match_all('/\/(?:Contents|RC)\s*\(((?:\\\\.|[^\\\\)])*)\)/su', $annotation, $matches)) {
            foreach ($matches[1] as $value) {
                $values[] = $this->decodePdfLiteralString($value);
            }
        }

        if (preg_match_all('/\/(?:Contents|RC)\s*<([0-9A-Fa-f]+)>/u', $annotation, $matches)) {
            foreach ($matches[1] as $value) {
                $decoded = @hex2bin($value);

                if (is_string($decoded)) {
                    $values[] = $this->normalizePdfStringEncoding($decoded);
                }
            }
        }

        $text = trim(implode(' ', array_filter($values, fn (string $value): bool => trim($value) !== '')));
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function decodePdfLiteralString(string $value): string
    {
        $value = preg_replace_callback('/\\\\([nrtbf()\\\\])|\\\\([0-7]{1,3})/u', function (array $matches): string {
            if (($matches[2] ?? '') !== '') {
                return chr(octdec($matches[2]));
            }

            return match ($matches[1]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'b' => "\b",
                'f' => "\f",
                default => $matches[1],
            };
        }, $value) ?? $value;

        return $this->normalizePdfStringEncoding($value);
    }

    private function normalizePdfStringEncoding(string $value): string
    {
        if (str_starts_with($value, "\xFE\xFF")) {
            $converted = @mb_convert_encoding(substr($value, 2), 'UTF-8', 'UTF-16BE');

            return is_string($converted) ? $converted : $value;
        }

        if (str_starts_with($value, "\xFF\xFE")) {
            $converted = @mb_convert_encoding(substr($value, 2), 'UTF-8', 'UTF-16LE');

            return is_string($converted) ? $converted : $value;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252, ISO-8859-1');

            return is_string($converted) ? $converted : $value;
        }

        return $value;
    }

    /**
     * @return array{question_number: int, answer: string}|null
     */
    private function matchHighlightedTextToQuestionAnswer(string $highlightText): ?array
    {
        $normalizedHighlight = $this->normalizeComparableText($highlightText);

        if ($normalizedHighlight === '') {
            return null;
        }

        $explicitAnswer = preg_match('/^([A-Ea-e])(?:[\.)]|\s|$)/u', trim($highlightText), $matches)
            ? strtoupper($matches[1])
            : null;

        foreach ($this->questions as $index => $question) {
            $options = (array) ($question['options'] ?? []);

            foreach ($options as $answer => $optionText) {
                $answer = strtoupper((string) $answer);

                if ($explicitAnswer !== null && $explicitAnswer !== $answer) {
                    continue;
                }

                $normalizedOption = $this->normalizeComparableText((string) $optionText);

                if ($normalizedOption === '') {
                    continue;
                }

                if (str_contains($normalizedHighlight, $normalizedOption) || str_contains($normalizedOption, $normalizedHighlight)) {
                    return ['question_number' => (int) ($question['number'] ?? $index + 1), 'answer' => $answer];
                }
            }
        }

        return null;
    }

    private function normalizeComparableText(string $text): string
    {
        $text = preg_replace('/^[A-Ea-e]\s*[\.)]\s*/u', '', trim($text)) ?? $text;
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\pL\pN]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * @return array<int, string>
     */
    public function detectFromRenderedPages(string $pdfPath, array $parsedQuestions = []): array
    {
        if ($parsedQuestions !== []) {
            $this->questions = $parsedQuestions;
        }
        $renderer = $this->resolveRenderer();

        if ($renderer === null) {
            $message = 'Deteksi highlight PDF membutuhkan Imagick, Poppler, atau Ghostscript.';
            $this->lastSummary['unavailable_reason'] = $message;
            Log::debug('[PDF IMPORT] highlight_detection_unavailable="Imagick/poppler/Ghostscript not installed"');

            return [];
        }

        $this->lastSummary['renderer'] = $renderer;
        $tempDir = $this->makeTempDirectory();

        try {
            $images = $this->renderPdf($pdfPath, $renderer, $tempDir);
            $answers = [];
            $highlightBoxesCount = 0;

            foreach ($images as $pageIndex => $imagePath) {
                $boxes = $this->findYellowHighlightBoxes($imagePath);
                $highlightBoxesCount += count($boxes);

                foreach ($this->matchBoxesToOptionRows($boxes, $pageIndex, $imagePath) as $questionNumber => $answer) {
                    $answers[$questionNumber] ??= $answer;
                }
            }

            ksort($answers);
            $this->lastSummary['highlight_boxes'] = $highlightBoxesCount;
            Log::debug('[PDF IMPORT] highlight_boxes='.$highlightBoxesCount);

            return $answers;
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    /** @return array<int, string> */
    public function detectAnswersFromRenderedPages(string $pdfPath): array
    {
        return $this->detectFromRenderedPages($pdfPath);
    }

    /** @return array<string, mixed> */
    public function getLastSummary(): array
    {
        return $this->lastSummary;
    }

    private function resetSummary(): void
    {
        $this->lastSummary = [
            'enabled' => true,
            'renderer' => null,
            'unavailable_reason' => null,
            'highlight_boxes' => 0,
            'answers' => [],
            'answers_found' => 0,
        ];
    }

    private function resolveRenderer(): ?string
    {
        $preferred = strtolower((string) config('question_import.pdf_renderer', 'auto'));

        $available = [
            'imagick' => extension_loaded('imagick') && class_exists(\Imagick::class),
            'poppler' => $this->commandExists('pdftoppm'),
            'ghostscript' => $this->firstExistingCommand(['gswin64c', 'gswin32c', 'gs']) !== null,
        ];

        if ($preferred !== 'auto') {
            return ($available[$preferred] ?? false) ? $preferred : null;
        }

        foreach (['imagick', 'poppler', 'ghostscript'] as $renderer) {
            if ($available[$renderer]) {
                return $renderer;
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private function renderPdf(string $pdfPath, string $renderer, string $tempDir): array
    {
        return match ($renderer) {
            'imagick' => $this->renderWithImagick($pdfPath, $tempDir),
            'poppler' => $this->renderWithPoppler($pdfPath, $tempDir),
            'ghostscript' => $this->renderWithGhostscript($pdfPath, $tempDir),
            default => [],
        };
    }

    /** @return array<int, string> */
    private function renderWithImagick(string $pdfPath, string $tempDir): array
    {
        $imagick = new \Imagick();
        $imagick->setResolution(144, 144);
        $imagick->readImage($pdfPath);
        $imagick->setImageFormat('png');

        $images = [];
        foreach ($imagick as $index => $page) {
            $page->setImageBackgroundColor('white');
            $page = $page->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $path = $tempDir.DIRECTORY_SEPARATOR.sprintf('page-%04d.png', $index + 1);
            $page->writeImage($path);
            $images[$index] = $path;
        }

        $imagick->clear();
        $imagick->destroy();

        return $images;
    }

    /** @return array<int, string> */
    private function renderWithPoppler(string $pdfPath, string $tempDir): array
    {
        $prefix = $tempDir.DIRECTORY_SEPARATOR.'page';
        $this->runCommand(['pdftoppm', '-r', '144', '-png', $pdfPath, $prefix]);

        return $this->collectRenderedImages($tempDir, '/^page-(\d+)\.png$/');
    }

    /** @return array<int, string> */
    private function renderWithGhostscript(string $pdfPath, string $tempDir): array
    {
        $command = $this->firstExistingCommand(['gswin64c', 'gswin32c', 'gs']);

        if ($command === null) {
            return [];
        }

        $output = $tempDir.DIRECTORY_SEPARATOR.'page-%04d.png';
        $this->runCommand([
            $command,
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-sDEVICE=png16m',
            '-r144',
            '-sOutputFile='.$output,
            $pdfPath,
        ]);

        return $this->collectRenderedImages($tempDir, '/^page-(\d+)\.png$/');
    }

    /**
     * @return array<int, array{x: int, y: int, width: int, height: int, center_y: float}>
     */
    private function findYellowHighlightBoxes(string $imagePath): array
    {
        $image = @imagecreatefrompng($imagePath);

        if (! $image) {
            $image = @imagecreatefromjpeg($imagePath);
        }

        if (! $image) {
            return [];
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);
            $step = max(1, (int) floor(max($width, $height) / 1400));
            $activeBoxes = [];
            $finalBoxes = [];

            for ($y = 0; $y < $height; $y += $step) {
                $runs = [];
                $runStart = null;
                $lastYellow = null;

                for ($x = 0; $x < $width; $x += $step) {
                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    if ($this->isYellow($r, $g, $b)) {
                        $runStart ??= $x;
                        $lastYellow = $x;
                    } elseif ($runStart !== null) {
                        if (($lastYellow - $runStart) >= self::MIN_BOX_WIDTH) {
                            $runs[] = ['x1' => $runStart, 'x2' => $lastYellow, 'y' => $y];
                        }

                        $runStart = null;
                        $lastYellow = null;
                    }
                }

                if ($runStart !== null && $lastYellow !== null && ($lastYellow - $runStart) >= self::MIN_BOX_WIDTH) {
                    $runs[] = ['x1' => $runStart, 'x2' => $lastYellow, 'y' => $y];
                }

                $matched = [];
                foreach ($runs as $run) {
                    $matchIndex = null;

                    foreach ($activeBoxes as $index => $box) {
                        $hasVerticalContinuity = ($run['y'] - $box['y2']) <= max(8, $step * 4);
                        $hasHorizontalOverlap = min($run['x2'], $box['x2']) >= max($run['x1'], $box['x1']) - 12;

                        if ($hasVerticalContinuity && $hasHorizontalOverlap) {
                            $matchIndex = $index;
                            break;
                        }
                    }

                    if ($matchIndex === null) {
                        $activeBoxes[] = [
                            'x1' => $run['x1'],
                            'x2' => $run['x2'],
                            'y1' => $run['y'],
                            'y2' => $run['y'],
                        ];
                        $matched[array_key_last($activeBoxes)] = true;
                    } else {
                        $activeBoxes[$matchIndex]['x1'] = min($activeBoxes[$matchIndex]['x1'], $run['x1']);
                        $activeBoxes[$matchIndex]['x2'] = max($activeBoxes[$matchIndex]['x2'], $run['x2']);
                        $activeBoxes[$matchIndex]['y2'] = $run['y'];
                        $matched[$matchIndex] = true;
                    }
                }

                foreach ($activeBoxes as $index => $box) {
                    if (! isset($matched[$index]) && ($y - $box['y2']) > max(14, $step * 6)) {
                        $finalBoxes[] = $box;
                        unset($activeBoxes[$index]);
                    }
                }
            }

            $finalBoxes = array_merge($finalBoxes, array_values($activeBoxes));

            return $this->normalizeBoxes($finalBoxes);
        } finally {
            imagedestroy($image);
        }
    }

    private function isYellow(int $r, int $g, int $b): bool
    {
        if ($r > self::YELLOW_MIN_R && $g > self::YELLOW_MIN_G && $b < self::YELLOW_MAX_B) {
            return true;
        }

        [$h, $s, $v] = $this->rgbToHsv($r, $g, $b);

        return $h >= 38 && $h <= 68 && $s >= 0.25 && $v >= 0.55;
    }

    /** @return array{0: float, 1: float, 2: float} */
    private function rgbToHsv(int $r, int $g, int $b): array
    {
        $red = $r / 255;
        $green = $g / 255;
        $blue = $b / 255;
        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $delta = $max - $min;

        if ($delta == 0.0) {
            $hue = 0.0;
        } elseif ($max === $red) {
            $hue = 60 * fmod((($green - $blue) / $delta), 6);
        } elseif ($max === $green) {
            $hue = 60 * ((($blue - $red) / $delta) + 2);
        } else {
            $hue = 60 * ((($red - $green) / $delta) + 4);
        }

        if ($hue < 0) {
            $hue += 360;
        }

        return [$hue, $max == 0.0 ? 0.0 : $delta / $max, $max];
    }

    /**
     * @param array<int, array{x1: int, x2: int, y1: int, y2: int}> $rawBoxes
     * @return array<int, array{x: int, y: int, width: int, height: int, center_y: float}>
     */
    private function normalizeBoxes(array $rawBoxes): array
    {
        $boxes = [];

        foreach ($rawBoxes as $box) {
            $width = $box['x2'] - $box['x1'];
            $height = $box['y2'] - $box['y1'];
            $area = $width * max(1, $height);

            if ($width < self::MIN_BOX_WIDTH || $height < self::MIN_BOX_HEIGHT || $area < self::MIN_BOX_AREA) {
                continue;
            }

            $boxes[] = [
                'x' => $box['x1'],
                'y' => $box['y1'],
                'width' => $width,
                'height' => $height,
                'center_y' => $box['y1'] + ($height / 2),
            ];
        }

        usort($boxes, fn (array $a, array $b): int => $a['center_y'] <=> $b['center_y']);

        return $boxes;
    }

    /**
     * @param array<int, array{x: int, y: int, width: int, height: int, center_y: float}> $boxes
     * @return array<int, string>
     */
    private function matchBoxesToOptionRows(array $boxes, int $pageIndex, string $imagePath): array
    {
        $rows = $this->optionRowsForPage($pageIndex);

        if ($boxes === [] || $rows === []) {
            return [];
        }

        [$imageWidth, $imageHeight] = $this->imageSize($imagePath);
        $lineCount = max(1, (int) max(array_column($rows, 'line_index')) + 1);
        $topMargin = $imageHeight * 0.06;
        $usableHeight = $imageHeight * 0.88;
        $lineHeight = $usableHeight / max(1, $lineCount - 1);
        $answers = [];

        foreach ($rows as $index => $row) {
            $rows[$index]['estimated_y'] = $topMargin + ($row['line_index'] * $lineHeight);
        }

        foreach ($boxes as $box) {
            $nearest = null;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($rows as $row) {
                $distance = abs($box['center_y'] - $row['estimated_y']);
                $allowedDistance = max(24.0, $lineHeight * 0.85);

                if ($distance <= $allowedDistance && $distance < $nearestDistance) {
                    $nearest = $row;
                    $nearestDistance = $distance;
                }
            }

            if ($nearest !== null && $this->boxLooksLikeOptionHighlight($box, $imageWidth)) {
                $answers[(int) $nearest['question_number']] = (string) $nearest['answer'];
            }
        }

        return $answers;
    }

    /**
     * @return array<int, array{line_index: int, question_number: int, answer: string}>
     */
    private function optionRowsForPage(int $pageIndex): array
    {
        $text = $this->pageTexts[$pageIndex] ?? '';

        if ($text === '') {
            return $this->fallbackOptionRowsForPage($pageIndex);
        }

        $lines = preg_split('/\R/u', str_replace(["\r\n", "\r"], "\n", $text)) ?: [];
        $rows = [];
        $currentQuestion = null;

        foreach ($lines as $lineIndex => $line) {
            $trimmed = trim((string) $line);

            if (preg_match('/^\s*(\d+)\s*[\.)]\s+/u', $trimmed, $matches)) {
                $currentQuestion = (int) $matches[1];
            }

            if ($currentQuestion !== null && preg_match('/^\s*([A-Ea-e])(?:[\.)]\s*|\s+)\S/u', $trimmed, $matches)) {
                $rows[] = [
                    'line_index' => $lineIndex,
                    'question_number' => $currentQuestion,
                    'answer' => strtoupper($matches[1]),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{line_index: int, question_number: int, answer: string}>
     */
    private function fallbackOptionRowsForPage(int $pageIndex): array
    {
        $rows = [];
        $questionsPerPage = max(1, (int) ceil(count($this->questions) / max(1, count($this->pageTexts) ?: 1)));
        $start = $pageIndex * $questionsPerPage;
        $end = min(count($this->questions), $start + $questionsPerPage);
        $lineIndex = 0;

        for ($questionIndex = $start; $questionIndex < $end; $questionIndex++) {
            $questionNumber = $questionIndex + 1;
            $lineIndex += 2;

            foreach (['A', 'B', 'C', 'D', 'E'] as $answer) {
                $rows[] = [
                    'line_index' => $lineIndex++,
                    'question_number' => $questionNumber,
                    'answer' => $answer,
                ];
            }
        }

        return $rows;
    }

    /** @param array{x: int, y: int, width: int, height: int, center_y: float} $box */
    private function boxLooksLikeOptionHighlight(array $box, int $imageWidth): bool
    {
        return $box['width'] >= 30 && $box['height'] >= 5 && $box['x'] < ($imageWidth * 0.95);
    }

    /** @return array{0: int, 1: int} */
    private function imageSize(string $imagePath): array
    {
        $size = @getimagesize($imagePath);

        return [
            (int) ($size[0] ?? 1),
            (int) ($size[1] ?? 1),
        ];
    }

    private function commandExists(string $command): bool
    {
        return $this->firstExistingCommand([$command]) !== null;
    }

    /** @param array<int, string> $commands */
    private function firstExistingCommand(array $commands): ?string
    {
        foreach ($commands as $command) {
            $check = DIRECTORY_SEPARATOR === '\\'
                ? 'where '.escapeshellarg($command).' 2>NUL'
                : 'command -v '.escapeshellarg($command).' 2>/dev/null';
            $output = [];
            $exitCode = 1;
            @exec($check, $output, $exitCode);

            if ($exitCode === 0 && $output !== []) {
                return $command;
            }
        }

        return null;
    }

    /** @param array<int, string> $command */
    private function runCommand(array $command): void
    {
        $escaped = implode(' ', array_map('escapeshellarg', $command));
        $output = [];
        $exitCode = 0;
        @exec($escaped.' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Renderer PDF gagal dijalankan: '.implode("\n", array_slice($output, -5)));
        }
    }

    /** @return array<int, string> */
    private function collectRenderedImages(string $tempDir, string $pattern): array
    {
        $images = [];

        foreach (scandir($tempDir) ?: [] as $file) {
            if (preg_match($pattern, $file, $matches)) {
                $images[((int) $matches[1]) - 1] = $tempDir.DIRECTORY_SEPARATOR.$file;
            }
        }

        ksort($images);

        return array_values($images);
    }

    private function makeTempDirectory(): string
    {
        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'pdf-highlight-'.bin2hex(random_bytes(8));

        if (! mkdir($base, 0777, true) && ! is_dir($base)) {
            throw new \RuntimeException('Gagal membuat folder sementara untuk deteksi highlight PDF.');
        }

        return $base;
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
