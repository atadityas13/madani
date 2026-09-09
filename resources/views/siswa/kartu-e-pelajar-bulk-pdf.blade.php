<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu E-Pelajar Massal</title>
    <style>
        @page { margin: 24pt 18pt; }
        @include('siswa.partials.kartu-e-pelajar-styles')
        .page-break { page-break-after: always; }
        .page-break:last-child { page-break-after: auto; }
    </style>
</head>
<body>
@php
    $dash = fn ($v) => filled($v) ? $v : '—';
@endphp
@foreach ($pages as $pageIndex => $pageRows)
    <div class="page-break">
        @foreach ($pageRows as $row)
            <div class="person-row">
                <table class="sheet">
                    <tr>
                        <td>
                            @include('siswa.partials.kartu-e-pelajar-depan', $row)
                        </td>
                        <td class="gap"></td>
                        <td>
                            @include('siswa.partials.kartu-e-pelajar-belakang', $row)
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
@endforeach
</body>
</html>
