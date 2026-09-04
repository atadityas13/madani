<?php

namespace App\Support;

use App\Models\Gtk;
use App\Models\User;

class GuruApiPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function user(User $user): array
    {
        $user->loadMissing('gtk');
        $gtk = $user->gtk;

        return [
            'id' => $user->id,
            'username' => $user->username,
            'nip' => $gtk?->nip ?: $user->username,
            'nama_lengkap' => $gtk?->nama_lengkap ?: $user->name,
            'jabatan' => $gtk?->jabatan,
            'role' => $user->peranUtama(),
            'foto' => self::fotoUrl($user, $gtk),
            'guru' => $gtk ? self::gtk($gtk) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function gtk(Gtk $gtk): array
    {
        $duk = $gtk->duk;
        $dukInt = is_numeric($duk) ? (int) $duk : null;

        $mapel = $gtk->metaGet('mapel', []);
        if (! is_array($mapel)) {
            $mapel = [];
        }

        return [
            'id' => $gtk->id,
            'kode_guru' => $gtk->kode_internal,
            'duk' => $dukInt,
            'gelar_depan' => $gtk->gelar_depan,
            'gelar_belakang' => $gtk->gelar_belakang,
            'nuptk' => $gtk->nuptk,
            'golongan' => $gtk->golongan,
            'status_pegawai' => $gtk->status_pegawai,
            'status_sertifikasi' => (bool) $gtk->metaGet('status_sertifikasi', false),
            'is_bk' => (bool) $gtk->metaGet('is_bk', false),
            'jenis_kelamin' => $gtk->jenis_kelamin,
            'tempat_lahir' => $gtk->tempat_lahir,
            'tanggal_lahir' => $gtk->tanggal_lahir?->format('Y-m-d'),
            'agama' => $gtk->agama,
            'nomor_hp' => $gtk->nomor_hp,
            'email' => $gtk->email,
            'alamat' => $gtk->alamat,
            'mapel_ijazah' => $gtk->metaGet('mapel_ijazah'),
            'mapel_sertifikasi' => $gtk->metaGet('mapel_sertifikasi'),
            'mapel' => array_values($mapel),
        ];
    }

    private static function fotoUrl(User $user, ?Gtk $gtk): ?string
    {
        if (filled($user->foto)) {
            return R2Url::public((string) $user->foto);
        }

        if (filled($gtk?->foto_url)) {
            $fotoUrl = (string) $gtk->foto_url;
            if (str_starts_with($fotoUrl, 'http://') || str_starts_with($fotoUrl, 'https://')) {
                return $fotoUrl;
            }

            return R2Url::public($fotoUrl);
        }

        return null;
    }
}
