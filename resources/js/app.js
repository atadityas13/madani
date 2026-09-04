import * as bootstrap from 'bootstrap';
import L from 'leaflet';
import Swal from 'sweetalert2';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';
import 'sweetalert2/dist/sweetalert2.min.css';

window.bootstrap = bootstrap;

const swalBase = {
    confirmButtonColor: '#1b7a5a',
    cancelButtonColor: '#6b7280',
    customClass: {
        popup: 'madani-swal-popup',
        confirmButton: 'madani-swal-confirm',
        cancelButton: 'madani-swal-cancel',
    },
};

window.madaniAlert = {
    success(message, title = 'Berhasil') {
        return Swal.fire({
            ...swalBase,
            icon: 'success',
            title,
            text: message || '',
            confirmButtonText: 'OK',
        });
    },
    error(message, title = 'Gagal') {
        return Swal.fire({
            ...swalBase,
            icon: 'error',
            title,
            text: message || '',
            confirmButtonText: 'OK',
        });
    },
    info(message, title = 'Info') {
        return Swal.fire({
            ...swalBase,
            icon: 'info',
            title,
            text: message || '',
            confirmButtonText: 'OK',
        });
    },
    warning(message, title = 'Peringatan') {
        return Swal.fire({
            ...swalBase,
            icon: 'warning',
            title,
            text: message || '',
            confirmButtonText: 'OK',
        });
    },
    async confirm({
        title = 'Konfirmasi',
        text = 'Lanjutkan tindakan ini?',
        confirmButtonText = 'Ya',
        cancelButtonText = 'Batal',
        icon = 'question',
    } = {}) {
        const result = await Swal.fire({
            ...swalBase,
            icon,
            title,
            text,
            showCancelButton: true,
            confirmButtonText,
            cancelButtonText,
            reverseButtons: true,
        });

        return result.isConfirmed;
    },
    loading(message = 'Memproses…') {
        Swal.fire({
            ...swalBase,
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });
    },
    closeLoading() {
        Swal.close();
    },
};

function readMadaniFlash() {
    const node = document.getElementById('madani-flash');

    if (! node) {
        return;
    }

    try {
        const flash = JSON.parse(node.textContent || '{}');

        if (flash.error) {
            window.madaniAlert.error(flash.error);

            return;
        }

        if (flash.warning) {
            window.madaniAlert.warning(flash.warning);

            return;
        }

        if (flash.status) {
            window.madaniAlert.success(flash.status);
        }
    } catch (_error) {
        // ignore malformed flash payload
    }
}

function bindConfirmForms() {
    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (! (form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.confirmHandled === '1') {
            form.dataset.confirmHandled = '0';

            return;
        }

        const message = form.getAttribute('data-confirm');

        if (! message) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const ok = await window.madaniAlert.confirm({
            title: form.getAttribute('data-confirm-title') || 'Konfirmasi',
            text: message,
            confirmButtonText: form.getAttribute('data-confirm-ok') || 'Ya',
            icon: 'warning',
        });

        if (! ok) {
            return;
        }

        form.dataset.confirmHandled = '1';
        form.requestSubmit();
    }, true);
}

function bindFormLoading() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (! (form instanceof HTMLFormElement)) {
            return;
        }

        if (form.hasAttribute('data-no-loading')) {
            return;
        }

        const method = (form.getAttribute('method') || 'get').toLowerCase();

        if (method !== 'post') {
            return;
        }

        if (form.hasAttribute('data-confirm') && form.dataset.confirmHandled !== '1') {
            return;
        }

        window.madaniAlert.loading(form.getAttribute('data-loading-text') || 'Menyimpan…');
    });
}

function bindStaticWarnings() {
    document.querySelectorAll('[data-swal-warning]').forEach((node) => {
        const message = node.getAttribute('data-swal-warning') || node.textContent?.trim();

        if (message) {
            window.madaniAlert.warning(message);
        }
    });
}

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

    [ayahBlok, ibuBlok].filter(Boolean).forEach(bindHidupFields);

    if (waliBlok) {
        const status = waliBlok.querySelector('[data-wali-status]');
        const locked = waliBlok.querySelector('[data-wali-status-locked]');
        const note = waliBlok.querySelector('[data-wali-wajib-note]');
        const optionNote = waliBlok.querySelector('[data-wali-option-note]');
        const detail = waliBlok.querySelector('[data-ortu-detail]');
        const ayahStatus = ayahBlok?.querySelector('[data-status-hidup]');
        const ibuStatus = ibuBlok?.querySelector('[data-status-hidup]');
        const opsiAyah = 'Sama dengan ayah kandung';
        const opsiIbu = 'Sama dengan ibu kandung';

        const setOptionDisabled = (value, disabled) => {
            const option = [...(status?.options || [])].find((item) => item.value === value);

            if (option) {
                option.disabled = disabled;
            }
        };

        const syncWali = () => {
            const ayahMeninggal = ayahStatus?.value === 'meninggal';
            const ibuMeninggal = ibuStatus?.value === 'meninggal';
            const keduaMeninggal = ayahMeninggal && ibuMeninggal;

            setOptionDisabled(opsiAyah, ayahMeninggal);
            setOptionDisabled(opsiIbu, ibuMeninggal);

            if (status) {
                if (keduaMeninggal) {
                    status.value = 'Lainnya';
                } else if (ayahMeninggal && status.value === opsiAyah) {
                    status.value = '';
                } else if (ibuMeninggal && status.value === opsiIbu) {
                    status.value = '';
                }

                status.disabled = keduaMeninggal;
                status.classList.toggle('bg-light', keduaMeninggal);
            }

            if (locked) {
                locked.disabled = ! keduaMeninggal;
            }

            if (note) {
                note.hidden = ! keduaMeninggal;
            }

            if (optionNote) {
                if (keduaMeninggal || (! ayahMeninggal && ! ibuMeninggal)) {
                    optionNote.hidden = true;
                    optionNote.textContent = '';
                } else if (ayahMeninggal) {
                    optionNote.hidden = false;
                    optionNote.textContent = 'Pilihan sama dengan ayah kandung tidak tersedia karena ayah sudah meninggal dunia.';
                } else {
                    optionNote.hidden = false;
                    optionNote.textContent = 'Pilihan sama dengan ibu kandung tidak tersedia karena ibu sudah meninggal dunia.';
                }
            }

            const lainnya = keduaMeninggal || status?.value === 'Lainnya' || status?.value === 'Isi sendiri';

            if (detail) {
                detail.hidden = ! lainnya;
            }
        };

        status?.addEventListener('change', syncWali);
        ayahStatus?.addEventListener('change', syncWali);
        ibuStatus?.addEventListener('change', syncWali);
        syncWali();
    }

    document.querySelectorAll('[data-bantuan-kartu], [data-kontak-bypass]').forEach((root) => {
        const check = root.querySelector('[data-tidak-punya]');
        const nomor = root.querySelector('[data-nomor]');
        const berkas = root.querySelector('[data-berkas]');

        const sync = () => {
            const skip = Boolean(check?.checked);

            if (nomor) {
                nomor.disabled = skip;
                if (skip) {
                    nomor.value = '';
                }
            }

            if (berkas) {
                berkas.disabled = skip;
                if (skip) {
                    berkas.value = '';
                }
            }

            const jenis = root.getAttribute('data-bantuan-kartu');
            const hasNomor = ! skip && String(nomor?.value || '').trim() !== '';

            if (jenis === 'kip') {
                const kipUpload = document.querySelector('[data-kip-upload]');
                if (kipUpload) {
                    kipUpload.hidden = ! hasNomor;
                }
            }

            const upload = root.querySelector('[data-bantuan-upload]');
            if (upload) {
                upload.hidden = ! hasNomor;
            }

            if (berkas && jenis && jenis !== 'kip') {
                const tersimpan = Boolean(root.querySelector('[data-berkas-tersimpan]'));
                berkas.required = hasNomor && ! tersimpan;
            }
        };

        check?.addEventListener('change', sync);
        nomor?.addEventListener('input', sync);
        sync();
    });
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

        const syncIbuAlamat = () => {
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
        const ayahMeninggal = form.querySelector('[data-alamat-ortu="ayah"]')?.getAttribute('data-status-hidup') === 'meninggal';
        const ibuMeninggal = form.querySelector('[data-alamat-ortu="ibu"]')?.getAttribute('data-status-hidup') === 'meninggal';
        const alamat = waliBlok.querySelector('[data-ortu-alamat]');
        const isiSendiri = status === 'Lainnya'
            || status === 'Isi sendiri'
            || (status === 'Sama dengan ayah kandung' && ayahMeninggal)
            || (status === 'Sama dengan ibu kandung' && ibuMeninggal);

        if (alamat) {
            alamat.hidden = ! isiSendiri;
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

    const ensureMap = () => {
        if (map || !mapEl || mapEl.closest('[hidden]')) {
            return;
        }

        const initial = parseKoordinat(koordinat?.value) || defaultCenter;
        map = L.map(mapEl, { scrollWheelZoom: true }).setView(initial, koordinat?.value ? 16 : 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);
        setMarker(initial, koordinat?.value ? 16 : 13);
        setTimeout(() => map.invalidateSize(), 80);
        setTimeout(() => map.invalidateSize(), 350);
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

            if (blok?.querySelector('[data-ortu-alamat]')?.hidden) {
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
        const isiBlocks = form.querySelectorAll('[data-siswa-alamat-isi]');
        const autoCopy = status === 'Asrama Madrasah' || status === 'Tinggal dengan Orang Tua/Wali';

        isiBlocks.forEach((block) => {
            block.hidden = status === '';
        });

        form.querySelectorAll('[data-wilayah-root="siswa"] select, [data-wilayah-root="siswa"] input').forEach((el) => {
            el.classList.toggle('bg-light', autoCopy);
            el.style.pointerEvents = autoCopy ? 'none' : '';
            el.tabIndex = autoCopy ? -1 : 0;
        });

        if (koordinat) {
            koordinat.readOnly = autoCopy;
        }

        if (lokasiBtn) {
            lokasiBtn.disabled = autoCopy;
        }

        if (marker?.dragging) {
            if (autoCopy) {
                marker.dragging.disable();
            } else {
                marker.dragging.enable();
            }
        }

        if (map && status !== '') {
            setTimeout(() => {
                ensureMap();
                map?.invalidateSize();
            }, 50);
        } else if (status !== '') {
            setTimeout(() => ensureMap(), 50);
        }

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
                lokasiBtn.disabled = tempat?.value === 'Asrama Madrasah' || tempat?.value === 'Tinggal dengan Orang Tua/Wali';
            },
            (error) => {
                const messages = {
                    1: 'Izin lokasi ditolak. Izinkan akses lokasi di browser.',
                    2: 'Lokasi perangkat tidak tersedia.',
                    3: 'Waktu mengambil lokasi habis. Coba lagi.',
                };

                setStatus(messages[error?.code] || 'Gagal mengambil lokasi perangkat.');
                lokasiBtn.disabled = tempat?.value === 'Asrama Madrasah' || tempat?.value === 'Tinggal dengan Orang Tua/Wali';
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
    };

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
                    box.disabled = true;
                }
            });
        } else if (changed && changed.value !== 'Tidak Ada' && changed.checked) {
            boxes.forEach((box) => {
                if (box.value === 'Tidak Ada') {
                    box.checked = false;
                }
                box.disabled = false;
            });
        } else {
            const tidakAda = boxes.find((box) => box.value === 'Tidak Ada');
            const kunci = Boolean(tidakAda?.checked);
            boxes.forEach((box) => {
                if (box.value !== 'Tidak Ada') {
                    box.disabled = kunci;
                    if (kunci) {
                        box.checked = false;
                    }
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
        box.addEventListener('change', async () => {
            if (! box.checked) {
                return;
            }

            const ok = await window.madaniAlert.confirm({
                title: 'Konfirmasi',
                text: 'Apakah anda yakin data ini sesuai?',
                confirmButtonText: 'Ya, sesuai',
            });

            if (! ok) {
                box.checked = false;
            }
        });
    });
}

function bindAjukanPerubahan() {
    const modalEl = document.querySelector('#modalAjukanPerubahan');

    if (! modalEl) {
        return;
    }

    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    const judul = modalEl.querySelector('[data-ajukan-judul]');
    const fieldInput = modalEl.querySelector('[data-ajukan-field-input]');
    const lama = modalEl.querySelector('[data-ajukan-lama]');
    const baru = modalEl.querySelector('[data-ajukan-baru]');
    const jk = modalEl.querySelector('[data-ajukan-jk]');

    document.querySelectorAll('[data-ajukan-field]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.hasAttribute('data-ajukan-terkunci')) {
                window.madaniAlert.warning('Lengkapi semua data dan dokumen terlebih dahulu.');

                return;
            }

            const field = btn.getAttribute('data-ajukan-field') || '';
            const label = btn.getAttribute('data-ajukan-label') || '';
            const nilai = btn.getAttribute('data-ajukan-nilai') || '';

            if (judul) {
                judul.textContent = label;
            }

            if (fieldInput) {
                fieldInput.value = field;
            }

            if (lama) {
                lama.value = field === 'jenis_kelamin'
                    ? (nilai === 'P' ? 'Perempuan' : nilai === 'L' ? 'Laki-laki' : nilai)
                    : nilai;
            }

            if (jk && baru) {
                const isJk = field === 'jenis_kelamin';
                jk.classList.toggle('d-none', ! isJk);
                baru.classList.toggle('d-none', isJk);
                jk.disabled = ! isJk;
                baru.disabled = isJk;
                jk.name = isJk ? 'nilai_baru' : '';
                baru.name = isJk ? '' : 'nilai_baru';
                baru.type = field === 'tanggal_lahir' ? 'date' : 'text';
                baru.value = isJk ? '' : nilai;
                jk.value = nilai === 'P' ? 'P' : 'L';
            }

            modal.show();
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

function bindFormatInputs() {
    const digitsOnly = (value, max) => {
        const digits = String(value ?? '').replace(/\D+/g, '');

        return max ? digits.slice(0, max) : digits;
    };

    const normalisasiHp = (value) => {
        let digits = digitsOnly(value, 17);

        if (digits.startsWith('0')) {
            digits = `62${digits.slice(1)}`.slice(0, 17);
        }

        return digits;
    };

    document.querySelectorAll('[data-angka]').forEach((el) => {
        const max = Number(el.getAttribute('maxlength')) || 32;
        const pad = Number(el.getAttribute('data-pad')) || 0;

        const apply = () => {
            el.value = digitsOnly(el.value, max);
        };

        el.addEventListener('input', apply);
        el.addEventListener('blur', () => {
            apply();

            if (pad > 0 && el.value !== '') {
                el.value = el.value.padStart(pad, '0');
            }
        });
    });

    document.querySelectorAll('[data-hp]').forEach((el) => {
        const apply = () => {
            el.value = normalisasiHp(el.value);
        };

        el.addEventListener('input', apply);
        el.addEventListener('blur', apply);
    });

    document.querySelectorAll('[data-nama-orang]').forEach((el) => {
        el.addEventListener('input', () => {
            el.value = el.value.replace(/[^A-Za-zÀ-ÿ\-'’`., ]+/gu, '');
        });
    });
}

function bindDokumenBoxes() {
    const csrfToken = () => document.querySelector('input[name="_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content
        || '';

    document.querySelectorAll('[data-dokumen-box]').forEach((box) => {
        const input = box.querySelector('[data-dokumen-input]');
        const pick = box.querySelector('[data-dokumen-pick]');
        const preview = box.querySelector('[data-dokumen-preview]');
        const hapus = box.querySelector('[data-dokumen-hapus]');

        if (input && pick && preview) {
            pick.addEventListener('click', () => input.click());

            input.addEventListener('change', () => {
                const file = input.files?.[0];

                if (!file) {
                    return;
                }

                pick.textContent = 'Unggah ulang';

                const isImage = /^image\//.test(file.type) || /\.(jpe?g|png)$/i.test(file.name);
                preview.innerHTML = '';

                if (isImage) {
                    const img = document.createElement('img');
                    img.alt = file.name;
                    img.dataset.dokumenImg = '';
                    img.src = URL.createObjectURL(file);
                    preview.appendChild(img);
                } else {
                    const pdf = document.createElement('div');
                    pdf.className = 'dokumen-box__pdf';
                    pdf.innerHTML = `<i class="bi bi-file-earmark-pdf"></i><span>${file.name}</span>`;
                    preview.appendChild(pdf);
                }
            });
        }

        hapus?.addEventListener('click', async () => {
            const judul = hapus.getAttribute('data-judul') || 'berkas';
            const url = hapus.getAttribute('data-url');

            if (!url) {
                return;
            }

            const ok = await window.madaniAlert.confirm({
                title: 'Hapus dokumen',
                text: `Hapus ${judul} dari database dan storage?`,
                confirmButtonText: 'Hapus',
                icon: 'warning',
            });

            if (! ok) {
                return;
            }

            window.madaniAlert.loading('Menghapus…');

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.style.display = 'none';
            form.setAttribute('data-no-loading', '1');

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = csrfToken();
            form.appendChild(token);

            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);

            document.body.appendChild(form);
            form.submit();
        });
    });
}

document.querySelectorAll('[data-wilayah-root]').forEach(bindWilayahRoot);
bindOrtuForm();
bindAlamatOrtu();
bindAlamatSiswa();
bindKebutuhanDisabilitas();
bindIjazahSesuai();
bindAjukanPerubahan();
bindOpenModals();
bindPeranUser();
bindFormatInputs();
bindDokumenBoxes();
bindConfirmForms();
bindFormLoading();
readMadaniFlash();
bindStaticWarnings();
