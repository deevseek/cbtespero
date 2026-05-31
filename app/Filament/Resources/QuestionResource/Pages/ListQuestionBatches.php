<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use App\Models\QuestionImport;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListQuestionBatches extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = QuestionResource::class;

    protected static ?string $title = 'Bank Soal';

    protected string $view = 'filament.resources.question-resource.pages.list-question-batches';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('batch_import')
                ->label('Batch Import')
                ->disabled()
                ->color('gray'),
            Actions\Action::make('semua_soal')
                ->label('Semua Soal')
                ->url(QuestionResource::getUrl('all', ['tableTab' => 'semua_soal']))
                ->icon('heroicon-m-list-bullet'),
            Actions\Action::make('import_soal')
                ->label('Import Soal')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('info')
                ->url(QuestionResource::getUrl('all')),
            Actions\CreateAction::make()
                ->label('Buat Soal Baru')
                ->url(QuestionResource::getUrl('create'))
                ->icon('heroicon-m-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(QuestionImport::query()->withCount('questions')->latest('imported_at')->latest('id'))
            ->columns([
                Tables\Columns\TextColumn::make('source_name')
                    ->label('Nama File / Sumber')
                    ->default('Soal Manual / Tanpa Batch')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('source_type')
                    ->label('Metode')
                    ->formatStateUsing(fn (?string $state): string => QuestionResource::sourceTypeLabel($state))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('Mapel')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('class_level')->label('Kelas')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Jumlah Soal')
                    ->formatStateUsing(fn (int $state): string => $state.' soal')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('needs_review_count')
                    ->label('Perlu Review')
                    ->formatStateUsing(fn (int $state): string => $state.' review')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('imported_at')->label('Tanggal Import')->dateTime('d M Y H:i')->sortable(),
            ])
            ->actions([
                \Filament\Actions\Action::make('lihat_soal')
                    ->label('Lihat Soal')
                    ->icon('heroicon-m-eye')
                    ->url(fn (QuestionImport $record): string => QuestionResource::getUrl('batch', ['record' => $record])),
                \Filament\Actions\EditAction::make()
                    ->label('Edit Info')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('source_name')->label('Sumber File')->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('subject')->label('Mapel')->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('class_level')->label('Kelas')->helperText('Contoh: X, XI, XII, atau nama kelas sesuai data sekolah.')->maxLength(255),
                        \Filament\Forms\Components\Select::make('difficulty')->label('Kesulitan')->options(['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit']),
                        \Filament\Forms\Components\TextInput::make('default_weight')->label('Nilai per Soal')->numeric()->minValue(1)->helperText('Digunakan sebagai nilai/poin default untuk setiap soal yang berhasil diimport.'),
                        \Filament\Forms\Components\Select::make('status')->label('Status')->options(['draft' => 'Draft', 'active' => 'Active', 'imported' => 'Imported']),
                    ]),
                \Filament\Actions\DeleteAction::make()->label('Hapus Batch'),
            ])
            ->emptyStateHeading('Belum ada batch soal')
            ->emptyStateDescription('Upload Word, PDF, atau Google Form untuk menambahkan soal ke Bank Soal.')
            ->emptyStateIcon('heroicon-o-folder-open');
    }
}
