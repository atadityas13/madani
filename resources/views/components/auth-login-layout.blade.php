@props([
    'title',
    'subtitle' => '',
    'eyebrow' => 'MTsN 11 Majalengka',
    'showFormEyebrow' => false,
])

@php
    $taglines = [
        'Management Academic Data Native Integration.',
        'Satu pusat data akademik madrasah yang terintegrasi.',
        'Administrasi tertata, Layanan cepat, Data aman',
    ];
@endphp

<div class="madani-login">
    <div class="madani-login__glow madani-login__glow--a" aria-hidden="true"></div>
    <div class="madani-login__glow madani-login__glow--b" aria-hidden="true"></div>
    <div class="madani-login__pattern" aria-hidden="true"></div>

    <div class="madani-login__frame">
        <aside class="madani-login__brand">
            <div class="madani-login__brand-inner">
                <div class="madani-login__logo-plate">
                    <img
                        class="madani-login__logo"
                        src="{{ asset('images/logo-madani.png') }}?v={{ filemtime(public_path('images/logo-madani.png')) }}"
                        alt="MADANI"
                    >
                </div>
                <p class="madani-login__school">{{ $eyebrow }}</p>
                <p
                    class="madani-login__tagline"
                    data-madani-taglines='@json($taglines)'
                    data-madani-tagline
                >{{ $taglines[0] }}</p>
                <p class="madani-login__pillars" aria-label="Nilai MADANI">
                    <span><i class="bi bi-database-check" aria-hidden="true"></i> Satu Data Terpadu untuk Layanan Terintegrasi</span>
                </p>
            </div>
        </aside>

        <section class="madani-login__panel">
            <div class="madani-login__card madani-login__card--enter">
                <div class="madani-login__card-accent" aria-hidden="true"></div>
                @if ($showFormEyebrow && $eyebrow !== '')
                    <p class="madani-login__eyebrow">{{ $eyebrow }}</p>
                @endif
                <h1 class="madani-login__title">{{ $title }}</h1>
                @if ($subtitle !== '')
                    <p class="madani-login__subtitle">{{ $subtitle }}</p>
                @endif

                <div class="madani-login__form">
                    {{ $slot }}
                </div>

                @isset($footer)
                    <div class="madani-login__footer">
                        {{ $footer }}
                    </div>
                @endisset
            </div>

            <p class="madani-login__credit">
                © {{ now()->year }} {{ $eyebrow }} · Developed by ATA DevLabs
            </p>
        </section>
    </div>
</div>
