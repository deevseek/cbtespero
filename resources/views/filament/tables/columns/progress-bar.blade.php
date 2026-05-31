@php
    $progress = max(0, min(100, (int) ($getState() ?? 0)));
@endphp
<div class="min-w-32 space-y-1">
    <div class="flex items-center justify-between text-xs text-slate-300">
        <span>{{ $progress }}%</span>
    </div>
    <div class="cbt-progress-track">
        <div class="cbt-progress-bar" style="width: {{ $progress }}%"></div>
    </div>
</div>
