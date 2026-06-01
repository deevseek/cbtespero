<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Siswa - CBT Julia</title>
    <x-partials.vite-assets :entries="['resources/css/app.css']" />
</head>
<body class="min-h-screen overflow-hidden bg-slate-950 text-white antialiased">
    <main class="relative flex min-h-screen items-center justify-center px-4 py-10" style="background: linear-gradient(135deg, #07111f 0%, #020617 55%, #071426 100%);">
        <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-blue-500/20 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-cyan-400/10 blur-3xl"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-sky-300/40 to-transparent"></div>

        <section class="relative w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-sky-300/20 bg-slate-900/80 shadow-2xl shadow-sky-950/70">
                    <span class="bg-gradient-to-br from-sky-200 to-blue-500 bg-clip-text text-2xl font-black text-transparent">CJ</span>
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-300/80">CBT Julia</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-white">Portal Peserta Ujian</h1>
                <p class="mt-2 text-sm leading-6 text-slate-400">Masuk menggunakan username atau NIS yang terdaftar untuk mengakses ujian.</p>
            </div>

            <form action="{{ route('student.login.attempt') }}" method="post" class="space-y-5 rounded-[20px] border border-slate-400/20 bg-slate-900/95 p-7 shadow-[0_30px_100px_rgba(2,6,23,0.85)] backdrop-blur-xl">
                @csrf

                @if($errors->has('login'))
                    <div class="rounded-2xl border border-red-400/25 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                        {{ $errors->first('login') }}
                    </div>
                @endif

                <div class="space-y-2">
                    <label for="username" class="text-sm font-semibold text-slate-200">Username / NIS</label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username atau NIS"
                        autocomplete="username"
                        required
                        autofocus
                        class="w-full rounded-2xl border border-slate-700/80 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-sky-400 focus:ring-4 focus:ring-sky-500/10"
                    >
                    @error('username')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-semibold text-slate-200">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-2xl border border-slate-700/80 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-sky-400 focus:ring-4 focus:ring-sky-500/10"
                    >
                    @error('password')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                </div>

                <button class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-blue-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-950/40 transition hover:-translate-y-0.5 hover:from-sky-400 hover:to-blue-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20">
                    Masuk Ujian
                    <span aria-hidden="true" class="transition group-hover:translate-x-1">→</span>
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-500">Gunakan akun test: <span class="font-semibold text-slate-300">siswa001</span> / <span class="font-semibold text-slate-300">password</span></p>
        </section>
    </main>
</body>
</html>
