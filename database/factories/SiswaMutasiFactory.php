<?php

namespace Database\Factories;

use App\Models\SiswaMutasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiswaMutasi>
 */
class SiswaMutasiFactory extends Factory
{
    protected $model = SiswaMutasi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis' => SiswaMutasi::JENIS_MASUK,
            'tanggal' => now()->toDateString(),
            'alasan' => 'Ikut pindah orang tua',
            'jenis_sekolah' => SiswaMutasi::SEKOLAH_UMUM,
            'nomor_dokumen_emis' => null,
            'nama_sekolah' => 'SDN Contoh',
            'rombel_id' => null,
            'tahun_ajaran_id' => null,
            'dicatat_oleh' => User::factory(),
        ];
    }
}
