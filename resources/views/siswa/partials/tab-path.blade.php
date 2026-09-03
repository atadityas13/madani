@php
    $navigasi = $navigasi ?? ['tab' => []];
    $tabAktif = $tab ?? 'data-siswa';
    $items = $navigasi['tab'] ?? [];
    $portal = $portal ?? false;
@endphp
<nav class="biodata-path" aria-label="Langkah kelengkapan data">
    @foreach ($items as $item)
        @php
            $terbuka = ! $portal || (bool) ($item['terbuka'] ?? false);
            $state = 'optional';
            if ($item['wajib']) {
                if ($item['id'] === $tabAktif) {
                    $state = 'current';
                } elseif ($item['selesai']) {
                    $state = 'done';
                } else {
                    $state = 'todo';
                }
            }
            $itemClass = 'biodata-path-item is-'.$state
                .($item['id'] === $tabAktif ? ' is-active' : '')
                .($terbuka ? ' is-open' : ' is-locked');
        @endphp
        @if ($terbuka)
            <a class="{{ $itemClass }}" href="{{ $tabUrl($item['id']) }}" aria-current="{{ $item['id'] === $tabAktif ? 'step' : 'false' }}">
                @include('siswa.partials.tab-path-step', ['state' => $state, 'item' => $item])
            </a>
        @else
            <div class="{{ $itemClass }}" aria-disabled="true" title="Lengkapi langkah sebelumnya terlebih dahulu">
                @include('siswa.partials.tab-path-step', ['state' => $state, 'item' => $item])
            </div>
        @endif
    @endforeach
</nav>
