@extends('layouts.app')
@section('content')
<div class="bg-white rounded-xl p-5 shadow">
    <form method="post" action="{{ route('admin.exams.store') }}" class="grid md:grid-cols-4 gap-2 mb-4">@csrf
        <input name="nama_ujian" placeholder="Nama ujian" class="border rounded-xl p-2" required>
        <input name="mata_pelajaran" placeholder="Mapel" class="border rounded-xl p-2" required>
        <input name="kelas" placeholder="Kelas" class="border rounded-xl p-2" required>
        <input type="date" name="tanggal_ujian" class="border rounded-xl p-2" required>
        <input type="time" name="jam_mulai" class="border rounded-xl p-2" required>
        <input type="time" name="jam_selesai" class="border rounded-xl p-2" required>
        <input name="durasi" type="number" placeholder="Durasi" class="border rounded-xl p-2" required>
        <input name="jumlah_soal" type="number" placeholder="Jumlah soal" class="border rounded-xl p-2" required>
        <select name="status" class="border rounded-xl p-2"><option>draft</option><option>aktif</option><option>selesai</option></select>
        <button class="bg-blue-600 text-white rounded-xl p-2 md:col-span-4">Buat jadwal ujian</button>
    </form>
    <table class="w-full text-sm"><tr><th>Ujian</th><th>Kelas</th><th>Status</th><th>Token</th><th>Aksi</th></tr>
        @foreach($exams as $e)
        <tr class="border-t"><td>{{ $e->nama_ujian }}</td><td>{{ $e->kelas }}</td><td>{{ $e->status }}</td><td><span class="font-mono">{{ $e->token }}</span></td><td class="flex gap-2">
            <form method="post" action="{{ route('admin.exams.regenerate-token', $e) }}">@csrf<button class="text-blue-600">Regenerate token</button></form>
            <form method="post" action="{{ route('admin.exams.destroy', $e) }}">@csrf @method('delete')<button class="text-red-500">Hapus</button></form>
        </td></tr>
        @endforeach
    </table>
    <div class="mt-4">{{ $exams->links() }}</div>
</div>
@endsection
