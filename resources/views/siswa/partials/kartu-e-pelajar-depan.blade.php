@php
    $dash = $dash ?? fn ($v) => filled($v) ? $v : '—';
    $namaSingkat = filled($kartu['madrasah']['nama_singkat'] ?? null)
        ? $kartu['madrasah']['nama_singkat']
        : (($kartu['madrasah']['nama'] ?? null) ?: 'Madrasah');
@endphp
<div class="card">
    <table class="inner">
        <tr>
            <td class="hdr">
                <table class="kop">
                    <tr>
                        <td class="kop-logo">
                            @if ($logoKemenagDataUri ?? null)
                                <img src="{{ $logoKemenagDataUri }}" width="28" height="28" alt="Kemenag">
                            @endif
                        </td>
                        <td class="kop-text">
                            <div class="kop-l1">{{ $kartu['madrasah']['instansi_1'] }}</div>
                            <div class="kop-l2">{{ $kartu['madrasah']['instansi_2'] }}</div>
                            <div class="kop-l3">{{ $kartu['madrasah']['nama'] }}</div>
                            @if (filled($kartu['madrasah']['alamat'] ?? null))
                                <div class="kop-l4">{{ $kartu['madrasah']['alamat'] }}</div>
                            @endif
                            @if (filled($kartu['madrasah']['kontak'] ?? null))
                                <div class="kop-l5">{{ $kartu['madrasah']['kontak'] }}</div>
                            @endif
                        </td>
                        <td class="kop-logo">
                            @if ($logoDataUri ?? null)
                                <img src="{{ $logoDataUri }}" width="28" height="28" alt="Madrasah">
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="bdy">
                <table class="top-row">
                    <tr>
                        <td class="top-left">
                            <table class="ribbon">
                                <tr>
                                    <td class="ribbon-main">KARTU PELAJAR</td>
                                </tr>
                                <tr>
                                    <td class="ribbon-gold-row">&nbsp;</td>
                                </tr>
                            </table>
                            <div class="ribbon-gap">&nbsp;</div>
                            <table class="main">
                                <tr>
                                    <td class="foto-col">
                                        @if ($fotoDataUri ?? null)
                                            <img class="foto" src="{{ $fotoDataUri }}" width="42" height="56" alt="Foto">
                                        @elseif ($fotoPlaceholderDataUri ?? null)
                                            <div class="foto-box"><img src="{{ $fotoPlaceholderDataUri }}" width="16" alt=""></div>
                                        @else
                                            <div class="foto-box"></div>
                                        @endif
                                    </td>
                                    <td>
                                        <table class="data">
                                            <tr><td class="lbl">NAMA</td><td class="col">:</td><td class="val">{{ mb_strtoupper((string) $dash($kartu['nama'] ?? null)) }}</td></tr>
                                            <tr><td class="lbl">NISN</td><td class="col">:</td><td class="val">{{ $dash($kartu['nisn'] ?? null) }}</td></tr>
                                            <tr><td class="lbl">NIS</td><td class="col">:</td><td class="val">{{ $dash($kartu['nis'] ?? null) }}</td></tr>
                                            <tr><td class="lbl">TTL</td><td class="col">:</td><td class="val">{{ $dash($kartu['ttl'] ?? null) }}</td></tr>
                                            <tr><td class="lbl">JK</td><td class="col">:</td><td class="val">{{ $dash($kartu['jenis_kelamin_label'] ?? null) }}</td></tr>
                                            <tr><td class="lbl">ALAMAT</td><td class="col">:</td><td class="val-alamat">{{ $dash($kartu['alamat'] ?? null) }}</td></tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="top-qr">
                            @if ($qrDataUri ?? null)
                                <img src="{{ $qrDataUri }}" width="42" height="42" alt="QR">
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="cap-row">
                <table class="cap">
                    <tr>
                        <td class="cap-txt">Berlaku selama menjadi siswa {{ $namaSingkat }}</td>
                        <td class="cap-logo">
                            @if ($logoMadaniDataUri ?? null)
                                <img src="{{ $logoMadaniDataUri }}" height="9" alt="MADANI">
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="ftr">
                <table class="ftr-tbl"><tr>
                    <td class="ftr-cell">Kartu Pelajar ini merupakan dokumen resmi yang sah serta dapat dipergunakan untuk keperluan administrasi akademik maupun nonakademik.</td>
                </tr></table>
            </td>
        </tr>
    </table>
</div>
