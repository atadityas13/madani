@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $rows */
    $ukuran = $ukuran ?? \App\Support\UkuranHalaman::STANDAR;
    $bawaan = $bawaan ?? ($ukuran[0] ?? 15);
    $semuanya = $semuanya ?? true;
    $pagerId = $pagerId ?? 'daftar';
    $navLabel = $navLabel ?? 'Navigasi halaman';
    $perPage = (string) ($perPage ?? \App\Support\UkuranHalaman::label(
        (string) request()->query('per_page', (string) $bawaan),
        $ukuran,
        $bawaan,
        $semuanya,
    ));
    $pageName = $rows->getPageName();
    $from = $rows->firstItem() ?? 0;
    $to = $rows->lastItem() ?? 0;
    $total = $rows->total();
    $current = $rows->currentPage();
    $last = max($rows->lastPage(), 1);
    $window = 2;
    $start = max(1, $current - $window);
    $end = min($last, $current + $window);
@endphp

<div class="siswa-index-pagination">
    <div class="siswa-index-pagination__summary">
        Menampilkan
        <strong>{{ $from === 0 ? 0 : $from.'–'.$to }}</strong>
        dari
        <strong>{{ $total }}</strong>
        data
    </div>

    <form class="siswa-index-pagination__controls" method="GET" action="{{ url()->current() }}">
        @foreach (request()->except([$pageName, 'per_page']) as $name => $value)
            @if (is_array($value))
                @foreach ($value as $item)
                    @if (is_scalar($item) || $item === null)
                        <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                    @endif
                @endforeach
            @elseif (is_scalar($value) || $value === null)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach

        <label class="siswa-index-pagination__label" for="{{ $pagerId }}PerPage">
            Tampil
            <select class="form-select form-select-sm" id="{{ $pagerId }}PerPage" name="per_page" onchange="this.form.elements['{{ $pageName }}'].value='1'; this.form.submit()">
                @foreach ($ukuran as $size)
                    <option value="{{ $size }}" @selected($perPage === (string) $size)>{{ $size }}</option>
                @endforeach
                @if ($semuanya)
                    <option value="all" @selected($perPage === 'all')>Semua</option>
                @endif
            </select>
        </label>

        <label class="siswa-index-pagination__label" for="{{ $pagerId }}PageJump">
            Halaman
            <input
                class="form-control form-control-sm siswa-index-pagination__page-input"
                id="{{ $pagerId }}PageJump"
                type="number"
                name="{{ $pageName }}"
                min="1"
                max="{{ $last }}"
                value="{{ $current }}"
            >
        </label>
        <button class="btn btn-sm btn-outline-secondary" type="submit">Go</button>
    </form>

    <nav class="siswa-index-pagination__pages" aria-label="{{ $navLabel }}">
        @if ($rows->onFirstPage())
            <span class="siswa-index-page is-disabled" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="siswa-index-page" href="{{ $rows->previousPageUrl() }}">Sebelumnya</a>
        @endif

        @if ($start > 1)
            <a class="siswa-index-page" href="{{ $rows->url(1) }}">1</a>
            @if ($start > 2)
                <span class="siswa-index-page is-ellipsis" aria-hidden="true">…</span>
            @endif
        @endif

        @for ($page = $start; $page <= $end; $page++)
            @if ($page === $current)
                <span class="siswa-index-page is-current" aria-current="page">{{ $page }}</span>
            @else
                <a class="siswa-index-page" href="{{ $rows->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($end < $last)
            @if ($end < $last - 1)
                <span class="siswa-index-page is-ellipsis" aria-hidden="true">…</span>
            @endif
            <a class="siswa-index-page" href="{{ $rows->url($last) }}">{{ $last }}</a>
        @endif

        @if ($rows->hasMorePages())
            <a class="siswa-index-page" href="{{ $rows->nextPageUrl() }}">Selanjutnya</a>
        @else
            <span class="siswa-index-page is-disabled" aria-disabled="true">Selanjutnya</span>
        @endif
    </nav>
</div>
