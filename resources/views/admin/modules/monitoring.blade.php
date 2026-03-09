@extends('layouts.app')
@section('content')
<div class="bg-white rounded-xl p-6 shadow">
    <h2 class="text-xl font-semibold mb-3">Pemantauan Ujian</h2>
    <table class="w-full text-sm"><tr><th>Nama</th><th>Kelas</th><th>Ujian</th><th>Status</th><th>Sisa Waktu</th></tr>
        @foreach($rows as $r)
        <tr class="border-t"><td>{{ $r->student->nama ?? '-' }}</td><td>{{ $r->student->kelas ?? '-' }}</td><td>{{ $r->exam->nama_ujian ?? '-' }}</td><td>{{ $r->status }}</td><td>{{ $r->exam->durasi ?? 0 }} menit</td></tr>
        @endforeach
    </table>
    <div class="mt-4">{{ $rows->links() }}</div>
</div>
@endsection
