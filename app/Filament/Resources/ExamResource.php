<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResource\Pages;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionImport;
use App\Services\ExamStatusService;
use App\Services\QuestionImport\GoogleFormImportService;
use App\Services\QuestionImport\PdfQuestionImportService;
use App\Services\QuestionImport\WordQuestionImportService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Ujian';
    protected static string | \UnitEnum | null $navigationGroup = 'AKADEMIK';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Ujian';
    protected static ?string $pluralModelLabel = 'Ujian';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            self::examDetailsSection(),
            self::examQuestionsSection(),
            Section::make('Keamanan CBT')->relationship('securitySetting')->schema([
                Forms\Components\Toggle::make('require_fullscreen')->label('Wajib Fullscreen')->default(true),
                Forms\Components\Toggle::make('block_screenshot')->label('Blok Screenshot')->default(true),
                Forms\Components\Toggle::make('device_binding')->label('Kunci Perangkat')->default(true),
                Forms\Components\Toggle::make('auto_submit_on_cheat')->label('Auto Submit saat Pelanggaran')->default(false),
                Forms\Components\Toggle::make('randomize_questions')->label('Acak Soal')->default(true),
                Forms\Components\Toggle::make('randomize_answers')->label('Acak Jawaban')->default(true),
                Forms\Components\Toggle::make('allow_reentry')->label('Izinkan Masuk Ulang')->default(true),
                Forms\Components\Toggle::make('show_result_after_exam')->label('Tampilkan Hasil')->default(false),
                Forms\Components\TextInput::make('max_app_exit')->label('Maks. Keluar App')->numeric()->default(3),
                Forms\Components\TextInput::make('max_fullscreen_exit')->label('Maks. Keluar Fullscreen')->numeric()->default(3),
                Forms\Components\TextInput::make('max_heartbeat_missed')->label('Toleransi Heartbeat')->numeric()->default(3),
                Forms\Components\TextInput::make('connection_tolerance_seconds')->label('Toleransi Koneksi (detik)')->numeric()->default(60),
                Forms\Components\TextInput::make('max_relogin')->label('Maks. Relogin')->numeric()->default(1),
                Forms\Components\Select::make('orientation')->label('Orientasi')->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])->default('portrait'),
            ])->columns(2),
        ]);
    }

    public static function examDetailsSection(): Section
    {
        return Section::make('Data Ujian')->schema(self::examDetailsFormComponents())->columns(2);
    }

    public static function examDetailsFormComponents(): array
    {
        return [
            Forms\Components\TextInput::make('nama_ujian')->label('Nama Ujian')->required(),
            Forms\Components\TextInput::make('mata_pelajaran')->label('Mata Pelajaran')->required(),
            Forms\Components\TextInput::make('kelas')->label('Kelas')->required(),
            Forms\Components\DatePicker::make('tanggal_ujian')->label('Tanggal Ujian')->required(),
            Forms\Components\TimePicker::make('jam_mulai')->label('Jam Mulai')->required(),
            Forms\Components\TimePicker::make('jam_selesai')->label('Jam Selesai')->required(),
            Forms\Components\TextInput::make('durasi')->label('Durasi')->numeric()->required(),
            Forms\Components\TextInput::make('jumlah_soal')->label('Jumlah Soal')->numeric()->required()->readOnly()->helperText('Otomatis mengikuti jumlah soal terpilih, batch, atau upload baru.'),
            Forms\Components\Select::make('status')->label('Status')->options(self::statusOptions())->default('draft')->required(),
            Forms\Components\TextInput::make('token')
                ->label('Token Lama')
                ->maxLength(5)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                ->helperText('Opsional. Token utama sebaiknya dibuat lewat menu Token Ujian.'),
        ];
    }


    public static function examQuestionsSection(): Section
    {
        return Section::make('Soal Ujian')->schema([
            Forms\Components\Select::make('question_source_mode')
                ->label('Pilih sumber soal')
                ->options([
                    'bank' => 'Pilih dari Bank Soal',
                    'batch' => 'Ambil dari Batch/File Upload',
                    'upload' => 'Upload Soal Baru',
                ])
                ->default('bank')
                ->live()
                ->dehydrated(),
            Forms\Components\Select::make('selected_question_ids')
                ->label('Pilih dari Bank Soal')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn (): array => Question::query()
                    ->orderByDesc('id')
                    ->limit(300)
                    ->get()
                    ->mapWithKeys(fn (Question $question): array => [$question->id => Str::limit(strip_tags($question->soal), 100).' — '.$question->mata_pelajaran.' / '.($question->kelas ?: '-')])
                    ->all())
                ->default(fn (?Exam $record): array => $record?->questions()->pluck('questions.id')->all() ?? [])
                ->helperText('Gunakan pencarian dan filter Bank Soal untuk menemukan soal lintas batch. Jumlah soal terpilih akan dihitung otomatis.')
                ->live()
                ->afterStateUpdated(fn ($state, $set) => $set('jumlah_soal', count((array) $state)))
                ->visible(fn ($get): bool => $get('question_source_mode') === 'bank')
                ->dehydrated(),
            Forms\Components\Select::make('selected_batch_id')
                ->label('Pilih Batch Soal')
                ->searchable()
                ->preload()
                ->options(fn (): array => QuestionImport::query()->withCount('questions')->latest('imported_at')->latest('id')->get()->mapWithKeys(fn (QuestionImport $batch): array => [
                    $batch->id => ($batch->source_name ?: 'Batch #'.$batch->id).' — '.$batch->questions_count.' soal',
                ])->all())
                ->live()
                ->afterStateUpdated(function ($state, $set, $get): void {
                    $count = $state ? Question::where('question_import_id', $state)->count() : 0;
                    if ($get('use_all_batch_questions') !== false) {
                        $set('jumlah_soal', $count);
                    }
                })
                ->visible(fn ($get): bool => $get('question_source_mode') === 'batch')
                ->dehydrated(),
            Forms\Components\Placeholder::make('batch_question_count')
                ->label('Jumlah soal dalam batch')
                ->content(fn ($get): string => $get('selected_batch_id') ? Question::where('question_import_id', $get('selected_batch_id'))->count().' soal tersedia' : 'Pilih batch terlebih dahulu')
                ->visible(fn ($get): bool => $get('question_source_mode') === 'batch'),
            Forms\Components\Toggle::make('use_all_batch_questions')
                ->label('Gunakan semua soal dari batch')
                ->default(true)
                ->live()
                ->afterStateUpdated(function ($state, $set, $get): void {
                    if ($state && $get('selected_batch_id')) {
                        $set('jumlah_soal', Question::where('question_import_id', $get('selected_batch_id'))->count());
                    }
                })
                ->visible(fn ($get): bool => $get('question_source_mode') === 'batch')
                ->dehydrated(),
            Forms\Components\Toggle::make('randomize_batch_subset')
                ->label('Gunakan sebagian secara acak')
                ->default(false)
                ->live()
                ->visible(fn ($get): bool => $get('question_source_mode') === 'batch' && ! $get('use_all_batch_questions'))
                ->dehydrated(),
            Forms\Components\TextInput::make('random_question_count')
                ->label('Jumlah soal diambil')
                ->numeric()
                ->minValue(1)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('jumlah_soal', (int) $state))
                ->visible(fn ($get): bool => $get('question_source_mode') === 'batch' && ! $get('use_all_batch_questions') && $get('randomize_batch_subset'))
                ->dehydrated(),
            Forms\Components\Select::make('selected_batch_question_ids')
                ->label('Pilih sebagian soal')
                ->multiple()
                ->searchable()
                ->options(fn ($get): array => Question::query()
                    ->where('question_import_id', $get('selected_batch_id'))
                    ->get()
                    ->mapWithKeys(fn (Question $question): array => [$question->id => Str::limit(strip_tags($question->soal), 100)])
                    ->all())
                ->live()
                ->afterStateUpdated(fn ($state, $set) => $set('jumlah_soal', count((array) $state)))
                ->visible(fn ($get): bool => $get('question_source_mode') === 'batch' && ! $get('use_all_batch_questions') && ! $get('randomize_batch_subset'))
                ->dehydrated(),
            Forms\Components\Select::make('upload_method')
                ->label('Upload Soal Baru')
                ->options([
                    'google_form' => 'Google Form',
                    'word' => 'Word/DOCX',
                    'pdf' => 'PDF',
                ])
                ->live()
                ->visible(fn ($get): bool => $get('question_source_mode') === 'upload')
                ->dehydrated(),
            Forms\Components\TextInput::make('upload_google_form_url')
                ->label('URL Google Form')
                ->url()
                ->required(fn ($get): bool => $get('question_source_mode') === 'upload' && $get('upload_method') === 'google_form')
                ->visible(fn ($get): bool => $get('question_source_mode') === 'upload' && $get('upload_method') === 'google_form')
                ->dehydrated(),
            Forms\Components\FileUpload::make('upload_word_file')
                ->label('Upload file DOCX')
                ->disk('local')
                ->directory('question-imports')
                ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                ->maxSize(10240)
                ->required(fn ($get): bool => $get('question_source_mode') === 'upload' && $get('upload_method') === 'word')
                ->visible(fn ($get): bool => $get('question_source_mode') === 'upload' && $get('upload_method') === 'word')
                ->dehydrated(),
            Forms\Components\FileUpload::make('upload_pdf_file')
                ->label('Upload file PDF')
                ->disk('local')
                ->directory('question-imports')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(10240)
                ->required(fn ($get): bool => $get('question_source_mode') === 'upload' && $get('upload_method') === 'pdf')
                ->visible(fn ($get): bool => $get('question_source_mode') === 'upload' && $get('upload_method') === 'pdf')
                ->dehydrated(),
            Forms\Components\Placeholder::make('selected_question_count')
                ->label('Jumlah soal terpilih')
                ->content(fn ($get): string => (int) ($get('jumlah_soal') ?: 0).' soal'),
        ])->columns(2);
    }

    public static function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'aktif' => 'Berlangsung',
            'berlangsung' => 'Berlangsung',
            'terjadwal' => 'Terjadwal',
            'belum_dimulai' => 'Belum Dimulai',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_ujian')->label('Nama Ujian')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('mata_pelajaran')->label('Mata Pelajaran')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kelas')->label('Kelas')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tanggal_ujian')->label('Tanggal Ujian')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('jumlah_soal')->label('Jumlah Soal')->alignCenter()->sortable(),
                Tables\Columns\TextColumn::make('question_source')->label('Sumber Soal')->getStateUsing(fn (Exam $record): string => self::questionSourceLabel($record)),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (?string $state): string => self::statusLabel($state))->color(fn (?string $state): string => self::statusColor($state)),
                Tables\Columns\TextColumn::make('results_count')->label('Peserta')->counts('results')->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options([
                    'draft' => 'Draft',
                    'aktif' => 'Berlangsung',
                    'berlangsung' => 'Berlangsung',
                    'terjadwal' => 'Terjadwal',
                    'belum_dimulai' => 'Belum Dimulai',
                    'selesai' => 'Selesai',
                    'dibatalkan' => 'Dibatalkan',
                ]),
                SelectFilter::make('kelas')->label('Kelas')->options(fn (): array => Exam::query()->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas', 'kelas')->all()),
                SelectFilter::make('mata_pelajaran')->label('Mapel')->options(fn (): array => Exam::query()->whereNotNull('mata_pelajaran')->distinct()->orderBy('mata_pelajaran')->pluck('mata_pelajaran', 'mata_pelajaran')->all()),
            ])
            ->actions([
                \Filament\Actions\Action::make('export_report')->label('Export Laporan')->icon('heroicon-m-arrow-down-tray')->color('success')->url(fn (Exam $record): string => route('filament.admin.exams.export', $record))->openUrlInNewTab(),
                \Filament\Actions\Action::make('regenerate_token')->label('Regenerate Token')->icon('heroicon-m-arrow-path')->color('info')->action(fn (Exam $record) => $record->update(['token' => strtoupper(Str::random(5))])),
                \Filament\Actions\EditAction::make()->label('Edit'),
            ])
            ->emptyStateHeading('Belum ada ujian')
            ->emptyStateDescription('Buat ujian baru untuk mulai mengatur jadwal CBT Julia.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function statusLabel(?string $state): string
    {
        $exam = new Exam(['status' => $state]);

        return app(ExamStatusService::class)->getAdminStatus($exam);
    }

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'aktif', 'berlangsung' => 'info',
            'selesai' => 'success',
            'terjadwal', 'belum_dimulai' => 'warning',
            'dibatalkan' => 'danger',
            'draft' => 'gray',
            default => 'gray',
        };
    }


    /**
     * @param array<string, mixed> $data
     * @return array<int, int>
     */
    public static function resolveQuestionIdsFromSelection(array $data): array
    {
        $mode = $data['question_source_mode'] ?? 'bank';

        if ($mode === 'bank') {
            return array_values(array_filter(array_map('intval', (array) ($data['selected_question_ids'] ?? []))));
        }

        if ($mode === 'batch') {
            $batchId = $data['selected_batch_id'] ?? null;
            if (! $batchId) {
                return [];
            }

            $query = Question::query()->where('question_import_id', $batchId);

            if (($data['use_all_batch_questions'] ?? true) === true || ($data['use_all_batch_questions'] ?? true) === '1') {
                return $query->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
            }

            if ($data['randomize_batch_subset'] ?? false) {
                return $query->inRandomOrder()->limit((int) ($data['random_question_count'] ?? 0))->pluck('id')->map(fn ($id): int => (int) $id)->all();
            }

            return array_values(array_filter(array_map('intval', (array) ($data['selected_batch_question_ids'] ?? []))));
        }

        if ($mode === 'upload') {
            $result = self::importQuestionsFromExamUpload($data);

            return $result['question_ids'];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{created: int, question_ids: array<int, int>}
     */
    public static function importQuestionsFromExamUpload(array $data): array
    {
        $method = (string) ($data['upload_method'] ?? '');
        $options = [
            'mata_pelajaran' => $data['mata_pelajaran'] ?? null,
            'kelas' => $data['kelas'] ?? null,
            'tingkat_kesulitan' => 'sedang',
            'bobot_nilai' => 1,
            'status' => 'draft',
        ];

        if ($method === 'google_form') {
            $service = app(GoogleFormImportService::class);
            $url = (string) ($data['upload_google_form_url'] ?? '');
            $formId = $service->extractFormIdFromUrl($url);
            $summary = $service->normalizeQuestionsWithSummary($service->fetchFormQuestions($formId));
            $options['source_name'] = 'Google Form '.Str::limit($formId, 16, '');
            $options['source_url'] = $url;
            $options['pre_failed_questions'] = $summary['failed'];
            $result = $service->importToDatabase($summary['questions'], $options);
        } elseif ($method === 'word') {
            $service = app(WordQuestionImportService::class);
            $storagePath = self::uploadedStoragePath($data['upload_word_file'] ?? null);
            $options['original_filename'] = basename($storagePath);
            $options['source_name'] = $options['original_filename'];
            $result = $service->importToDatabase($service->parseFile(Storage::disk('local')->path($storagePath)), $options);
        } elseif ($method === 'pdf') {
            $service = app(PdfQuestionImportService::class);
            $storagePath = self::uploadedStoragePath($data['upload_pdf_file'] ?? null);
            $options['original_filename'] = basename($storagePath);
            $options['source_name'] = $options['original_filename'];
            $result = $service->importToDatabase($service->parseFile(Storage::disk('local')->path($storagePath)), $options);
        } else {
            return ['created' => 0, 'question_ids' => []];
        }

        return [
            'created' => (int) $result['created'],
            'question_ids' => array_values(array_map('intval', $result['question_ids'] ?? [])),
        ];
    }

    public static function uploadedStoragePath(mixed $uploadedFile): string
    {
        $path = is_array($uploadedFile) ? reset($uploadedFile) : $uploadedFile;

        return is_string($path) ? $path : '';
    }

    public static function syncExamQuestions(Exam $exam, array $questionIds): void
    {
        $sync = [];
        foreach (array_values(array_unique($questionIds)) as $index => $questionId) {
            $question = Question::find($questionId);
            if (! $question) {
                continue;
            }

            $sync[$question->id] = [
                'order_number' => $index + 1,
                'score' => $question->bobot_nilai,
            ];
        }

        $exam->questions()->sync($sync);
        $exam->updateQuietly(['jumlah_soal' => count($sync)]);
    }

    public static function questionSourceLabel(Exam $exam): string
    {
        $batchNames = $exam->questions()
            ->with('importBatch')
            ->get()
            ->map(fn (Question $question): ?string => $question->importBatch?->source_name ?: ($question->question_import_id ? 'Batch #'.$question->question_import_id : null))
            ->filter()
            ->unique()
            ->values();

        if ($batchNames->count() > 1) {
            return 'Beberapa Batch';
        }

        if ($batchNames->count() === 1) {
            return (string) $batchNames->first();
        }

        return $exam->questions()->exists() ? 'Bank Soal' : 'Manual';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
