<?php

namespace Tests\Unit;

use App\Services\QuestionImport\PdfHighlightAnswerDetector;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class PdfHighlightAnswerDetectorTest extends TestCase
{
    public function test_reads_answer_from_highlight_annotation_contents_when_available(): void
    {
        $detector = new PdfHighlightAnswerDetector();
        $questions = new ReflectionProperty($detector, 'questions');
        $questions->setAccessible(true);
        $questions->setValue($detector, [[
            'question_text' => 'The story is mainly about',
            'options' => [
                'A' => 'White and black pebbles',
                'B' => 'A clever girl and a wicked moneylender',
                'C' => 'A misfortune merchant and his daughter',
                'D' => 'A clever moneylender and a dull girl',
                'E' => 'A merchant and a genial moneylender',
            ],
        ]]);

        $pdf = <<<'PDF'
%PDF-1.7
1 0 obj
<< /Type /Annot /Subtype /Highlight /Contents (B. A clever girl and a wicked moneylender) >>
endobj
PDF;
        $path = tempnam(sys_get_temp_dir(), 'highlight-');
        file_put_contents($path, $pdf);

        try {
            $this->assertSame([1 => 'B'], $detector->detectAnswersFromAnnotations($path));
        } finally {
            @unlink($path);
        }
    }
}
