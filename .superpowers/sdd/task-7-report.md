# Task 7 Report — Upgrade Laravel 12 to Laravel 13 and PHP 8.5 Hardening

## Status

PASS. Laravel 13.26.1 is installed and the application passes the PHP 8.5.9 quality gate without `Deprecated:` or `PHP Deprecated:` output. Composer dependency resolution used normal security blocking; no advisory ignore or security bypass was added.

## Scope

- Worktree: `C:\xampp\htdocs\lavel-arsipin\.worktrees\upgrade-laravel13`
- Branch: `codex/upgrade-laravel13`
- Base: `5109e780419d1c27a7b8b782b067a233b03e5ddc`
- Planned commit: `chore: upgrade framework to Laravel 13`
- Runtime: PHP `8.5.9`; Composer `2.10.2`
- PHP extensions used by the gate: `pdo_sqlite` and `pdo_mysql` enabled.
- Production database and production storage were not accessed. `migrate:fresh` ran only with `--env=testing --force`; `phpunit.xml` fixes the testing database to SQLite `:memory:`.
- The existing Laravel application skeleton was preserved. Laravel Boost, Pail, PAO/prompt optimization, and Pint were not added as direct dependencies.
- Composer platform resolution is pinned to PHP `8.3.0` so the committed lock remains installable across the full root constraint `^8.3`, while the application quality gate runs on PHP 8.5.9.

## RED evidence before implementation

- Framework before update: Laravel Framework `12.67.0` on PHP `8.5.9`.
- Added the two Task 7 assertions before changing framework/configuration code.
- `artisan test tests\Feature\UpgradeBaselineSmokeTest.php` exited `1` with 10 existing tests reported deprecated and the two new tests failing (45 assertions total):
  - `session.serialization` was `null`, not `php`.
  - `App\Http\Middleware\PreventRequestForgery` was not a subclass of the Laravel 13 middleware class because the application class did not exist yet.
- The same RED run emitted the expected PHP 8.5 deprecations for `PDO::MYSQL_ATTR_SSL_CA` at the two old option keys in `config/database.php`.
- Review hardening added a separate RED subprocess test under PHP 8.5.9 with `-n`: PDO was available but `pdo_mysql` and both MySQL SSL constants were unavailable, causing the first implementation to fatal with `Undefined constant PDO::MYSQL_ATTR_SSL_CA`.
- Before the platform pin, `composer prohibits php 8.3.0 --locked` exited `1` and identified 17 locked Symfony 8.1 packages requiring PHP `>=8.4.1`.

## Implementation

- Raised the direct constraints exactly to PHP `^8.3`, Laravel Framework `^13.0`, Sanctum `^4.3`, Tinker `^3.0`, Laravel UI `^4.6`, Guzzle `^7.10`, PHPUnit `^12.0`, and the other versions specified by the Task 7 brief.
- Replaced the application `VerifyCsrfToken` middleware with `PreventRequestForgery`, updated the web kernel entry, and retained Sanctum's `validate_csrf_token` key while switching its class value.
- Added explicit session serialization with the production-compatible default `php`; the existing explicit session cookie and cache prefix remain intact.
- Selected `Pdo\Mysql::ATTR_SSL_CA` when `pdo_mysql` is available and kept the legacy constant only as a lazy compatibility fallback. Without `pdo_mysql`, no MySQL constant is evaluated and both guarded connection option arrays remain empty.
- Added Composer `config.platform.php` at `8.3.0` and regenerated the lock so the declared PHP `^8.3` support is truthful rather than locking Symfony 8.1 packages that require PHP 8.4.1.
- Removed the unused `MustVerifyEmail` import from `User`.

## Dependency result

- Laravel Framework: `v13.26.1`
- Laravel Sanctum: `v4.3.3`
- Laravel Tinker: `v3.0.2`
- Laravel UI: `v4.6.3`
- PHPUnit: `12.5.33`
- Collision: `v8.9.5`
- Laravel Ignition: `2.12.0`
- Guzzle: `7.15.3`
- Google API Client: `v2.19.4`
- Flysystem AWS S3 adapter: `3.35.2`
- Faker: `v1.24.1`
- Mockery: `1.6.15`
- Initial Laravel 13 lock operations: 1 install, 41 updates, and 3 removals. The PHP 8.3 compatibility re-resolve then performed 1 install and 17 Symfony updates/downgrades with 0 removals.
- `composer update --with-all-dependencies` completed with normal security blocking and reported no security vulnerability advisories.

## GREEN and PHP 8.5 quality gate evidence

- Targeted GREEN: `artisan test tests\Feature\UpgradeBaselineSmokeTest.php` exited `0`; 13 tests passed, 47 assertions.
- No-driver regression GREEN: the isolated PHP `-n` subprocess loaded `config/database.php` successfully without `pdo_mysql`; 1 test passed, 2 assertions.
- `artisan --version`: exit `0`; Laravel Framework `13.26.1`.
- `artisan optimize:clear` before and after cache checks: exit `0`.
- `artisan migrate:fresh --env=testing --force`: exit `0`; all 30 migrations passed against the testing SQLite database.
- Full `artisan test`: exit `0`; 32 tests passed, 107 assertions.
- `artisan route:list`: exit `0`; 75 routes.
- `artisan config:cache`, `route:cache`, `event:cache`, and `view:cache`: all exit `0`.
- `composer validate --no-check-publish`: exit `0`; `composer.json` is valid.
- Normal `composer audit`: exit `0`; no security vulnerability advisories found.
- `composer install --dry-run`: exit `0`; the lock is installable and has nothing missing or inconsistent.
- `composer prohibits php 8.3.0 --locked`: exit `0`; no locked package requires a PHP version outside 8.3.0.
- `composer check-platform-reqs --lock`: exit `0`; all requirements pass on PHP 8.5.9.
- PHP lint passed for every modified PHP source/config/test file.
- Exact-constraint verification passed and confirmed there are no new-skeleton extras among direct dependencies.
- PDO compatibility diagnostics bootstrapped the application under `E_ALL`, confirmed `Pdo\Mysql::ATTR_SSL_CA` exists and selects key `1008`, and separately loaded database configuration without any MySQL extension under PHP `-n`.
- Composer, Artisan, migration, tests, route/config/event/view cache gates, validation, audit, and the PDO diagnostic produced no final `Deprecated:` or `PHP Deprecated:` line.

## Review notes and concerns

- An earlier audit invocation experienced a Packagist timeout and fell back to the cache refreshed by the successful normal-security `composer update`. The final fresh `composer audit` then completed cleanly with exit `0` and no advisories.
- No persistent `safe.directory`, Composer ignore, `--no-security-blocking`, `--no-audit`, platform-requirement bypass, or global deprecation suppression was added.
- `config.platform.php=8.3.0` is a dependency-resolution compatibility floor, not a platform-requirement or security bypass; normal Composer validation, install dry-run, update security blocking, and audit remain enabled.
- The legacy `PDO::MYSQL_ATTR_SSL_CA` token remains only in the guarded lazy fallback required for older PHP/PDO MySQL environments; PHP 8.5 takes the namespaced constant branch, and installations without `pdo_mysql` evaluate neither constant.
