<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $owner = $this->owner($request);
        if ($owner === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:32'],
        ]);

        DeviceToken::query()->where('fcm_token', $data['fcm_token'])->delete();

        DeviceToken::query()->updateOrCreate(
            [
                'tokenable_type' => $owner::class,
                'tokenable_id' => (string) $owner->getKey(),
                'fcm_token' => $data['fcm_token'],
            ],
            [
                'platform' => $data['platform'] ?? 'android',
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $owner = $this->owner($request);
        if ($owner === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $token = $request->input('fcm_token') ?? $request->query('fcm_token');
        if (! is_string($token) || $token === '') {
            return response()->json(['success' => false, 'message' => 'fcm_token wajib.'], 422);
        }

        DeviceToken::query()
            ->where('tokenable_type', $owner::class)
            ->where('tokenable_id', (string) $owner->getKey())
            ->where('fcm_token', $token)
            ->delete();

        return response()->json(['success' => true]);
    }

    private function owner(Request $request): User|Siswa|null
    {
        $user = $request->user();
        if ($user instanceof User || $user instanceof Siswa) {
            return $user;
        }

        return null;
    }
}
