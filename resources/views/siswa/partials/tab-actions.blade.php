@php
    $prev = $navigasi['sebelumnya'] ?? null;
    $next = $navigasi['berikutnya'] ?? null;
    $showSave = $showSave ?? true;
    $kunciUrutan = (bool) ($portal ?? false);
    $prevTerbuka = $prev && (! $kunciUrutan || ($prev['terbuka'] ?? false));
    $nextAktif = $next && (
        ! $kunciUrutan
        || (
            ($next['terbuka'] ?? false)
            && (! ($navigasi['tab_wajib'] ?? true) || ($navigasi['tab_selesai'] ?? false))
        )
    );
@endphp
<div class="biodata-actions">
    @if ($prevTerbuka)
        <a class="btn biodata-actions-nav" href="{{ $tabUrl($prev['id']) }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ $prev['label'] }}</span>
        </a>
    @else
        <span class="biodata-actions-nav-placeholder"></span>
    @endif

    @if ($showSave)
        <button class="btn btn-madani biodata-actions-save" type="submit">Simpan</button>
    @else
        <span class="biodata-actions-save-placeholder"></span>
    @endif

    @if ($next && $nextAktif)
        <a class="btn biodata-actions-nav biodata-actions-nav-next" href="{{ $tabUrl($next['id']) }}">
            <span>{{ $next['label'] }}</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    @elseif ($next)
        <button class="btn biodata-actions-nav biodata-actions-nav-next" type="button" disabled>
            <span>{{ $next['label'] }}</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </button>
    @else
        <span class="biodata-actions-nav-placeholder"></span>
    @endif
</div>
