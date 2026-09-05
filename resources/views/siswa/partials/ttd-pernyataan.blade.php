<table class="ttd-table">
    <tr>
        <td>
            <div>Mengetahui</div>
            <div>Orang tua/wali</div>
            <div style="margin-top: 4px;"><strong>{{ $namaWali ?: '—' }}</strong></div>
            @if (filled($ttdWaliDataUri))
                <img class="ttd-img" src="{{ $ttdWaliDataUri }}" alt="TTD wali">
            @else
                <div class="ttd-spacer"></div>
            @endif
        </td>
        <td>
            <div>{{ $kota }}, {{ $tanggalSurat }}</div>
            <div>Peserta Didik</div>
            @if (filled($ttdSiswaDataUri))
                <img class="ttd-img" src="{{ $ttdSiswaDataUri }}" alt="TTD siswa">
            @else
                <div class="ttd-spacer"></div>
            @endif
            <div><strong>{{ $namaSiswa }}</strong></div>
            <div>NISN. {{ $nisn ?: '—' }}</div>
        </td>
    </tr>
</table>
