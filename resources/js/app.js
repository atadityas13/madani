import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

document.querySelectorAll('[data-siswa-menu]').forEach((group) => {
    const trigger = group.querySelector('[data-siswa-trigger]');

    trigger?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        group.classList.toggle('open');
    });
});

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-siswa-menu].open').forEach((group) => {
        if (!group.contains(event.target)) {
            group.classList.remove('open');
        }
    });
});

const wilayahTree = (() => {
    const node = document.getElementById('madani-wilayah-data');

    if (!node) {
        return {};
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch {
        return {};
    }
})();

function wilayahKeys(node) {
    return node && typeof node === 'object' ? Object.keys(node) : [];
}

function fillSelect(select, items, current = '') {
    if (!select) {
        return;
    }

    const placeholder = select.querySelector('option[value=""]')?.textContent || 'Pilih';
    select.innerHTML = '';

    const empty = document.createElement('option');
    empty.value = '';
    empty.textContent = placeholder;
    select.appendChild(empty);

    items.forEach((name) => {
        const option = document.createElement('option');
        option.value = name;
        option.textContent = name;
        if (name === current) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

function formatAlamat(blok, rt, rw, desa, kecamatan, kabupaten) {
    const values = [blok, rt, rw, desa, kecamatan, kabupaten].map((value) => (value || '').trim());

    if (values.every((value) => value === '')) {
        return '';
    }

    return `Blok ${values[0]}, RT. ${values[1]} RW. ${values[2]} Desa ${values[3]} Kec. ${values[4]} Kab. ${values[5]}`;
}

function bindWilayahRoot(root) {
    const field = (name) => root.querySelector(`[data-wilayah-field="${name}"]`);
    const step = (name) => root.querySelector(`[data-wilayah-step="${name}"]`);
    const provinsi = field('provinsi');
    const kabupaten = field('kabupaten');
    const kecamatan = field('kecamatan');
    const desa = field('desa');
    const blok = field('blok');
    const rt = field('rt');
    const rw = field('rw');
    const kodePos = field('kode_pos');
    const alamat = field('alamat');

    if (!provinsi || !kabupaten || !kecamatan || !desa) {
        return;
    }

    const setStep = (name, visible) => {
        const node = step(name);
        if (node) {
            node.hidden = !visible;
        }
    };

    const syncAlamat = () => {
        if (!alamat) {
            return;
        }

        alamat.value = formatAlamat(
            blok?.value,
            rt?.value,
            rw?.value,
            desa.value,
            kecamatan.value,
            kabupaten.value,
        );
    };

    const onProvinsi = (resetChildren = true) => {
        const items = wilayahKeys(wilayahTree[provinsi.value]);
        fillSelect(kabupaten, items, resetChildren ? '' : kabupaten.value);
        setStep('kabupaten', provinsi.value !== '');
        onKabupaten(resetChildren);
    };

    const onKabupaten = (resetChildren = true) => {
        const items = wilayahKeys(wilayahTree[provinsi.value]?.[kabupaten.value]);
        fillSelect(kecamatan, items, resetChildren ? '' : kecamatan.value);
        setStep('kecamatan', kabupaten.value !== '');
        onKecamatan(resetChildren);
    };

    const onKecamatan = (resetChildren = true) => {
        const items = wilayahKeys(wilayahTree[provinsi.value]?.[kabupaten.value]?.[kecamatan.value]);
        fillSelect(desa, items, resetChildren ? '' : desa.value);
        setStep('desa', kecamatan.value !== '');
        onDesa(resetChildren);
    };

    const onDesa = (resetChildren = true) => {
        setStep('detail', desa.value !== '');

        if (kodePos && (resetChildren || kodePos.value === '')) {
            const kode = wilayahTree[provinsi.value]?.[kabupaten.value]?.[kecamatan.value]?.[desa.value] || '';
            kodePos.value = kode;
        }

        if (resetChildren && desa.value === '') {
            if (blok) blok.value = '';
            if (rt) rt.value = '';
            if (rw) rw.value = '';
            if (kodePos) kodePos.value = '';
        }

        syncAlamat();
    };

    fillSelect(provinsi, wilayahKeys(wilayahTree), provinsi.value);
    onProvinsi(false);

    provinsi.addEventListener('change', () => onProvinsi(true));
    kabupaten.addEventListener('change', () => onKabupaten(true));
    kecamatan.addEventListener('change', () => onKecamatan(true));
    desa.addEventListener('change', () => onDesa(true));
    [blok, rt, rw].forEach((input) => input?.addEventListener('input', syncAlamat));
}

function copyWilayah(fromRoot, toRoot) {
    const names = ['provinsi', 'kabupaten', 'kecamatan', 'desa', 'blok', 'rt', 'rw', 'kode_pos', 'alamat'];

    names.forEach((name) => {
        const source = fromRoot.querySelector(`[data-wilayah-field="${name}"]`);
        const target = toRoot.querySelector(`[data-wilayah-field="${name}"]`);

        if (!source || !target) {
            return;
        }

        if (target.tagName === 'SELECT') {
            fillSelect(target, [...source.options].map((option) => option.value).filter(Boolean), source.value);
        } else {
            target.value = source.value;
        }
    });

    ['kabupaten', 'kecamatan', 'desa', 'detail'].forEach((name) => {
        const sourceStep = fromRoot.querySelector(`[data-wilayah-step="${name}"]`);
        const targetStep = toRoot.querySelector(`[data-wilayah-step="${name}"]`);

        if (sourceStep && targetStep) {
            targetStep.hidden = sourceStep.hidden;
        }
    });
}

function bindOrtuForm() {
    const ibuBlok = document.querySelector('[data-ortu-blok="ibu"]');
    const ayahBlok = document.querySelector('[data-ortu-blok="ayah"]');
    const waliBlok = document.querySelector('[data-ortu-blok="wali"]');

    if (ibuBlok && ayahBlok) {
        const checkbox = ibuBlok.querySelector('[data-ibu-kk-ayah]');
        const alamat = ibuBlok.querySelector('[data-ortu-alamat]');
        const note = ibuBlok.querySelector('[data-ibu-alamat-note]');
        const ayahStatus = ayahBlok.querySelector('[name="ortu[ayah][status_tempat_tinggal]"]');
        const ibuStatus = ibuBlok.querySelector('[name="ortu[ibu][status_tempat_tinggal]"]');
        const ayahWilayah = ayahBlok.querySelector('[data-wilayah-root]');
        const ibuWilayah = ibuBlok.querySelector('[data-wilayah-root]');

        const syncIbuAlamat = () => {
            const same = Boolean(checkbox?.checked);

            if (alamat) {
                alamat.hidden = same;
            }

            if (note) {
                note.hidden = !same;
            }

            if (same && ayahWilayah && ibuWilayah) {
                copyWilayah(ayahWilayah, ibuWilayah);

                if (ayahStatus && ibuStatus) {
                    ibuStatus.value = ayahStatus.value;
                }
            }
        };

        checkbox?.addEventListener('change', syncIbuAlamat);
        ayahBlok.addEventListener('change', () => {
            if (checkbox?.checked) {
                syncIbuAlamat();
            }
        });
        ayahBlok.addEventListener('input', () => {
            if (checkbox?.checked) {
                syncIbuAlamat();
            }
        });
        syncIbuAlamat();
    }

    if (waliBlok) {
        const status = waliBlok.querySelector('[data-wali-status]');
        const detail = waliBlok.querySelector('[data-ortu-detail]');

        const syncWali = () => {
            const lainnya = status?.value === 'Lainnya' || status?.value === 'Isi sendiri';

            if (detail) {
                detail.hidden = !lainnya;
            }
        };

        status?.addEventListener('change', syncWali);
        syncWali();
    }
}

document.querySelectorAll('[data-wilayah-root]').forEach(bindWilayahRoot);
bindOrtuForm();
