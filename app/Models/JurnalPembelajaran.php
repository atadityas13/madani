<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'kelas_id',
    'nama_kelas',
    'mapel_id',
    'nama_mapel',
    'tanggal',
    'hari',
    'jam_ke',
    'jam_list',
    'jadwal_id',
    'jadwal_ids',
    'materi_pokok',
    'ketercapaian',
    'penugasan_siswa',
    'catatan_guru',
    'semester_id',
    'semester_tipe',
    'semester_nama_tahun',
    'source_simpatisans_id',
])]
class JurnalPembelajaran extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_list' => 'array',
            'jadwal_ids' => 'array',
            'jam_ke' => 'integer',
            'kelas_id' => 'integer',
            'mapel_id' => 'integer',
            'jadwal_id' => 'integer',
            'semester_id' => 'integer',
            'source_simpatisans_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $jamList = array_values(array_map('intval', $this->jam_list ?? []));
        $jadwalIds = array_values(array_map('intval', $this->jadwal_ids ?? []));

        return [
            'id' => $this->id,
            'kelas_id' => $this->kelas_id,
            'mapel_id' => $this->mapel_id,
            'mapel' => $this->nama_mapel,
            'jadwal_id' => $this->jadwal_id,
            'jadwal_ids' => $jadwalIds,
            'tanggal' => optional($this->tanggal)->format('Y-m-d'),
            'hari' => $this->hari,
            'jam_ke' => $this->jam_ke,
            'jam_list' => $jamList,
            'materi_pokok' => $this->materi_pokok,
            'ketercapaian' => $this->ketercapaian,
            'penugasan_siswa' => $this->penugasan_siswa,
            'catatan_guru' => $this->catatan_guru,
        ];
    }
}
