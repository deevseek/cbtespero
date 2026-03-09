@extends('layouts.app')
@section('content')
<div class="bg-white rounded-xl p-5 shadow" x-data="{q:'{{ request('q') }}'}">
    <div class="flex justify-between mb-4 gap-2">
        <form class="flex-1"><input x-model.debounce.500ms="q" @input="$el.form.submit()" name="q" class="w-full border rounded-xl p-2" placeholder="Search realtime siswa..."></form>
        <form method="post" action="{{ route('admin.students.store') }}" class="grid grid-cols-6 gap-2">@csrf
            <input name="nis" placeholder="NIS" class="border rounded-xl p-2" required>
            <input name="nama" placeholder="Nama" class="border rounded-xl p-2" required>
            <input name="kelas" placeholder="Kelas" class="border rounded-xl p-2" required>
            <input name="username" placeholder="Username" class="border rounded-xl p-2" required>
            <input name="password" placeholder="Password" class="border rounded-xl p-2" required>
            <select name="status" class="border rounded-xl p-2"><option>aktif</option><option>nonaktif</option></select>
            <button class="col-span-6 bg-blue-600 text-white rounded-xl p-2">Tambah Siswa</button>
        </form>
    </div>
    <table class="w-full text-sm"><tr class="text-left"><th>NIS</th><th>Nama</th><th>Kelas</th><th>Username</th><th>Status</th><th>Aksi</th></tr>
        @foreach($students as $s)
        <tr class="border-t"><td>{{ $s->nis }}</td><td>{{ $s->nama }}</td><td>{{ $s->kelas }}</td><td>{{ $s->username }}</td><td>{{ $s->status }}</td><td>
            <form method="post" action="{{ route('admin.students.destroy', $s) }}">@csrf @method('delete')<button class="text-red-500">Hapus</button></form></td></tr>
        @endforeach
    </table>
    <div class="mt-4">{{ $students->links() }}</div>
</div>
@endsection
