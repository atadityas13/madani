<?php

namespace App\Support;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class TokenIntrospector
{
    /**
     * @return array{active: bool, username?: string, nip?: string, name?: string, user_id?: int, gtk_id?: int|null, is_aktif?: bool, token_name?: string}
     */
    public function inspect(string $plainTextToken): array
    {
        $accessToken = PersonalAccessToken::findToken($plainTextToken);
        if (! $accessToken) {
            return ['active' => false];
        }

        $tokenable = $accessToken->tokenable;
        if (! $tokenable instanceof User) {
            return ['active' => false];
        }

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
            'username' => $tokenable->username,
            'nip' => $nip,
            'name' => $tokenable->name,
            'user_id' => $tokenable->id,
            'gtk_id' => $tokenable->gtk_id,
            'is_aktif' => (bool) $tokenable->is_aktif,
            'token_name' => $accessToken->name,
        ];
    }
}
