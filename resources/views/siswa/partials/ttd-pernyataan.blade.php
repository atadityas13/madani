<table class="ttd-table" width="100%">
    <tr>
        <td width="50%" valign="top" align="left">
            <table class="ttd-inner" width="230" align="left">
                <tr><td>Mengetahui</td></tr>
                <tr><td>Orang tua/wali</td></tr>
                <tr>
                    <td>
                        @if (filled($ttdWaliDataUri))
                            <img class="ttd-img" src="{{ $ttdWaliDataUri }}" alt="TTD wali">
                        @else
                            <div class="ttd-spacer"></div>
                        @endif
                    </td>
                </tr>
                <tr><td><strong>{{ $namaWali ?: '—' }}</strong></td></tr>
            </table>
        </td>
        <td width="50%" valign="top" align="right">
            <table class="ttd-inner" width="230" align="right">
                <tr><td class="ttd-date">{{ $kota }}, {{ $tanggalSurat }}</td></tr>
                <tr><td>Peserta Didik</td></tr>
                <tr>
                    <td>
                        @if (filled($ttdSiswaDataUri))
                            <img class="ttd-img" src="{{ $ttdSiswaDataUri }}" alt="TTD siswa">
                        @else
                            <div class="ttd-spacer"></div>
                        @endif
                    </td>
                </tr>
                <tr><td><strong>{{ $namaSiswa }}</strong></td></tr>
                <tr><td>NISN. {{ $nisn ?: '—' }}</td></tr>
            </table>
        </td>
    </tr>
</table>
