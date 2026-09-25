@extends('layouts.app')

@section('title', 'Mutasi/DO')
@section('heading', 'Mutasi/DO')
@section('subheading', 'Mutasi masuk, keluar, dan dropout')

@section('content')
@php
    $formKey = old('_mutasi_form');
    $bukaModalMasuk = $errors->any() && $formKey === 'masuk';
    $bukaModalKeluar = $errors->any() && $formKey === 'keluar';
    $bukaModalDo = $errors->any() && $formKey === 'do';
    $createLabels = [
        'masuk' => 'Tambah',
        'keluar' => 'Tambah',
        'do' => 'Tambah',
    ];
    $modalTargets = [
        'masuk' => '#mutasiMasukModal',
        'keluar' => '#mutasiKeluarModal',
        'do' => '#mutasiDoModal',
    ];
    $confirmMessages = [
        'masuk' => 'Batalkan mutasi masuk dan hapus permanen data siswa ini? Tindakan tidak dapat dibatalkan.',
        'keluar' => 'Batalkan mutasi keluar dan aktifkan kembali siswa?',
        'do' => 'Batalkan dropout dan aktifkan kembali siswa?',
    ];
    $showSekolah = $tab !== 'do';
@endphp

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
    <ul class="nav nav-pills">
        @foreach ($tabOptions as $key => $label)
            <li class="nav-item">
                <a class="nav-link @if ($tab === $key) active @endif" href="{{ route('mutasi.index', ['tab' => $key]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>
    <button class="btn btn-madani" type="button" data-bs-toggle="modal" data-bs-target="{{ $modalTargets[$tab] }}">
        {{ $createLabels[$tab] }}
    </button>
</div>

<div class="madani-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 4rem;">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>NISN</th>
                    @if ($showSekolah)
                        <th>{{ $tab === 'masuk' ? 'Sekolah asal' : 'Sekolah tujuan' }}</th>
                    @endif
                    <th>Alasan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mutasis as $mutasi)
                    @php
                        $siswa = $mutasi->siswa;
                        $bisaBatalkanMasuk = $tab === 'masuk' && blank($siswa?->nis);
                    @endphp
                    <tr>
                        <td>{{ $mutasis->firstItem() + $loop->index }}</td>
                        <td>{{ $mutasi->tanggal?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if ($siswa)
                                <a href="{{ route('siswa.show', $siswa) }}">{{ $siswa->nama }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $siswa?->nisn ?: '—' }}</td>
                        @if ($showSekolah)
                            <td>
                                {{ $mutasi->nama_sekolah ?: '—' }}
                                @if ($mutasi->jenis_sekolah === 'madrasah' && $mutasi->nomor_dokumen_emis)
                                    <div class="small text-secondary">EMIS: {{ $mutasi->nomor_dokumen_emis }}</div>
                                @endif
                            </td>
                        @endif
                        <td>{{ $mutasi->alasan }}</td>
                        <td>
                            <div class="emis-aksi justify-content-end">
                                @if ($tab === 'masuk' && ! $bisaBatalkanMasuk)
                                    <span class="emis-aksi-btn text-secondary" title="Sudah punya NIS — gunakan Mutasi keluar / DO" aria-disabled="true">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        <span class="visually-hidden">Tidak bisa batalkan</span>
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('mutasi.batalkan', $mutasi) }}"
                                          data-confirm="{{ $confirmMessages[$tab] }}"
                                          data-confirm-title="Batalkan"
                                          data-loading-text="Membatalkan…">
                                        @csrf
                                        @method('DELETE')
                                        <button class="emis-aksi-btn" type="submit" title="Batalkan">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span class="visually-hidden">Batalkan</span>
                                        </button>
                                    </form>
                                @endif
                                <button class="emis-aksi-btn text-secondary" type="button" disabled title="Cetak surat (menyusul)">
                                    <i class="bi bi-printer"></i>
                                    <span class="visually-hidden">Cetak surat</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showSekolah ? 7 : 6 }}" class="text-secondary p-3">
                            Belum ada catatan {{ strtolower($tabOptions[$tab] ?? $tab) }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($mutasis->hasPages())
        <div class="p-3">{{ $mutasis->links() }}</div>
    @endif
</div>

@include('mutasi.partials.modal-masuk', ['bukaModal' => $bukaModalMasuk])
@include('mutasi.partials.modal-keluar', ['bukaModal' => $bukaModalKeluar])
@include('mutasi.partials.modal-do', ['bukaModal' => $bukaModalDo])

<script>
(() => {
    const cariUrl = @json($cariSiswaUrl);

    const syncEmis = (root) => {
        const jenis = root.querySelector('[data-emis-toggle]');
        const wrap = root.querySelector('[data-emis-wrap]');
        const input = root.querySelector('[data-emis-input]');
        if (! jenis || ! wrap || ! input) return;
        const apply = () => {
            const madrasah = jenis.value === 'madrasah';
            wrap.style.display = madrasah ? '' : 'none';
            input.required = madrasah;
            if (! madrasah) input.value = '';
        };
        jenis.addEventListener('change', apply);
        apply();
    };

    document.querySelectorAll('.modal').forEach((modal) => syncEmis(modal));

    const bindCombobox = (form) => {
        const tingkat = form.querySelector('[data-tingkat-select]');
        const box = form.querySelector('[data-siswa-combobox]');
        const detail = form.querySelector('[data-siswa-detail]');
        if (! tingkat || ! box) return;

        const search = box.querySelector('[data-siswa-search]');
        const results = box.querySelector('[data-siswa-results]');
        const idInput = box.querySelector('[data-siswa-id]');
        const labelStore = box.querySelector('[data-siswa-label-store]');
        let items = [];

        const setDetail = (item) => {
            if (! detail) return;
            if (! item) {
                detail.hidden = true;
                return;
            }
            detail.querySelector('[data-detail-nisn]').value = item.nisn || '—';
            detail.querySelector('[data-detail-rombel]').value = item.rombel || '—';
            detail.querySelector('[data-detail-wali]').value = item.wali || '—';
            detail.hidden = false;
        };

        const clearSiswa = () => {
            idInput.value = '';
            labelStore.value = '';
            search.value = '';
            setDetail(null);
            results.hidden = true;
            results.innerHTML = '';
        };

        const render = (list) => {
            results.innerHTML = '';
            if (! list.length) {
                results.innerHTML = '<div class="list-group-item text-secondary">Tidak ada siswa</div>';
                results.hidden = false;
                return;
            }
            list.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action';
                btn.textContent = item.label;
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    idInput.value = item.id;
                    labelStore.value = item.label;
                    search.value = item.label;
                    setDetail(item);
                    results.hidden = true;
                });
                results.appendChild(btn);
            });
            results.hidden = false;
        };

        const filterLocal = () => {
            const q = search.value.trim().toLowerCase();
            const list = ! q ? items : items.filter((item) => item.label.toLowerCase().includes(q));
            render(list.slice(0, 30));
        };

        const loadSiswa = async (tingkatValue) => {
            const keepId = idInput.value;
            clearSiswa();
            search.disabled = ! tingkatValue;
            if (! tingkatValue) return;

            search.placeholder = 'Memuat…';
            try {
                const url = new URL(cariUrl, window.location.origin);
                url.searchParams.set('tingkat', tingkatValue);
                const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                const json = await res.json();
                items = Array.isArray(json.data) ? json.data : [];
                search.placeholder = 'Ketik NISN atau nama…';

                if (keepId) {
                    const found = items.find((item) => String(item.id) === String(keepId));
                    if (found) {
                        idInput.value = found.id;
                        labelStore.value = found.label;
                        search.value = found.label;
                        setDetail(found);
                    }
                }
            } catch (e) {
                items = [];
                search.placeholder = 'Gagal memuat siswa';
            }
        };

        tingkat.addEventListener('change', () => loadSiswa(tingkat.value));

        search.addEventListener('focus', () => {
            if (! tingkat.value || ! items.length) return;
            filterLocal();
        });
        search.addEventListener('input', () => {
            idInput.value = '';
            labelStore.value = '';
            setDetail(null);
            filterLocal();
        });
        search.addEventListener('blur', () => {
            setTimeout(() => { results.hidden = true; }, 150);
        });

        if (tingkat.value) {
            loadSiswa(tingkat.value);
        }
    };

    document.querySelectorAll('[data-mutasi-nonaktif-form]').forEach(bindCombobox);
})();
</script>
@endsection
