<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    /** @var array<string, mixed> */
    private array $questionSelectionData = [];

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->questionSelectionData = $data;
        $questionIds = ($data['question_source_mode'] ?? null) === 'upload'
            ? []
            : ExamResource::resolveQuestionIdsFromSelection($data);
        $data['jumlah_soal'] = ($data['question_source_mode'] ?? null) === 'upload'
            ? (int) ($data['jumlah_soal'] ?? 0)
            : (count($questionIds) ?: (int) ($data['jumlah_soal'] ?? 0));

        return $this->removeQuestionSelectionFields($data);
    }

    protected function afterSave(): void
    {
        $questionIds = ExamResource::resolveQuestionIdsFromSelection($this->questionSelectionData);
        ExamResource::syncExamQuestions($this->record, $questionIds);

        if (($this->questionSelectionData['question_source_mode'] ?? null) === 'upload') {
            Notification::make()
                ->title('Soal berhasil ditambahkan ke ujian')
                ->body(count($questionIds).' soal berhasil diimport dan ditambahkan ke ujian.')
                ->success()
                ->send();
        }
    }

    /** @param array<string, mixed> $data */
    private function removeQuestionSelectionFields(array $data): array
    {
        foreach (['question_source_mode', 'selected_question_ids', 'selected_batch_id', 'use_all_batch_questions', 'randomize_batch_subset', 'random_question_count', 'selected_batch_question_ids', 'upload_method', 'upload_google_form_url', 'upload_word_file', 'upload_pdf_file'] as $field) {
            unset($data[$field]);
        }

        return $data;
    }
}
