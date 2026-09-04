<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'tahun_ajaran_id', 'tingkat', 'nama', 'program', 'wali_kelas_id',
    'gtk_id', 'ruangan', 'jenis_rombel', 'waktu_mengajar', 'kurikulum',
])]
class Rombel extends Model
{
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Gtk::class, 'gtk_id');
    }

    public function siswas(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'rombel_siswas')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function anggotaAktif(): BelongsToMany
    {
        return $this->siswas()->wherePivot('status', 'aktif');
    }

    public function label(): string
    {
        $tingkat = filled($this->tingkat) ? (string) $this->tingkat : '';
        $nama = filled($this->nama) ? (string) $this->nama : '';

        if ($tingkat !== '' && $nama !== '') {
            return $tingkat.'-'.$nama;
        }

        return $tingkat !== '' ? $tingkat : $nama;
    }

    public static function tingkatOrder(mixed $tingkat): int
    {
        return match (strtoupper(trim((string) $tingkat))) {
            '7', 'VII' => 7,
            '8', 'VIII' => 8,
            '9', 'IX' => 9,
            default => is_numeric($tingkat) ? (int) $tingkat : 0,
        };
    }
}
