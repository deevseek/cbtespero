<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login - Espero CBT</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
<form action="{{ route('login.attempt') }}" method="post" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8 space-y-4">@csrf
    <h1 class="text-2xl font-bold text-blue-700">Espero CBT</h1>
    <input type="text" name="username" placeholder="Username" class="w-full rounded-xl border p-3" required>
    <input type="password" name="password" placeholder="Password" class="w-full rounded-xl border p-3" required>
    @error('username')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
    <button class="w-full bg-blue-600 text-white rounded-xl py-3">Login</button>
</form>
</body></html>
