<?php

namespace App\Http\Controllers;

use App\Models\AppMaintenance;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppMaintenanceController extends Controller
{
    public function index(): View
    {
        try {
            $item = AppMaintenance::current();
        } catch (QueryException) {
            $item = null;
            session()->flash(
                'error',
                'Tabel maintenance belum tersedia. Jalankan: php artisan migrate',
            );
        }

        return view('app-maintenance.index', [
            'item' => $item,
            'defaults' => $this->defaults(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('app_maintenances')) {
            return redirect()
                ->route('app-maintenance.index')
                ->with('error', 'Tabel maintenance belum tersedia. Jalankan: php artisan migrate');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'show_countdown' => ['nullable', 'boolean'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $showCountdown = $request->boolean('show_countdown');
        $endsAt = $data['ends_at'] ?? null;

        if ($showCountdown && blank($endsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => 'Isi waktu selesai jika countdown diaktifkan.',
            ]);
        }

        $payload = [
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'show_countdown' => $showCountdown,
            'ends_at' => $showCountdown ? $endsAt : null,
            'updated_by' => $request->user()?->id,
        ];

        $existing = AppMaintenance::current();
        if ($existing) {
            $existing->update($payload);
        } else {
            AppMaintenance::query()->create($payload);
        }

        return redirect()
            ->route('app-maintenance.index')
            ->with('success', 'Pengaturan maintenance Ta\'lim berhasil disimpan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'title' => 'Sedang dilakukan perbaikan pada server',
            'message' => 'Mohon maaf, layanan Ta\'lim sementara tidak dapat digunakan. Silakan coba lagi nanti.',
            'is_active' => false,
            'show_countdown' => false,
            'ends_at' => null,
        ];
    }
}
