<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResource\Pages;
use App\Models\Exam;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Ujian';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Ujian')->schema([
                Forms\Components\TextInput::make('nama_ujian')->required(),
                Forms\Components\TextInput::make('mata_pelajaran')->required(),
                Forms\Components\TextInput::make('kelas')->required(),
                Forms\Components\DatePicker::make('tanggal_ujian')->required(),
                Forms\Components\TimePicker::make('jam_mulai')->required(),
                Forms\Components\TimePicker::make('jam_selesai')->required(),
                Forms\Components\TextInput::make('durasi')->numeric()->required(),
                Forms\Components\TextInput::make('jumlah_soal')->numeric()->required(),
                Forms\Components\Select::make('status')->options(['draft' => 'Draft', 'aktif' => 'Aktif', 'selesai' => 'Selesai'])->required(),
                Forms\Components\TextInput::make('token')->maxLength(5)->helperText('Token lama tetap dipertahankan untuk kompatibilitas.'),
            ])->columns(2),
            Forms\Components\Section::make('Keamanan CBT')->relationship('securitySetting')->schema([
                Forms\Components\Toggle::make('require_fullscreen')->default(true),
                Forms\Components\Toggle::make('block_screenshot')->default(true),
                Forms\Components\Toggle::make('device_binding')->default(true),
                Forms\Components\Toggle::make('auto_submit_on_cheat')->default(false),
                Forms\Components\Toggle::make('randomize_questions')->default(true),
                Forms\Components\Toggle::make('randomize_answers')->default(true),
                Forms\Components\Toggle::make('allow_reentry')->default(true),
                Forms\Components\Toggle::make('show_result_after_exam')->default(false),
                Forms\Components\TextInput::make('max_app_exit')->numeric()->default(3),
                Forms\Components\TextInput::make('max_fullscreen_exit')->numeric()->default(3),
                Forms\Components\TextInput::make('max_heartbeat_missed')->numeric()->default(3),
                Forms\Components\TextInput::make('connection_tolerance_seconds')->numeric()->default(60),
                Forms\Components\TextInput::make('max_relogin')->numeric()->default(1),
                Forms\Components\Select::make('orientation')->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])->default('portrait'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nama_ujian')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('mata_pelajaran')->sortable(),
            Tables\Columns\TextColumn::make('kelas')->sortable(),
            Tables\Columns\TextColumn::make('tanggal_ujian')->date()->sortable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('token')->label('Token'),
        ])->actions([
            Tables\Actions\Action::make('regenerate_token')->label('Regenerate Token')->action(fn (Exam $record) => $record->update(['token' => strtoupper(Str::random(5))])),
            Tables\Actions\EditAction::make(),
        ]);
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
