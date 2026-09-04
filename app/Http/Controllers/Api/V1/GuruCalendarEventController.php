<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuruCalendarEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = (int) $request->query('tahun', now('Asia/Jakarta')->year);
        if ($year < 2000 || $year > 2100) {
            $year = now('Asia/Jakarta')->year;
        }

        try {
            $items = CalendarEvent::query()
                ->active()
                ->whereBetween('event_date', ["{$year}-01-01", "{$year}-12-31"])
                ->orderBy('event_date')
                ->orderBy('event_time')
                ->orderBy('id')
                ->get()
                ->map(fn (CalendarEvent $event) => [
                    'id' => 'server-'.$event->id,
                    'date' => optional($event->event_date)->format('Y-m-d'),
                    'title' => $event->title,
                    'note' => $event->note,
                    'type' => 'acara',
                    'marked' => (bool) $event->is_important,
                    'time' => $this->formatTime($event->event_time),
                    'source' => 'server',
                ])
                ->values();
        } catch (QueryException) {
            $items = collect();
        }

        return response()->json([
            'success' => true,
            'tahun' => $year,
            'data' => $items,
        ]);
    }

    private function formatTime(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H.i');
        }

        return str_replace(':', '.', substr((string) $value, 0, 5));
    }
}
