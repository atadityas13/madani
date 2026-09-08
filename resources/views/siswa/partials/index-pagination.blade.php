@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $siswas */
    $from = $siswas->firstItem() ?? 0;
    $to = $siswas->lastItem() ?? 0;
    $total = $siswas->total();
    $current = $siswas->currentPage();
    $last = max($siswas->lastPage(), 1);
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

    <form class="siswa-index-pagination__controls" method="GET" action="{{ route('siswa.index') }}">
        <input type="hidden" name="q" value="{{ $q }}">
        <input type="hidden" name="tingkat" value="{{ $tingkat }}">
        <input type="hidden" name="rombel_id" value="{{ $rombelId }}">

        <label class="siswa-index-pagination__label" for="siswaPerPage">
            Tampil
            <select class="form-select form-select-sm" id="siswaPerPage" name="per_page" onchange="this.form.page.value='1'; this.form.submit()">
                @foreach ([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage === (string) $size)>{{ $size }}</option>
                @endforeach
                <option value="all" @selected($perPage === 'all')>Semua</option>
            </select>
        </label>

        <label class="siswa-index-pagination__label" for="siswaPageJump">
            Halaman
            <input
                class="form-control form-control-sm siswa-index-pagination__page-input"
                id="siswaPageJump"
                type="number"
                name="page"
                min="1"
                max="{{ $last }}"
                value="{{ $current }}"
            >
        </label>
        <button class="btn btn-sm btn-outline-secondary" type="submit">Go</button>
    </form>

    <nav class="siswa-index-pagination__pages" aria-label="Navigasi halaman siswa">
        @if ($siswas->onFirstPage())
            <span class="siswa-index-page is-disabled" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="siswa-index-page" href="{{ $siswas->previousPageUrl() }}">Sebelumnya</a>
        @endif

        @if ($start > 1)
            <a class="siswa-index-page" href="{{ $siswas->url(1) }}">1</a>
            @if ($start > 2)
                <span class="siswa-index-page is-ellipsis" aria-hidden="true">…</span>
            @endif
        @endif

        @for ($page = $start; $page <= $end; $page++)
            @if ($page === $current)
                <span class="siswa-index-page is-current" aria-current="page">{{ $page }}</span>
            @else
                <a class="siswa-index-page" href="{{ $siswas->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($end < $last)
            @if ($end < $last - 1)
                <span class="siswa-index-page is-ellipsis" aria-hidden="true">…</span>
            @endif
            <a class="siswa-index-page" href="{{ $siswas->url($last) }}">{{ $last }}</a>
        @endif

        @if ($siswas->hasMorePages())
            <a class="siswa-index-page" href="{{ $siswas->nextPageUrl() }}">Selanjutnya</a>
        @else
            <span class="siswa-index-page is-disabled" aria-disabled="true">Selanjutnya</span>
        @endif
    </nav>
</div>
