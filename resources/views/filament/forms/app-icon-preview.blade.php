<div>
    @php
        $iconUrl = $getState() ?? $getRecord()?->app_icon_url ?? data_get($this->data, 'app_icon_url');
    @endphp

    @if (!empty($iconUrl))
        <img src="{{ $iconUrl }}" alt="App Icon" style="width: 64px; height: 64px; object-fit: cover;" class="rounded-2xl ring-1 ring-gray-950/10 dark:ring-white/20 bg-white">
    @else
        <div style="width: 64px; height: 64px;" class="rounded-2xl bg-gray-100 dark:bg-white/5 ring-1 ring-gray-950/10 dark:ring-white/20 flex items-center justify-center text-xl font-bold text-gray-400">
            {{ strtoupper(substr(data_get($this->data, 'app_name', 'A'), 0, 1)) }}
        </div>
    @endif
</div>
