@php
    $bukaModalBeasiswa = $errors->any() && old('bagian') === 'beasiswa';
    $kategoriInvalid = $errors->has('kategori');
@endphp

<div class="d-flex align-items-center mb-3 gap-3 flex-wrap">
    <div class="stat-label mb-0">Daftar bantuan pendidikan</div>
    <button class="btn btn-madani" type="button" data-bs-toggle="modal" data-bs-target="#beasiswaModal">Tambah</button>
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Tahun</th>
                    <th>Jenis bantuan</th>
                    <th>Nomor rekening penerima</th>
                    <th>Nominal</th>
                    <th>Bukti pencairan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siswa->beasiswas as $item)
                    <tr>
                        <td>{{ $item->tahun }}</td>
                        <td>{{ $item->kategori ?: '—' }}</td>
                        <td>{{ $item->nomor_rekening ?: '—' }}</td>
                        <td>{{ $item->nominal !== null ? 'Rp '.number_format($item->nominal, 0, ',', '.') : '—' }}</td>
                        <td>
                            @if ($item->bukti_path)
                                <a href="{{ asset('storage/'.$item->bukti_path) }}" target="_blank" rel="noopener">Lihat</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('siswa.relasi.destroy', $siswa) }}" onsubmit="return confirm('Hapus data ini?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="jenis" value="beasiswa">
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary p-3">Belum ada bantuan pendidikan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="beasiswaModal" tabindex="-1" aria-labelledby="beasiswaModalLabel" aria-hidden="true" @if ($bukaModalBeasiswa) data-modal-open @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('siswa.update', $siswa) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="bagian" value="beasiswa">
                <div class="modal-header">
                    <h5 class="modal-title stat-label mb-0" id="beasiswaModalLabel">Tambah bantuan pendidikan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tahun</label>
                        <input class="form-control @error('tahun') is-invalid @enderror" type="number" name="tahun" value="{{ old('tahun', date('Y')) }}" min="2000" max="2100" required>
                        @error('tahun') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis bantuan</label>
                        <x-emis-select class="{{ $kategoriInvalid ? 'is-invalid' : '' }}" name="kategori" :options="$emis['jenis_beasiswa']" :value="old('kategori')" required />
                        @error('kategori') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor rekening penerima</label>
                        <input class="form-control @error('nomor_rekening') is-invalid @enderror" name="nomor_rekening" value="{{ old('nomor_rekening') }}">
                        @error('nomor_rekening') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nominal</label>
                        <input class="form-control @error('nominal') is-invalid @enderror" type="number" name="nominal" value="{{ old('nominal') }}" min="0">
                        @error('nominal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Bukti pencairan</label>
                        <input class="form-control @error('bukti') is-invalid @enderror" type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">Maks. 2MB, pdf / jpg / png</div>
                        @error('bukti') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
