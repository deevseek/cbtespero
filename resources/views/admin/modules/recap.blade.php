@extends('layouts.app')
@section('content')
<div class="bg-white rounded-xl p-6 shadow">
    <h2 class="text-xl font-semibold mb-3">Rekapitulasi Nilai</h2>
    <table class="w-full text-sm"><tr><th>Nama Siswa</th><th>Kelas</th><th>Mapel</th><th>Nilai</th><th>Status</th></tr>
    @foreach($rows as $r)
      <tr class="border-t"><td>{{ $r->student->nama ?? '-' }}</td><td>{{ $r->student->kelas ?? '-' }}</td><td>{{ $r->exam->mata_pelajaran ?? '-' }}</td><td>{{ $r->nilai }}</td><td>{{ $r->nilai >= 75 ? 'Lulus' : 'Tidak lulus' }}</td></tr>
    @endforeach</table>
    <div class="mt-4">{{ $rows->links() }}</div>
</div>
@endsection
