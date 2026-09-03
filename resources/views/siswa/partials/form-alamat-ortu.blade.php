@php
    $ortu = $siswa->orangTuas->firstWhere('peran', $peran);
    $input = "ortu[$peran]";
    $old = "ortu.$peran";
    $judul = ['ayah' => 'Ayah Kandung', 'ibu' => 'Ibu Kandung', 'wali' => 'Wali'][$peran];
    $statusHidup = (string) ($ortu?->status_hidup);
    $ayahHidup = (string) ($siswa->orangTuas->firstWhere('peran', 'ayah')?->status_hidup);
    $ibuHidup = (string) ($siswa->orangTuas->firstWhere('peran', 'ibu')?->status_hidup);
    $meninggal = $statusHidup === 'meninggal';
    $ayahMeninggal = $ayahHidup === 'meninggal';
    $ibuMeninggal = $ibuHidup === 'meninggal';
    $kkSamaAyah = ! $ayahMeninggal && (bool) old($old.'.sama_dengan_ayah', $ortu?->sama_dengan_ayah);
    $statusWali = (string) old('ortu.wali.status', $siswa->orangTuas->firstWhere('peran', 'wali')?->status);
    if ($statusWali === 'Isi sendiri') {
        $statusWali = 'Lainnya';
    }
    $waliMengikutiAyah = $statusWali === 'Sama dengan ayah kandung' && ! $ayahMeninggal;
    $waliMengikutiIbu = $statusWali === 'Sama dengan ibu kandung' && ! $ibuMeninggal;
    $waliIsiSendiri = $peran === 'wali' && (
        $statusWali === 'Lainnya'
        || ($statusWali === 'Sama dengan ayah kandung' && $ayahMeninggal)
        || ($statusWali === 'Sama dengan ibu kandung' && $ibuMeninggal)
    );
    $waliMengikuti = $peran === 'wali' && ($waliMengikutiAyah || $waliMengikutiIbu);
    $sembunyikanForm = $meninggal || ($peran === 'ibu' && $kkSamaAyah) || ($peran === 'wali' && ! $waliIsiSendiri);
    $catatanWali = $waliMengikutiIbu
        ? 'Alamat wali mengikuti ibu kandung.'
        : 'Alamat wali mengikuti ayah kandung.';
    if ($peran === 'wali' && $statusWali === 'Sama dengan ayah kandung' && $ayahMeninggal) {
        $catatanWali = 'Ayah kandung sudah meninggal dunia. Lengkapi alamat wali, atau ubah status wali di tab Orang tua.';
    } elseif ($peran === 'wali' && $statusWali === 'Sama dengan ibu kandung' && $ibuMeninggal) {
        $catatanWali = 'Ibu kandung sudah meninggal dunia. Lengkapi alamat wali, atau ubah status wali di tab Orang tua.';
    }
@endphp
<div
    class="madani-card p-4"
    data-alamat-ortu="{{ $peran }}"
    data-status-hidup="{{ $statusHidup }}"
    @if ($peran === 'wali') data-wali-status="{{ $statusWali }}" @endif
>
    <div class="stat-label mb-3">Alamat {{ $judul }}</div>
    @if ($meninggal)
        <p class="form-text mb-0">Alamat tidak diisi karena sudah meninggal dunia.</p>
    @else
        @if ($peran === 'ibu' && ! $ayahMeninggal)
            <div class="form-check mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="{{ $input }}[sama_dengan_ayah]"
                    value="1"
                    id="alamat_sama_ayah"
                    data-ibu-kk-ayah
                    @checked($kkSamaAyah)
                >
                <label class="form-check-label" for="alamat_sama_ayah">Alamat sama dengan ayah kandung</label>
            </div>
        @endif
        @if ($peran === 'wali' && $waliMengikuti)
            <p class="form-text mb-0" data-wali-alamat-note>{{ $catatanWali }}</p>
        @elseif ($peran === 'wali' && $statusWali === 'Sama dengan ayah kandung' && $ayahMeninggal)
            <p class="form-text mb-3" data-wali-alamat-note>{{ $catatanWali }}</p>
        @elseif ($peran === 'wali' && $statusWali === 'Sama dengan ibu kandung' && $ibuMeninggal)
            <p class="form-text mb-3" data-wali-alamat-note>{{ $catatanWali }}</p>
        @elseif ($peran === 'wali' && ! $waliIsiSendiri)
            <p class="form-text mb-0" data-wali-alamat-note>Status wali dipilih di tab Orang tua. Alamat mengikuti pilihan tersebut.</p>
        @endif
        <div data-ortu-alamat @if ($sembunyikanForm) hidden @endif>
            <div class="mb-3">
                <label class="form-label">Status tempat tinggal</label>
                <x-emis-select :name="$input.'[status_tempat_tinggal]'" :options="$emis['status_tempat_tinggal_ortu']" :value="old($old.'.status_tempat_tinggal', $ortu?->status_tempat_tinggal)" data-status-tempat-tinggal />
            </div>
            @include('siswa.partials.form-wilayah', [
                'namePrefix' => $input,
                'oldPrefix' => $old,
                'record' => $ortu,
                'root' => 'ortu-'.$peran,
                'wide' => true,
            ])
        </div>
        @if ($peran === 'ibu')
            <p class="form-text mb-0 mt-2" data-ibu-alamat-note @if (! $kkSamaAyah) hidden @endif>
                Alamat mengikuti ayah kandung.
            </p>
        @endif
    @endif
</div>
