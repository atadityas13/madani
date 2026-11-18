@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $gtks */
    $from = $gtks->firstItem() ?? 0;
    $to = $gtks->lastItem() ?? 0;
    $total = $gtks->total();
    $current = $gtks->currentPage();
    $last = max($gtks->lastPage(), 1);
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

    <form class="siswa-index-pagination__controls" method="GET" action="{{ route('gtk.index') }}">
        <input type="hidden" name="q" value="{{ $q }}">
        <input type="hidden" name="jenis" value="{{ $jenis }}">

        <label class="siswa-index-pagination__label" for="gtkPerPage">
            Tampil
            <select class="form-select form-select-sm" id="gtkPerPage" name="per_page" onchange="this.form.page.value='1'; this.form.submit()">
                @foreach ([15, 30, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage === (string) $size)>{{ $size }}</option>
                @endforeach
                <option value="all" @selected($perPage === 'all')>Semua</option>
            </select>
        </label>

        <label class="siswa-index-pagination__label" for="gtkPageJump">
            Halaman
            <input
                class="form-control form-control-sm siswa-index-pagination__page-input"
                id="gtkPageJump"
                type="number"
                name="page"
                min="1"
                max="{{ $last }}"
                value="{{ $current }}"
            >
        </label>
        <button class="btn btn-sm btn-outline-secondary" type="submit">Go</button>
    </form>

    <nav class="siswa-index-pagination__pages" aria-label="Navigasi halaman GTK">
        @if ($gtks->onFirstPage())
            <span class="siswa-index-page is-disabled" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="siswa-index-page" href="{{ $gtks->previousPageUrl() }}">Sebelumnya</a>
        @endif

        @if ($start > 1)
            <a class="siswa-index-page" href="{{ $gtks->url(1) }}">1</a>
            @if ($start > 2)
                <span class="siswa-index-page is-ellipsis" aria-hidden="true">…</span>
            @endif
        @endif

        @for ($page = $start; $page <= $end; $page++)
            @if ($page === $current)
                <span class="siswa-index-page is-current" aria-current="page">{{ $page }}</span>
            @else
                <a class="siswa-index-page" href="{{ $gtks->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($end < $last)
            @if ($end < $last - 1)
                <span class="siswa-index-page is-ellipsis" aria-hidden="true">…</span>
            @endif
            <a class="siswa-index-page" href="{{ $gtks->url($last) }}">{{ $last }}</a>
        @endif

        @if ($gtks->hasMorePages())
            <a class="siswa-index-page" href="{{ $gtks->nextPageUrl() }}">Selanjutnya</a>
        @else
            <span class="siswa-index-page is-disabled" aria-disabled="true">Selanjutnya</span>
        @endif
    </nav>
</div>
