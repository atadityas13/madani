<?php

namespace App\Http\Controllers;

use App\Models\PeriodePendataan;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PeriodePendataanController extends Controller
{
    public function index(): View
    {
        $this->authorize('create', Siswa::class);

        $periode = PeriodePendataan::current();

        return view('manajemen.periode-pendataan', [
            'periode' => $periode,
            'sedangTerbuka' => $periode?->isCurrentlyOpen() ?? false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('create', Siswa::class);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:160'],
            'pesan' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $isActive = $request->boolean('is_active');
        $startsAt = $data['starts_at'] ?? null;
        $endsAt = $data['ends_at'] ?? null;

        if ($isActive && (blank($startsAt) || blank($endsAt))) {
            throw ValidationException::withMessages([
                'ends_at' => 'Isi waktu mulai dan selesai jika periode diaktifkan.',
            ]);
        }

        $payload = [
            'judul' => $data['judul'],
            'pesan' => $data['pesan'] ?? null,
            'is_active' => $isActive,
            'starts_at' => $isActive ? $startsAt : null,
            'ends_at' => $isActive ? $endsAt : null,
            'updated_by' => $request->user()?->id,
        ];

        $existing = PeriodePendataan::current();
        if ($existing) {
            $existing->update($payload);
        } else {
            PeriodePendataan::query()->create($payload);
        }

        return redirect()
            ->route('manajemen.periode-pendataan.index')
            ->with('status', 'Pengaturan periode pendataan berhasil disimpan.');
    }
}
