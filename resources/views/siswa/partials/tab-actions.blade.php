@php
    $prev = $navigasi['sebelumnya'] ?? null;
    $next = $navigasi['berikutnya'] ?? null;
    $showSave = $showSave ?? true;
    $nextAktif = $next && (! ($navigasi['tab_wajib'] ?? true) || ($navigasi['tab_selesai'] ?? false));
@endphp
<div class="biodata-actions">
    @if ($prev)
        <a class="btn biodata-actions-nav" href="{{ $tabUrl($prev['id']) }}">{{ $prev['label'] }}</a>
    @else
        <span class="biodata-actions-nav-placeholder"></span>
    @endif

    @if ($showSave)
        <button class="btn btn-madani biodata-actions-save" type="submit">Simpan</button>
    @else
        <span class="biodata-actions-save-placeholder"></span>
    @endif

    @if ($next && $nextAktif)
        <a class="btn biodata-actions-nav biodata-actions-nav-next" href="{{ $tabUrl($next['id']) }}">{{ $next['label'] }}</a>
    @elseif ($next)
        <button class="btn biodata-actions-nav biodata-actions-nav-next" type="button" disabled>{{ $next['label'] }}</button>
    @else
        <span class="biodata-actions-nav-placeholder"></span>
    @endif
</div>
