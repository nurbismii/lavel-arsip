# Laravel 8 to Laravel 13 Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade Arsipin from Laravel 8.83.29/PHP 7.4 to Laravel 13/PHP 8.5 without modifying the running production application during development.

**Architecture:** Perform each major framework upgrade in a dedicated Git worktree and commit one independently verified checkpoint per Laravel major. Use an in-memory SQLite database for automated tests, a migration-only MariaDB staging database for integration checks, PHP 8.2 portable for Laravel 9–12, and PHP 8.5.9 for Laravel 13.

**Tech Stack:** Laravel 8–13, PHP 7.4/8.2/8.5, Composer 2, PHPUnit 9–12, MariaDB 10.4, SQLite, Laravel UI/Bootstrap 5, Laravel Mix 6, Cloudflare R2-compatible S3 storage, Google API Client.

## Global Constraints

- The production folder `C:\xampp\htdocs\lavel-arsipin` must not receive dependency, environment, cache, database, or application-code changes during upgrade execution.
- Create the execution worktree at `C:\xampp\htdocs\lavel-arsipin\.worktrees\upgrade-laravel13` on branch `codex/upgrade-laravel13` using the `using-git-worktrees` skill.
- Use `C:\xampp\php\php.exe` only for the Laravel 8 baseline, worktree-local `.tools\php82\php.exe` for Laravel 9–12, and `C:\xampp\php85\php.exe` for Laravel 13.
- Run the worktree-local `.tools\composer.phar` with the PHP executable assigned to the checkpoint; never use the global `composer.bat` or mutate the global Composer installation.
- Never use `--ignore-platform-reqs`, `--no-scripts`, or a hand-edited `composer.lock` to bypass dependency failures.
- Do not copy production database rows, `.env`, `storage/app`, credentials, sessions, caches, or logs into the worktree.
- Automated tests use SQLite `:memory:` and fake local/R2 storage; integration testing uses a MariaDB database whose name is exactly `arsipin_upgrade_staging`.
- Keep the Laravel 10-style application structure when upgrading through Laravel 11–13; do not migrate to the minimal skeleton.
- Keep Laravel Mix unless its production build cannot pass after dependency hardening; a Vite migration is not part of this plan.
- Do not deploy to production as part of implementation. Produce a verified Laravel 13 worktree and a cutover/rollback runbook, then obtain separate deployment approval.
- Every checkpoint must pass Composer validation/audit, migrations, automated tests, route/config/view cache checks, and the production frontend build before its commit.
- Preserve existing route names, role behavior, storage paths, and authentication behavior.
- References: `docs/superpowers/specs/2026-08-18-laravel-8-to-13-upgrade-design.md` and the official Laravel upgrade guides for [9.x](https://laravel.com/docs/9.x/upgrade), [10.x](https://laravel.com/docs/10.x/upgrade), [11.x](https://laravel.com/docs/11.x/upgrade), [12.x](https://laravel.com/docs/12.x/upgrade), and [13.x](https://laravel.com/docs/13.x/upgrade).

---

### Task 1: Create the isolated worktree and PHP 8.2 runtime

**Files:**
- Modify: `.gitignore`
- Create: `public/css/**`
- Create: `public/js/**`
- Create: `public/mix-manifest.json`
- Runtime only, never commit: `.tools/php82/**`
- Runtime only, never commit: `.env`
- Runtime only, never commit: `.env.testing`

**Interfaces:**
- Consumes: approved design and clean `main` at commit `cd4679f` or later.
- Produces: branch `codex/upgrade-laravel13`, an isolated worktree, `$php74`, `$php82`, `$php85`, and `$composerPhar` command paths used by all later tasks.

- [ ] **Step 1: Create and enter the worktree**

Invoke the `using-git-worktrees` skill. Create:

```powershell
git -c safe.directory='C:/xampp/htdocs/lavel-arsipin' worktree add `
    'C:\xampp\htdocs\lavel-arsipin\.worktrees\upgrade-laravel13' `
    -b 'codex/upgrade-laravel13'
Set-Location 'C:\xampp\htdocs\lavel-arsipin\.worktrees\upgrade-laravel13'
```

Expected: the worktree is on `codex/upgrade-laravel13`; `git status --short` is empty.

- [ ] **Step 2: Ignore the portable runtime**

Append these exact entries to `.gitignore`:

```gitignore
/.tools
/.env.testing
```

Run:

```powershell
git check-ignore -v .tools .env.testing
```

Expected: `.gitignore` reports the new rules for both paths.

- [ ] **Step 3: Install the official PHP 8.2 x64 thread-safe portable build**

Download PHP and Composer from their official rolling stable URLs and place them inside the ignored worktree tool directory:

```powershell
New-Item -ItemType Directory -Force '.tools' | Out-Null
Invoke-WebRequest `
    'https://windows.php.net/downloads/releases/latest/php-8.2-Win32-vs16-x64-latest.zip' `
    -OutFile '.tools\php82.zip'
Expand-Archive '.tools\php82.zip' '.tools\php82' -Force
Copy-Item '.tools\php82\php.ini-development' '.tools\php82\php.ini'
Invoke-WebRequest `
    'https://getcomposer.org/download/latest-stable/composer.phar' `
    -OutFile '.tools\composer.phar'
```

Edit `.tools/php82/php.ini` so these directives are active exactly once:

```ini
extension_dir = "ext"
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=sqlite3
extension=zip
```

- [ ] **Step 4: Verify all three runtimes and required extensions**

```powershell
$php74 = 'C:\xampp\php\php.exe'
$php82 = (Resolve-Path '.tools\php82\php.exe').Path
$php85 = 'C:\xampp\php85\php.exe'
$composerPhar = (Resolve-Path '.tools\composer.phar').Path

& $php74 -r "exit(PHP_VERSION_ID >= 70400 -and PHP_VERSION_ID < 80000 ? 0 : 1);"
& $php82 -r "exit(PHP_VERSION_ID >= 80200 -and PHP_VERSION_ID < 80300 ? 0 : 1);"
& $php85 -r "exit(PHP_VERSION_ID >= 80500 -and PHP_VERSION_ID < 80600 ? 0 : 1);"
& $php82 -r "foreach(['curl','fileinfo','mbstring','openssl','pdo_mysql','pdo_sqlite','sqlite3'] as `$e){if(!extension_loaded(`$e)){fwrite(STDERR,`$e.PHP_EOL);exit(1);}}"
& $php85 -r "foreach(['curl','fileinfo','mbstring','openssl','pdo_mysql','pdo_sqlite','sqlite3'] as `$e){if(!extension_loaded(`$e)){fwrite(STDERR,`$e.PHP_EOL);exit(1);}}"
$composerVersionOutput = & $php82 $composerPhar --version --no-ansi
if ($composerVersionOutput -notmatch 'Composer version (?<version>\d+\.\d+\.\d+)') {
    throw "Unable to parse Composer version: $composerVersionOutput"
}
if ([version] $Matches.version -lt [version] '2.8.0') {
    throw "Composer 2.8.0 or newer is required. Found: $($Matches.version)"
}
```

Expected: all commands exit `0`; Composer reports version 2.8 or newer. If PHP 8.5 still lacks SQLite, enable `extension=pdo_sqlite` and `extension=sqlite3` in `C:\xampp\php85\php.ini` before continuing.

- [ ] **Step 5: Install the locked Laravel 8 dependencies and capture the baseline**

```powershell
& $php74 $composerPhar install
Copy-Item .env.example .env
```

Use `apply_patch` to set the ignored `.env` to these safe local values before booting Artisan:

```dotenv
APP_NAME="Arsipin Upgrade Workspace"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8013
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_DRIVER=array
FILESYSTEM_DRIVER=local
MAIL_MAILER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
```

Then generate a worktree-only key and create the isolated Artisan testing environment:

```powershell
& $php74 artisan key:generate
Copy-Item .env .env.testing
& $php74 artisan --version
& $php74 artisan test
npm ci
npm run production
```

Expected: Laravel `8.83.29`, 18 existing tests pass, and Mix creates the production assets.

- [ ] **Step 6: Commit the runtime ignore rule**

```powershell
git add .gitignore public/css public/js public/mix-manifest.json
git commit -m "chore: isolate runtime and capture baseline assets"
```

Expected: only `.gitignore` and generated baseline assets are committed; `.tools`, `.env`, and `.env.testing` remain ignored.

---

### Task 2: Add the upgrade safety harness and characterization tests

**Files:**
- Modify: `.env.example`
- Modify: `phpunit.xml`
- Modify: `tests/TestCase.php`
- Modify: `routes/web.php`
- Modify: `database/factories/UserFactory.php`
- Modify: `database/migrations/2026_06_04_000006_make_nama_sistem_nullable_on_alur_kerja_tahap_sistem_table.php`
- Modify: `database/migrations/2026_06_04_000007_make_nama_nullable_on_alur_kerja_tahap_pic_table.php`
- Create: `tests/Feature/UpgradeBaselineSmokeTest.php`

**Interfaces:**
- Consumes: Laravel 8 baseline and working PHP 7.4/SQLite.
- Produces: a test environment that refuses non-SQLite databases and fakes all outbound storage/mail/notification/queue side effects.

- [ ] **Step 1: Make testing environment values explicit**

In `phpunit.xml`, replace the commented database entries and add safe external-service values so the `<php>` block contains:

```xml
<php>
    <server name="APP_ENV" value="testing"/>
    <server name="BCRYPT_ROUNDS" value="4"/>
    <server name="CACHE_DRIVER" value="array"/>
    <server name="DB_CONNECTION" value="sqlite"/>
    <server name="DB_DATABASE" value=":memory:"/>
    <server name="FILESYSTEM_DRIVER" value="local"/>
    <server name="MAIL_MAILER" value="array"/>
    <server name="QUEUE_CONNECTION" value="sync"/>
    <server name="SESSION_DRIVER" value="array"/>
    <server name="TELESCOPE_ENABLED" value="false"/>
</php>
```

Update `.env.example` with non-secret, collision-safe configuration keys:

```dotenv
APP_NAME=Arsipin
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

CACHE_PREFIX=arsipin_local_cache
REDIS_PREFIX=arsipin_local_database
SESSION_COOKIE=arsipin_local_session
SESSION_SERIALIZATION=php
FILESYSTEM_DISK=local
FILESYSTEM_DRIVER=local
```

Keep the existing database, HRIS, mail, AWS, R2, and Pusher placeholders below this block; do not insert real credentials.

- [ ] **Step 2: Add the database and side-effect guard to the base test case**

Replace `tests/TestCase.php` with:

```php
<?php

namespace Tests;

use IlluminateFoundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Automated tests must use SQLite :memory:.');
        }

        Mail::fake();
        Notification::fake();
        Queue::fake();
        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('r2');
    }
}
```

- [ ] **Step 3: Give generated users safe role defaults**

Add `use App\Models\User;` to `database/factories/UserFactory.php` and include these values in `definition()`:

```php
'role' => User::ROLE_STAFF,
'is_active' => true,
```

- [ ] **Step 4: Make the two redundant MySQL ALTER migrations safe for fresh SQLite builds**

Both original create-table migrations already declare `nama_sistem` and `nama` nullable. In each nullable-alter migration, add this guard immediately after the `Schema::hasTable` guard in both `up()` and `down()`:

```php
if (DB::connection()->getDriverName() === 'sqlite') {
    return;
}
```

Keep the existing MySQL/MariaDB `ALTER TABLE ... MODIFY` statements unchanged. This is intentionally a no-op only for SQLite, where the fresh table is already created with the desired nullable column.

- [ ] **Step 5: Add baseline route, schema, and role characterization tests**

Create `tests/Feature/UpgradeBaselineSmokeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
}
```

- [ ] **Step 6: Remove the duplicate `home` route name**

In `routes/web.php`, change the public root declaration from:

```php
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
```

to:

```php
Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);
```

Keep the authenticated `/home` route named `home`. Existing `route('home')` calls will continue generating `/home`.

- [ ] **Step 7: Run the safety harness on Laravel 8**

```powershell
$php74 = 'C:\xampp\php\php.exe'
& $php74 artisan config:clear
& $php74 artisan test
& $php74 artisan route:cache
& $php74 artisan route:clear
```

Expected: all existing and new tests pass, the route cache builds, and no MySQL/HRIS/R2/Google request is made.

- [ ] **Step 8: Commit the baseline safety harness**

```powershell
git add .env.example phpunit.xml tests/TestCase.php routes/web.php `
    database/factories/UserFactory.php tests/Feature/UpgradeBaselineSmokeTest.php `
    database/migrations/2026_06_04_000006_make_nama_sistem_nullable_on_alur_kerja_tahap_sistem_table.php `
    database/migrations/2026_06_04_000007_make_nama_nullable_on_alur_kerja_tahap_pic_table.php
git commit -m "test: lock Laravel upgrade baseline behavior"
```

---

### Task 3: Upgrade Laravel 8 to Laravel 9

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `app/Http/Kernel.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `config/filesystems.php`

**Interfaces:**
- Consumes: Task 2 safety harness and PHP 8.2 portable.
- Produces: Laravel 9 checkpoint with framework CORS middleware, Flysystem 3, Bootstrap 5 pagination, and Google API Client compatible with PHP 8.5 later.

- [ ] **Step 1: Confirm the baseline tests pass under PHP 8.2 before changing dependencies**

```powershell
$php82 = (Resolve-Path '.tools\php82\php.exe').Path
$composerPhar = (Resolve-Path '.tools\composer.phar').Path
& $php82 artisan test
```

Expected: tests pass. Deprecations from Laravel 8 dependencies may appear at this pre-upgrade point only.

- [ ] **Step 2: Replace the Laravel 8 dependency constraints**

In `composer.json`, replace the `require` and `require-dev` blocks with:

```json
"require": {
    "php": "^8.2",
    "google/apiclient": "^2.19",
    "guzzlehttp/guzzle": "^7.4.5",
    "laravel/framework": "^9.0",
    "laravel/sanctum": "^2.15",
    "laravel/tinker": "^2.7",
    "laravel/ui": "^4.0",
    "league/flysystem-aws-s3-v3": "^3.0"
},
"require-dev": {
    "fakerphp/faker": "^1.21",
    "mockery/mockery": "^1.5.1",
    "nunomaduro/collision": "^6.1",
    "phpunit/phpunit": "^9.5.10",
    "spatie/laravel-ignition": "^1.7"
},
```

Remove `minimum-stability`; retain `"prefer-stable": true`. Do not retain `fruitcake/laravel-cors`, `facade/ignition`, or `laravel/sail` because CORS is built into Laravel 9 and Sail is not configured in this repository.

- [ ] **Step 3: Switch middleware and configuration before Composer discovery runs**

In `app/Http/Kernel.php`, replace:

```php
\Fruitcake\Cors\HandleCors::class,
```

with:

```php
\Illuminate\Http\Middleware\HandleCors::class,
```

In `app/Providers/AppServiceProvider.php`, replace:

```php
Paginator::useBootstrap();
```

with:

```php
Paginator::useBootstrapFive();
```

In `config/filesystems.php`, replace the default disk expression with the backward-compatible key transition:

```php
'default' => env('FILESYSTEM_DISK', env('FILESYSTEM_DRIVER', 'local')),
```

- [ ] **Step 4: Resolve Laravel 9 dependencies**

```powershell
& $php82 $composerPhar update --with-all-dependencies
& $php82 artisan --version
& $php82 $composerPhar validate --no-check-publish
& $php82 $composerPhar audit
```

Expected: Laravel reports `9.x`; Composer reports no solver error and no unhandled security advisory.

- [ ] **Step 5: Run the complete Laravel 9 quality gate**

```powershell
& $php82 artisan optimize:clear
& $php82 artisan migrate:fresh --env=testing --force
& $php82 artisan test
& $php82 artisan route:list
& $php82 artisan config:cache
& $php82 artisan route:cache
& $php82 artisan view:cache
& $php82 artisan optimize:clear
npm ci
npm run production
```

Expected: migrations and tests pass, route/config/view caches build, and Mix exits `0`.

- [ ] **Step 6: Commit the Laravel 9 checkpoint**

```powershell
git add composer.json composer.lock app/Http/Kernel.php `
    app/Providers/AppServiceProvider.php config/filesystems.php public/css public/js public/mix-manifest.json
git commit -m "chore: upgrade framework to Laravel 9"
```

---

### Task 4: Upgrade Laravel 9 to Laravel 10 and PHPUnit 10

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `phpunit.xml`

**Interfaces:**
- Consumes: passing Laravel 9 checkpoint.
- Produces: Laravel 10, Sanctum 3.2, Ignition 2, Collision 7, and PHPUnit 10 with valid coverage configuration.

- [ ] **Step 1: Update Laravel 10 dependency constraints**

Change only these constraints in `composer.json`:

```json
"php": "^8.2",
"laravel/framework": "^10.0",
"laravel/sanctum": "^3.2",
"laravel/ui": "^4.0",
"nunomaduro/collision": "^7.0",
"phpunit/phpunit": "^10.0",
"spatie/laravel-ignition": "^2.0"
```

Keep Google API Client, Guzzle, Tinker, Flysystem, Faker, and Mockery constraints from Task 3.

- [ ] **Step 2: Migrate PHPUnit coverage configuration**

Replace:

```xml
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
</coverage>
```

with:

```xml
<source>
    <include>
        <directory suffix=".php">./app</directory>
    </include>
</source>
```

- [ ] **Step 3: Resolve dependencies and inspect database-expression usage**

```powershell
$php82 = (Resolve-Path '.tools\php82\php.exe').Path
$composerPhar = (Resolve-Path '.tools\composer.phar').Path
& $php82 $composerPhar update --with-all-dependencies
rg -n "\(string\).*DB::raw|DB::raw.*__toString|getValue\(" app tests
& $php82 artisan --version
```

Expected: Laravel reports `10.x`; the scan finds no database expression cast that needs Laravel 10's `Expression::getValue()` migration.

- [ ] **Step 4: Run the Laravel 10 quality gate**

```powershell
& $php82 $composerPhar validate --no-check-publish
& $php82 $composerPhar audit
& $php82 artisan optimize:clear
& $php82 artisan migrate:fresh --env=testing --force
& $php82 artisan test
& $php82 artisan route:list
& $php82 artisan config:cache
& $php82 artisan route:cache
& $php82 artisan event:cache
& $php82 artisan view:cache
& $php82 artisan optimize:clear
npm ci
npm run production
```

Expected: all commands exit `0`; PHPUnit emits no configuration deprecation.

- [ ] **Step 5: Commit the Laravel 10 checkpoint**

```powershell
git add composer.json composer.lock phpunit.xml public/css public/js public/mix-manifest.json
git commit -m "chore: upgrade framework to Laravel 10"
```

---

### Task 5: Upgrade Laravel 10 to Laravel 11 without changing the app skeleton

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `config/sanctum.php`
- Verify unchanged: `bootstrap/app.php`
- Verify unchanged: `app/Http/Kernel.php`
- Verify unchanged: `app/Exceptions/Handler.php`
- Verify existing: `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php`

**Interfaces:**
- Consumes: passing Laravel 10 checkpoint and existing Sanctum token migration.
- Produces: Laravel 11/Sanctum 4 while preserving the established Laravel 10-style bootstrap, kernels, providers, and exception handler.

- [ ] **Step 1: Update Laravel 11 dependency constraints**

Change these constraints in `composer.json`:

```json
"php": "^8.2",
"laravel/framework": "^11.0",
"laravel/sanctum": "^4.0",
"laravel/tinker": "^2.9",
"nunomaduro/collision": "^8.1",
"phpunit/phpunit": "^11.0",
"spatie/laravel-ignition": "^2.4"
```

Retain Laravel UI `^4.0`; it supports Laravel 11. Do not add Laravel Breeze, Pail, Pint, or other new-skeleton packages.

- [ ] **Step 2: Update Sanctum 4 middleware configuration**

Replace the `middleware` array in `config/sanctum.php` with:

```php
'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
    'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
],
```

Do not run `vendor:publish --tag=sanctum-migrations`: this repository already owns `2019_12_14_000001_create_personal_access_tokens_table.php`, and publishing a second create-table migration would break `migrate:fresh`.

- [ ] **Step 3: Resolve dependencies and verify the legacy skeleton remains bound**

```powershell
$php82 = (Resolve-Path '.tools\php82\php.exe').Path
$composerPhar = (Resolve-Path '.tools\composer.phar').Path
& $php82 $composerPhar update --with-all-dependencies
& $php82 artisan --version
& $php82 -r "`$app=require 'bootstrap/app.php'; exit(`$app->bound(Illuminate\Contracts\Http\Kernel::class) ? 0 : 1);"
```

Expected: Laravel reports `11.x`; `bootstrap/app.php` still binds `App\Http\Kernel`, `App\Console\Kernel`, and `App\Exceptions\Handler`.

- [ ] **Step 4: Run the Laravel 11 quality gate**

```powershell
& $php82 $composerPhar validate --no-check-publish
& $php82 $composerPhar audit
& $php82 artisan optimize:clear
& $php82 artisan migrate:fresh --env=testing --force
& $php82 artisan test
& $php82 artisan route:list
& $php82 artisan config:cache
& $php82 artisan route:cache
& $php82 artisan event:cache
& $php82 artisan view:cache
& $php82 artisan optimize:clear
npm ci
npm run production
```

Expected: all commands exit `0`, including fresh creation of `personal_access_tokens` exactly once.

- [ ] **Step 5: Commit the Laravel 11 checkpoint**

```powershell
git add composer.json composer.lock config/sanctum.php public/css public/js public/mix-manifest.json
git commit -m "chore: upgrade framework to Laravel 11"
```

---

### Task 6: Upgrade Laravel 11 to Laravel 12

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `tests/Feature/UpgradeBaselineSmokeTest.php`
- Verify unchanged: `config/filesystems.php`
- Verify: `app/Http/Controllers/JobdescController.php`
- Verify: `app/Http/Controllers/SopPengetahuanController.php`

**Interfaces:**
- Consumes: passing Laravel 11 checkpoint.
- Produces: Laravel 12/Carbon 3 with regression coverage for the intentionally preserved `storage/app` local root and non-SVG image policy.

- [ ] **Step 1: Add Laravel 12 storage and image-policy characterization tests**

Add these methods to `tests/Feature/UpgradeBaselineSmokeTest.php` before dependency changes:

```php
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
```

Run on Laravel 11:

```powershell
$php82 = (Resolve-Path '.tools\php82\php.exe').Path
& $php82 artisan test --filter=UpgradeBaselineSmokeTest
```

Expected: both characterization tests pass before the framework update.

- [ ] **Step 2: Update Laravel 12 constraints**

Change these constraints in `composer.json`:

```json
"php": "^8.2",
"laravel/framework": "^12.0",
"phpunit/phpunit": "^11.0"
```

Retain Sanctum `^4.0`, Laravel UI `^4.0`, Collision `^8.1`, Ignition `^2.4`, and all compatible runtime packages. Carbon 3 is resolved transitively by Laravel 12; do not add it as a direct dependency.

- [ ] **Step 3: Resolve dependencies and scan application-specific Laravel 12 impacts**

```powershell
$composerPhar = (Resolve-Path '.tools\composer.phar').Path
& $php82 $composerPhar update --with-all-dependencies
rg -n "mergeIfMissing\(|HasVersion7Uuids|HasUuids|image:allow_svg" app tests
& $php82 artisan --version
```

Expected: Laravel reports `12.x`; no code relies on old nested `mergeIfMissing` semantics or UUID generation, and SVG remains intentionally excluded.

- [ ] **Step 4: Run the Laravel 12 quality gate**

```powershell
& $php82 $composerPhar validate --no-check-publish
& $php82 $composerPhar audit
& $php82 artisan optimize:clear
& $php82 artisan migrate:fresh --env=testing --force
& $php82 artisan test
& $php82 artisan route:list
& $php82 artisan config:cache
& $php82 artisan route:cache
& $php82 artisan event:cache
& $php82 artisan view:cache
& $php82 artisan optimize:clear
npm ci
npm run production
```

Expected: all commands exit `0`; the local disk test proves Laravel 12 did not move uploads to `storage/app/private`.

- [ ] **Step 5: Commit the Laravel 12 checkpoint**

```powershell
git add composer.json composer.lock tests/Feature/UpgradeBaselineSmokeTest.php `
    public/css public/js public/mix-manifest.json
git commit -m "chore: upgrade framework to Laravel 12"
```

---

### Task 7: Upgrade Laravel 12 to Laravel 13 and harden PHP 8.5 compatibility

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Move: `app/Http/Middleware/VerifyCsrfToken.php` to `app/Http/Middleware/PreventRequestForgery.php`
- Modify: `app/Http/Kernel.php`
- Modify: `config/sanctum.php`
- Modify: `config/session.php`
- Modify: `config/database.php`
- Modify: `app/Models/User.php`
- Modify: `tests/Feature/UpgradeBaselineSmokeTest.php`

**Interfaces:**
- Consumes: passing Laravel 12 checkpoint and PHP 8.5.9 with SQLite enabled.
- Produces: Laravel 13/PHP 8.5 application with non-deprecated CSRF middleware, explicit session compatibility, and non-deprecated MySQL SSL option keys.

- [ ] **Step 1: Add PHP 8.5 configuration assertions before changing framework code**

Append these methods to `tests/Feature/UpgradeBaselineSmokeTest.php`:

```php
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
```

Expected before implementation: the test fails because the new middleware class and `session.serialization` configuration do not exist.

- [ ] **Step 2: Update final Laravel 13 dependency constraints**

Use these final constraints in `composer.json`:

```json
"require": {
    "php": "^8.3",
    "google/apiclient": "^2.19",
    "guzzlehttp/guzzle": "^7.10",
    "laravel/framework": "^13.0",
    "laravel/sanctum": "^4.3",
    "laravel/tinker": "^3.0",
    "laravel/ui": "^4.6",
    "league/flysystem-aws-s3-v3": "^3.0"
},
"require-dev": {
    "fakerphp/faker": "^1.24",
    "mockery/mockery": "^1.6",
    "nunomaduro/collision": "^8.6",
    "phpunit/phpunit": "^12.0",
    "spatie/laravel-ignition": "^2.12"
},
```

Do not add Laravel Boost, Pail, PAO, or Pint solely because they appear in the Laravel 13 skeleton; the application does not currently depend on them.

- [ ] **Step 3: Replace the deprecated CSRF middleware class**

Move `app/Http/Middleware/VerifyCsrfToken.php` to `app/Http/Middleware/PreventRequestForgery.php` and replace its contents with:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware
{
    /**
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
```

In `app/Http/Kernel.php`, replace the web middleware entry with:

```php
\App\Http\Middleware\PreventRequestForgery::class,
```

In `config/sanctum.php`, keep the key `validate_csrf_token` but change its value:

```php
'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
```

- [ ] **Step 4: Preserve sessions and remove PHP 8.5 PDO deprecations**

In `config/session.php`, add after the `encrypt` option:

```php
'serialization' => env('SESSION_SERIALIZATION', 'php'),
```

At the top of `config/database.php`, after the imports and before `return`, add:

```php
$mysqlSslCaAttribute = defined('Pdo\\Mysql::ATTR_SSL_CA')
    ? constant('Pdo\\Mysql::ATTR_SSL_CA')
    : PDO::MYSQL_ATTR_SSL_CA;
```

Replace both `PDO::MYSQL_ATTR_SSL_CA` option keys in the `mysql` and `hris` connections with:

```php
$mysqlSslCaAttribute => env('MYSQL_ATTR_SSL_CA'),
```

and:

```php
$mysqlSslCaAttribute => env('HRIS_MYSQL_ATTR_SSL_CA', env('MYSQL_ATTR_SSL_CA')),
```

Remove the unused import below from `app/Models/User.php` because the model does not implement the contract:

```php
use Illuminate\Contracts\Auth\MustVerifyEmail;
```

- [ ] **Step 5: Resolve Laravel 13 under PHP 8.5**

```powershell
$php85 = 'C:\xampp\php85\php.exe'
$composerPhar = (Resolve-Path '.tools\composer.phar').Path
& $php85 $composerPhar update --with-all-dependencies
& $php85 artisan --version
```

Expected: Laravel reports `13.x`; Composer itself and installed dependencies emit no PHP 8.5 deprecation.

- [ ] **Step 6: Run the Laravel 13 quality gate with all deprecations enabled**

```powershell
& $php85 -d error_reporting=E_ALL artisan optimize:clear
& $php85 -d error_reporting=E_ALL artisan migrate:fresh --env=testing --force
& $php85 -d error_reporting=E_ALL artisan test
& $php85 -d error_reporting=E_ALL artisan route:list
& $php85 -d error_reporting=E_ALL artisan config:cache
& $php85 -d error_reporting=E_ALL artisan route:cache
& $php85 -d error_reporting=E_ALL artisan event:cache
& $php85 -d error_reporting=E_ALL artisan view:cache
& $php85 artisan optimize:clear
& $php85 $composerPhar validate --no-check-publish
& $php85 $composerPhar audit
```

Expected: all commands exit `0`, tests pass, and output contains no `Deprecated:` or `PHP Deprecated:` line.

- [ ] **Step 7: Commit the Laravel 13 checkpoint**

```powershell
git add composer.json composer.lock app/Http/Middleware/PreventRequestForgery.php `
    app/Http/Middleware/VerifyCsrfToken.php app/Http/Kernel.php config/sanctum.php `
    config/session.php config/database.php app/Models/User.php `
    tests/Feature/UpgradeBaselineSmokeTest.php
git commit -m "chore: upgrade framework to Laravel 13"
```

---

### Task 8: Harden and verify the Laravel Mix frontend toolchain

**Files:**
- Modify: `package.json`
- Modify: `package-lock.json`
- Verify: `resources/js/bootstrap.js`
- Verify: `webpack.mix.js`
- Regenerate: `public/css/app.css`
- Regenerate: `public/js/app.js`
- Regenerate: `public/mix-manifest.json`

**Interfaces:**
- Consumes: Laravel 13 checkpoint and existing CommonJS frontend entry points.
- Produces: reproducible Bootstrap 5 assets without retaining Axios 0.21 or the old Laravel Mix patch release.

- [ ] **Step 1: Update direct frontend dependency constraints without migrating to Vite**

Replace `devDependencies` in `package.json` with:

```json
"devDependencies": {
    "@popperjs/core": "^2.11.8",
    "axios": "^1.18.1",
    "bootstrap": "^5.3.8",
    "laravel-mix": "^6.0.49",
    "lodash": "^4.17.21",
    "postcss": "^8.5.6",
    "resolve-url-loader": "^5.0.0",
    "sass": "^1.90.0",
    "sass-loader": "^12.6.0"
}
```

Keep the existing Mix scripts unchanged.

- [ ] **Step 2: Re-resolve the frontend lock file and build assets**

```powershell
npm install
npm run production
npm audit --audit-level=high
```

Expected: production build exits `0`. High/critical findings must be resolved with `npm audit fix` only when it does not introduce a major version; rerun the build after any lock change. If the only available fix requires replacing Laravel Mix, stop and document the advisory for a separately approved Vite migration rather than silently expanding scope.

- [ ] **Step 3: Verify generated assets and page references**

```powershell
Test-Path public\css\app.css
Test-Path public\js\app.js
Get-Content public\mix-manifest.json
rg -n "mix\('(?:css|js)/app" resources\views
```

Expected: both assets exist, the manifest contains `/css/app.css` and `/js/app.js`, and existing Blade references still use `mix()`.

- [ ] **Step 4: Commit frontend hardening**

```powershell
git add package.json package-lock.json public/css/app.css public/js/app.js public/mix-manifest.json
git commit -m "chore: harden Laravel Mix dependencies"
```

---

### Task 9: Validate a migration-only MariaDB staging database

**Files:**
- Runtime only, never commit: `.env`
- Runtime only: MariaDB database `arsipin_upgrade_staging`
- Create: `docs/verification/laravel-13-staging-results.md`

**Interfaces:**
- Consumes: final Laravel 13 code, PHP 8.5, and local MariaDB 10.4.
- Produces: a fresh MariaDB schema and a written evidence record without production data or credentials.

- [ ] **Step 1: Create the isolated database**

Use the local MariaDB administrator account interactively so its password is not placed on the command line or in shell history:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root -p -e `
    "CREATE DATABASE IF NOT EXISTS arsipin_upgrade_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Expected: `SHOW DATABASES LIKE 'arsipin_upgrade_staging';` returns exactly one database.

- [ ] **Step 2: Create a non-production worktree environment**

Copy `.env.example` to the ignored `.env`, generate a new key, and set these exact non-secret values; enter only the local staging database password interactively/manualy:

```dotenv
APP_NAME="Arsipin Upgrade Staging"
APP_ENV=staging
APP_DEBUG=true
APP_URL=http://127.0.0.1:8013
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arsipin_upgrade_staging
CACHE_DRIVER=file
CACHE_PREFIX=arsipin_upgrade_staging_cache
REDIS_PREFIX=arsipin_upgrade_staging_database
SESSION_DRIVER=file
SESSION_COOKIE=arsipin_upgrade_staging_session
SESSION_SERIALIZATION=php
FILESYSTEM_DISK=local
MAIL_MAILER=array
QUEUE_CONNECTION=sync
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_ENDPOINT=
```

Run:

```powershell
$php85 = 'C:\xampp\php85\php.exe'
Copy-Item .env.example .env
# Apply the values above to the ignored .env, then:
& $php85 artisan key:generate
& $php85 artisan config:clear
```

- [ ] **Step 3: Enforce the staging database-name guard before destructive migration**

```powershell
$databaseName = & $php85 artisan tinker --execute="echo config('database.connections.'.config('database.default').'.database');"
if ($databaseName.Trim() -ne 'arsipin_upgrade_staging') {
    throw "Refusing migrate:fresh for database: $databaseName"
}
& $php85 artisan migrate:fresh --force
& $php85 artisan migrate:status
```

Expected: all 29 application migrations report `Ran`; no production database name appears.

- [ ] **Step 4: Run final integration commands against MariaDB staging**

```powershell
& $php85 artisan about
& $php85 artisan route:list
& $php85 artisan config:cache
& $php85 artisan route:cache
& $php85 artisan event:cache
& $php85 artisan view:cache
& $php85 artisan optimize:clear
& $php85 artisan test
npm ci
npm run production
```

Expected: Laravel 13/PHP 8.5 are reported; migrations, caches, tests, and frontend build all pass.

- [ ] **Step 5: Record verification evidence**

Create `docs/verification/laravel-13-staging-results.md` with concrete command results in this structure:

```markdown
# Laravel 13 Staging Verification

- Date: 2026-08-18
- Branch: `codex/upgrade-laravel13`
- PHP: 8.5.9
- Laravel: `^13.0` with the exact patch version locked in `composer.lock`
- Database: MariaDB 10.4 / `arsipin_upgrade_staging`
- Production data copied: No
- Migration result: all 29 application migrations passed
- PHPUnit result: at least 30 tests passed
- Composer audit: no unresolved security advisory
- Frontend build: Passed
- Route/config/event/view cache: Passed
- External R2/Google/mail calls: Disabled or faked
```

If implementation adds regression tests or migrations, update only the numeric totals upward before committing; do not include credentials or absolute secret paths.

- [ ] **Step 6: Commit the staging verification record**

```powershell
git add docs/verification/laravel-13-staging-results.md
git commit -m "docs: record Laravel 13 staging verification"
```

---

### Task 10: Write the production cutover and rollback runbook

**Files:**
- Create: `docs/runbooks/laravel-13-production-cutover.md`

**Interfaces:**
- Consumes: verified Laravel 13 branch and migration-only staging evidence.
- Produces: a human-approved deployment checklist; it does not perform production deployment.

- [ ] **Step 1: Create the production runbook**

Create `docs/runbooks/laravel-13-production-cutover.md` with this complete operational sequence:

```markdown
# Laravel 13 Production Cutover Runbook

## Approval gate

- Confirm an approved maintenance window.
- Confirm `codex/upgrade-laravel13` has passed the staging verification record.
- Confirm PHP 8.5 Apache module/configuration is available but not yet active.
- Confirm database and storage backup destinations are outside the web root.

## Read-only preflight

1. Record `php artisan --version`, `php -v`, `php artisan migrate:status`, and `git rev-parse HEAD`.
2. Run `composer audit` against the final lock file.
3. Confirm MariaDB is at least 10.3 and all 29 application migrations are already represented.
4. Inspect nullable/legacy values used by roles, document statuses, workflow risks, and SOP statuses without updating rows.
5. Confirm writable permissions for `storage` and `bootstrap/cache`.

## Backup

1. Create a timestamped code archive excluding `vendor`, `node_modules`, cache, and session files.
2. Run `mysqldump` interactively with `--single-transaction --routines --triggers` to a path outside the web root.
3. Archive `storage/app` separately.
4. Verify the database dump contains table definitions and final `COMMIT`; verify both archives can be opened.

## Cutover

1. Run `php artisan down --render="errors::503"` with the current PHP 7.4 runtime.
2. Deploy the reviewed Laravel 13 source and lock files; do not deploy `.env`, tests, `.tools`, or staging artifacts.
3. Run PHP 8.5 Composer `install --no-dev --prefer-dist --optimize-autoloader`.
4. Point Apache at PHP 8.5 and verify required extensions before serving traffic.
5. Run `php artisan optimize:clear` and inspect the active database name.
6. Run `php artisan migrate --force`; never use `migrate:fresh` in production.
7. Run `php artisan config:cache`, `route:cache`, `event:cache`, and `view:cache`.
8. Verify login, dashboard, one read-only page per role, one staging-safe upload/download path, and activity logging.
9. Run `php artisan up` only after health checks pass.

## Rollback triggers

- Application boot failure, HTTP 500, login failure, authorization regression, migration failure, missing upload/download, or sustained error-log growth.

## Rollback

1. Keep maintenance mode active.
2. Restore the previous Laravel 8 code release and `composer.lock`.
3. Restore the PHP 7.4 Apache configuration.
4. Restore the database dump only if a Laravel 13 deployment migration changed schema/data; otherwise leave data intact.
5. Restore `storage/app` only if the cutover modified files.
6. Clear Laravel caches with PHP 7.4, run the Laravel 8 smoke checks, then reopen traffic.

## Post-cutover monitoring

- Monitor application/PHP/Apache logs, authentication, authorization failures, queue failures, upload/download, R2 temporary URLs, Google Drive actions, and response latency.
- Keep backups until the agreed observation window closes.
```

- [ ] **Step 2: Review the runbook for destructive-command safety**

Verify that it contains no `migrate:fresh`, `db:wipe`, `DROP DATABASE`, recursive delete, secret on a command line, or automatic production action. The only production migration command must be:

```powershell
& 'C:\xampp\php85\php.exe' artisan migrate --force
```

- [ ] **Step 3: Commit the runbook**

```powershell
git add docs/runbooks/laravel-13-production-cutover.md
git commit -m "docs: add Laravel 13 production cutover runbook"
```

---

### Task 11: Perform final review and handoff

**Files:**
- Verify all tracked changes on `codex/upgrade-laravel13`
- Verify: `docs/verification/laravel-13-staging-results.md`
- Verify: `docs/runbooks/laravel-13-production-cutover.md`

**Interfaces:**
- Consumes: Tasks 1–10.
- Produces: a reviewed Laravel 13 branch ready for a separate production deployment decision.

- [ ] **Step 1: Run the final reproducible verification from a clean dependency state**

```powershell
$php85 = 'C:\xampp\php85\php.exe'
$composerPhar = (Resolve-Path '.tools\composer.phar').Path

& $php85 $composerPhar install
& $php85 $composerPhar validate --no-check-publish
& $php85 $composerPhar audit
& $php85 artisan optimize:clear
& $php85 -d error_reporting=E_ALL artisan test
& $php85 artisan route:list
npm ci
npm run production
git diff --check
git status --short
```

Expected: all commands pass; no deprecation appears; `git diff --check` is empty. Generated assets must either be unchanged or committed.

- [ ] **Step 2: Review the checkpoint history**

```powershell
git log --oneline --decorate --reverse main..HEAD
git diff --stat main...HEAD
```

Expected: separate commits exist for baseline tests, Laravel 9, 10, 11, 12, 13, frontend hardening, staging evidence, and deployment runbook.

- [ ] **Step 3: Request code review**

Invoke the `requesting-code-review` skill. The review must explicitly check:

```text
- production isolation and absence of secrets
- Composer constraints and lock file for Laravel 13/PHP 8.5
- preservation of Laravel 10-style bootstrap on Laravel 11–13
- Sanctum migration/configuration correctness
- CSRF middleware rename and origin protection
- local/R2 storage behavior after Flysystem 3
- role/authentication regressions
- PHP 8.5 deprecations
- migration-only MariaDB verification evidence
- rollback safety
```

- [ ] **Step 4: Fix all accepted review findings and rerun the final gate**

For each accepted finding, add a regression test first when behavior changes, implement the smallest fix, run the focused test, then rerun Step 1. Commit fixes with a scoped message such as:

```powershell
git commit -m "fix: address Laravel 13 upgrade review findings"
```

- [ ] **Step 5: Prepare branch-completion choices**

Invoke the `finishing-a-development-branch` skill. Do not merge into production or switch Apache/PHP without a new explicit user approval.
