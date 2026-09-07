<?php

namespace App\Http\Controllers;

use App\Models\AppMenu;
use App\Support\AppMenuHost;
use Database\Seeders\AppMenuSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppMenuController extends Controller
{
    public function index(Request $request): View
    {
        if (! Schema::hasTable('app_menus')) {
            session()->flash('error', 'Tabel menu belum tersedia. Jalankan: php artisan migrate');

            return view('app-menus.index', [
                'audience' => AppMenu::AUDIENCE_GURU,
                'items' => collect(),
            ]);
        }

        if (AppMenu::query()->doesntExist()) {
            (new AppMenuSeeder)->run();
        }

        $audience = $request->string('audience')->toString();
        if (! in_array($audience, [AppMenu::AUDIENCE_GURU, AppMenu::AUDIENCE_SISWA], true)) {
            $audience = AppMenu::AUDIENCE_GURU;
        }

        $items = AppMenu::query()
            ->where('audience', $audience)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('app-menus.index', compact('items', 'audience'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCustom($request);
        $audience = $data['audience'];

        $max = (int) AppMenu::query()->where('audience', $audience)->max('sort_order');

        AppMenu::query()->create([
            'type' => AppMenu::TYPE_CUSTOM,
            'key' => null,
            'judul' => $data['judul'],
            'url' => $data['url'] ?? null,
            'icon_path' => $this->storeIcon($request),
            'open_mode' => $data['open_mode'],
            'package_name' => $data['package_name'] ?? null,
            'play_store_url' => $data['play_store_url'] ?? null,
            'audience' => $audience,
            'requires_auth' => (bool) ($data['requires_auth'] ?? false),
            'sort_order' => $max + 10,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('app-menus.index', ['audience' => $audience])
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, AppMenu $appMenu): RedirectResponse
    {
        if ($appMenu->isBuiltin()) {
            throw ValidationException::withMessages([
                'judul' => 'Menu bawaan hanya bisa diubah urutannya.',
            ]);
        }

        $data = $this->validatedCustom($request, $appMenu);

        $payload = [
            'judul' => $data['judul'],
            'url' => $data['url'] ?? null,
            'open_mode' => $data['open_mode'],
            'package_name' => $data['package_name'] ?? null,
            'play_store_url' => $data['play_store_url'] ?? null,
            'requires_auth' => (bool) ($data['requires_auth'] ?? false),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('icon')) {
            $this->deleteIcon($appMenu->icon_path);
            $payload['icon_path'] = $this->storeIcon($request);
        }

        $appMenu->update($payload);

        return redirect()
            ->route('app-menus.index', ['audience' => $appMenu->audience])
            ->with('success', 'Menu diperbarui.');
    }

    public function destroy(AppMenu $appMenu): RedirectResponse
    {
        if ($appMenu->isBuiltin()) {
            return redirect()
                ->route('app-menus.index', ['audience' => $appMenu->audience])
                ->with('error', 'Menu bawaan tidak bisa dihapus.');
        }

        $audience = $appMenu->audience;
        $this->deleteIcon($appMenu->icon_path);
        $appMenu->delete();

        return redirect()
            ->route('app-menus.index', ['audience' => $audience])
            ->with('success', 'Menu dihapus.');
    }

    public function moveUp(AppMenu $appMenu): RedirectResponse
    {
        return $this->swapOrder($appMenu, -1);
    }

    public function moveDown(AppMenu $appMenu): RedirectResponse
    {
        return $this->swapOrder($appMenu, 1);
    }

    private function swapOrder(AppMenu $appMenu, int $direction): RedirectResponse
    {
        $neighbor = AppMenu::query()
            ->where('audience', $appMenu->audience)
            ->when(
                $direction < 0,
                fn ($q) => $q->where('sort_order', '<', $appMenu->sort_order)->orderByDesc('sort_order')->orderByDesc('id'),
                fn ($q) => $q->where('sort_order', '>', $appMenu->sort_order)->orderBy('sort_order')->orderBy('id'),
            )
            ->first();

        if ($neighbor) {
            $current = $appMenu->sort_order;
            $appMenu->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $current]);
        }

        return redirect()
            ->route('app-menus.index', ['audience' => $appMenu->audience])
            ->with('success', 'Urutan menu diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCustom(Request $request, ?AppMenu $existing = null): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:100'],
            'audience' => ['required', Rule::in([AppMenu::AUDIENCE_GURU, AppMenu::AUDIENCE_SISWA])],
            'open_mode' => ['required', Rule::in([AppMenu::OPEN_WEBVIEW, AppMenu::OPEN_CHROME_TAB, AppMenu::OPEN_APP])],
            'url' => ['nullable', 'string', 'max:500'],
            'package_name' => ['nullable', 'string', 'max:200'],
            'play_store_url' => ['nullable', 'string', 'max:500'],
            'requires_auth' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]);

        if ($existing !== null) {
            $data['audience'] = $existing->audience;
        }

        $mode = $data['open_mode'];
        if (in_array($mode, [AppMenu::OPEN_WEBVIEW, AppMenu::OPEN_CHROME_TAB], true)) {
            if (blank($data['url'] ?? null)) {
                throw ValidationException::withMessages([
                    'url' => 'URL wajib untuk mode WebView atau Custom Tab.',
                ]);
            }
            if (! filter_var($data['url'], FILTER_VALIDATE_URL)) {
                throw ValidationException::withMessages([
                    'url' => 'URL tidak valid.',
                ]);
            }
        }

        if ($mode === AppMenu::OPEN_APP && blank($data['package_name'] ?? null)) {
            throw ValidationException::withMessages([
                'package_name' => 'Package name wajib untuk mode buka aplikasi.',
            ]);
        }

        $requiresAuth = (bool) ($data['requires_auth'] ?? false);
        if ($requiresAuth) {
            if ($mode === AppMenu::OPEN_APP || ! AppMenuHost::isMadaniHost($data['url'] ?? null)) {
                throw ValidationException::withMessages([
                    'requires_auth' => 'SSO hanya untuk URL domain Madani (bukan mode app).',
                ]);
            }
        }

        return $data;
    }

    private function storeIcon(Request $request): ?string
    {
        if (! $request->hasFile('icon')) {
            return null;
        }

        $path = $request->file('icon')->store('app-menus', 'r2');
        if ($path === false || $path === null || $path === '') {
            throw ValidationException::withMessages([
                'icon' => 'Gagal mengunggah ikon ke penyimpanan. Periksa konfigurasi R2.',
            ]);
        }

        return $path;
    }

    private function deleteIcon(?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http')) {
            return;
        }

        Storage::disk('r2')->delete($path);
    }
}
