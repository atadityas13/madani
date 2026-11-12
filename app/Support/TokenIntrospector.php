<?php

namespace App\Support;

use App\Models\Siswa;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class TokenIntrospector
{
    /**
     * @return array{
     *     active: bool,
     *     role?: string,
     *     username?: string,
     *     nip?: string,
     *     nisn?: string,
     *     name?: string,
     *     user_id?: int,
     *     gtk_id?: int|null,
     *     siswa_id?: string,
     *     is_aktif?: bool,
     *     token_name?: string,
     *     rombel?: array{label: string, tingkat: ?string, nama: ?string}|null
     * }
     */
    public function inspect(string $plainTextToken): array
    {
        $accessToken = PersonalAccessToken::findToken($plainTextToken);
        if (! $accessToken) {
            return ['active' => false];
        }

        $tokenable = $accessToken->tokenable;

        if ($tokenable instanceof User) {
            return $this->inspectGuru($accessToken, $tokenable);
        }

        if ($tokenable instanceof Siswa) {
            return $this->inspectSiswa($accessToken, $tokenable);
        }

        return ['active' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private function inspectGuru(PersonalAccessToken $accessToken, User $tokenable): array
    {
        if ($accessToken->name !== 'talim-guru') {
            return ['active' => false];
        }

        if (! $tokenable->gtk_id || ! $tokenable->is_aktif) {
            return ['active' => false];
        }

        $tokenable->loadMissing('gtk');
        $nip = $tokenable->gtk?->nip ?: $tokenable->username;

        return [
            'active' => true,
            'role' => 'guru',
            'username' => $tokenable->username,
            'nip' => $nip,
            'name' => $tokenable->name,
            'user_id' => $tokenable->id,
            'gtk_id' => $tokenable->gtk_id,
            'is_aktif' => (bool) $tokenable->is_aktif,
            'token_name' => $accessToken->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inspectSiswa(PersonalAccessToken $accessToken, Siswa $siswa): array
    {
        if ($accessToken->name !== 'talim') {
            return ['active' => false];
        }

        if (! $siswa->bisaMasuk()) {
            return ['active' => false];
        }

        $rombel = $siswa->rombelAktif();

        return [
            'active' => true,
            'role' => 'siswa',
            'nisn' => $siswa->nisn,
            'username' => $siswa->nisn,
            'name' => $siswa->nama,
            'siswa_id' => $siswa->id,
            'token_name' => $accessToken->name,
            'rombel' => $rombel ? [
                'label' => $rombel->label(),
                'tingkat' => filled($rombel->tingkat) ? (string) $rombel->tingkat : null,
                'nama' => filled($rombel->nama) ? (string) $rombel->nama : null,
            ] : null,
        ];
    }
}
