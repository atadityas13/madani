@php
    $menuSiswaAktif = request()->routeIs('siswa.*', 'ppdb.*', 'mutasi.*', 'alumni.*');
    $tautanSiswa = [
        ['route' => 'ppdb.index', 'label' => 'PPDB', 'icon' => 'bi-person-plus'],
        ['route' => 'siswa.index', 'label' => 'Data Siswa', 'icon' => 'bi-people', 'match' => 'siswa.*'],
        ['route' => 'mutasi.index', 'label' => 'Mutasi', 'icon' => 'bi-arrow-left-right'],
        ['route' => 'alumni.index', 'label' => 'Alumni', 'icon' => 'bi-mortarboard'],
    ];
@endphp

@if ($variant === 'rail')
    <div class="emis-nav-group {{ $menuSiswaAktif ? 'is-current' : '' }}" data-siswa-menu>
        <button class="emis-nav-rail {{ $menuSiswaAktif ? 'active' : '' }}" type="button" data-siswa-trigger aria-haspopup="true">
            <i class="bi bi-people-fill"></i> Siswa
        </button>
        <div class="emis-nav-flyout">
            @foreach ($tautanSiswa as $item)
                @php $aktif = request()->routeIs($item['match'] ?? $item['route']); @endphp
                <a class="{{ $aktif ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@else
    <button class="emis-nav-link {{ $menuSiswaAktif ? 'active' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#navSiswaMobile">
        <i class="bi bi-people-fill"></i> Siswa
    </button>
    <div class="collapse {{ $menuSiswaAktif ? 'show' : '' }} ps-3" id="navSiswaMobile">
        @foreach ($tautanSiswa as $item)
            @php $aktif = request()->routeIs($item['match'] ?? $item['route']); @endphp
            <a class="emis-nav-link {{ $aktif ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
            </a>
        @endforeach
    </div>
@endif
