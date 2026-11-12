<?php

namespace App\Http\Controllers;

use App\Models\AppMenu;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class WebviewEnterController extends Controller
{
    /** Detik reuse tiket setelah first hit (Chrome Custom Tabs sering double-request). */
    private const TICKET_REUSE_GRACE_SECONDS = 120;

    public function __invoke(Request $request): RedirectResponse
    {
        $ticket = $request->string('ticket')->toString();
        if ($ticket === '') {
            abort(404);
        }

        $payload = Cache::get('app_menu_webview_ticket:'.$ticket);
        if (! is_array($payload)) {
            abort(410, 'Tiket SSO kedaluwarsa atau sudah dipakai.');
        }

        // Chrome Custom Tabs sering request URL dua kali (prefetch / open).
        // Izinkan reuse selama grace, selaras dengan masa hidup tiket launch.
        $usedAt = isset($payload['used_at']) ? (int) $payload['used_at'] : null;
        if ($usedAt === null) {
            $payload['used_at'] = now()->getTimestamp();
            Cache::put('app_menu_webview_ticket:'.$ticket, $payload, now()->addMinutes(2));
        } elseif (now()->getTimestamp() - $usedAt > self::TICKET_REUSE_GRACE_SECONDS) {
            Cache::forget('app_menu_webview_ticket:'.$ticket);
            abort(410, 'Tiket SSO kedaluwarsa atau sudah dipakai.');
        }

        $menu = AppMenu::query()->find($payload['menu_id'] ?? null);
        $url = is_string($payload['url'] ?? null) ? $payload['url'] : null;
        if ($menu === null || $url === null || ! $menu->allowsRequiresAuth()) {
            abort(404);
        }

        $guard = ($payload['guard'] ?? 'web') === 'siswa' ? 'siswa' : 'web';
        $userId = $payload['user_id'] ?? null;

        if ($guard === 'siswa') {
            $user = Siswa::query()->find($userId);
            if ($user === null) {
                abort(404);
            }
            Auth::guard('siswa')->login($user);
        } else {
            $user = User::query()->find($userId);
            if ($user === null) {
                abort(404);
            }
            Auth::guard('web')->login($user);
        }

        return redirect()->away($url);
    }
}
