<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\PortofolioPdfService;
use App\Services\SiswaBiodataService;
use App\Support\KelengkapanSiswa;
use App\Support\R2Url;
use App\Support\SiswaPortalPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SiswaController extends Controller
{
    public function __construct(private SiswaBiodataService $biodata) {}

    public function update(Request $request, string $bagian): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();

        if ($bagian === 'aktivitas') {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat akademik hanya dapat diubah oleh madrasah.',
            ], 403);
        }

        $pesan = $this->biodata->updateBagian($request, $siswa, $bagian, kunciIdentitas: true);

        return response()->json([
            'success' => true,
            'message' => $pesan,
            'data' => SiswaPortalPayload::make($siswa->fresh()),
        ]);
    }

    public function storePrestasi(Request $request): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();
        $pesan = $this->biodata->storePrestasi($request, $siswa);

        return response()->json([
            'success' => true,
            'message' => $pesan,
            'data' => SiswaPortalPayload::make($siswa->fresh()),
        ]);
    }

    public function storeBeasiswa(Request $request): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();
        $pesan = $this->biodata->storeBeasiswa($request, $siswa);

        return response()->json([
            'success' => true,
            'message' => $pesan,
            'data' => SiswaPortalPayload::make($siswa->fresh()),
        ]);
    }

    public function destroyRelasi(Request $request, string $jenis, int $id): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();
        $this->biodata->hapusRelasi($siswa, $jenis, $id);

        return response()->json([
            'success' => true,
            'message' => 'Data dihapus.',
            'data' => SiswaPortalPayload::make($siswa->fresh()),
        ]);
    }

    public function uploadDokumen(Request $request, string $jenis): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();
        $map = [
            'kk' => 'file_kk',
            'akta_lahir' => 'file_akta',
            'kip' => 'file_kip',
            'kks' => 'file_kks',
            'pkh' => 'file_pkh',
            'ijazah_sd' => 'file_ijazah',
            'foto' => 'foto',
        ];

        if (! isset($map[$jenis])) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis dokumen tidak dikenali.',
            ], 422);
        }

        $field = $map[$jenis];
        $request->validate([
            $field => $jenis === 'foto'
                ? ['required', 'image', 'mimes:jpg,jpeg,png', 'max:1024']
                : ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:1024'],
        ]);

        if ($jenis === 'foto') {
            $this->biodata->simpanFoto($request, $siswa);
        } else {
            $this->biodata->simpanDokumen($request, $siswa, $field, $jenis);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berkas diunggah.',
            'data' => SiswaPortalPayload::make($siswa->fresh()),
        ]);
    }

    public function requestUploadUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => ['required', 'string', 'in:kk,akta_lahir,kip,kks,pkh,ijazah_sd,foto'],
            'filename' => ['required', 'string', 'max:255'],
        ]);

        /** @var Siswa $siswa */
        $siswa = $request->user();
        $ext = strtolower(pathinfo($validated['filename'], PATHINFO_EXTENSION) ?: 'jpg');
        $key = $this->objectKeyUntuk($siswa->id, $validated['jenis'], $ext);

        /** @var \Aws\S3\S3Client $client */
        $client = Storage::disk('r2')->getClient();
        $cmd = $client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.r2.bucket'),
            'Key' => $key,
        ]);
        $url = (string) $client->createPresignedRequest($cmd, '+15 minutes')->getUri();

        return response()->json([
            'success' => true,
            'upload_url' => $url,
            'object_key' => $key,
        ]);
    }

    public function confirmUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'object_key' => ['required', 'string', 'max:500'],
            'jenis' => ['required', 'string', 'in:kk,akta_lahir,kip,kks,pkh,ijazah_sd,foto'],
            'nama_asli' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var Siswa $siswa */
        $siswa = $request->user();

        if (! Storage::disk('r2')->exists($validated['object_key'])) {
            return response()->json([
                'success' => false,
                'message' => 'File belum ditemukan di storage.',
            ], 422);
        }

        if ($validated['jenis'] === 'foto') {
            $lama = $siswa->foto;
            $siswa->update(['foto' => $validated['object_key']]);
            $this->hapusObjectLama($lama, $validated['object_key']);
        } else {
            $lama = $siswa->dokumens()->where('jenis', $validated['jenis'])->value('path');
            $siswa->dokumens()->updateOrCreate(
                ['jenis' => $validated['jenis']],
                [
                    'path' => $validated['object_key'],
                    'nama_asli' => $validated['nama_asli'] ?? basename($validated['object_key']),
                ],
            );
            $this->hapusObjectLama($lama, $validated['object_key']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berkas dikonfirmasi.',
            'url' => R2Url::temporary($validated['object_key']),
            'data' => SiswaPortalPayload::make($siswa->fresh()),
        ]);
    }

    private function objectKeyUntuk(string $siswaId, string $jenis, string $ext): string
    {
        $ext = preg_replace('/[^a-z0-9]/', '', strtolower($ext)) ?: 'jpg';

        if ($jenis === 'foto') {
            return "foto/{$siswaId}/profil.{$ext}";
        }

        return "dokumen/{$siswaId}/{$jenis}.{$ext}";
    }

    private function hapusObjectLama(?string $lama, string $baru): void
    {
        if (! $lama || $lama === $baru) {
            return;
        }

        Storage::disk('r2')->delete($lama);
    }

    public function storePengajuan(Request $request): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();
        $pesan = $this->biodata->ajukanPerubahan($request, $siswa);

        return response()->json([
            'success' => true,
            'message' => $pesan,
            'data' => SiswaPortalPayload::make($siswa->fresh()),
        ]);
    }

    public function portofolio(Request $request, PortofolioPdfService $portofolio): Response|JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();

        $kelengkapan = KelengkapanSiswa::ringkasan($siswa);
        if (! ($kelengkapan['wajib_semua_selesai'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi semua data wajib terlebih dahulu sebelum membuka portofolio.',
            ], 422);
        }

        return $portofolio->download($siswa);
    }
}
