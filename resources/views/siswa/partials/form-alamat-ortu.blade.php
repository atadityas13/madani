@php
    $ortu = $siswa->orangTuas->firstWhere('peran', $peran);
    $input = "ortu[$peran]";
    $old = "ortu.$peran";
    $judul = ['ayah' => 'Ayah Kandung', 'ibu' => 'Ibu Kandung', 'wali' => 'Wali'][$peran];
    $statusHidup = (string) ($ortu?->status_hidup);
    $ayahHidup = (string) ($siswa->orangTuas->firstWhere('peran', 'ayah')?->status_hidup);
    $meninggal = $statusHidup === 'meninggal';
    $ayahMeninggal = $ayahHidup === 'meninggal';
    $kkSamaAyah = ! $ayahMeninggal && (bool) old($old.'.sama_dengan_ayah', $ortu?->sama_dengan_ayah);
    $statusWali = (string) old('ortu.wali.status', $siswa->orangTuas->firstWhere('peran', 'wali')?->status);
    if ($statusWali === 'Isi sendiri') {
        $statusWali = 'Lainnya';
    }
    $waliMengikuti = $peran === 'wali' && in_array($statusWali, ['Sama dengan ayah kandung', 'Sama dengan ibu kandung'], true);
    $sembunyikanForm = $meninggal || ($peran === 'ibu' && $kkSamaAyah) || $waliMengikuti;
    $catatanWali = $statusWali === 'Sama dengan ibu kandung'
        ? 'Alamat wali mengikuti ibu kandung.'
        : 'Alamat wali mengikuti ayah kandung.';
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
        @if ($peran === 'ibu')
            <div class="form-check mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="{{ $input }}[sama_dengan_ayah]"
                    value="1"
                    id="alamat_sama_ayah"
                    data-ibu-kk-ayah
                    @checked($kkSamaAyah)
                    @disabled($ayahMeninggal)
                >
                <label class="form-check-label" for="alamat_sama_ayah">Alamat sama dengan ayah kandung</label>
            </div>
            <p class="form-text mb-3" data-ayah-meninggal-note @if (! $ayahMeninggal) hidden @endif>
                Tidak dapat disamakan karena ayah kandung sudah meninggal dunia.
            </p>
        @endif
        @if ($waliMengikuti)
            <p class="form-text mb-0" data-wali-alamat-note>{{ $catatanWali }}</p>
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
