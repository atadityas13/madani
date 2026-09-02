@php
    $ortu = $siswa->orangTuas->firstWhere('peran', $peran);
    $input = "ortu[$peran]";
    $old = "ortu.$peran";
    $judul = ['ayah' => 'Ayah Kandung', 'ibu' => 'Ibu Kandung', 'wali' => 'Wali'][$peran];
    $kkSamaAyah = (bool) old($old.'.sama_dengan_ayah', $ortu?->sama_dengan_ayah);
    $statusWali = old('ortu.wali.status', $siswa->orangTuas->firstWhere('peran', 'wali')?->status ?? 'Sama dengan ayah kandung');
    if ($statusWali === 'Isi sendiri') {
        $statusWali = 'Lainnya';
    }
    $waliMengikuti = $peran === 'wali' && $statusWali !== 'Lainnya';
    $sembunyikanForm = ($peran === 'ibu' && $kkSamaAyah) || $waliMengikuti;
    $catatanWali = $statusWali === 'Sama dengan ibu kandung'
        ? 'Alamat wali mengikuti ibu kandung.'
        : 'Alamat wali mengikuti ayah kandung.';
@endphp
<div class="madani-card p-4" data-alamat-ortu="{{ $peran }}" @if ($peran === 'wali') data-wali-status="{{ $statusWali }}" @endif>
    <div class="stat-label mb-3">Alamat {{ $judul }}</div>
    @if ($peran === 'ibu')
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="{{ $input }}[sama_dengan_ayah]" value="1" id="alamat_kk_sama_ayah" data-ibu-kk-ayah @checked($kkSamaAyah)>
            <label class="form-check-label" for="alamat_kk_sama_ayah">Alamat dan KK sama dengan ayah kandung</label>
        </div>
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
        <div class="mt-3" data-ortu-kk>
            <label class="form-label">Unggah KK</label>
            <input class="form-control" type="file" name="file_kk_{{ $peran }}" accept=".pdf,.jpg,.jpeg,.png">
            <div class="form-text">Maks. 2MB bertipe pdf jpg png</div>
        </div>
    </div>
    @if ($peran === 'ibu')
        <p class="form-text mb-0 mt-2" data-ibu-alamat-note @if (! $kkSamaAyah) hidden @endif>
            Alamat dan unggah KK mengikuti ayah kandung.
        </p>
    @endif
</div>
