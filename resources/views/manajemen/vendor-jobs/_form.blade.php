<div class="madani-card p-4" style="max-width: 760px;">
    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="mb-3">
            <label class="form-label">Nama job</label>
            <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $job->nama) }}" required>
            @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Vendor</label>
            <select class="form-select @error('user_id') is-invalid @enderror" name="user_id" required>
                <option value="">Pilih vendor</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((string) old('user_id', $job->user_id) === (string) $vendor->id)>
                        {{ $vendor->name }} ({{ $vendor->username }})
                    </option>
                @endforeach
            </select>
            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label class="form-label">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                @foreach (['draft' => 'Draft', 'aktif' => 'Aktif', 'selesai' => 'Selesai'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $job->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label class="form-label d-block">Rombel</label>
            <div class="small text-secondary mb-2">Centang rombel untuk menambahkan semua siswa aktif di rombel tersebut ke job.</div>
            <div class="border rounded p-3" style="max-height: 280px; overflow-y: auto;">
                @php
                    $oldRombels = old('rombel_ids', $selectedRombelIds ?? []);
                @endphp
                @forelse ($rombels as $rombel)
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="rombel_ids[]"
                            value="{{ $rombel->id }}"
                            id="rombel-{{ $rombel->id }}"
                            @checked(in_array($rombel->id, $oldRombels, false) || in_array((string) $rombel->id, array_map('strval', $oldRombels), true))
                        >
                        <label class="form-check-label" for="rombel-{{ $rombel->id }}">{{ $rombel->label() }}</label>
                    </div>
                @empty
                    <div class="text-secondary small">Belum ada rombel tahun ajaran aktif.</div>
                @endforelse
            </div>
            @error('rombel_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            @error('rombel_ids.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('manajemen.vendor-jobs.index') }}">Batal</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
</div>
