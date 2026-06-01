<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Siswa - CBT Julia</title>
    <x-partials.vite-assets :entries="['resources/css/app.css']" />
</head>
<body class="min-h-screen bg-[#f3f6fb] text-[#0f172a] antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="absolute -left-24 top-12 h-80 w-80 rounded-full bg-blue-200/70 blur-3xl"></div>
        <div class="absolute -right-20 bottom-0 h-96 w-96 rounded-full bg-indigo-200/70 blur-3xl"></div>

        <section class="relative grid w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-2xl shadow-blue-950/10 lg:grid-cols-[1fr_440px]">
            <div class="hidden bg-gradient-to-br from-[#2563eb] to-[#4f46e5] p-10 text-white lg:block">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-blue-100">CBT Julia</p>
                <h1 class="mt-4 text-4xl font-black leading-tight">Portal Peserta Ujian yang aman dan modern.</h1>
                <p class="mt-4 text-base font-semibold text-blue-100">Masuk untuk melihat jadwal ujian, memasukkan token, mengerjakan CBT, dan memantau riwayat nilai kamu.</p>
                <div class="mt-10 grid gap-3">
                    @foreach(['Jadwal ujian sesuai kelas', 'Token ujian tervalidasi', 'Pengawasan fullscreen dan tab', 'Riwayat nilai otomatis'] as $feature)
                        <div class="rounded-2xl bg-white/15 p-4 font-bold backdrop-blur">✓ {{ $feature }}</div>
                    @endforeach
                </div>
            </div>

            <div class="p-6 sm:p-10">
                <div class="mb-8 text-center lg:text-left">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-50 text-2xl font-black text-[#2563eb] lg:mx-0">CJ</div>
                    <p class="text-sm font-bold uppercase tracking-[0.25em] text-[#2563eb]">Portal Peserta</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">Login Siswa</h2>
                    <p class="mt-2 text-sm font-semibold text-slate-500">Gunakan username atau NIS yang diberikan pengawas.</p>
                </div>

                <form action="{{ route('student.login.attempt') }}" method="post" class="space-y-5">
                    @csrf

                    @if($errors->has('login'))
                        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                            {{ $errors->first('login') }}
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label for="username" class="text-sm font-black text-slate-700">Username / NIS</label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="Masukkan username atau NIS" autocomplete="username" required autofocus class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition placeholder:text-slate-400 focus:border-[#2563eb] focus:bg-white focus:ring-4 focus:ring-blue-100">
                        @error('username')<p class="text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-sm font-black text-slate-700">Password</label>
                        <input id="password" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition placeholder:text-slate-400 focus:border-[#2563eb] focus:bg-white focus:ring-4 focus:ring-blue-100">
                        @error('password')<p class="text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-[#2563eb] px-4 py-3.5 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:-translate-y-0.5 hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        Masuk ke Dashboard
                        <span aria-hidden="true" class="transition group-hover:translate-x-1">→</span>
                    </button>
                </form>

                <p class="mt-6 rounded-2xl bg-slate-50 px-4 py-3 text-center text-xs font-semibold text-slate-500">Akun uji: <span class="font-black text-slate-700">siswa001</span> / <span class="font-black text-slate-700">password</span></p>
            </div>
        </section>
    </main>
</body>
</html>
