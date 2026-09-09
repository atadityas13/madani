@php
    $ikrarItems = $ikrarItems ?? [
        'Belajar dengan baik',
        'Menghormati orang tua',
        'Menghormati guru',
        'Rukun sama teman',
        'Mencintai tanah air Indonesia',
    ];
@endphp
<div class="card">
    <table class="inner">
        <tr><td class="bh">IKRAR PELAJAR INDONESIA</td></tr>
        <tr><td class="bgold">&nbsp;</td></tr>
        <tr>
            <td class="bc"@if ($bgBelakangDataUri ?? null) style="background-image: url('{{ $bgBelakangDataUri }}'); background-position: center; background-repeat: no-repeat; background-size: cover;"@endif>
                <div class="bc-lead">Kami Pelajar Indonesia, berikrar untuk:</div>
                @foreach ($ikrarItems as $i => $item)
                    <div class="bc-item">{{ $i + 1 }}.&nbsp;&nbsp;{{ $item }}</div>
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="bf">
                <table class="bf-tbl"><tr>
                    <td class="bf-cell">Madrasah Maju, Bermutu, Mendunia.</td>
                </tr></table>
            </td>
        </tr>
    </table>
</div>
