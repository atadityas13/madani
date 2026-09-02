@php
    $ortu = $siswa->orangTuas->firstWhere('peran', $peran);
    $input = "ortu[$peran]";
    $old = "ortu.$peran";
    $judul = ['ayah' => 'Ayah kandung', 'ibu' => 'Ibu kandung', 'wali' => 'Wali'][$peran];
    $statusWali = old($old.'.status', $ortu?->status ?? 'Sama dengan ayah kandung');
    if ($statusWali === 'Isi sendiri') {
        $statusWali = 'Lainnya';
    }
    $kkSamaAyah = (bool) old($old.'.sama_dengan_ayah', $ortu?->sama_dengan_ayah);
    $waliLainnya = $statusWali === 'Lainnya';
@endphp
<div class="madani-card p-4" data-ortu-blok="{{ $peran }}">
    <div class="stat-label mb-3">{{ $judul }}</div>
    @if ($peran === 'ibu')
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="{{ $input }}[sama_dengan_ayah]" value="1" id="kk_sama_ayah" data-ibu-kk-ayah @checked($kkSamaAyah)>
            <label class="form-check-label" for="kk_sama_ayah">KK sama dengan ayah kandung</label>
        </div>
    @endif
    @if ($peran === 'wali')
        <div class="mb-3">
            <label class="form-label">Status</label>
            <x-emis-select :name="$input.'[status]'" :options="$emis['status_wali']" :value="$statusWali" data-wali-status />
            <div class="form-text">Isian wali hanya muncul jika statusnya selain ayah atau ibu kandung.</div>
        </div>
    @endif

    <div @if ($peran === 'wali' && ! $waliLainnya) hidden @endif data-ortu-detail>
        @if ($peran === 'wali')
            <div class="mb-3">
                <label class="form-label">Hubungan dengan siswa</label>
                <x-emis-select :name="$input.'[hubungan]'" :options="$emis['hubungan_wali']" :value="old($old.'.hubungan', $ortu?->hubungan)" />
            </div>
        @endif
        <div class="mb-3">
            <label class="form-label">Nama lengkap</label>
            <input class="form-control" name="{{ $input }}[nama]" value="{{ old($old.'.nama', $ortu?->nama) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <x-emis-select :name="$input.'[status_hidup]'" :options="$emis['status_hidup']" :value="old($old.'.status_hidup', $ortu?->status_hidup)" />
        </div>
        <div class="mb-3">
            <label class="form-label">NIK</label>
            <input class="form-control" name="{{ $input }}[nik]" value="{{ old($old.'.nik', $ortu?->nik) }}" maxlength="16">
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label">Tempat lahir</label>
                <input class="form-control" name="{{ $input }}[tempat_lahir]" value="{{ old($old.'.tempat_lahir', $ortu?->tempat_lahir) }}">
            </div>
            <div class="col-6">
                <label class="form-label">Tanggal lahir</label>
                <input class="form-control" type="date" name="{{ $input }}[tanggal_lahir]" value="{{ old($old.'.tanggal_lahir', optional($ortu?->tanggal_lahir)->format('Y-m-d')) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Pendidikan terakhir</label>
                <x-emis-select :name="$input.'[pendidikan]'" :options="$emis['pendidikan']" :value="old($old.'.pendidikan', $ortu?->pendidikan)" />
            </div>
            <div class="col-12">
                <label class="form-label">Pekerjaan utama</label>
                <x-emis-select :name="$input.'[pekerjaan]'" :options="$emis['pekerjaan']" :value="old($old.'.pekerjaan', $ortu?->pekerjaan)" />
            </div>
            <div class="col-12">
                <label class="form-label">Penghasilan rata-rata per bulan (Rp)</label>
                <x-emis-select :name="$input.'[penghasilan]'" :options="$emis['penghasilan']" :value="old($old.'.penghasilan', $ortu?->penghasilan)" />
            </div>
            <div class="col-12">
                <label class="form-label">Nomor HP/Whatsapp</label>
                <input class="form-control" name="{{ $input }}[no_hp]" value="{{ old($old.'.no_hp', $ortu?->no_hp) }}" placeholder="628…">
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="{{ $input }}[tidak_punya_hp]" value="1" @checked(old($old.'.tidak_punya_hp', $ortu?->tidak_punya_hp))>
                    <label class="form-check-label small">Tidak memiliki nomor HP/Whatsapp</label>
                </div>
            </div>
        </div>

        <div class="mt-3" data-ortu-alamat @if ($peran === 'ibu' && $kkSamaAyah) hidden @endif>
            <div class="mb-2">
                <label class="form-label">Status tempat tinggal</label>
                <x-emis-select :name="$input.'[status_tempat_tinggal]'" :options="$emis['status_tempat_tinggal_ortu']" :value="old($old.'.status_tempat_tinggal', $ortu?->status_tempat_tinggal)" />
            </div>
            @include('siswa.partials.form-wilayah', [
                'namePrefix' => $input,
                'oldPrefix' => $old,
                'record' => $ortu,
                'root' => 'ortu-'.$peran,
            ])
            <div class="mt-2" data-ortu-kk>
                <label class="form-label">Unggah KK</label>
                <input class="form-control" type="file" name="file_kk_{{ $peran }}" accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text">Maks. 2MB bertipe pdf jpg png</div>
            </div>
        </div>
        @if ($peran === 'ibu')
            <p class="form-text mb-0 mt-2" data-ibu-alamat-note @if (! $kkSamaAyah) hidden @endif>
                Tempat tinggal dan unggah KK mengikuti ayah kandung.
            </p>
        @endif
    </div>
</div>
