<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class UkuranHalaman
{
    /** @var list<int> */
    public const STANDAR = [15, 30, 50, 100];

    /** @var list<int> */
    public const SISWA = [10, 20, 50, 100];

    /** @var list<int> */
    public const MONITORING = [10, 25, 50, 100];

    /** @var list<int> */
    public const IZIN = [15, 25, 50, 100];

    /** @var list<int> */
    public const DENGAN_DUA_PULUH = [15, 20, 30, 50, 100];

    /**
     * @param  list<int>  $opsi
     */
    public static function label(string $mentah, array $opsi, int $bawaan, bool $bolehSemua = true): string
    {
        if ($bolehSemua && $mentah === 'all') {
            return 'all';
        }

        $angka = (int) $mentah;

        if (! in_array($angka, $opsi, true)) {
            $angka = in_array($bawaan, $opsi, true) ? $bawaan : ($opsi[0] ?? $bawaan);
        }

        return (string) $angka;
    }

    /**
     * @param  list<int>  $opsi
     * @return array{jumlah: int, label: string}
     */
    public static function resolve(string $mentah, array $opsi, int $bawaan, int $jumlahSemua, bool $bolehSemua = true): array
    {
        $label = self::label($mentah, $opsi, $bawaan, $bolehSemua);

        if ($label === 'all') {
            return [
                'jumlah' => max($jumlahSemua, 1),
                'label' => 'all',
            ];
        }

        return [
            'jumlah' => (int) $label,
            'label' => $label,
        ];
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>|Relation<\Illuminate\Database\Eloquent\Model, *, *>  $query
     * @param  list<int>  $opsi
     * @return array{baris: LengthAwarePaginator, label: string}
     */
    public static function paginateQuery(Builder|Relation $query, Request $request, array $opsi, int $bawaan, bool $bolehSemua = true): array
    {
        $label = self::label((string) $request->query('per_page', (string) $bawaan), $opsi, $bawaan, $bolehSemua);
        $jumlah = $label === 'all' ? max((clone $query)->count(), 1) : (int) $label;

        return [
            'baris' => $query->paginate($jumlah)->withQueryString(),
            'label' => $label,
        ];
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @param  list<int>  $opsi
     */
    public static function paginate(Collection $items, Request $request, array $opsi, int $bawaan, bool $bolehSemua = true): LengthAwarePaginator
    {
        $ukuran = self::resolve(
            (string) $request->query('per_page', (string) $bawaan),
            $opsi,
            $bawaan,
            $items->count(),
            $bolehSemua,
        );
        $page = max(1, (int) $request->query('page', 1));

        return (new Paginator(
            $items->forPage($page, $ukuran['jumlah'])->values(),
            $items->count(),
            $ukuran['jumlah'],
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ],
        ))->withQueryString();
    }

    public static function kosong(Request $request, int $bawaan = 15): LengthAwarePaginator
    {
        return new Paginator(collect(), 0, $bawaan, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => 'page',
        ]);
    }
}
