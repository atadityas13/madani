@php
    use App\Support\Navigasi;
@endphp

<div class="emis-sidebar-head">
    <a href="{{ route('dashboard') }}" class="emis-logo" title="MADANI">
        <img src="{{ asset('images/logo-madani.png') }}" alt="MADANI">
    </a>
    <button class="emis-sidebar-collapse" type="button" data-sidebar-toggle aria-label="Ciutkan menu">
        <i class="bi bi-chevron-left"></i>
    </button>
</div>

<nav class="emis-sidebar-nav">
    @foreach ($menuEmis as $item)
        @php
            $punyaAnak = ! empty($item['children']);
            $aktif = Navigasi::aktif($item);
        @endphp

        @if ($punyaAnak)
            <div class="emis-nav-block {{ $aktif ? 'is-open is-current' : '' }}" data-nav-group>
                <button class="emis-nav-item {{ $aktif ? 'is-active' : '' }}" type="button" data-nav-trigger>
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                    <i class="bi bi-chevron-down emis-nav-caret"></i>
                </button>
                <div class="emis-nav-sub">
                    @foreach ($item['children'] as $child)
                        @php $anakAktif = Navigasi::aktif($child); @endphp
                        <a class="emis-nav-subitem {{ $anakAktif ? 'is-active' : '' }}" href="{{ route($child['route']) }}">
                            {{ $child['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <a class="emis-nav-item {{ $aktif ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>

<div class="emis-sidebar-foot">
    <strong>MADANI</strong>
    <div>MTsN 11 Majalengka</div>
</div>
