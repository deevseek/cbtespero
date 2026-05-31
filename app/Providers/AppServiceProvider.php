<?php

namespace App\Providers;

use App\Filament\Widgets\CbtStatsOverview;
use App\Filament\Widgets\LatestExamLogsTable;
use App\Filament\Widgets\LatestExamsTable;
use App\Models\Student;
use App\Policies\StudentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);

        Livewire::component('app.filament.widgets.cbt-stats-overview', CbtStatsOverview::class);
        Livewire::component('app.filament.widgets.latest-exams-table', LatestExamsTable::class);
        Livewire::component('app.filament.widgets.latest-exam-logs-table', LatestExamLogsTable::class);
    }
}
