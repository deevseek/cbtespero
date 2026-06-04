<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - CBT Julia</title>
    <x-partials.vite-assets :entries="['resources/css/app.css']" />
</head>
<body class="min-h-screen bg-[#f8fafc] text-[#0f172a] antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="absolute -left-24 top-12 h-80 w-80 rounded-full bg-slate-200/80 blur-3xl"></div>
        <div class="absolute -right-20 bottom-0 h-96 w-96 rounded-full bg-blue-200/70 blur-3xl"></div>

        <section class="relative grid w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-2xl shadow-slate-950/10 lg:grid-cols-[1fr_440px]">
            <div class="hidden bg-gradient-to-br from-slate-950 to-[#1d4ed8] p-10 text-white lg:block">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-blue-100">CBT Julia</p>
                <h1 class="mt-4 text-4xl font-black leading-tight">Portal Admin untuk pengelolaan CBT.</h1>
                <p class="mt-4 text-base font-semibold text-blue-100">Masuk untuk mengelola siswa, bank soal, jadwal ujian, token, monitoring, dan laporan hasil CBT.</p>
                <div class="mt-10 grid gap-3">
                    @foreach(['Manajemen ujian dan token', 'Bank soal dan peserta', 'Monitoring pelaksanaan CBT', 'Rekap dan laporan hasil'] as $feature)
                        <div class="rounded-2xl bg-white/15 p-4 font-bold backdrop-blur">✓ {{ $feature }}</div>
                    @endforeach
                </div>
            </div>

            <div class="p-6 sm:p-10">
                <div class="mb-8 text-center lg:text-left">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-950 text-2xl font-black text-white lg:mx-0">CJ</div>
                    <p class="text-sm font-bold uppercase tracking-[0.25em] text-[#1d4ed8]">Portal Admin</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">Login Admin</h2>
                    <p class="mt-2 text-sm font-semibold text-slate-500">Gunakan username atau email admin untuk masuk ke panel administrasi.</p>
                </div>

                <form action="{{ route('login.attempt') }}" method="post" class="space-y-5">
                    @csrf

                    @if($errors->has('login'))
                        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                            {{ $errors->first('login') }}
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label for="login" class="text-sm font-black text-slate-700">Username / Email Admin</label>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" placeholder="Masukkan username atau email admin" autocomplete="username" required autofocus class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition placeholder:text-slate-400 focus:border-[#1d4ed8] focus:bg-white focus:ring-4 focus:ring-blue-100">
                        @error('login')<p class="text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-sm font-black text-slate-700">Password</label>
                        <input id="password" type="password" name="password" placeholder="Masukkan password admin" autocomplete="current-password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition placeholder:text-slate-400 focus:border-[#1d4ed8] focus:bg-white focus:ring-4 focus:ring-blue-100">
                        @error('password')<p class="text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-[#1d4ed8] focus:ring-blue-100">
                        Ingat sesi admin
                    </label>

                    <button class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3.5 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-[#1d4ed8] focus:outline-none focus:ring-4 focus:ring-blue-100">
                        Masuk ke Panel Admin
                        <span aria-hidden="true" class="transition group-hover:translate-x-1">→</span>
                    </button>
                </form>

                <div class="mt-6 grid gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">
                    <p>Login siswa tetap terpisah di <a href="{{ route('student.login') }}" class="font-black text-[#1d4ed8] hover:underline">/student/login</a>.</p>
                    <p class="text-xs">Route <span class="font-black text-slate-700">/login</span> adalah pintu login admin utama.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
