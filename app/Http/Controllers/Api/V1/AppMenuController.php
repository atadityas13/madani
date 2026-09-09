<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppMenu;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AppMenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $audience = $this->audienceFor($user);

        $items = AppMenu::query()
            ->forAudience($audience)
            ->visibleToApi()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (AppMenu $menu) => $menu->isVisibleToApiUser($user))
            ->map(fn (AppMenu $menu) => $menu->toApiArray())
            ->values();

        return response()->json([
            'success' => true,
            'audience' => $audience,
            'data' => $items,
        ]);
    }

    public function launch(Request $request, AppMenu $menu): JsonResponse
    {
        $user = $request->user();
        $audience = $this->audienceFor($user);

        if (! $this->menuVisibleToAudience($menu, $audience) || ! $menu->isVisibleToApiUser($user)) {
            return response()->json(['success' => false, 'message' => 'Menu tidak ditemukan.'], 404);
        }

        if (! $menu->isCustom() || ! $menu->requires_auth || ! $menu->allowsRequiresAuth()) {
            return response()->json([
                'success' => false,
                'message' => 'Menu ini tidak memakai SSO Madani.',
            ], 422);
        }

        $ticket = Str::random(48);
        $guard = $user instanceof Siswa ? 'siswa' : 'web';

        Cache::put('app_menu_webview_ticket:'.$ticket, [
            'menu_id' => $menu->id,
            'user_id' => $user->getAuthIdentifier(),
            'guard' => $guard,
            'url' => $menu->url,
        ], now()->addMinutes(2));

        return response()->json([
            'success' => true,
            'url' => url('/webview/enter?ticket='.$ticket),
            'expires_in' => 120,
        ]);
    }

    private function audienceFor(mixed $user): string
    {
        if ($user instanceof Siswa) {
            return AppMenu::AUDIENCE_SISWA;
        }

        if ($user instanceof User) {
            return AppMenu::AUDIENCE_GURU;
        }

        abort(403);
    }

    private function menuVisibleToAudience(AppMenu $menu, string $audience): bool
    {
        if (! $menu->isBuiltin() && ! $menu->is_active) {
            return false;
        }

        return in_array($menu->audience, [$audience, AppMenu::AUDIENCE_SEMUA], true);
    }
}
