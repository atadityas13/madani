<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Support\UkuranHalaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CalendarEventController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $hasil = UkuranHalaman::paginateQuery(
                CalendarEvent::query()
                    ->orderByDesc('event_date')
                    ->orderByDesc('event_time')
                    ->orderByDesc('id'),
                $request,
                UkuranHalaman::STANDAR,
                15,
            );
            $items = $hasil['baris'];
            $perPage = $hasil['label'];
        } catch (QueryException) {
            $items = UkuranHalaman::kosong($request);
            $perPage = '15';
            session()->flash('error', 'Tabel kalender belum tersedia. Jalankan: php artisan migrate');
        }

        return view('calendar-events.index', compact('items', 'perPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('calendar_events')) {
            return redirect()
                ->route('calendar-events.index')
                ->with('error', 'Tabel kalender belum tersedia. Jalankan: php artisan migrate');
        }

        $data = $this->validated($request);

        CalendarEvent::query()->create([
            'title' => $data['title'],
            'note' => $data['note'] ?? null,
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'] ?? null,
            'is_important' => $request->boolean('is_important'),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('calendar-events.index')->with('success', 'Acara kalender berhasil ditambahkan.');
    }

    public function update(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $data = $this->validated($request);

        $calendarEvent->update([
            'title' => $data['title'],
            'note' => $data['note'] ?? null,
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'] ?? null,
            'is_important' => $request->boolean('is_important'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('calendar-events.index')->with('success', 'Acara kalender diperbarui.');
    }

    public function destroy(CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->delete();

        return redirect()->route('calendar-events.index')->with('success', 'Acara kalender dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'note' => ['nullable', 'string', 'max:2000'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'is_important' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
