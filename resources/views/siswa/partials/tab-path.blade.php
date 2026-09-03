@php
    $navigasi = $navigasi ?? ['tab' => []];
    $tabAktif = $tab ?? 'data-siswa';
@endphp
<nav class="biodata-path" aria-label="Langkah kelengkapan data">
    @foreach ($navigasi['tab'] as $item)
        @php
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
        @endphp
        <div class="biodata-path-item is-{{ $state }} {{ $item['id'] === $tabAktif ? 'is-active' : '' }} {{ ($item['terbuka'] ?? false) ? 'is-open' : 'is-locked' }}">
            @if ($item['terbuka'] ?? false)
                <a class="biodata-path-node" href="{{ $tabUrl($item['id']) }}" aria-current="{{ $item['id'] === $tabAktif ? 'step' : 'false' }}">
                    @include('siswa.partials.tab-path-icon', ['state' => $state])
                </a>
            @else
                <span class="biodata-path-node" aria-disabled="true">
                    @include('siswa.partials.tab-path-icon', ['state' => $state])
                </span>
            @endif
            <span class="biodata-path-label">{{ $item['label'] }}</span>
        </div>
    @endforeach
</nav>
