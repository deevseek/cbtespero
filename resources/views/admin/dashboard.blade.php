@extends('layouts.app')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-slate-800">Dashboard Overview</h2>
        <p class="mt-1 text-sm text-slate-500">Ringkasan aktivitas ujian dan performa siswa hari ini.</p>
    </div>
</div>

@php
    $stats = [
        [
            'label' => 'Total siswa',
            'value' => $totalSiswa,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.742-.479 3 3 0 0 0-4.682-2.72m.94 3.198a6.115 6.115 0 0 0-.94-.198M15 17.25a48.172 48.172 0 0 1-6 0M15 17.25v.162c0 1.113.285 2.19.784 3.132M9 17.25v.162c0 1.113-.285 2.19-.784 3.132M9.75 8.25a3 3 0 1 1 4.5 0 3 3 0 0 1-4.5 0ZM3.75 17.25a3.75 3.75 0 1 1 7.5 0v.162a48.058 48.058 0 0 1-7.5 0v-.162Z" /></svg>',
        ],
        [
            'label' => 'Total soal',
            'value' => $totalSoal,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9A2.25 2.25 0 0 1 5.25 16.5v-9A2.25 2.25 0 0 1 7.5 5.25h9A2.25 2.25 0 0 1 18.75 7.5v9A2.25 2.25 0 0 1 16.5 18.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75h6m-6 3h6m-6 3h3" /></svg>',
        ],
        [
            'label' => 'Ujian aktif',
            'value' => $totalUjianAktif,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
        ],
        [
            'label' => 'Ujian selesai',
            'value' => $totalUjianSelesai,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>',
        ],
    ];
@endphp

<div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-4">
    @foreach($stats as $stat)
        <div class="rounded-xl border bg-white p-6 shadow-sm transition duration-200 hover:shadow-md">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                {!! $stat['icon'] !!}
            </div>
            <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="rounded-xl bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-xl font-semibold text-slate-800">Peserta ujian hari ini</h3>
        <canvas id="participantsChart" height="120"></canvas>
    </div>

    <div class="rounded-xl bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-xl font-semibold text-slate-800">Nilai rata rata ujian</h3>
        <canvas id="avgScoreChart" height="120"></canvas>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const avgScoreLabels = @json($avgScores->pluck('tanggal'));
        const avgScoreData = @json($avgScores->pluck('rata_nilai'));

        new Chart(document.getElementById('participantsChart'), {
            type: 'bar',
            data: {
                labels: ['Hari ini'],
                datasets: [{
                    label: 'Peserta',
                    data: [{{ $todayParticipants }}],
                    backgroundColor: '#6366f1',
                    borderRadius: 8,
                    barThickness: 40,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });

        new Chart(document.getElementById('avgScoreChart'), {
            type: 'line',
            data: {
                labels: avgScoreLabels,
                datasets: [{
                    label: 'Rata-rata nilai',
                    data: avgScoreData,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.15)',
                    fill: true,
                    tension: 0.35,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    </script>
@endpush
