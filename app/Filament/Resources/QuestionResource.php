<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
use App\Models\QuestionImport;
use App\Support\FilamentNumberFormatter;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Tables\Table;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Bank Soal';
    protected static string | \UnitEnum | null $navigationGroup = 'AKADEMIK';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Soal';
    protected static ?string $pluralModelLabel = 'Bank Soal';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('mata_pelajaran')->label('Mapel'),
            Forms\Components\TextInput::make('kelas')->label('Kelas'),
            Forms\Components\Textarea::make('soal')->label('Soal')->rows(6)->columnSpanFull(),
            Forms\Components\Textarea::make('pilihan_a')->label('Opsi A')->rows(2)->required(),
            Forms\Components\Textarea::make('pilihan_b')->label('Opsi B')->rows(2)->required(),
            Forms\Components\Textarea::make('pilihan_c')->label('Opsi C')->rows(2)->required(),
            Forms\Components\Textarea::make('pilihan_d')->label('Opsi D')->rows(2)->required(),
            Forms\Components\Textarea::make('pilihan_e')->label('Opsi E')->rows(2),
            Forms\Components\Select::make('jawaban_benar')
                ->label('Jawaban Benar')
                ->options(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E'])
                ->native(false)
                ->placeholder('Belum ada kunci'),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(['aktif' => 'Aktif', 'draft' => 'Draft'])
                ->native(false)
                ->default('draft'),
            Forms\Components\Toggle::make('needs_review')
                ->label('Perlu Review / Ada Kunci Belum Valid')
                ->helperText('Aktifkan jika soal hasil import PDF masih perlu diperbaiki atau kunci jawaban belum pasti.'),
            Forms\Components\TextInput::make('tingkat_kesulitan')->label('Kesulitan'),
            Forms\Components\TextInput::make('bobot_nilai')->label('Nilai per Soal')->numeric()->helperText('Digunakan sebagai nilai/poin default untuk setiap soal yang berhasil diimport.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('importBatch'))
            ->columns([
                Tables\Columns\TextColumn::make('importBatch.source_name')
                    ->label('Sumber File')
                    ->default('Soal Manual / Tanpa Batch')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('mata_pelajaran')->label('Mapel')->badge()->color('info')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kelas')->label('Kelas')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('soal')->label('Ringkasan Soal')->limit(90)->wrap()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('jumlah_opsi')
                    ->label('Jumlah Opsi')
                    ->badge()
                    ->getStateUsing(fn (Question $record): int => collect(['a', 'b', 'c', 'd', 'e'])->filter(fn (string $key): bool => filled($record->{'pilihan_'.$key}))->count())
                    ->color(fn (int $state): string => $state >= 5 ? 'success' : ($state >= 4 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('jawaban_benar')->label('Jawaban Benar')->formatStateUsing(fn (?string $state): string => filled($state) ? strtoupper($state) : 'Belum ada')->badge()->color(fn (?string $state): string => filled($state) ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('tingkat_kesulitan')->label('Kesulitan')->badge()->color(fn (?string $state): string => match (strtolower((string) $state)) { 'mudah' => 'success', 'sulit' => 'danger', default => 'warning' })->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bobot_nilai')->label('Nilai per Soal')->formatStateUsing(fn (mixed $state): ?string => FilamentNumberFormatter::format($state))->sortable(),
                Tables\Columns\IconColumn::make('has_answer_key')->label('Ada Kunci')->boolean()->getStateUsing(fn (Question $record): bool => filled($record->jawaban_benar)),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->sortable(),
            ])
            ->filters(self::questionFilters())
            ->actions([\Filament\Actions\EditAction::make()->label('Edit')])
            ->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()->label('Hapus')])])
            ->emptyStateHeading('Belum ada soal')
            ->emptyStateDescription('Soal lama tanpa batch tetap tampil di mode Semua Soal sebagai Soal Manual / Tanpa Batch.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function questionFilters(): array
    {
        return [
            SelectFilter::make('question_import_id')
                ->label('File/Batch Import')
                ->options(fn (): array => QuestionImport::query()->orderByDesc('imported_at')->orderByDesc('id')->pluck('source_name', 'id')->all()),
            SelectFilter::make('mata_pelajaran')
                ->label('Mapel')
                ->options(fn (): array => Question::query()->whereNotNull('mata_pelajaran')->distinct()->orderBy('mata_pelajaran')->pluck('mata_pelajaran', 'mata_pelajaran')->all()),
            SelectFilter::make('kelas')
                ->label('Kelas')
                ->options(fn (): array => Question::query()->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas', 'kelas')->all()),
            SelectFilter::make('tingkat_kesulitan')
                ->label('Kesulitan')
                ->options(['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit']),
            SelectFilter::make('status')
                ->label('Status')
                ->options(['aktif' => 'Aktif', 'draft' => 'Draft']),
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
        ];
    }

    public static function sourceTypeLabel(?string $state): string
    {
        return match ($state) {
            'word' => 'Word',
            'pdf' => 'PDF',
            'google_form' => 'Google Form',
            'manual' => 'Manual',
            default => filled($state) ? Str::of($state)->replace('_', ' ')->title()->toString() : '-',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestionBatches::route('/'),
            'all' => Pages\ListQuestions::route('/semua-soal'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
            'batch' => Pages\ViewQuestionBatch::route('/batch/{record}'),
        ];
    }
}
