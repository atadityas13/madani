<table class="ttd-table">
    <tr>
        <td class="ttd-side ttd-side-left">
            <div class="ttd-block">
                <div class="ttd-meta">Mengetahui</div>
                <div class="ttd-meta">Orang tua/wali</div>
                @if (filled($ttdWaliDataUri))
                    <img class="ttd-img" src="{{ $ttdWaliDataUri }}" alt="TTD wali">
                @else
                    <div class="ttd-spacer"></div>
                @endif
                <div class="ttd-name"><strong>{{ $namaWali ?: '—' }}</strong></div>
            </div>
        </td>
        <td class="ttd-side ttd-side-right">
            <div class="ttd-block">
                <div class="ttd-meta">{{ $kota }}, {{ $tanggalSurat }}</div>
                <div class="ttd-meta">Peserta Didik</div>
                @if (filled($ttdSiswaDataUri))
                    <img class="ttd-img" src="{{ $ttdSiswaDataUri }}" alt="TTD siswa">
                @else
                    <div class="ttd-spacer"></div>
                @endif
                <div class="ttd-name"><strong>{{ $namaSiswa }}</strong></div>
                <div class="ttd-meta">NISN. {{ $nisn ?: '—' }}</div>
            </div>
        </td>
    </tr>
</table>
