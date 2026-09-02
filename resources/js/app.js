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

document.querySelectorAll('[data-madani-shell]').forEach((shell) => {
    if (localStorage.getItem('madani-sidebar') === 'collapsed') {
        shell.classList.add('is-sidebar-collapsed');
    }

    shell.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            shell.classList.toggle('is-sidebar-collapsed');
            localStorage.setItem(
                'madani-sidebar',
                shell.classList.contains('is-sidebar-collapsed') ? 'collapsed' : 'expanded',
            );
        });
    });
});

document.querySelectorAll('[data-nav-group]').forEach((group) => {
    const trigger = group.querySelector('[data-nav-trigger]');

    trigger?.addEventListener('click', () => {
        const shell = document.querySelector('[data-madani-shell]');

        if (shell?.classList.contains('is-sidebar-collapsed')) {
            shell.classList.remove('is-sidebar-collapsed');
            localStorage.setItem('madani-sidebar', 'expanded');
            group.classList.add('is-open');

            return;
        }

        group.classList.toggle('is-open');
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

function bindHidupFields(blok) {
    const status = blok.querySelector('[data-status-hidup]');
    const extra = blok.querySelector('[data-ortu-hidup]');

    if (!status || !extra) {
        return;
    }

    const sync = () => {
        extra.hidden = status.value !== 'hidup';
    };

    status.addEventListener('change', sync);
    sync();
}

function bindOrtuForm() {
    const ibuBlok = document.querySelector('[data-ortu-blok="ibu"]');
    const ayahBlok = document.querySelector('[data-ortu-blok="ayah"]');
    const waliBlok = document.querySelector('[data-ortu-blok="wali"]');

    [ayahBlok, ibuBlok, waliBlok].filter(Boolean).forEach(bindHidupFields);

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

function bindAlamatOrtu() {
    const form = document.querySelector('[data-alamat-form]');

    if (!form) {
        return;
    }

    const ayahBlok = form.querySelector('[data-alamat-ortu="ayah"]');
    const ibuBlok = form.querySelector('[data-alamat-ortu="ibu"]');
    const waliBlok = form.querySelector('[data-alamat-ortu="wali"]');

    if (ibuBlok && ayahBlok) {
        const checkbox = ibuBlok.querySelector('[data-ibu-kk-ayah]');
        const alamat = ibuBlok.querySelector('[data-ortu-alamat]');
        const note = ibuBlok.querySelector('[data-ibu-alamat-note]');
        const ayahStatus = ayahBlok.querySelector('[data-status-tempat-tinggal]');
        const ibuStatus = ibuBlok.querySelector('[data-status-tempat-tinggal]');
        const ayahWilayah = ayahBlok.querySelector('[data-wilayah-root]');
        const ibuWilayah = ibuBlok.querySelector('[data-wilayah-root]');

        const ayahMeninggal = ayahBlok.getAttribute('data-status-hidup') === 'meninggal';
        const ayahMeninggalNote = ibuBlok.querySelector('[data-ayah-meninggal-note]');

        const syncIbuAlamat = () => {
            if (checkbox) {
                checkbox.disabled = ayahMeninggal;
                if (ayahMeninggal) {
                    checkbox.checked = false;
                }
            }

            if (ayahMeninggalNote) {
                ayahMeninggalNote.hidden = !ayahMeninggal;
            }

            const same = Boolean(checkbox?.checked) && !ayahMeninggal;

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
        const status = waliBlok.getAttribute('data-wali-status');
        const alamat = waliBlok.querySelector('[data-ortu-alamat]');
        const note = waliBlok.querySelector('[data-wali-alamat-note]');

        const mengikuti = status === 'Sama dengan ayah kandung' || status === 'Sama dengan ibu kandung';

        if (alamat) {
            alamat.hidden = mengikuti;
        }

        if (note) {
            note.hidden = !mengikuti;
        }
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

function readWilayahFields(root) {
    const field = (name) => root.querySelector(`[data-wilayah-field="${name}"]`)?.value?.trim() || '';

    return {
        blok: field('blok'),
        desa: field('desa'),
        kecamatan: field('kecamatan'),
        kabupaten: field('kabupaten'),
        provinsi: field('provinsi'),
    };
}

function addressQueries(fields) {
    const { desa, kecamatan, kabupaten, provinsi } = fields;
    const queries = [];

    if (desa && kecamatan && kabupaten) {
        queries.push(`${desa}, ${kecamatan}, ${kabupaten}, ${provinsi}, Indonesia`.replace(/, ,/g, ','));
        queries.push(`Desa ${desa}, ${kabupaten}, Indonesia`);
        queries.push(`${desa}, ${kabupaten}, Indonesia`);
    }

    return [...new Set(queries.filter(Boolean))];
}

function includesName(haystack, needle) {
    if (!needle) {
        return false;
    }

    return String(haystack || '').toLowerCase().includes(needle.toLowerCase());
}

function scoreGeocode(item, fields) {
    const type = item.addresstype || item.type || '';
    const name = `${item.name || ''} ${item.display_name || ''}`;
    const address = item.address || {};
    const village = address.village || address.hamlet || address.suburb || '';
    const county = address.county || address.city || address.municipality || '';
    let score = Number(item.importance || 0) * 5;

    if (fields.kabupaten && !includesName(county, fields.kabupaten) && !includesName(name, fields.kabupaten)) {
        return -100;
    }

    const desaMatch = includesName(item.name, fields.desa) || includesName(village, fields.desa);
    const kecamatanMatch = includesName(name, fields.kecamatan) || includesName(village, fields.kecamatan);

    if (desaMatch) {
        score += 60;
    }

    if (kecamatanMatch && !desaMatch) {
        score -= 25;
    }

    if (['village', 'hamlet', 'isolated_dwelling', 'neighbourhood', 'suburb'].includes(type) && desaMatch) {
        score += 20;
    }

    if (type === 'administrative' || item.category === 'boundary' || item.class === 'boundary') {
        score -= 20;
    }

    return score;
}

function pickGeocode(results, fields) {
    if (!Array.isArray(results) || results.length === 0) {
        return null;
    }

    const ranked = results
        .map((item) => ({ item, score: scoreGeocode(item, fields) }))
        .filter((entry) => entry.score >= 50)
        .sort((a, b) => b.score - a.score);

    return ranked[0] || null;
}

async function searchNominatim(query) {
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=8&countrycodes=id&q=${encodeURIComponent(query)}`;
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'Accept-Language': 'id',
        },
    });

    if (!response.ok) {
        return [];
    }

    const results = await response.json();

    return Array.isArray(results) ? results : [];
}

async function geocodeAddress(fields) {
    const queries = addressQueries(fields);

    for (let index = 0; index < queries.length; index += 1) {
        if (index > 0) {
            await new Promise((resolve) => {
                setTimeout(resolve, 1100);
            });
        }

        const picked = pickGeocode(await searchNominatim(queries[index]), fields);

        if (picked) {
            return {
                lat: Number(picked.item.lat),
                lng: Number(picked.item.lon),
            };
        }
    }

    return null;
}

function bindAlamatSiswa() {
    const form = document.querySelector('[data-alamat-form]') || document.querySelector('[data-alamat-siswa]');

    if (!form) {
        return;
    }

    const tempat = form.querySelector('[data-tempat-tinggal]');
    const koordinat = form.querySelector('[data-koordinat]');
    const mapEl = form.querySelector('[data-siswa-map]');
    const note = form.querySelector('[data-alamat-ortu-kosong]');
    const lokasiBtn = form.querySelector('[data-lokasi-saat-ini]');
    const lokasiStatus = form.querySelector('[data-lokasi-status]');
    const root = form.querySelector('[data-wilayah-root="siswa"]');
    const alamatOrtu = readJson('madani-alamat-ortu');
    const alamatAsrama = readJson('madani-alamat-asrama');
    const defaultCenter = parseKoordinat(alamatAsrama.koordinat) || { lat: -7.043314, lng: 108.353711 };
    const statusDefault = 'Geser penanda di peta untuk menyesuaikan titik lokasi rumah.';
    let geocodeTimer = null;
    let map = null;
    let marker = null;
    let pinSource = koordinat?.value ? 'saved' : 'auto';

    const setStatus = (message) => {
        if (lokasiStatus) {
            lokasiStatus.textContent = message || statusDefault;
        }
    };

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
                pinSource = 'manual';
                setStatus();
            });
        }

        map.setView(latlng, zoom);
    };

    const applyAsramaPin = () => {
        const parsed = parseKoordinat(alamatAsrama.koordinat);

        if (!parsed) {
            return false;
        }

        setMarker(parsed, 17);
        if (koordinat) {
            koordinat.value = alamatAsrama.koordinat;
        }
        pinSource = 'asrama';
        setStatus();

        return true;
    };

    const copyAlamatDariOrtu = () => {
        if (!root) {
            return false;
        }

        for (const peran of ['ayah', 'ibu', 'wali']) {
            const blok = document.querySelector(`[data-alamat-ortu="${peran}"]`);

            if (blok?.getAttribute('data-status-hidup') === 'meninggal') {
                continue;
            }

            const sumber = document.querySelector(`[data-wilayah-root="ortu-${peran}"]`);
            const desa = sumber?.querySelector('[data-wilayah-field="desa"]')?.value?.trim();

            if (sumber && desa) {
                copyWilayah(sumber, root);

                return true;
            }
        }

        return false;
    };

    const applyStatus = () => {
        const status = tempat?.value || '';

        if (status === 'Asrama Madrasah' && root?._wilayah) {
            root._wilayah.apply(alamatAsrama);
            applyAsramaPin();
            if (note) {
                note.hidden = true;
            }
            return;
        }

        if (status === 'Tinggal dengan Orang Tua/Wali') {
            const copied = copyAlamatDariOrtu();
            const adaAlamat = copied || Boolean(alamatOrtu.desa);

            if (note) {
                note.hidden = adaAlamat;
            }

            if (! copied && adaAlamat && root?._wilayah) {
                root._wilayah.apply(alamatOrtu);
            }
        } else if (note) {
            note.hidden = true;
        }
    };

    const shouldAutoGeocode = () => pinSource === 'auto' || pinSource === 'geocode';

    const geocodeNow = async () => {
        if (!root || tempat?.value === 'Asrama Madrasah' || !shouldAutoGeocode()) {
            return;
        }

        const fields = readWilayahFields(root);

        if (!fields.desa && !fields.kecamatan) {
            return;
        }

        const result = await geocodeAddress(fields);

        if (!shouldAutoGeocode()) {
            return;
        }

        if (!result) {
            setStatus();
            return;
        }

        setMarker(result, 16);
        if (koordinat) {
            koordinat.value = formatKoordinat(result);
        }
        pinSource = 'geocode';
        setStatus();
    };

    const scheduleGeocode = () => {
        if (!shouldAutoGeocode() || tempat?.value === 'Asrama Madrasah') {
            return;
        }

        clearTimeout(geocodeTimer);
        geocodeTimer = setTimeout(() => {
            geocodeNow();
        }, 700);
    };

    const ambilLokasiSaatIni = () => {
        if (!navigator.geolocation) {
            setStatus('Browser tidak mendukung lokasi perangkat.');
            return;
        }

        lokasiBtn.disabled = true;
        setStatus('Mengambil lokasi perangkat…');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const latlng = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };

                setMarker(latlng, 18);
                if (koordinat) {
                    koordinat.value = formatKoordinat(latlng);
                }
                pinSource = 'gps';
                setStatus();
                lokasiBtn.disabled = false;
            },
            (error) => {
                const messages = {
                    1: 'Izin lokasi ditolak. Izinkan akses lokasi di browser.',
                    2: 'Lokasi perangkat tidak tersedia.',
                    3: 'Waktu mengambil lokasi habis. Coba lagi.',
                };

                setStatus(messages[error?.code] || 'Gagal mengambil lokasi perangkat.');
                lokasiBtn.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
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
        if (tempat.value === 'Asrama Madrasah') {
            applyStatus();
            return;
        }

        applyStatus();
        if (pinSource === 'asrama') {
            pinSource = 'auto';
        }
        scheduleGeocode();
    });

    root?.addEventListener('wilayah:changed', () => {
        if (tempat?.value === 'Asrama Madrasah') {
            return;
        }

        scheduleGeocode();
    });

    ['ortu-ayah', 'ortu-ibu', 'ortu-wali'].forEach((id) => {
        document.querySelector(`[data-wilayah-root="${id}"]`)?.addEventListener('wilayah:changed', () => {
            if (tempat?.value === 'Tinggal dengan Orang Tua/Wali') {
                copyAlamatDariOrtu();
                scheduleGeocode();
            }
        });
    });

    lokasiBtn?.addEventListener('click', ambilLokasiSaatIni);

    if (koordinat?.value) {
        const parsed = parseKoordinat(koordinat.value);
        if (parsed) {
            setMarker(parsed);
        }
    } else if (tempat?.value !== 'Asrama Madrasah') {
        scheduleGeocode();
    }
}

function bindKebutuhanDisabilitas() {
    const kebutuhanSelect = document.querySelector('[data-kebutuhan-khusus-select]');
    const kebutuhanLainnya = document.querySelector('[data-kebutuhan-khusus-lainnya]');
    const disabilitasRoot = document.querySelector('[data-disabilitas]');
    const disabilitasLainnya = document.querySelector('[data-disabilitas-lainnya]');
    const boxes = [...document.querySelectorAll('[data-disabilitas-item]')];

    const syncKebutuhan = () => {
        if (!kebutuhanLainnya) {
            return;
        }

        kebutuhanLainnya.hidden = kebutuhanSelect?.value !== 'Lainnya';
    };

    const syncDisabilitas = (changed) => {
        if (!boxes.length) {
            return;
        }

        if (changed?.value === 'Tidak Ada' && changed.checked) {
            boxes.forEach((box) => {
                if (box !== changed) {
                    box.checked = false;
                }
            });
        } else if (changed && changed.value !== 'Tidak Ada' && changed.checked) {
            boxes.forEach((box) => {
                if (box.value === 'Tidak Ada') {
                    box.checked = false;
                }
            });
        }

        if (disabilitasLainnya) {
            disabilitasLainnya.hidden = !boxes.some((box) => box.value === 'Lainnya' && box.checked);
        }
    };

    kebutuhanSelect?.addEventListener('change', syncKebutuhan);
    disabilitasRoot?.addEventListener('change', (event) => {
        if (event.target?.matches('[data-disabilitas-item]')) {
            syncDisabilitas(event.target);
        }
    });

    syncKebutuhan();
    syncDisabilitas();
}

function bindIjazahSesuai() {
    document.querySelectorAll('[data-ijazah-sesuai]').forEach((box) => {
        box.addEventListener('change', () => {
            if (box.checked && !window.confirm('Apakah anda yakin data ini sesuai?')) {
                box.checked = false;
            }
        });
    });
}

function bindOpenModals() {
    document.querySelectorAll('[data-modal-open]').forEach((node) => {
        window.bootstrap.Modal.getOrCreateInstance(node).show();
    });
}

function bindPeranUser() {
    document.querySelectorAll('[data-peran-user]').forEach((select) => {
        const field = document.querySelector('[data-gtk-field]');

        if (! field) {
            return;
        }

        const sync = () => {
            field.hidden = select.value !== 'wali_kelas';
        };

        select.addEventListener('change', sync);
        sync();
    });
}

document.querySelectorAll('[data-wilayah-root]').forEach(bindWilayahRoot);
bindOrtuForm();
bindAlamatOrtu();
bindAlamatSiswa();
bindKebutuhanDisabilitas();
bindIjazahSesuai();
bindOpenModals();
bindPeranUser();
