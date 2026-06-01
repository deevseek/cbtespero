@extends('student.layout', ['title' => 'Profil Saya', 'subtitle' => 'Identitas Peserta'])

@section('content')
<div class="mx-auto max-w-3xl rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
        <div class="flex h-20 w-20 items-center justify-center rounded-[1.75rem] bg-gradient-to-br from-[#2563eb] to-[#4f46e5] text-3xl font-black text-white">{{ mb_substr($student->nama, 0, 1) }}</div>
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.25em] text-[#2563eb]">Peserta Ujian</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $student->nama }}</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">Status akun {{ ucfirst($student->status) }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        @foreach([
            'Nama' => $student->nama,
            'NIS' => $student->nis,
            'Username' => $student->username,
            'Kelas' => $student->kelas,
            'Status Akun' => ucfirst($student->status),
        ] as $label => $value)
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                <p class="mt-1 font-black text-slate-950">{{ $value }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
