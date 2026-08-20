# Laravel 13 Staging Verification

- Date: 2026-08-20
- Branch: `codex/upgrade-laravel13`
- PHP: `8.5.9`
- Laravel: `^13.0`; exact installed version `13.26.1`
- Database: `10.4.27-MariaDB` / `arsipin_upgrade_staging`
- Production data copied: No
- Migration result: 32 of 32 application migrations passed in batch 1
- Route result: 75 routes loaded
- PHPUnit result: 45 tests passed with 157 assertions
- Composer audit: Passed; no security vulnerability advisories found
- Frontend install: Passed; `npm ci` installed 771 packages
- Frontend build: Passed with Laravel Mix `6.0.49`
- Route/config/event/view cache: Passed
- External R2/Google/mail calls: Disabled, absent, or faked during verification

## Database isolation and migration evidence

The local administrator connection was validated without recording credentials. Only `arsipin_upgrade_staging` was created for this gate, using `utf8mb4` and `utf8mb4_unicode_ci`.

Before `migrate:fresh --force`, independent guards confirmed all of the following:

- Laravel's default connection was `mysql`.
- The configured host and port were exactly `127.0.0.1:3306`.
- Both the configured database and live connected database were exactly `arsipin_upgrade_staging`.
- Migration files contained no detected external-service calls.

`migrate:fresh --force` completed successfully. A subsequent `migrate:status` reported all 32 migrations as `Ran`; the staging migrations table also contained exactly 32 rows. The migration command was run without --seed. No production rows or storage files were copied.

## Application verification evidence

- `artisan about` reported Laravel `13.26.1`, PHP `8.5.9`, environment `staging`, database driver `mysql`, file-backed cache/session, array mail, sync queue, and local filesystem storage.
- `artisan route:list` loaded 75 routes.
- Config, route, event, and Blade view caches were each built successfully, then `optimize:clear` completed successfully.
- `artisan test` passed 45 tests with 157 assertions. The committed test configuration uses SQLite in-memory for test isolation; mail and notifications are faked. Storage regressions cover local and fake-R2 upload, inline/temporary-URL access, and deletion. A separate no-network regression temporarily resolves the configured R2 disk to the real Flysystem v3 AWS S3 adapter, then restores the fake.
- Sanctum 4 expiry storage was verified through real `User::createToken` calls, a fresh indexed/nullable schema assertion, the guarded additive migration path, and deterministic `sanctum:prune-expired --hours=0` coverage proving only the expired token is deleted. The document-status rollback regression confirms expanded business outcomes are never silently coerced.

## Dependency and frontend evidence

- The controller-authorized PHP 8.5 Composer gate ran `audit --locked --no-interaction` successfully and found no security vulnerability advisories.
- `npm ci` completed successfully and installed 771 packages.
- `npm run production` compiled successfully with Laravel Mix `6.0.49`.
- A fresh production build produced no tracked frontend or package metadata diff.

`npm ci` reported 11 residual package vulnerabilities: 5 low and 6 moderate, with no high or critical findings. These findings do not invalidate the Laravel 13 staging gate, but should be reviewed in a separate dependency-maintenance change to avoid an unscoped upgrade.

## External-service controls

The staging runtime used local filesystem storage and array mail. R2 credentials, bucket, and endpoint were empty; no Google Drive credential file was present. Tests faked mail, notifications, local storage, and R2. No external workflows were invoked, and no production rows or storage files were copied.
