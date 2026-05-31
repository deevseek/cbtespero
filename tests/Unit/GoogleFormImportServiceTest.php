<?php

namespace Tests\Unit;

use App\Services\QuestionImport\GoogleFormImportService;
use PHPUnit\Framework\TestCase;

class GoogleFormImportServiceTest extends TestCase
{
    public function test_extracts_form_id_from_edit_url_with_query_string(): void
    {
        $service = new GoogleFormImportService();

        $this->assertSame(
            '1aXb4zD6fbOsK8hpBUA9szzX_5Sncvc3z9msrMjyvDzs',
            $service->extractFormIdFromUrl('https://docs.google.com/forms/d/1aXb4zD6fbOsK8hpBUA9szzX_5Sncvc3z9msrMjyvDzs/edit?ts=6a1bceaf')
        );
    }

    public function test_normalizes_all_sections_and_ignores_identity_name_dropdown(): void
    {
        $service = new GoogleFormImportService();

        $summary = $service->normalizeQuestionsWithSummary([
            [null, 'NAME', null, null, [[null, [
                ['Andi Saputra'],
                ['Budi Santoso'],
                ['Citra Lestari'],
                ['Dewi Anggraini'],
                ['Eko Prasetyo'],
            ], null, 3]]],
            [null, 'What is the purpose of the procedure text?', null, null, [[null, [
                ['To explain steps'],
                ['To entertain readers'],
                ['To describe a person'],
                ['To retell past events'],
            ], null, 2]]],
            [null, 'Write the conclusion of the text.', null, null, [[null, [], null, 1]]],
        ]);

        $this->assertSame(1, $summary['ignored_identity']);
        $this->assertSame(0, $summary['failed']);
        $this->assertCount(2, $summary['questions']);
        $this->assertSame('What is the purpose of the procedure text?', $summary['questions'][0]['question_text']);
        $this->assertSame('To explain steps', $summary['questions'][0]['options']['A']);
        $this->assertNull($summary['questions'][0]['correct_answer']);
        $this->assertTrue($summary['questions'][0]['needs_review']);
        $this->assertSame('paragraph', $summary['questions'][1]['type']);
    }
}
