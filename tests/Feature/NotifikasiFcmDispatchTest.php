<?php

namespace Tests\Feature;

use App\Jobs\SendNotifikasiFcmJob;
use App\Models\Notifikasi;
use App\Models\User;
use App\Support\FcmSender;
use App\Support\NotifikasiPersonalizer;
use App\Support\Peran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifikasiFcmDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate(Peran::SUPERADMIN);

        $user = User::factory()->create(['is_aktif' => true]);
        $user->syncRoles([Peran::SUPERADMIN]);

        return $user;
    }

    public function test_job_does_not_set_sent_at_when_fcm_unconfigured(): void
    {
        $notifikasi = Notifikasi::query()->create([
            'judul' => 'Tes',
            'isi' => 'Isi',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
            'sent_at' => null,
        ]);

        $fcm = Mockery::mock(FcmSender::class);
        $fcm->shouldReceive('isConfigured')->once()->andReturn(false);
        $fcm->shouldNotReceive('sendToTokens');
        $this->app->instance(FcmSender::class, $fcm);

        (new SendNotifikasiFcmJob($notifikasi->id))->handle(
            $fcm,
            $this->app->make(NotifikasiPersonalizer::class),
        );

        $this->assertNull($notifikasi->fresh()->sent_at);
    }

    public function test_update_dispatches_fcm_when_never_sent(): void
    {
        Queue::fake([SendNotifikasiFcmJob::class]);

        $admin = $this->admin();
        $notifikasi = Notifikasi::query()->create([
            'judul' => 'Draft',
            'isi' => 'Isi',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => false,
            'published_at' => now(),
            'sent_at' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('notifikasi.update', $notifikasi), [
                'judul' => 'Aktif sekarang',
                'isi' => 'Isi pengumuman',
                'jenis' => Notifikasi::JENIS_PENGUMUMAN,
                'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
                'is_active' => '1',
            ])
            ->assertRedirect(route('notifikasi.index'))
            ->assertSessionHas('success');

        Queue::assertPushed(SendNotifikasiFcmJob::class, fn (SendNotifikasiFcmJob $job) => $job->notifikasiId === $notifikasi->id);
    }

    public function test_update_does_not_redispatch_when_already_sent(): void
    {
        Queue::fake([SendNotifikasiFcmJob::class]);

        $admin = $this->admin();
        $notifikasi = Notifikasi::query()->create([
            'judul' => 'Sudah kirim',
            'isi' => 'Isi',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
            'is_active' => true,
            'published_at' => now(),
            'sent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('notifikasi.update', $notifikasi), [
                'judul' => 'Judul baru',
                'isi' => 'Isi baru',
                'jenis' => Notifikasi::JENIS_PENGUMUMAN,
                'audience' => Notifikasi::AUDIENCE_SEMUA_GURU,
                'is_active' => '1',
            ])
            ->assertRedirect(route('notifikasi.index'));

        Queue::assertNotPushed(SendNotifikasiFcmJob::class);
    }
}
