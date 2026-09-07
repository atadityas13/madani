@php
    $judul = old('judul', $item?->judul);
    $openMode = old('open_mode', $item?->open_mode ?? 'webview');
    $url = old('url', $item?->url);
    $package = old('package_name', $item?->package_name);
    $play = old('play_store_url', $item?->play_store_url);
    $requiresAuth = old('requires_auth', $item?->requires_auth ? '1' : null);
    $isActive = old('is_active', $item === null || $item->is_active ? '1' : null);
@endphp

<div class="mb-3">
    <label class="form-label">Judul</label>
    <input class="form-control" name="judul" value="{{ $judul }}" required maxlength="100">
</div>
<div class="mb-3">
    <label class="form-label">Mode buka</label>
    <select class="form-select" name="open_mode" required>
        <option value="webview" @selected($openMode === 'webview')>WebView in-app</option>
        <option value="chrome_tab" @selected($openMode === 'chrome_tab')>Chrome Custom Tab</option>
        <option value="app" @selected($openMode === 'app')>Buka aplikasi</option>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">URL</label>
    <input class="form-control" name="url" value="{{ $url }}" maxlength="500" placeholder="https://…">
    <div class="form-text">Wajib untuk WebView / Custom Tab. SSO hanya untuk domain Madani.</div>
</div>
<div class="mb-3">
    <label class="form-label">Package name (mode app)</label>
    <input class="form-control" name="package_name" value="{{ $package }}" maxlength="200" placeholder="com.contoh.app">
</div>
<div class="mb-3">
    <label class="form-label">Play Store URL</label>
    <input class="form-control" name="play_store_url" value="{{ $play }}" maxlength="500">
</div>
<div class="mb-3">
    <label class="form-label">Ikon</label>
    <input class="form-control" type="file" name="icon" accept="image/*">
    @if ($item?->iconUrl())
        <div class="mt-2"><img src="{{ $item->iconUrl() }}" alt="" width="48" height="48" class="rounded border"></div>
    @endif
</div>
<div class="form-check form-switch mb-2">
    <input class="form-check-input" type="checkbox" role="switch" id="requiresAuth{{ $item?->id ?? 'new' }}"
           name="requires_auth" value="1" @checked($requiresAuth)>
    <label class="form-check-label" for="requiresAuth{{ $item?->id ?? 'new' }}">SSO Madani (hanya URL Madani)</label>
</div>
<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" role="switch" id="isActive{{ $item?->id ?? 'new' }}"
           name="is_active" value="1" @checked($isActive)>
    <label class="form-check-label" for="isActive{{ $item?->id ?? 'new' }}">Aktif</label>
</div>
