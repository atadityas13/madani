@php
    $ortu = $siswa->orangTuas->firstWhere('peran', $peran);
    $input = "ortu[$peran]";
    $old = "ortu.$peran";
    $judul = ['ayah' => 'Ayah kandung', 'ibu' => 'Ibu kandung', 'wali' => 'Wali'][$peran];
    $statusWali = old($old.'.status', $ortu?->status);
    if ($statusWali === 'Isi sendiri') {
        $statusWali = 'Lainnya';
    }
    $statusHidup = old($old.'.status_hidup', $ortu?->status_hidup);
    $waliLainnya = $statusWali === 'Lainnya';
    $tampilHidup = $statusHidup === 'hidup';
@endphp
<div class="madani-card p-4 h-100" data-ortu-blok="{{ $peran }}">
    <div class="stat-label mb-3">{{ $judul }}</div>
    @if ($peran === 'wali')
        <div class="mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <x-emis-select :name="$input.'[status]'" :options="$emis['status_wali']" :value="$statusWali" required data-wali-status />
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
            <label class="form-label">Nama lengkap {{ $judul }} <span class="text-danger">*</span></label>
            <input class="form-control" name="{{ $input }}[nama]" value="{{ old($old.'.nama', $ortu?->nama) }}" @required($peran !== 'wali')>
        </div>
        <div class="mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <x-emis-select :name="$input.'[status_hidup]'" :options="$emis['status_hidup']" :value="$statusHidup" :required="$peran !== 'wali'" data-status-hidup />
        </div>

        <div class="row g-2" data-ortu-hidup @if (! $tampilHidup) hidden @endif>
            <div class="col-12">
                <label class="form-label">NIK <span class="text-danger">*</span></label>
                <input class="form-control" name="{{ $input }}[nik]" value="{{ old($old.'.nik', $ortu?->nik) }}" maxlength="16">
            </div>
            <div class="col-sm-6">
                <label class="form-label">Tempat lahir <span class="text-danger">*</span></label>
                <input class="form-control" name="{{ $input }}[tempat_lahir]" value="{{ old($old.'.tempat_lahir', $ortu?->tempat_lahir) }}">
            </div>
            <div class="col-sm-6">
                <label class="form-label">Tanggal lahir <span class="text-danger">*</span></label>
                <input class="form-control" type="date" name="{{ $input }}[tanggal_lahir]" value="{{ old($old.'.tanggal_lahir', optional($ortu?->tanggal_lahir)->format('Y-m-d')) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Pendidikan terakhir <span class="text-danger">*</span></label>
                <x-emis-select :name="$input.'[pendidikan]'" :options="$emis['pendidikan']" :value="old($old.'.pendidikan', $ortu?->pendidikan)" />
            </div>
            <div class="col-12">
                <label class="form-label">Pekerjaan utama <span class="text-danger">*</span></label>
                <x-emis-select :name="$input.'[pekerjaan]'" :options="$emis['pekerjaan']" :value="old($old.'.pekerjaan', $ortu?->pekerjaan)" />
            </div>
            <div class="col-12">
                <label class="form-label">Penghasilan rata-rata per bulan (Rp) <span class="text-danger">*</span></label>
                <x-emis-select :name="$input.'[penghasilan]'" :options="$emis['penghasilan']" :value="old($old.'.penghasilan', $ortu?->penghasilan)" />
            </div>
            <div class="col-12">
                <label class="form-label">Nomor HP/Whatsapp <span class="text-danger">*</span></label>
                <input class="form-control" name="{{ $input }}[no_hp]" value="{{ old($old.'.no_hp', $ortu?->no_hp) }}" placeholder="628…">
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="{{ $input }}[tidak_punya_hp]" value="1" @checked(old($old.'.tidak_punya_hp', $ortu?->tidak_punya_hp))>
                    <label class="form-check-label small">Tidak memiliki nomor HP/Whatsapp</label>
                </div>
            </div>
        </div>
    </div>
</div>
