@props([
    'rows' => [],
])

@php
    /** @var array<string, mixed> $rows */
@endphp

<dl class="siswa-detail__list">
    @foreach ($rows as $label => $value)
        @php
            $display = is_string($value) || is_numeric($value)
                ? (filled((string) $value) ? (string) $value : '—')
                : '—';
            $copyable = $display !== '—';
        @endphp
        <div
            class="siswa-detail__row{{ $copyable ? ' is-copyable' : '' }}"
            @if ($copyable)
                role="button"
                tabindex="0"
                data-copy="{{ $display }}"
                title="Klik untuk menyalin"
            @endif
        >
            <dt>{{ $label }}</dt>
            <dd>
                <span class="siswa-detail__sep" aria-hidden="true">:</span>
                <span class="siswa-detail__value">{{ $display }}</span>
            </dd>
        </div>
    @endforeach
</dl>
