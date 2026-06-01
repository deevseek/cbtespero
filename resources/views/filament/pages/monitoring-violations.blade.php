<div class="space-y-3">
    @forelse($logs as $log)
        <div class="rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-semibold text-danger-600">{{ \Illuminate\Support\Str::of($log->activity_type)->replace('_', ' ')->title() }}</span>
                <span class="text-xs text-gray-500">{{ optional($log->logged_at)->format('d M Y H:i:s') }}</span>
            </div>
            <p class="mt-1 text-gray-700 dark:text-gray-200">{{ $log->description ?: 'Pelanggaran anti-cheat terdeteksi.' }}</p>
            <p class="mt-1 text-xs text-gray-500">IP: {{ $log->ip_address ?: '-' }}</p>
            <p class="mt-1 break-all text-xs text-gray-500">User Agent: {{ $log->user_agent ?: '-' }}</p>
        </div>
    @empty
        <p class="text-sm text-gray-500">Belum ada log pelanggaran.</p>
    @endforelse
</div>
