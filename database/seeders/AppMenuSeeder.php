<?php

namespace Database\Seeders;

use App\Models\AppMenu;
use Illuminate\Database\Seeder;

class AppMenuSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $item) {
            AppMenu::query()->updateOrCreate(
                [
                    'audience' => $item['audience'],
                    'key' => $item['key'],
                ],
                [
                    'type' => $item['type'],
                    'judul' => $item['judul'],
                    'url' => $item['url'] ?? null,
                    'icon_path' => $item['icon_path'] ?? null,
                    'open_mode' => $item['open_mode'] ?? null,
                    'package_name' => $item['package_name'] ?? null,
                    'play_store_url' => $item['play_store_url'] ?? null,
                    'requires_auth' => $item['requires_auth'] ?? false,
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        $bitlearnPackage = 'com.atadevlabs.bitlearn';
        $bitlearnPlay = 'https://play.google.com/store/apps/details?id=com.atadevlabs.bitlearn';
        $website = 'https://mtsn11majalengka.sch.id/';

        $guru = [
            ['key' => 'jadwal', 'type' => AppMenu::TYPE_BUILTIN, 'judul' => 'Jadwal', 'sort_order' => 10],
            ['key' => 'pembagian', 'type' => AppMenu::TYPE_BUILTIN, 'judul' => 'Pembagian', 'sort_order' => 20],
            ['key' => 'ejournal', 'type' => AppMenu::TYPE_BUILTIN, 'judul' => 'E-Journal', 'sort_order' => 30],
            ['key' => 'kinerja', 'type' => AppMenu::TYPE_BUILTIN, 'judul' => 'Kinerja', 'sort_order' => 40],
            [
                'key' => 'rdm',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'RDM',
                'url' => 'https://rdm.mtsn11majalengka.sch.id/',
                'open_mode' => AppMenu::OPEN_CHROME_TAB,
                'sort_order' => 50,
            ],
            [
                'key' => 'cbt',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'CBT',
                'url' => 'https://cbt.mtsn11majalengka.sch.id/',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 60,
            ],
            [
                'key' => 'prisma',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'PRISMA',
                'url' => 'https://prisma.mtsn11majalengka.sch.id/login.php',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 70,
            ],
            [
                'key' => 'tracer',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'TRACER',
                'url' => 'https://tracer.mtsn11majalengka.sch.id/',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 80,
            ],
            [
                'key' => 'pusaka',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'PUSAKA',
                'url' => 'https://pusaka-v3.kemenag.go.id/',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 90,
            ],
            [
                'key' => 'emis',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'EMIS',
                'url' => 'https://emis.kemenag.go.id/login',
                'open_mode' => AppMenu::OPEN_CHROME_TAB,
                'sort_order' => 100,
            ],
            [
                'key' => 'emis_gtk',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'EMIS-GTK',
                'url' => 'https://emisgtk.kemenag.go.id/login',
                'open_mode' => AppMenu::OPEN_CHROME_TAB,
                'sort_order' => 110,
            ],
            [
                'key' => 'absensi',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'Absensi',
                'url' => 'https://absensi.kemenag.go.id/',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 120,
            ],
            [
                'key' => 'simpeg5',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'Simpeg5',
                'url' => 'https://simpeg5.kemenag.go.id/auth',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 130,
            ],
            [
                'key' => 'sdm',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'SDM',
                'url' => 'https://simsdm.kemenag.go.id/login',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 140,
            ],
            [
                'key' => 'asn_digital',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'ASN Digital',
                'url' => 'https://asndigital.bkn.go.id/',
                'open_mode' => AppMenu::OPEN_CHROME_TAB,
                'sort_order' => 150,
            ],
            [
                'key' => 'pintar',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'Pintar',
                'url' => 'https://pintar.kemenag.go.id/',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 160,
            ],
            [
                'key' => 'epppk',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'E-PPPK',
                'url' => 'https://epppk.kankemenagmajalengka.org/',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 170,
            ],
            [
                'key' => 'sipaga',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'SIPAGA',
                'url' => 'https://sipaga.kankemenagmajalengka.org/login.php',
                'open_mode' => AppMenu::OPEN_WEBVIEW,
                'sort_order' => 180,
            ],
            [
                'key' => 'website',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'Website',
                'url' => $website,
                'open_mode' => AppMenu::OPEN_CHROME_TAB,
                'sort_order' => 190,
            ],
            [
                'key' => 'bitlearn',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'Bitlearn',
                'open_mode' => AppMenu::OPEN_APP,
                'package_name' => $bitlearnPackage,
                'play_store_url' => $bitlearnPlay,
                'sort_order' => 200,
            ],
        ];

        $siswa = [
            [
                'key' => 'website',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'Website',
                'url' => $website,
                'open_mode' => AppMenu::OPEN_CHROME_TAB,
                'sort_order' => 10,
            ],
            [
                'key' => 'bitlearn',
                'type' => AppMenu::TYPE_CUSTOM,
                'judul' => 'Bitlearn',
                'open_mode' => AppMenu::OPEN_APP,
                'package_name' => $bitlearnPackage,
                'play_store_url' => $bitlearnPlay,
                'sort_order' => 20,
            ],
        ];

        $rows = [];
        foreach ($guru as $item) {
            $rows[] = array_merge($item, ['audience' => AppMenu::AUDIENCE_GURU]);
        }
        foreach ($siswa as $item) {
            $rows[] = array_merge($item, ['audience' => AppMenu::AUDIENCE_SISWA]);
        }

        return $rows;
    }
}
