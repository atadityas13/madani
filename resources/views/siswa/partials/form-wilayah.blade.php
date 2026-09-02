@php
    $namePrefix = $namePrefix ?? '';
    $oldPrefix = $oldPrefix ?? '';
    $record = $record ?? null;
    $root = $root ?? 'siswa';
    $wide = $wide ?? false;
    $colMain = $wide ? 'col-md-3' : 'col-12';

    $fieldName = function (string $field) use ($namePrefix): string {
        return $namePrefix === '' ? $field : $namePrefix.'['.$field.']';
    };
    $fieldValue = function (string $field) use ($oldPrefix, $record) {
        $key = $oldPrefix === '' ? $field : $oldPrefix.'.'.$field;

        return old($key, $record?->{$field});
    };

    $valProvinsi = (string) $fieldValue('provinsi');
    $valKabupaten = (string) $fieldValue('kota');
    $valKecamatan = (string) $fieldValue('kecamatan');
    $valDesa = (string) $fieldValue('desa');
    $valBlok = (string) $fieldValue('blok');
    $valRt = (string) $fieldValue('rt');
    $valRw = (string) $fieldValue('rw');
    $valKodePos = (string) $fieldValue('kode_pos');
    $valAlamat = (string) $fieldValue('alamat');
    $nameProvinsi = $fieldName('provinsi');
    $nameKabupaten = $fieldName('kota');
    $nameKecamatan = $fieldName('kecamatan');
    $nameDesa = $fieldName('desa');
    $nameBlok = $fieldName('blok');
    $nameRt = $fieldName('rt');
    $nameRw = $fieldName('rw');
    $nameKodePos = $fieldName('kode_pos');
    $nameAlamat = $fieldName('alamat');

    $optProvinsi = \App\Support\Wilayah::options(array_keys(\App\Support\Wilayah::tree()));
    $optKabupaten = \App\Support\Wilayah::options($valProvinsi !== '' ? \App\Support\Wilayah::kabupaten($valProvinsi) : []);
    $optKecamatan = \App\Support\Wilayah::options($valProvinsi !== '' && $valKabupaten !== '' ? \App\Support\Wilayah::kecamatan($valProvinsi, $valKabupaten) : []);
    $optDesa = \App\Support\Wilayah::options($valProvinsi !== '' && $valKabupaten !== '' && $valKecamatan !== '' ? \App\Support\Wilayah::desa($valProvinsi, $valKabupaten, $valKecamatan) : []);
@endphp
<div class="row g-2" data-wilayah-root="{{ $root }}">
    <div class="{{ $colMain }}">
        <label class="form-label">Provinsi</label>
        <x-emis-select :name="$nameProvinsi" :options="$optProvinsi" :value="$valProvinsi" data-wilayah-field="provinsi" />
    </div>
    <div class="{{ $colMain }}" data-wilayah-step="kabupaten" @if ($valProvinsi === '') hidden @endif>
        <label class="form-label">Kabupaten</label>
        <x-emis-select :name="$nameKabupaten" :options="$optKabupaten" :value="$valKabupaten" data-wilayah-field="kabupaten" />
    </div>
    <div class="{{ $colMain }}" data-wilayah-step="kecamatan" @if ($valKabupaten === '') hidden @endif>
        <label class="form-label">Kecamatan</label>
        <x-emis-select :name="$nameKecamatan" :options="$optKecamatan" :value="$valKecamatan" data-wilayah-field="kecamatan" />
    </div>
    <div class="{{ $colMain }}" data-wilayah-step="desa" @if ($valKecamatan === '') hidden @endif>
        <label class="form-label">Desa</label>
        <x-emis-select :name="$nameDesa" :options="$optDesa" :value="$valDesa" data-wilayah-field="desa" />
    </div>
    <div class="col-12" data-wilayah-step="detail" @if ($valDesa === '') hidden @endif>
        <div class="row g-2">
            <div class="col-12">
                <label class="form-label">Blok</label>
                <input class="form-control" name="{{ $nameBlok }}" value="{{ $valBlok }}" data-wilayah-field="blok">
            </div>
            <div class="col-4">
                <label class="form-label">RT</label>
                <input class="form-control" name="{{ $nameRt }}" value="{{ $valRt }}" maxlength="5" data-wilayah-field="rt">
            </div>
            <div class="col-4">
                <label class="form-label">RW</label>
                <input class="form-control" name="{{ $nameRw }}" value="{{ $valRw }}" maxlength="5" data-wilayah-field="rw">
            </div>
            <div class="col-4">
                <label class="form-label">Kode pos</label>
                <input class="form-control" name="{{ $nameKodePos }}" value="{{ $valKodePos }}" maxlength="10" data-wilayah-field="kode_pos">
            </div>
            <div class="col-12">
                <label class="form-label">Alamat lengkap</label>
                <input class="form-control" name="{{ $nameAlamat }}" value="{{ $valAlamat }}" data-wilayah-field="alamat" readonly>
            </div>
        </div>
    </div>
</div>
