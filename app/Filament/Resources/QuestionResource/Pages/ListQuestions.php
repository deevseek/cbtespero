<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use App\Services\QuestionImport\GoogleFormImportService;
use App\Services\QuestionImport\PdfQuestionImportService;
use App\Services\QuestionImport\WordQuestionImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected static ?string $title = 'Bank Soal';

    protected function getHeaderActions(): array
    {
        return [
            $this->getImportAction(),
            Actions\CreateAction::make()
                ->label('Buat Soal Baru')
                ->icon('heroicon-m-plus'),
        ];
    }

    private function getImportAction(): Actions\Action
    {
        return Actions\Action::make('import_soal')
            ->label('Import Soal')
            ->icon('heroicon-m-arrow-up-tray')
            ->color('info')
            ->modalHeading('Import Soal')
            ->modalDescription('Pilih metode import, lengkapi data default, lalu sistem akan membaca dan menyimpan soal yang valid.')
            ->modalSubmitActionLabel('Import')
            ->form([
                Forms\Components\Select::make('method')
                    ->label('Metode Import')
                    ->options([
                        'google_form' => 'Import dari Google Form',
                        'pdf' => 'Import dari PDF',
                        'word' => 'Import dari Word',
                    ])
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('google_form_url')
                    ->label('URL Google Form')
                    ->placeholder('https://docs.google.com/forms/d/{FORM_ID}/edit')
                    ->url()
                    ->required(fn ($get): bool => $get('method') === 'google_form')
                    ->visible(fn ($get): bool => $get('method') === 'google_form'),
                Forms\Components\FileUpload::make('pdf_file')
                    ->label('Upload file PDF')
                    ->disk('local')
                    ->directory('question-imports')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->required(fn ($get): bool => $get('method') === 'pdf')
                    ->visible(fn ($get): bool => $get('method') === 'pdf'),
                Forms\Components\FileUpload::make('word_file')
                    ->label('Upload file DOCX')
                    ->disk('local')
                    ->directory('question-imports')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(10240)
                    ->required(fn ($get): bool => $get('method') === 'word')
                    ->visible(fn ($get): bool => $get('method') === 'word'),
                Forms\Components\TextInput::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('kelas')
                    ->label('Kelas')
                    ->helperText('Disimpan jika kolom kelas tersedia pada tabel questions.')
                    ->maxLength(255),
                Forms\Components\Select::make('tingkat_kesulitan')
                    ->label('Kesulitan default')
                    ->options([
                        'mudah' => 'Mudah',
                        'sedang' => 'Sedang',
                        'sulit' => 'Sulit',
                    ])
                    ->default('sedang')
                    ->required(),
                Forms\Components\TextInput::make('bobot_nilai')
                    ->label('Bobot default')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'draft' => 'Draft',
                    ])
                    ->default('draft')
                    ->helperText('Disimpan jika kolom status tersedia pada tabel questions.'),
            ])
            ->action(fn (array $data) => $this->handleImport($data));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleImport(array $data): void
    {
        try {
            $method = (string) ($data['method'] ?? '');
            $options = $this->importOptions($data);

            if ($method === 'google_form') {
                $service = app(GoogleFormImportService::class);
                $formId = $service->extractFormIdFromUrl((string) ($data['google_form_url'] ?? ''));
                $questions = $service->normalizeQuestions($service->fetchFormQuestions($formId));
                $result = $service->importToDatabase($questions, $options);
            } elseif ($method === 'pdf') {
                $service = app(PdfQuestionImportService::class);
                $questions = $service->parseFile($this->resolveUploadedPath($data['pdf_file'] ?? null));
                $result = $service->importToDatabase($questions, $options);
            } elseif ($method === 'word') {
                $service = app(WordQuestionImportService::class);
                $questions = $service->parseFile($this->resolveUploadedPath($data['word_file'] ?? null));
                $result = $service->importToDatabase($questions, $options);
            } else {
                throw new \InvalidArgumentException('Metode import tidak valid.');
            }

            $this->sendImportNotification($result);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Import soal gagal')
                ->body($exception->getMessage() ?: 'File tidak dapat dibaca atau format soal tidak dikenali.')
                ->danger()
                ->send();
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function importOptions(array $data): array
    {
        return [
            'mata_pelajaran' => $data['mata_pelajaran'] ?? null,
            'kelas' => $data['kelas'] ?? null,
            'tingkat_kesulitan' => $data['tingkat_kesulitan'] ?? 'sedang',
            'bobot_nilai' => $data['bobot_nilai'] ?? 1,
            'status' => $data['status'] ?? 'draft',
        ];
    }

    private function resolveUploadedPath(mixed $uploadedFile): string
    {
        $path = is_array($uploadedFile) ? reset($uploadedFile) : $uploadedFile;

        if (! is_string($path) || $path === '') {
            throw new \InvalidArgumentException('File import wajib diunggah.');
        }

        $absolutePath = Storage::disk('local')->path($path);

        if (! is_file($absolutePath)) {
            throw new \InvalidArgumentException('File import tidak ditemukan. Silakan unggah ulang file.');
        }

        return $absolutePath;
    }

    /**
     * @param array{created: int, review: int, failed: int, errors: array<int, string>} $result
     */
    private function sendImportNotification(array $result): void
    {
        $created = $result['created'];
        $needsReview = $result['review'] + $result['failed'];

        if ($created > 0 && $needsReview === 0) {
            Notification::make()
                ->title('Import soal berhasil')
                ->body("Berhasil mengimpor {$created} soal.")
                ->success()
                ->send();

            return;
        }

        if ($created > 0) {
            Notification::make()
                ->title('Import selesai dengan catatan')
                ->body("{$created} soal berhasil, {$needsReview} soal perlu diperiksa.")
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Import soal gagal')
            ->body('File tidak dapat dibaca atau format soal tidak dikenali. Pastikan soal memiliki opsi A-D dan kunci jawaban.')
            ->danger()
            ->send();
    }
}
