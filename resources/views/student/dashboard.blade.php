@extends('layouts.app')
@section('content')
<div class="bg-white rounded-xl p-5 shadow mb-4">
    <h2 class="font-semibold mb-2">Jadwal Ujian Aktif</h2>
    @foreach($exams as $exam)
        <form action="{{ route('student.exams.start', $exam) }}" method="post" class="flex gap-2 border-t py-2">@csrf
            <div class="flex-1">{{ $exam->nama_ujian }} - {{ $exam->mata_pelajaran }}</div>
            <input name="token" class="border rounded-xl p-2" placeholder="Token ujian" required>
            <button class="bg-blue-600 text-white px-4 rounded-xl">Mulai</button>
        </form>
    @endforeach
</div>
<div class="bg-white rounded-xl p-5 shadow">
    <h2 class="font-semibold mb-2">Riwayat Nilai</h2>
    @foreach($results as $r)
        <div class="border-t py-2 flex justify-between"><span>{{ $r->exam->nama_ujian }}</span><span>{{ $r->nilai }}</span></div>
    @endforeach
</div>
@endsection
