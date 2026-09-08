@props([
    'title',
    'subtitle' => '',
    'eyebrow' => 'MTsN 11 Majalengka',
])

@php
    $taglines = [
        'Management Academic Data Native Integration.',
        'Satu pusat data akademik madrasah yang terintegrasi.',
        'Administrasi rapi, layanan cepat, data terpercaya.',
    ];
@endphp

<div class="madani-login">
    <div class="madani-login__glow madani-login__glow--a" aria-hidden="true"></div>
    <div class="madani-login__glow madani-login__glow--b" aria-hidden="true"></div>
    <div class="madani-login__pattern" aria-hidden="true"></div>

    <div class="madani-login__frame">
        <aside class="madani-login__brand">
            <div class="madani-login__brand-inner">
                <img
                    class="madani-login__logo"
                    src="{{ asset('images/logo-madani.png') }}?v={{ filemtime(public_path('images/logo-madani.png')) }}"
                    alt="MADANI — Management Academic Data Native Integration"
                >
                <p class="madani-login__school">{{ $eyebrow }}</p>
                <h1 class="madani-login__headline">MADANI</h1>
                <p
                    class="madani-login__tagline"
                    data-madani-taglines='@json($taglines)'
                    data-madani-tagline
                >{{ $taglines[0] }}</p>
                <ul class="madani-login__pillars" aria-label="Nilai MADANI">
                    <li><i class="bi bi-database-check"></i> Data terpadu</li>
                    <li><i class="bi bi-people"></i> Guru &amp; siswa</li>
                    <li><i class="bi bi-shield-check"></i> Aman &amp; resmi</li>
                </ul>
            </div>
        </aside>

        <section class="madani-login__panel">
            <div class="madani-login__card madani-login__card--enter">
                <div class="madani-login__card-accent" aria-hidden="true"></div>
                <p class="madani-login__eyebrow">{{ $eyebrow }}</p>
                <h2 class="madani-login__title">{{ $title }}</h2>
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
