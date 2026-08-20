<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class UpgradeBaselineSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_migrations_create_critical_tables(): void
    {
        foreach ([
            'users',
            'teams',
            'pekerjaan',
            'dokumen',
            'alur_kerja',
            'sop_pengetahuan',
            'jobdescs',
            'activity_logs',
            'personal_access_tokens',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_critical_route_names_remain_registered(): void
    {
        foreach ([
            'login',
            'home',
            'pekerjaan.index',
            'pekerjaan.store',
            'alur-kerja.index',
            'sop-pengetahuan.index',
            'jobdesc.index',
            'lokasi-dokumen.index',
            'users.index',
            'activity-logs.index',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing route: {$routeName}");
        }

        $this->assertSame('/home', route('home', [], false));
    }

    public function test_fresh_sqlite_workflow_columns_remain_nullable(): void
    {
        $systemColumns = collect(DB::select("PRAGMA table_info('alur_kerja_tahap_sistem')"));
        $picColumns = collect(DB::select("PRAGMA table_info('alur_kerja_tahap_pic')"));

        $this->assertSame(0, (int) $systemColumns->firstWhere('name', 'nama_sistem')->notnull);
        $this->assertSame(0, (int) $picColumns->firstWhere('name', 'nama')->notnull);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('pekerjaan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_each_active_role_can_open_the_dashboard(): void
    {
        foreach (array_keys(User::roleOptions()) as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('home'))
                ->assertOk();
        }
    }

    public function test_only_admin_can_open_user_management(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();

        foreach ([User::ROLE_MANAGER, User::ROLE_SUPERVISOR, User::ROLE_STAFF] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('users.index'))
                ->assertForbidden();
        }
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('pekerjaan.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_local_and_r2_storage_are_faked(): void
    {
        Storage::disk('local')->put('upgrade/local.txt', 'safe');
        Storage::disk('r2')->put('upgrade/r2.txt', 'safe');

        Storage::disk('local')->assertExists('upgrade/local.txt');
        Storage::disk('r2')->assertExists('upgrade/r2.txt');
    }

    public function test_local_disk_keeps_the_legacy_storage_app_root(): void
    {
        $this->assertSame(
            storage_path('app'),
            config('filesystems.disks.local.root')
        );
    }

    public function test_svg_is_not_accepted_as_a_jobdesc_structure_image(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $svg = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'diagram.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"></svg>'
        );

        $this->actingAs($admin)
            ->post(route('jobdesc.store'), [
                'jabatan' => 'Penguji Upgrade',
                'bagan_struktur' => $svg,
            ])
            ->assertSessionHasErrors('bagan_struktur');
    }

    public function test_session_and_cache_names_are_explicit(): void
    {
        $this->assertNotEmpty(config('session.cookie'));
        $this->assertNotEmpty(config('cache.prefix'));
        $this->assertSame('php', config('session.serialization'));
    }

    public function test_request_forgery_middleware_uses_the_laravel_13_class(): void
    {
        $this->assertTrue(is_subclass_of(
            \App\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class
        ));
    }

    public function test_database_config_loads_without_pdo_mysql(): void
    {
        $script = <<<'PHP'
function env($key, $default = null) { return $default; }
function database_path($path = '') { return $path; }

if (extension_loaded('pdo_mysql')) {
    fwrite(STDERR, 'The subprocess unexpectedly loaded pdo_mysql.');
    exit(2);
}

require $argv[1].'/vendor/autoload.php';
require $argv[1].'/config/database.php';

echo 'loaded';
PHP;

        $process = new Process([
            PHP_BINARY,
            '-n',
            '-d',
            'error_reporting=E_ALL',
            '-d',
            'display_errors=1',
            '-r',
            $script,
            base_path(),
        ]);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            trim($process->getErrorOutput().PHP_EOL.$process->getOutput())
        );
        $this->assertSame('loaded', $process->getOutput());
        $this->assertSame('', $process->getErrorOutput());
    }
}
