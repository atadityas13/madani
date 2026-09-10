<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'gtk_id',
    'jenis',
    'slot_key',
    'tahun_anggaran',
    'tahun_ajaran_id',
    'periode',
    'path',
    'nama_asli',
])]
class TunjanganDokumen extends Model
{
    public const JENIS_SKMT = 'skmt';

    public const JENIS_SKBK = 'skbk';

    public const JENIS_SKAKPT = 'skakpt';

    public const JENIS_SPTJM = 'sptjm';

    /**
     * @return list<string>
     */
    public static function jenisUpload(): array
    {
        return [self::JENIS_SKMT, self::JENIS_SKBK, self::JENIS_SKAKPT];
    }

    /**
     * @return list<string>
     */
    public static function semuaJenis(): array
    {
        return [self::JENIS_SKMT, self::JENIS_SKBK, self::JENIS_SPTJM, self::JENIS_SKAKPT];
    }

    public function gtk(): BelongsTo
    {
        return $this->belongsTo(Gtk::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public static function slotKeySkakpt(int $tahunAjaranId, int $bulan): string
    {
        return sprintf('skakpt:ta%d:%d', $tahunAjaranId, $bulan);
    }

    public static function slotKeySemester(string $jenis, int $tahunAjaranId, int $semester): string
    {
        return sprintf('%s:ta%d:%d', $jenis, $tahunAjaranId, $semester);
    }
}
