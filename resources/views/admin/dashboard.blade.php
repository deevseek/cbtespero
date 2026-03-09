@extends('layouts.app')
@section('content')
<div class="grid md:grid-cols-4 gap-4 mb-6">
    @foreach([
        'Total siswa' => $totalSiswa,
        'Total soal' => $totalSoal,
        'Ujian aktif' => $totalUjianAktif,
        'Ujian selesai' => $totalUjianSelesai,
    ] as $label => $value)
        <div class="bg-white rounded-xl p-5 shadow"><p class="text-sm">{{ $label }}</p><p class="text-3xl font-bold text-blue-700">{{ $value }}</p></div>
    @endforeach
</div>
<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl p-5 shadow"><h2 class="font-semibold mb-2">Peserta hari ini</h2><p class="text-5xl text-blue-700">{{ $todayParticipants }}</p></div>
    <div class="bg-white rounded-xl p-5 shadow"><h2 class="font-semibold mb-2">Rata-rata nilai ujian</h2>
        <div class="space-y-2">@foreach($avgScores as $item)<div class="flex justify-between"><span>{{ $item->tanggal }}</span><span class="font-semibold">{{ number_format($item->rata_nilai,2) }}</span></div>@endforeach</div>
    </div>
</div>
@endsection
