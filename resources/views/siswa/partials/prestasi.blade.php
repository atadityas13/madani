@php
    $bukaModalPrestasi = $errors->any() && old('bagian') === 'prestasi';
    $jenisInvalid = $errors->has('jenis');
    $tingkatInvalid = $errors->has('tingkat');
@endphp

<div class="d-flex align-items-center mb-3 gap-3 flex-wrap">
    <div class="stat-label mb-0">Daftar prestasi</div>
    <button class="btn btn-madani" type="button" data-bs-toggle="modal" data-bs-target="#prestasiModal">Tambah</button>
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama prestasi</th>
                    <th>Jenis prestasi</th>
                    <th>Tahun</th>
                    <th>Tingkat</th>
                    <th>Penyelenggara</th>
                    <th>Sertifikat/Piagam</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siswa->prestasis as $item)
                    <tr>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->jenis ?: '—' }}</td>
                        <td>{{ $item->tahun ?: '—' }}</td>
                        <td>{{ $item->tingkat ?: '—' }}</td>
                        <td>{{ $item->penyelenggara ?: '—' }}</td>
                        <td>
                            @if ($item->sertifikat_path)
                                <a href="{{ Storage::disk('r2')->url($item->sertifikat_path) }}" target="_blank" rel="noopener">Lihat</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ $relasiAction }}" onsubmit="return confirm('Hapus data ini?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="jenis" value="prestasi">
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-secondary p-3">Belum ada prestasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="prestasiModal" tabindex="-1" aria-labelledby="prestasiModalLabel" aria-hidden="true" @if ($bukaModalPrestasi) data-modal-open @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $updateAction }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="bagian" value="prestasi">
                <div class="modal-header">
                    <h5 class="modal-title stat-label mb-0" id="prestasiModalLabel">Tambah prestasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama prestasi</label>
                        <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama') }}" required>
                        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis prestasi</label>
                        <x-emis-select class="{{ $jenisInvalid ? 'is-invalid' : '' }}" name="jenis" :options="$emis['jenis_prestasi']" :value="old('jenis')" required />
                        @error('jenis') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun</label>
                        <input class="form-control @error('tahun') is-invalid @enderror" type="number" name="tahun" value="{{ old('tahun', date('Y')) }}" min="2000" max="2100">
                        @error('tahun') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tingkat</label>
                        <x-emis-select class="{{ $tingkatInvalid ? 'is-invalid' : '' }}" name="tingkat" :options="$emis['tingkat_prestasi']" :value="old('tingkat')" />
                        @error('tingkat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penyelenggara</label>
                        <input class="form-control" name="penyelenggara" value="{{ old('penyelenggara') }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Sertifikat/Piagam</label>
                        <input class="form-control @error('sertifikat') is-invalid @enderror" type="file" name="sertifikat" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">Maks. 2MB, pdf / jpg / png</div>
                        @error('sertifikat') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-madani" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
