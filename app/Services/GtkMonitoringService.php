<?php

namespace App\Services;

use App\Models\Gtk;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GtkMonitoringService
{
    /**
     * @return array{
     *     rows: Collection<int, array{no: int, nama: string, terakhir_login: string, jumlah_jurnal: int, terakhir_jurnal: string}>,
     *     q: string
     * }
     */
    public function halaman(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));

        $gtks = Gtk::query()
            ->with(['akun' => function ($query) {
                $query->withCount('jurnalPembelajarans')
                    ->withMax('jurnalPembelajarans', 'updated_at');
            }])
            ->whereHas('akun')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->get();

        $rows = $gtks->values()->map(function (Gtk $gtk, int $index) {
            $akun = $gtk->akun;
            $jumlah = (int) ($akun?->jurnal_pembelajarans_count ?? 0);
            $terakhirJurnalAt = $akun?->jurnal_pembelajarans_max_updated_at;

            return [
                'no' => $index + 1,
                'nama' => $gtk->nama_lengkap,
                'terakhir_login' => $this->formatWaktu($akun?->last_login_at, 'Belum pernah login'),
                'jumlah_jurnal' => $jumlah,
                'terakhir_jurnal' => $this->formatWaktu(
                    $terakhirJurnalAt ? Carbon::parse($terakhirJurnalAt) : null,
                    'Belum mengisi jurnal',
                ),
            ];
        });

        return [
            'rows' => $rows,
            'q' => $q,
        ];
    }

    public function formatWaktu(?CarbonInterface $at, string $kosong): string
    {
        if ($at === null) {
            return $kosong;
        }

        $local = $at->timezone(config('app.timezone'));

        return $this->hariIndonesia($local).', '.$local->format('d-m-Y').' pukul '.$local->format('H:i:s');
    }

    private function hariIndonesia(CarbonInterface $date): string
    {
        return match ($date->dayOfWeek) {
            CarbonInterface::MONDAY => 'Senin',
            CarbonInterface::TUESDAY => 'Selasa',
            CarbonInterface::WEDNESDAY => 'Rabu',
            CarbonInterface::THURSDAY => 'Kamis',
            CarbonInterface::FRIDAY => 'Jumat',
            CarbonInterface::SATURDAY => 'Sabtu',
            default => 'Minggu',
        };
    }
}
