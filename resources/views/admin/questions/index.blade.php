@extends('layouts.app')
@section('content')
<div class="bg-white rounded-xl p-5 shadow">
    <form class="mb-3"><input name="q" value="{{ request('q') }}" placeholder="Cari soal" class="border rounded-xl p-2"></form>
    <form method="post" action="{{ route('admin.questions.store') }}" class="grid md:grid-cols-4 gap-2 mb-4">@csrf
        <input name="mata_pelajaran" placeholder="Mapel" class="border rounded-xl p-2" required>
        <input name="soal" placeholder="Soal" class="border rounded-xl p-2 md:col-span-3" required>
        @foreach(['a','b','c','d','e'] as $opt)<input name="pilihan_{{ $opt }}" placeholder="Pilihan {{ strtoupper($opt) }}" class="border rounded-xl p-2" @if($opt!=='e') required @endif>@endforeach
        <select name="jawaban_benar" class="border rounded-xl p-2">@foreach(['a','b','c','d','e'] as $opt)<option>{{ $opt }}</option>@endforeach</select>
        <input type="number" name="bobot_nilai" value="1" class="border rounded-xl p-2">
        <select name="tingkat_kesulitan" class="border rounded-xl p-2"><option>mudah</option><option>sedang</option><option>sulit</option></select>
        <input name="image_path" placeholder="URL gambar" class="border rounded-xl p-2">
        <button class="bg-blue-600 text-white rounded-xl p-2 md:col-span-4">Tambah Soal</button>
    </form>
    <table class="w-full text-sm"><tr><th>Mapel</th><th>Soal</th><th>Kesulitan</th><th>Aksi</th></tr>
        @foreach($questions as $q)
        <tr class="border-t"><td>{{ $q->mata_pelajaran }}</td><td>{{ \Illuminate\Support\Str::limit($q->soal, 70) }}</td><td>{{ $q->tingkat_kesulitan }}</td><td><form method="post" action="{{ route('admin.questions.destroy', $q) }}">@csrf @method('delete')<button class="text-red-500">Hapus</button></form></td></tr>
        @endforeach
    </table>
    <div class="mt-4">{{ $questions->links() }}</div>
</div>
@endsection
