<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CetakPresetService
{
    private const SETTINGS_FILE = 'presets/cetak_settings.json';

    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        if (! Storage::disk('public')->exists(self::SETTINGS_FILE)) {
            return $this->defaultSettings();
        }

        $raw = Storage::disk('public')->get(self::SETTINGS_FILE);
        $data = json_decode($raw, true);

        if (! is_array($data)) {
            return $this->defaultSettings();
        }

        return array_merge($this->defaultSettings(), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(): array
    {
        return [
            'tanggal_cetak' => now('Asia/Jakarta')->format('Y-m-d'),
            'pejabat_penandatangan' => 'kepala',
        ];
    }

    public function tanggalCarbon(): Carbon
    {
        $settings = $this->getSettings();

        try {
            return Carbon::parse($settings['tanggal_cetak'] ?? now('Asia/Jakarta'))->timezone('Asia/Jakarta');
        } catch (\Throwable) {
            return now('Asia/Jakarta');
        }
    }

    public function pejabatLabel(): string
    {
        return $this->getSettings()['pejabat_penandatangan'] === 'plt_kepala'
            ? 'Plt. Kepala Madrasah'
            : 'Kepala Madrasah';
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        $settings = $this->getSettings();

        return [
            'cetakSettings' => $settings,
            'cetakPejabatLabel' => $this->pejabatLabel(),
        ];
    }
}
