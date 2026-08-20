<?php

namespace Tests\Feature;

use App\Http\Kernel as HttpKernel;
use App\Http\Middleware\PreventRequestForgery;
use App\Models\AlurKerja;
use App\Models\AlurKerjaTahapLampiran;
use App\Models\Dokumen;
use App\Models\Pekerjaan;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class Laravel13UpgradeRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_sanctum_schema_has_nullable_indexed_expiry_in_the_expected_position(): void
    {
        $columns = collect(Schema::getColumns('personal_access_tokens'));
        $columnNames = $columns->pluck('name')->values();
        $expiresAt = $columns->firstWhere('name', 'expires_at');

        $this->assertNotNull($expiresAt);
        $this->assertTrue($expiresAt['nullable']);
        $this->assertSame(
            $columnNames->search('last_used_at') + 1,
            $columnNames->search('expires_at')
        );
        $this->assertTrue(
            collect(Schema::getIndexes('personal_access_tokens'))->contains(
                fn (array $index): bool => $index['columns'] === ['expires_at']
            )
        );
    }

    public function test_user_can_create_a_real_sanctum_token_with_expiry(): void
    {
        $user = User::factory()->create();
        $expiresAt = now()->addHour()->startOfSecond();

        $newToken = $user->createToken('upgrade-regression', ['documents:read'], $expiresAt);
        $storedToken = $newToken->accessToken->fresh();

        $this->assertSame(['documents:read'], $storedToken->abilities);
        $this->assertTrue($storedToken->expires_at->equalTo($expiresAt));
    }

    public function test_guarded_sanctum_upgrade_adds_expiry_without_destructive_down(): void
    {
        if (Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            });
        }

        $migrationPath = database_path(
            'migrations/2026_08_20_000000_add_expires_at_to_personal_access_tokens_table.php'
        );

        $this->assertFileExists($migrationPath);

        $migration = require $migrationPath;
        $migration->up();

        $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'expires_at'));
        $this->assertTrue(
            collect(Schema::getIndexes('personal_access_tokens'))->contains(
                fn (array $index): bool => $index['columns'] === ['expires_at']
            )
        );

        $expiresAt = now()->addDay()->startOfSecond();
        $token = User::factory()->create()->createToken('preserved-expiry', ['*'], $expiresAt);

        $migration->down();

        $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'expires_at'));
        $this->assertTrue($token->accessToken->fresh()->expires_at->equalTo($expiresAt));
        $this->assertCount(
            1,
            glob(database_path('migrations/*create_personal_access_tokens_table.php'))
        );
    }

    public function test_configured_r2_disk_resolves_the_real_flysystem_v3_s3_adapter_without_network(): void
    {
        $originalConfig = config('filesystems.disks.r2');

        try {
            Storage::forgetDisk('r2');
            config([
                'filesystems.disks.r2' => [
                    'driver' => 's3',
                    'key' => 'test-access-key',
                    'secret' => 'test-secret-key',
                    'region' => 'auto',
                    'bucket' => 'test-bucket',
                    'endpoint' => 'https://example.invalid',
                    'use_path_style_endpoint' => false,
                    'throw' => false,
                ],
            ]);

            $this->assertInstanceOf(
                AwsS3V3Adapter::class,
                Storage::disk('r2')->getAdapter()
            );
        } finally {
            Storage::forgetDisk('r2');
            config(['filesystems.disks.r2' => $originalConfig]);
            Storage::fake('r2');
        }

        Storage::disk('r2')->put('upgrade/fake-restored.txt', 'restored');
        Storage::disk('r2')->assertExists('upgrade/fake-restored.txt');
    }

    public function test_alur_kerja_stage_upload_uses_local_fallback_and_serves_inline(): void
    {
        $this->configureR2(null);
        [$user, $alurKerja] = $this->createManageableWorkflow();

        $this->actingAs($user)
            ->post(route('alur-kerja.tahap.store', $alurKerja), [
                'nama' => 'Tahap lokal',
                'lampiran' => [
                    UploadedFile::fake()->createWithContent('panduan-lokal.txt', 'konten lokal'),
                ],
            ])
            ->assertRedirect(route('alur-kerja.show', $alurKerja));

        $lampiran = AlurKerjaTahapLampiran::query()->firstOrFail();

        $this->assertSame('local', $lampiran->storage_disk);
        Storage::disk('local')->assertExists($lampiran->path);

        $this->actingAs($user)
            ->get(route('alur-kerja.tahap.lampiran.show', [
                $alurKerja,
                $lampiran->tahap,
                $lampiran,
            ]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="panduan-lokal.txt"');
    }

    public function test_alur_kerja_stage_upload_uses_fake_r2_and_redirects_to_temporary_url(): void
    {
        $this->configureR2('configured');
        [$user, $alurKerja] = $this->createManageableWorkflow();
        Storage::disk('r2')->buildTemporaryUrlsUsing(
            fn (string $path): string => 'https://r2.example.test/' . $path . '?signature=test'
        );

        $this->actingAs($user)
            ->post(route('alur-kerja.tahap.store', $alurKerja), [
                'nama' => 'Tahap R2',
                'lampiran' => [
                    UploadedFile::fake()->createWithContent('panduan-r2.txt', 'konten r2'),
                ],
            ])
            ->assertRedirect(route('alur-kerja.show', $alurKerja));

        $lampiran = AlurKerjaTahapLampiran::query()->firstOrFail();

        $this->assertSame('r2', $lampiran->storage_disk);
        Storage::disk('r2')->assertExists($lampiran->path);

        $this->actingAs($user)
            ->get(route('alur-kerja.tahap.lampiran.show', [
                $alurKerja,
                $lampiran->tahap,
                $lampiran,
            ]))
            ->assertRedirect('https://r2.example.test/' . $lampiran->path . '?signature=test');
    }

    public function test_alur_kerja_attachment_delete_removes_the_file_from_its_recorded_disk(): void
    {
        $this->configureR2('configured');
        [$user, $alurKerja] = $this->createManageableWorkflow();
        $tahap = $alurKerja->tahaps()->create([
            'urutan' => 1,
            'nama' => 'Tahap hapus',
        ]);
        $path = 'alur-kerja/' . $alurKerja->id . '/tahap/' . $tahap->id . '/hapus.txt';
        Storage::disk('r2')->put($path, 'hapus saya');
        $lampiran = $tahap->lampirans()->create([
            'nama_file' => 'hapus.txt',
            'path' => $path,
            'storage_disk' => 'r2',
        ]);

        $this->actingAs($user)
            ->delete(route('alur-kerja.tahap.lampiran.destroy', [
                $alurKerja,
                $tahap,
                $lampiran,
            ]))
            ->assertRedirect(route('alur-kerja.show', $alurKerja));

        Storage::disk('r2')->assertMissing($path);
        $this->assertDatabaseMissing('alur_kerja_tahap_lampiran', ['id' => $lampiran->id]);
    }

    public function test_custom_request_forgery_middleware_remains_in_the_web_group(): void
    {
        $middlewareGroups = app(HttpKernel::class)->getMiddlewareGroups();

        $this->assertContains(
            PreventRequestForgery::class,
            $middlewareGroups['web']
        );
    }

    public function test_same_origin_post_is_accepted_without_a_csrf_token_by_laravel_13_semantics(): void
    {
        $request = $this->csrfRequest('same-origin');

        $response = $this->csrfMiddleware()->handle(
            $request,
            fn (): Response => new Response('accepted')
        );

        $this->assertSame('accepted', $response->getContent());
    }

    public function test_cross_site_post_without_a_csrf_token_is_rejected(): void
    {
        $this->expectException(TokenMismatchException::class);

        $this->csrfMiddleware()->handle(
            $this->csrfRequest('cross-site'),
            fn (): Response => new Response('must not be reached')
        );
    }

    public function test_status_expansion_rollback_refuses_to_coerce_new_business_outcomes(): void
    {
        Schema::table('dokumen', function (Blueprint $table) {
            $table->string('status_dokumen', 40)->default('draft')->change();
        });

        $user = User::factory()->create();
        $pekerjaan = Pekerjaan::create([
            'judul' => 'Rollback status',
            'user_id' => $user->id,
        ]);
        $dokumen = Dokumen::create([
            'pekerjaan_id' => $pekerjaan->id,
            'nama_file' => 'status.txt',
            'path' => 'dokumen/status.txt',
            'status_dokumen' => Dokumen::STATUS_TIDAK_SELESAI,
        ]);
        $migration = require database_path(
            'migrations/2026_08_19_000000_expand_dokumen_status_values.php'
        );

        $rollbackBlocked = false;

        try {
            $migration->down();
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('tidak dapat di-rollback', $exception->getMessage());
            $rollbackBlocked = true;
        }

        $this->assertTrue($rollbackBlocked);
        $this->assertSame(
            Dokumen::STATUS_TIDAK_SELESAI,
            $dokumen->fresh()->status_dokumen
        );
    }

    private function createManageableWorkflow(): array
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $alurKerja = AlurKerja::create([
            'kode' => 'FLOW-' . $user->id,
            'nama' => 'Alur Kerja Pengujian',
            'pemilik_utama_user_id' => $user->id,
        ]);

        return [$user, $alurKerja];
    }

    private function configureR2(?string $value): void
    {
        config([
            'filesystems.disks.r2.key' => $value,
            'filesystems.disks.r2.secret' => $value,
            'filesystems.disks.r2.bucket' => $value,
            'filesystems.disks.r2.endpoint' => $value,
        ]);
    }

    private function csrfRequest(string $secFetchSite): Request
    {
        $request = Request::create('/csrf-probe', 'POST');
        $request->headers->set('Sec-Fetch-Site', $secFetchSite);

        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);

        return $request;
    }

    private function csrfMiddleware(): PreventRequestForgery
    {
        $application = Mockery::mock(ApplicationContract::class);
        $application->shouldReceive('runningInConsole')->andReturnFalse();

        return new PreventRequestForgery($application, app('encrypter'));
    }
}
