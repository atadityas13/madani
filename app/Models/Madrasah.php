<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'nama', 'npsn', 'nsm', 'jenjang', 'status', 'akreditasi', 'alamat', 'desa',
    'kecamatan', 'kota', 'provinsi', 'kode_pos', 'telepon', 'email', 'website', 'logo_path',
])]
class Madrasah extends Model
{
    public static function saatIni(): self
    {
        $madrasah = static::query()->first();

        if ($madrasah) {
            return $madrasah;
        }

        $config = config('madrasah', []);

        return static::query()->create([
            'nama' => $config['nama'] ?? 'MTsN 11 Majalengka',
            'npsn' => $config['npsn'] ?? null,
            'nsm' => $config['nsm'] ?? null,
            'jenjang' => $config['jenjang'] ?? null,
            'status' => $config['status'] ?? null,
            'akreditasi' => $config['akreditasi'] ?? null,
            'alamat' => $config['alamat'] ?? null,
            'desa' => $config['desa'] ?? null,
            'kecamatan' => $config['kecamatan'] ?? null,
            'kota' => $config['kota'] ?? null,
            'provinsi' => $config['provinsi'] ?? null,
            'kode_pos' => $config['kode_pos'] ?? null,
            'telepon' => $config['telepon'] ?? null,
            'email' => $config['email'] ?? null,
            'website' => $config['website'] ?? null,
        ]);
    }

    public function urlLogo(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
