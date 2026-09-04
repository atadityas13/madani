<?php

namespace App\Support;

use App\Models\Gtk;
use Illuminate\Support\Carbon;

/**
 * Merge baris GTK Madani dengan payload Simpatisans (atau sumber lain) by NIP.
 * Tidak menghapus data Madani; field kosong diisi, field terisi dipertahankan kecuali $overwrite.
 */
class GtkMerger
{
    /**
     * @param  array<string, mixed>  $source  Baris gurus Simpatisans / array setara
     * @return array{action: string, gtk: Gtk}
     */
    public function mergeFromSimpatisansGuru(array $source, bool $overwrite = false): array
    {
        $nip = $this->normalizeNip($source['username'] ?? $source['nip'] ?? null);
        $gtk = $nip !== null
            ? Gtk::query()->where('nip', $nip)->first()
            : null;

        $payload = $this->mapSimpatisansGuru($source, $nip);

        if ($gtk === null) {
            $gtk = Gtk::query()->create($payload);

            return ['action' => 'created', 'gtk' => $gtk];
        }

        $updates = $overwrite
            ? $payload
            : $this->onlyFillEmpty($gtk, $payload);

        // Selalu catat tautan sumber jika belum ada
        if (blank($gtk->simpatisans_guru_id) && ! empty($payload['simpatisans_guru_id'])) {
            $updates['simpatisans_guru_id'] = $payload['simpatisans_guru_id'];
        }

        if ($updates !== []) {
            $gtk->update($updates);
        }

        return ['action' => $updates === [] ? 'unchanged' : 'updated', 'gtk' => $gtk->fresh()];
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    public function mapSimpatisansGuru(array $source, ?string $nip): array
    {
        $meta = array_filter([
            'status_sertifikasi' => (bool) ($source['status_sertifikasi'] ?? false),
            'is_bk' => (bool) ($source['is_bk'] ?? false),
            'id_gtk_emis' => $source['id_gtk'] ?? null,
            'mapel_ijazah_id' => $source['mapel_ijazah_id'] ?? null,
            'mapel_sertifikasi_id' => $source['mapel_sertifikasi_id'] ?? null,
            'rumpun_ijazah_id' => $source['rumpun_ijazah_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return array_filter([
            'nama' => $source['nama_guru'] ?? $source['nama'] ?? null,
            'gelar_depan' => $this->nullIfDash($source['gelar_depan'] ?? null),
            'gelar_belakang' => $this->nullIfDash($source['gelar_belakang'] ?? null),
            'nip' => $nip,
            'nuptk' => $this->blankToNull($source['nuptk'] ?? null),
            'jenis_kelamin' => $this->normalizeJk($source['jenis_kelamin'] ?? null),
            'tempat_lahir' => $this->blankToNull($source['tempat_lahir'] ?? null),
            'tanggal_lahir' => $this->parseDate($source['tanggal_lahir'] ?? null),
            'agama' => $this->blankToNull($source['agama'] ?? null),
            'nomor_hp' => $this->blankToNull($source['nomor_hp'] ?? null),
            'email' => $this->blankToNull($source['email'] ?? null),
            'alamat' => $this->blankToNull($source['alamat'] ?? null),
            'jabatan' => $this->blankToNull($source['jabatan'] ?? null),
            'golongan' => $this->blankToNull($source['golongan'] ?? null),
            'status_pegawai' => $this->blankToNull($source['status_pegawai'] ?? null),
            'kode_internal' => $this->blankToNull($source['kode_guru'] ?? null),
            'duk' => $this->blankToNull($source['duk'] ?? null),
            'jenis' => Gtk::JENIS_GURU,
            'status' => 'aktif',
            'meta' => $meta === [] ? null : $meta,
            'simpatisans_guru_id' => isset($source['id']) ? (int) $source['id'] : null,
        ], fn ($v) => $v !== null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function onlyFillEmpty(Gtk $gtk, array $payload): array
    {
        $updates = [];

        foreach ($payload as $key => $value) {
            if ($key === 'meta') {
                $current = $gtk->meta ?? [];
                $incoming = is_array($value) ? $value : [];
                $fill = [];
                foreach ($incoming as $metaKey => $metaVal) {
                    if (! array_key_exists($metaKey, $current) || blank($current[$metaKey])) {
                        $fill[$metaKey] = $metaVal;
                    }
                }
                if ($fill !== []) {
                    $updates['meta'] = array_merge($current, $fill);
                }

                continue;
            }

            if ($key === 'jenis' || $key === 'status') {
                continue;
            }

            $current = $gtk->getAttribute($key);
            if (blank($current) && ! blank($value)) {
                $updates[$key] = $value;
            }
        }

        return $updates;
    }

    private function normalizeNip(mixed $nip): ?string
    {
        $nip = trim((string) $nip);

        return $nip === '' ? null : $nip;
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return blank($value) ? null : (string) $value;
    }

    private function nullIfDash(mixed $value): ?string
    {
        $value = $this->blankToNull($value);

        return ($value === '-' || $value === null) ? null : $value;
    }

    private function normalizeJk(mixed $jk): ?string
    {
        $jk = strtoupper(trim((string) $jk));

        return in_array($jk, ['L', 'P'], true) ? $jk : null;
    }

    private function parseDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
