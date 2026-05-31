<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use App\Models\Question;
use App\Models\QuestionImport;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewQuestionBatch extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = QuestionResource::class;

    protected string $view = 'filament.resources.question-resource.pages.view-question-batch';

    public QuestionImport $record;

    public function mount(QuestionImport $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Bank Soal > '.$this->record->display_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Batch Import')
                ->url(QuestionResource::getUrl('index'))
                ->icon('heroicon-m-arrow-left'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Question::query()->where('question_import_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('soal')->label('Soal')->limit(100)->wrap()->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->default('Pilihan Ganda'),
                Tables\Columns\TextColumn::make('tingkat_kesulitan')->label('Kesulitan')->badge()->sortable(),
                Tables\Columns\TextColumn::make('bobot_nilai')->label('Nilai per Soal')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('jawaban_benar')->label('Jawaban Benar')->formatStateUsing(fn (?string $state): string => filled($state) ? strtoupper($state) : 'Belum ada')->badge()->color(fn (?string $state): string => filled($state) ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(['aktif' => 'Aktif', 'draft' => 'Draft']),
                SelectFilter::make('tingkat_kesulitan')->label('Kesulitan')->options(['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit']),
                TernaryFilter::make('has_answer_key')
                    ->label('Kunci Jawaban')
                    ->placeholder('Semua')
                    ->trueLabel('Ada kunci jawaban')
                    ->falseLabel('Belum ada kunci')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('jawaban_benar'),
                        false: fn (Builder $query): Builder => $query->whereNull('jawaban_benar'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->label('Edit soal')
                    ->url(fn (Question $record): string => QuestionResource::getUrl('edit', ['record' => $record])),
                \Filament\Actions\Action::make('review')
                    ->label('Review soal')
                    ->icon('heroicon-m-check-circle')
                    ->color('warning')
                    ->action(fn (Question $record) => $record->update(['needs_review' => false, 'status' => filled($record->jawaban_benar) ? 'aktif' : 'draft']))
                    ->visible(fn (Question $record): bool => (bool) $record->needs_review),
                \Filament\Actions\DeleteAction::make()->label('Hapus soal'),
            ])
            ->emptyStateHeading('Belum ada soal pada batch ini')
            ->emptyStateDescription('Soal yang berasal dari file upload ini akan tampil di sini.');
    }
}
