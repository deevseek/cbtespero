@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-6">
    <div class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-slate-800">Selamat Datang, Administrator!</h2>
                <p class="mt-1 text-sm text-slate-500">Ini adalah ringkasan aktivitas sekolah Anda hari ini.</p>
            </div>
        </div>

        <div class="w-full rounded-xl border border-dashed border-indigo-200 bg-indigo-50 p-4 lg:w-64">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-500">QR Login Admin</p>
            <div class="mt-3 flex items-center justify-between gap-3">
                <div class="rounded-lg bg-white p-2 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-10 w-10 text-indigo-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h5.25v5.25H3.75V3.75Zm0 11.25h5.25v5.25H3.75V15Zm11.25-11.25h5.25v5.25H15V3.75Zm-3.75 7.5h1.5m1.5 0h1.5m1.5 0h1.5m-7.5 1.5h1.5m3 0h1.5m-6 1.5h1.5m1.5 0h1.5m1.5 0h1.5m-7.5 1.5h1.5m3 0h1.5" />
                    </svg>
                </div>
                <p class="text-xs text-slate-500">Scan untuk akses cepat panel admin dengan perangkat terdaftar.</p>
            </div>
        </div>
    </div>

    @php
        $stats = [
            [
                'label' => 'Total siswa',
                'value' => $totalSiswa,
                'gradient' => 'bg-gradient-to-r from-blue-500 to-cyan-500',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.742-.479 3 3 0 0 0-4.682-2.72m.94 3.198a6.115 6.115 0 0 0-.94-.198M15 17.25a48.172 48.172 0 0 1-6 0M15 17.25v.162c0 1.113.285 2.19.784 3.132M9 17.25v.162c0 1.113-.285 2.19-.784 3.132M9.75 8.25a3 3 0 1 1 4.5 0 3 3 0 0 1-4.5 0ZM3.75 17.25a3.75 3.75 0 1 1 7.5 0v.162a48.058 48.058 0 0 1-7.5 0v-.162Z" /></svg>',
            ],
            [
                'label' => 'Total ujian',
                'value' => $totalUjianAktif + $totalUjianSelesai,
                'gradient' => 'bg-gradient-to-r from-green-500 to-emerald-500',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h12a2.25 2.25 0 0 0 2.25-2.25V3M3.75 18.75h16.5" /></svg>',
            ],
            [
                'label' => 'Total soal',
                'value' => $totalSoal,
                'gradient' => 'bg-gradient-to-r from-orange-500 to-yellow-500',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9A2.25 2.25 0 0 1 5.25 16.5v-9A2.25 2.25 0 0 1 7.5 5.25h9A2.25 2.25 0 0 1 18.75 7.5v9A2.25 2.25 0 0 1 16.5 18.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75h6m-6 3h6m-6 3h3" /></svg>',
            ],
            [
                'label' => 'Sesi aktif',
                'value' => $todayParticipants,
                'gradient' => 'bg-gradient-to-r from-pink-500 to-red-500',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
            ],
        ];

        $subjects = [
            [
                'name' => 'Matematika',
                'exam' => 'Ujian Tengah Semester',
                'duration' => '120 menit',
                'questions' => 40,
                'gradient' => 'bg-gradient-to-r from-blue-600 to-blue-400',
            ],
            [
                'name' => 'Bahasa Jawa',
                'exam' => 'Ujian Kompetensi',
                'duration' => '90 menit',
                'questions' => 35,
                'gradient' => 'bg-gradient-to-r from-green-600 to-teal-400',
            ],
            [
                'name' => 'Bahasa Indonesia',
                'exam' => 'Ujian Akhir Semester',
                'duration' => '100 menit',
                'questions' => 45,
                'gradient' => 'bg-gradient-to-r from-purple-600 to-pink-500',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
        @foreach($stats as $stat)
            <div class="rounded-xl p-6 text-white shadow-lg transition hover:shadow-xl {{ $stat['gradient'] }}">
                <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-lg bg-white/20">
                    {!! $stat['icon'] !!}
                </div>
                <p class="text-sm text-white/90">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border-l-4 border-yellow-400 bg-white p-6 shadow-md transition hover:shadow-lg">
        <h3 class="text-xl font-semibold text-slate-800">Sinkronisasi Data Siswa</h3>
        <p class="mt-2 text-sm text-slate-500">Pastikan data siswa terbaru telah tersinkron agar proses ujian berjalan lancar tanpa kendala identitas peserta.</p>
        <button class="mt-4 rounded-lg bg-yellow-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-yellow-600">Sinkronkan Sekarang</button>
    </div>

    <div>
        <h3 class="mb-4 text-xl font-semibold text-slate-800">Mata Pelajaran Ujian</h3>
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            @foreach($subjects as $subject)
                <div class="rounded-xl p-6 text-white shadow-lg transition hover:shadow-xl {{ $subject['gradient'] }}">
                    <h4 class="text-lg font-semibold">{{ $subject['name'] }}</h4>
                    <p class="mt-1 text-sm text-white/90">{{ $subject['exam'] }}</p>
                    <div class="mt-4 space-y-1 text-sm text-white/90">
                        <p>Durasi: <span class="font-semibold text-white">{{ $subject['duration'] }}</span></p>
                        <p>Jumlah soal: <span class="font-semibold text-white">{{ $subject['questions'] }} soal</span></p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <h3 class="mb-4 text-xl font-semibold text-slate-800">Grafik Dashboard</h3>
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm transition hover:shadow-lg">
                <h4 class="mb-4 text-base font-semibold text-slate-700">Distribusi siswa</h4>
                <canvas id="distributionChart" height="120"></canvas>
            </div>
            <div class="rounded-xl bg-white p-6 shadow-sm transition hover:shadow-lg">
                <h4 class="mb-4 text-base font-semibold text-slate-700">Kinerja kelulusan</h4>
                <canvas id="graduationChart" height="120"></canvas>
            </div>
            <div class="rounded-xl bg-white p-6 shadow-sm transition hover:shadow-lg xl:col-span-2">
                <h4 class="mb-4 text-base font-semibold text-slate-700">Penyelesaian ujian</h4>
                <canvas id="completionChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const avgScoreLabels = @json($avgScores->pluck('tanggal'));
        const avgScoreData = @json($avgScores->pluck('rata_nilai'));
        const finishedExams = {{ $totalUjianSelesai }};
        const activeExams = {{ $totalUjianAktif }};

        new Chart(document.getElementById('distributionChart'), {
            type: 'doughnut',
            data: {
                labels: ['Peserta Aktif', 'Belum Masuk'],
                datasets: [{
                    data: [{{ $todayParticipants }}, Math.max({{ $totalSiswa }} - {{ $todayParticipants }}, 0)],
                    backgroundColor: ['#3b82f6', '#cbd5e1'],
                    borderWidth: 0,
                }],
            },
            options: {
                plugins: { legend: { position: 'bottom' } },
                cutout: '68%',
            },
        });

        new Chart(document.getElementById('graduationChart'), {
            type: 'bar',
            data: {
                labels: ['Lulus', 'Remedial'],
                datasets: [{
                    data: [finishedExams, activeExams],
                    backgroundColor: ['#22c55e', '#f97316'],
                    borderRadius: 10,
                    barThickness: 36,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });

        new Chart(document.getElementById('completionChart'), {
            type: 'line',
            data: {
                labels: avgScoreLabels,
                datasets: [{
                    label: 'Penyelesaian / Nilai',
                    data: avgScoreData,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.15)',
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
