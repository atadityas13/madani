import * as bootstrap from 'bootstrap';
import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';

window.bootstrap = bootstrap;

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

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

function readJson(id) {
    const node = document.getElementById(id);

    if (!node) {
        return {};
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch {
        return {};
    }
}

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

    return `Blok/Kp. ${values[0]}, RT. ${values[1]} RW. ${values[2]} Desa ${values[3]} Kec. ${values[4]} Kab. ${values[5]}`;
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

    const apply = (values = {}) => {
        fillSelect(provinsi, wilayahKeys(wilayahTree), values.provinsi || '');
        fillSelect(kabupaten, wilayahKeys(wilayahTree[provinsi.value]), values.kota || '');
        setStep('kabupaten', provinsi.value !== '');
        fillSelect(kecamatan, wilayahKeys(wilayahTree[provinsi.value]?.[kabupaten.value]), values.kecamatan || '');
        setStep('kecamatan', kabupaten.value !== '');
        fillSelect(desa, wilayahKeys(wilayahTree[provinsi.value]?.[kabupaten.value]?.[kecamatan.value]), values.desa || '');
        setStep('desa', kecamatan.value !== '');
        setStep('detail', desa.value !== '');

        if (blok) {
            blok.value = values.blok || '';
        }
        if (rt) {
            rt.value = values.rt || '';
        }
        if (rw) {
            rw.value = values.rw || '';
        }
        if (kodePos) {
            kodePos.value = values.kode_pos
                || wilayahTree[provinsi.value]?.[kabupaten.value]?.[kecamatan.value]?.[desa.value]
                || '';
        }

        syncAlamat();
        root.dispatchEvent(new CustomEvent('wilayah:changed', { bubbles: true }));
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
        root.dispatchEvent(new CustomEvent('wilayah:changed', { bubbles: true }));
    };

    fillSelect(provinsi, wilayahKeys(wilayahTree), provinsi.value);
    onProvinsi(false);

    provinsi.addEventListener('change', () => onProvinsi(true));
    kabupaten.addEventListener('change', () => onKabupaten(true));
    kecamatan.addEventListener('change', () => onKecamatan(true));
    desa.addEventListener('change', () => onDesa(true));
    [blok, rt, rw].forEach((input) => input?.addEventListener('input', () => {
        syncAlamat();
        root.dispatchEvent(new CustomEvent('wilayah:changed', { bubbles: true }));
    }));

    root._wilayah = { apply, field };
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

function parseKoordinat(value) {
    const match = String(value || '').match(/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/);

    if (!match) {
        return null;
    }

    return { lat: Number(match[1]), lng: Number(match[2]) };
}

function formatKoordinat(latlng) {
    return `${Number(latlng.lat).toFixed(6)}, ${Number(latlng.lng).toFixed(6)}`;
}

function addressQuery(root) {
    const field = (name) => root.querySelector(`[data-wilayah-field="${name}"]`)?.value?.trim();

    return ['blok', 'desa', 'kecamatan', 'kabupaten', 'provinsi']
        .map((name) => field(name))
        .filter(Boolean)
        .concat('Indonesia')
        .join(', ');
}

async function geocodeAddress(query) {
    if (!query || query === 'Indonesia') {
        return null;
    }

    const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`;
    const response = await fetch(url, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        return null;
    }

    const results = await response.json();

    if (!results[0]) {
        return null;
    }

    return { lat: Number(results[0].lat), lng: Number(results[0].lon) };
}

function bindAlamatSiswa() {
    const form = document.querySelector('[data-alamat-siswa]');

    if (!form) {
        return;
    }

    const tempat = form.querySelector('[data-tempat-tinggal]');
    const koordinat = form.querySelector('[data-koordinat]');
    const mapEl = form.querySelector('[data-siswa-map]');
    const note = form.querySelector('[data-alamat-ortu-kosong]');
    const root = form.querySelector('[data-wilayah-root="siswa"]');
    const alamatOrtu = readJson('madani-alamat-ortu');
    const alamatAsrama = readJson('madani-alamat-asrama');
    const defaultCenter = parseKoordinat(alamatAsrama.koordinat) || { lat: -7.043314, lng: 108.353711 };
    let geocodeTimer = null;
    let map = null;
    let marker = null;

    const setMarker = (latlng, zoom = 16) => {
        if (!map) {
            return;
        }

        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng, { draggable: true }).addTo(map);
            marker.on('dragend', () => {
                if (koordinat) {
                    koordinat.value = formatKoordinat(marker.getLatLng());
                }
            });
        }

        map.setView(latlng, zoom);
    };

    const applyStatus = () => {
        const status = tempat?.value || '';

        if (status === 'Asrama Madrasah' && root?._wilayah) {
            root._wilayah.apply(alamatAsrama);
            if (koordinat && !koordinat.value && alamatAsrama.koordinat) {
                koordinat.value = alamatAsrama.koordinat;
            }
            if (note) {
                note.hidden = true;
            }
            return;
        }

        if (status === 'Tinggal dengan Orang Tua/Wali') {
            const adaAlamat = Boolean(alamatOrtu.desa);

            if (note) {
                note.hidden = adaAlamat;
            }

            if (adaAlamat && root?._wilayah) {
                root._wilayah.apply(alamatOrtu);
            }
        } else if (note) {
            note.hidden = true;
        }
    };

    const geocodeNow = async () => {
        if (!root) {
            return;
        }

        const query = addressQuery(root);
        const result = await geocodeAddress(query);

        if (result) {
            setMarker(result);
            if (koordinat) {
                koordinat.value = formatKoordinat(result);
            }
            return;
        }

        if (tempat?.value === 'Asrama Madrasah' && alamatAsrama.koordinat) {
            const fallback = parseKoordinat(alamatAsrama.koordinat);
            if (fallback) {
                setMarker(fallback);
                if (koordinat && !koordinat.value) {
                    koordinat.value = alamatAsrama.koordinat;
                }
            }
        }
    };

    const scheduleGeocode = () => {
        clearTimeout(geocodeTimer);
        geocodeTimer = setTimeout(() => {
            geocodeNow();
        }, 700);
    };

    if (mapEl) {
        const initial = parseKoordinat(koordinat?.value) || defaultCenter;
        map = L.map(mapEl).setView(initial, koordinat?.value ? 16 : 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);
        setMarker(initial, koordinat?.value ? 16 : 12);
        setTimeout(() => map.invalidateSize(), 150);
    }

    applyStatus();

    tempat?.addEventListener('change', () => {
        applyStatus();
        scheduleGeocode();
    });

    root?.addEventListener('wilayah:changed', scheduleGeocode);

    koordinat?.addEventListener('change', () => {
        const parsed = parseKoordinat(koordinat.value);
        if (parsed) {
            setMarker(parsed);
        }
    });

    if (koordinat?.value) {
        const parsed = parseKoordinat(koordinat.value);
        if (parsed) {
            setMarker(parsed);
        }
    } else {
        scheduleGeocode();
    }
}

document.querySelectorAll('[data-wilayah-root]').forEach(bindWilayahRoot);
bindOrtuForm();
bindAlamatSiswa();
