<?php

namespace App\Support;

use App\Models\Siswa;
use App\Models\User;

class NotifikasiPersonalizer
{
    /**
     * @return array{nama: string, nip: string, nisn: string, rombel: string}
     */
    public function varsFor(User|Siswa $reader): array
    {
        if ($reader instanceof User) {
            $gtk = $reader->gtk;

            return [
                'nama' => (string) ($gtk?->nama ?: $reader->name ?: $reader->username ?: 'Guru'),
                'nip' => (string) ($gtk?->nip ?: $reader->username ?: ''),
                'nisn' => '',
                'rombel' => '',
            ];
        }

        $rombel = $reader->rombels()
            ->wherePivot('status', 'aktif')
            ->orderByDesc('rombels.id')
            ->first();

        $rombelLabel = '';
        if ($rombel !== null) {
            $rombelLabel = trim(($rombel->tingkat ?? '').' '.($rombel->nama ?? ''));
        }

        return [
            'nama' => (string) ($reader->nama ?: 'Siswa'),
            'nip' => '',
            'nisn' => (string) ($reader->nisn ?: ''),
            'rombel' => $rombelLabel,
        ];
    }

    public function render(string $template, User|Siswa $reader): string
    {
        $vars = $this->varsFor($reader);

        return str_replace(
            ['{{nama}}', '{{nip}}', '{{nisn}}', '{{rombel}}'],
            [$vars['nama'], $vars['nip'], $vars['nisn'], $vars['rombel']],
            $template,
        );
    }

    public function renderPreview(string $template, ?string $sampleNama = null): string
    {
        return str_replace(
            ['{{nama}}', '{{nip}}', '{{nisn}}', '{{rombel}}'],
            [
                $sampleNama ?: 'Budi Santoso',
                '198001012005011001',
                '0123456789',
                'VII A',
            ],
            $template,
        );
    }
}
