# Laravel 13 Production Cutover Runbook

This runbook is a human-operated checklist for a separately approved production change. It does not authorize or automate deployment. Run each command individually from an elevated PowerShell session only after the named approval gate is signed. Do not paste an entire command block at once.

## Scope and operator record

- Production application: Arsipin
- Current release: Laravel 8 / PHP 7.4
- Target release: Laravel 13 / PHP 8.5
- Production root: `C:\xampp\htdocs\lavel-arsipin`
- Target branch: `codex/upgrade-laravel13`
- Staging evidence: `docs/verification/laravel-13-staging-results.md`
- Expected migration files: **30**

Record these values before continuing:

| Item | Recorded value |
| --- | --- |
| Change ticket | |
| Maintenance window | |
| Deployment operator | |
| Database operator | |
| Approver | |
| Approved commit SHA | |
| Approved production database name | |
| Previous release SHA/archive | |
| Backup directory | |
| Cutover start time | |

The deployment operator owns the cutover journal. Record the result and timestamp of every numbered step. A failed check means **stop**, keep traffic closed, and ask the approver whether to correct the issue or start rollback.

## Approval gate

- [ ] The change owner and production approver have approved the maintenance window and this exact commit.
- [ ] Branch `codex/upgrade-laravel13` has passed the staging verification record, including 30 of 30 migrations, 32 tests, cache builds, Composer audit, and the production frontend build.
- [ ] The release artifact checksum matches the artifact built from the approved commit.
- [ ] A tested PHP 8.5 Apache module/configuration set is available but is not active.
- [ ] The current PHP 7.4 Apache configuration has a separately identifiable rollback copy.
- [ ] The operator has a tested Apache stop/start procedure and console access if Apache fails to restart.
- [ ] Database and storage backup destinations are outside `C:\xampp\htdocs` and have enough free space.
- [ ] The database operator has confirmed the approved production database name out of band. Do not obtain or record a password in this document, a ticket comment, shell history, or a command argument.
- [ ] Queue workers, scheduled commands, webhooks, and other background writers have an approved pause/resume procedure.
- [ ] Rollback owner, rollback decision deadline, and observation window are recorded.

Do not continue without every item above and explicit human approval. The commands below never substitute for approval.

## Read-only preflight

Run from the current production release before changing code, runtime, configuration, database, or storage.

1. Set only non-secret working values and confirm the backup destination is outside the web root.

   ```powershell
   $ReleaseRoot = 'C:\xampp\htdocs\lavel-arsipin'
   $WebRoot = 'C:\xampp\htdocs'
   $BackupRoot = 'D:\arsipin-backups\laravel13-cutover'
   $ReviewedReleaseRoot = [IO.Path]::GetFullPath((Read-Host 'Path to the extracted, checksummed Laravel 13 release artifact')).TrimEnd('\')
   $ApprovedCommit = Read-Host 'Approved Laravel 13 commit SHA'
   $ApprovedDatabaseName = Read-Host 'Approved production database name'
   $Stamp = Get-Date -Format 'yyyyMMdd-HHmmss'

   $releaseFullPath = [IO.Path]::GetFullPath($ReleaseRoot).TrimEnd('\')
   $webFullPath = [IO.Path]::GetFullPath($WebRoot).TrimEnd('\') + '\'
   $backupFullPath = [IO.Path]::GetFullPath($BackupRoot).TrimEnd('\') + '\'
   if ($backupFullPath.StartsWith($webFullPath, [StringComparison]::OrdinalIgnoreCase)) {
       throw 'Backup destination must be outside the web root.'
   }
   if ($ApprovedCommit -notmatch '^[0-9a-fA-F]{40}$') {
       throw 'Record the full 40-character approved commit SHA.'
   }
   if ($ApprovedDatabaseName -notmatch '^[A-Za-z0-9_]+$') {
       throw 'Database name may contain only letters, numbers, and underscore.'
   }
   if (!(Test-Path -LiteralPath (Join-Path $ReviewedReleaseRoot 'composer.lock'))) {
       throw 'Reviewed release artifact does not contain composer.lock.'
   }
   if ($ReviewedReleaseRoot -eq $releaseFullPath) {
       throw 'Read-only artifact checks must not use the active production release directory.'
   }
   Set-Location $releaseFullPath
   ```

2. Record the current versions, migration status, release SHA, and MariaDB version. These commands are read-only.

   ```powershell
   $PreflightDatabaseUser = Read-Host 'Database user'
   & 'C:\xampp\php\php.exe' artisan --version
   & 'C:\xampp\php\php.exe' -v
   & 'C:\xampp\php\php.exe' artisan migrate:status
   git rev-parse HEAD
   $CurrentDatabaseName = (& 'C:\xampp\php\php.exe' artisan tinker --execute="echo config('database.connections.'.config('database.default').'.database');").Trim()
   "Current database: $CurrentDatabaseName"
   if ($CurrentDatabaseName -ne $ApprovedDatabaseName) {
       throw 'Current Laravel 8 database does not match the approved production database.'
   }
   & 'C:\xampp\mysql\bin\mysql.exe' --user=$PreflightDatabaseUser --password --execute='SELECT VERSION();'
   ```

   The database password must be entered only at the interactive prompt. Stop if the current runtime is not the expected PHP 7.4/Laravel 8 release or MariaDB is older than 10.3.

3. Audit the final lock file with the reviewed PHP 8.5 runtime and the host-installed Composer PHAR. Do not use the staging-only `.tools` directory.

   ```powershell
   & 'C:\xampp\php85\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' --working-dir=$ReviewedReleaseRoot validate --no-check-publish --no-interaction
   & 'C:\xampp\php85\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' --working-dir=$ReviewedReleaseRoot audit --locked --no-interaction
   ```

   Stop for any validation error or unresolved security advisory.

4. Verify that the reviewed release contains exactly 30 migration files and that all 30 appear in the reviewed migration-status evidence. Record which entries are already `Ran` and which are expected `Pending`; every pending migration must have explicit database-operator approval.

   ```powershell
   $MigrationCount = @(Get-ChildItem -LiteralPath (Join-Path $ReviewedReleaseRoot 'database\migrations') -File -Filter '*.php').Count
   if ($MigrationCount -ne 30) {
       throw "Expected 30 migration files; found $MigrationCount."
   }
   ```

5. Using the interactive database client, run only reviewed `SELECT` statements to inventory null/legacy values for `users.role`, `dokumen.status_dokumen`, `alur_kerja.risiko`, `alur_kerja.status_dokumentasi`, `alur_kerja.status_operasional`, `sop_pengetahuan.status`, and `jobdescs.status`. Record counts, not personal data. Do not update rows during preflight.

   ```powershell
   $DatabaseUser = Read-Host 'Database user'
   & 'C:\xampp\mysql\bin\mysql.exe' --user=$DatabaseUser --password --database=$ApprovedDatabaseName --execute="SELECT role, COUNT(*) AS total FROM users GROUP BY role; SELECT status_dokumen, COUNT(*) AS total FROM dokumen GROUP BY status_dokumen; SELECT risiko, status_dokumentasi, status_operasional, COUNT(*) AS total FROM alur_kerja GROUP BY risiko, status_dokumentasi, status_operasional; SELECT status, COUNT(*) AS total FROM sop_pengetahuan GROUP BY status; SELECT status, COUNT(*) AS total FROM jobdescs GROUP BY status;"
   if ($LASTEXITCODE -ne 0) {
       throw 'Read-only legacy-value inventory failed.'
   }
   ```

6. Confirm `storage` and `bootstrap/cache` exist and are writable by the Apache service identity. This local operator check does not replace an ACL check for the actual service account.

   ```powershell
   Get-Acl (Join-Path $ReleaseRoot 'storage') | Format-List
   Get-Acl (Join-Path $ReleaseRoot 'bootstrap\cache') | Format-List
   ```

7. Review application, PHP, and Apache logs for an existing error baseline. Record queue depth and active scheduled/background processes. Stop if an unexplained production fault already exists.

## Backup

Backups are created before any cutover action. Pause or otherwise quiesce all approved background writers for the backup window, and record how new web writes are prevented or reconciled. Do not continue unless the database and file snapshots represent the same documented recovery point.

1. Create the timestamped backup directory outside the web root, then create a code archive. The code archive deliberately excludes secrets, dependencies, staging/worktree material, user files, caches, logs, and sessions. Production `.env` remains managed by the existing secret-management procedure and must never be added to the release artifact.

   ```powershell
   New-Item -ItemType Directory -Path $BackupRoot -Force | Out-Null
   $CodeArchive = Join-Path $BackupRoot "arsipin-code-$Stamp.tar.gz"
   tar.exe -czf $CodeArchive --exclude='.git' --exclude='.worktrees' --exclude='.env' --exclude='.tools' --exclude='vendor' --exclude='node_modules' --exclude='docs' --exclude='tests' --exclude='storage/app' --exclude='storage/framework/cache' --exclude='storage/framework/sessions' --exclude='storage/framework/views' --exclude='storage/logs' --exclude='bootstrap/cache/*.php' -C $ReleaseRoot .
   if ($LASTEXITCODE -ne 0 -or !(Test-Path -LiteralPath $CodeArchive)) {
       throw 'Code archive failed.'
   }
   ```

2. Create the database dump with a password prompt. Never append the database password to this command.

   ```powershell
   $DatabaseDump = Join-Path $BackupRoot "arsipin-database-$Stamp.sql"
   & 'C:\xampp\mysql\bin\mysqldump.exe' --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 --user=$DatabaseUser --password --result-file=$DatabaseDump $ApprovedDatabaseName
   if ($LASTEXITCODE -ne 0 -or !(Test-Path -LiteralPath $DatabaseDump) -or (Get-Item -LiteralPath $DatabaseDump).Length -eq 0) {
       throw 'Database dump failed or is empty.'
   }
   ```

3. Archive `storage/app` separately.

   ```powershell
   $StorageArchive = Join-Path $BackupRoot "arsipin-storage-app-$Stamp.tar.gz"
   tar.exe -czf $StorageArchive -C (Join-Path $ReleaseRoot 'storage') app
   if ($LASTEXITCODE -ne 0 -or !(Test-Path -LiteralPath $StorageArchive)) {
       throw 'Storage archive failed.'
   }
   ```

4. Validate the recovery material before maintenance mode. A successful command is not enough: inspect the archive listings, confirm the SQL contains table definitions and a final transaction commit, and record SHA-256 hashes.

   ```powershell
   tar.exe -tzf $CodeArchive | Select-Object -First 20
   if ($LASTEXITCODE -ne 0) { throw 'Code archive cannot be opened.' }
   tar.exe -tzf $StorageArchive | Select-Object -First 20
   if ($LASTEXITCODE -ne 0) { throw 'Storage archive cannot be opened.' }
   if (!(Select-String -LiteralPath $DatabaseDump -Pattern '^CREATE TABLE ' -Quiet)) {
       throw 'Database dump contains no table definition.'
   }
   if (!(Get-Content -LiteralPath $DatabaseDump -Tail 100 | Select-String -Pattern '^COMMIT;' -Quiet)) {
       throw 'Database dump has no final COMMIT marker.'
   }
   Get-FileHash -Algorithm SHA256 $CodeArchive, $DatabaseDump, $StorageArchive
   ```

5. The database operator must confirm the dump can be restored into an isolated non-production database using the approved restore procedure, or cite a recent successful restore test for the same backup mechanism. Never test a restore against production.

6. Record the final backup paths, sizes, hashes, validation result, and recovery-point timestamp. The database operator and approver must sign the backup gate before cutover.

## Cutover

The site is intentionally unavailable during this sequence. Keep a second terminal open for logs. Run one step at a time, record the result, and stop at the first failure.

1. Enter maintenance mode with the current PHP 7.4 runtime, confirm HTTP 503 from an unauthenticated client, then stop approved queue/scheduler/background writers. Do not deploy until maintenance mode is confirmed.

   ```powershell
   Set-Location 'C:\xampp\htdocs\lavel-arsipin'
   & 'C:\xampp\php\php.exe' artisan down --render='errors::503'
   if ($LASTEXITCODE -ne 0) { throw 'Failed to enter maintenance mode.' }
   ```

2. Stop Apache with the approved XAMPP procedure. Confirm no Apache process is serving requests before changing code or PHP configuration.

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -k shutdown
   Start-Sleep -Seconds 3
   Get-Process httpd -ErrorAction SilentlyContinue
   ```

   If an `httpd` process remains, stop and escalate; do not force-kill it without separate approval.

3. Deploy only the reviewed Laravel 13 application source, `composer.json`, `composer.lock`, and already-reviewed production frontend assets from the checksummed artifact. Preserve the production `.env` and persistent `storage/app`. Do **not** deploy `.env`, `.tools`, `.superpowers`, `docs`, tests, staging verification artifacts, caches, sessions, logs, credentials, or a worktree. The release mechanism must write a non-secret `.release-sha` metadata file from the artifact manifest. Verify it equals `$ApprovedCommit`; a mismatch requires rollback before Apache is restarted.

   ```powershell
   Set-Location 'C:\xampp\htdocs\lavel-arsipin'
   $DeployedCommit = (Get-Content -LiteralPath (Join-Path $ReleaseRoot '.release-sha') -Raw).Trim()
   if ($DeployedCommit -ne $ApprovedCommit) {
       throw "Deployed commit $DeployedCommit does not match approved commit $ApprovedCommit."
   }
   ```

4. Install locked production dependencies with PHP 8.5 and the host Composer PHAR. The staging `.tools` Composer copy is not a deployable artifact.

   ```powershell
   & 'C:\xampp\php85\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   if ($LASTEXITCODE -ne 0) { throw 'Composer production install failed.' }
   ```

5. While Apache remains stopped, activate the pre-reviewed PHP 8.5 Apache configuration using the host's approved configuration-switch procedure. Do not edit module paths ad hoc. Validate Apache syntax and the required PHP 8.5 CLI extensions; keep Apache stopped after validation.

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -t
   if ($LASTEXITCODE -ne 0) { throw 'Apache configuration test failed.' }
   & 'C:\xampp\php85\php.exe' -r "foreach (['curl','fileinfo','mbstring','openssl','pdo_mysql','pdo_sqlite','sqlite3','zip'] as `$extension) { if (!extension_loaded(`$extension)) { fwrite(STDERR, `$extension.PHP_EOL); exit(1); } }"
   if ($LASTEXITCODE -ne 0) { throw 'A required PHP 8.5 extension is missing.' }
   ```

6. Clear stale Laravel caches with PHP 8.5, then read the effective environment and database name from the booted application. This check exposes neither passwords nor connection tokens.

   ```powershell
   & 'C:\xampp\php85\php.exe' artisan optimize:clear
   if ($LASTEXITCODE -ne 0) { throw 'Laravel cache clear failed.' }
   $ActiveEnvironment = (& 'C:\xampp\php85\php.exe' artisan tinker --execute="echo app()->environment();").Trim()
   $ActiveDatabaseName = (& 'C:\xampp\php85\php.exe' artisan tinker --execute="echo config('database.connections.'.config('database.default').'.database');").Trim()
   $ActiveDebug = (& 'C:\xampp\php85\php.exe' artisan tinker --execute="echo config('app.debug') ? 'true' : 'false';").Trim()
   "Environment: $ActiveEnvironment"
   "Database: $ActiveDatabaseName"
   "Debug: $ActiveDebug"
   if ($ActiveEnvironment -ne 'production') { throw 'APP_ENV is not production.' }
   if ($ActiveDatabaseName -ne $ApprovedDatabaseName) { throw 'Active database does not match the approved production database.' }
   if ($ActiveDebug -ne 'false') { throw 'APP_DEBUG must be false.' }
   & 'C:\xampp\php85\php.exe' artisan migrate:status
   ```

   Confirm that this definitive PHP 8.5 status lists all 30 reviewed migration files and that its pending set exactly matches the database operator's approved list.

7. **Second human approval gate:** the deployment operator and database operator must compare the displayed database name to the approved value, review the definitive PHP 8.5 migration status, and explicitly authorize the single migration command below. Record both names and the approval time. Do not continue on silence, assumption, status mismatch, or name mismatch.

   The commands `migrate:fresh`, `db:wipe`, SQL `DROP DATABASE`, recursive deletion, and any equivalent destructive reset are forbidden in production. Do not run migration rollback commands as part of cutover or recovery.

8. After recorded approval, run the only production schema migration command:

   ```powershell
   & 'C:\xampp\php85\php.exe' artisan migrate --force
   ```

   Immediately record the exit code and output, then capture the read-only migration status separately. Treat any migration attempt as potentially schema/data-changing, including a failed or partially completed attempt. Do not retry automatically. On failure, keep Apache stopped and enter the rollback decision gate.

   ```powershell
   & 'C:\xampp\php85\php.exe' artisan migrate:status
   ```

9. Build Laravel caches with PHP 8.5. A failure triggers the rollback decision gate; do not open traffic.

   ```powershell
   & 'C:\xampp\php85\php.exe' artisan config:cache
   if ($LASTEXITCODE -ne 0) { throw 'Config cache failed.' }
   & 'C:\xampp\php85\php.exe' artisan route:cache
   if ($LASTEXITCODE -ne 0) { throw 'Route cache failed.' }
   & 'C:\xampp\php85\php.exe' artisan event:cache
   if ($LASTEXITCODE -ne 0) { throw 'Event cache failed.' }
   & 'C:\xampp\php85\php.exe' artisan view:cache
   if ($LASTEXITCODE -ne 0) { throw 'View cache failed.' }
   ```

10. Start Apache under the pre-reviewed PHP 8.5 configuration. Confirm it starts cleanly and the site still returns the maintenance response. Check the Apache and PHP logs for module-load errors before functional checks.

    ```powershell
    Start-Process -FilePath 'C:\xampp\apache\bin\httpd.exe' -WorkingDirectory 'C:\xampp' -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Get-Process httpd -ErrorAction Stop
    Get-Content -LiteralPath 'C:\xampp\apache\logs\error.log' -Tail 100
    ```

11. While maintenance mode remains active, perform health checks through a pre-tested maintenance bypass provisioned by the approved secret-management procedure. Do not put a bypass secret on the command line or in the journal. Verify application boot, login, dashboard, one read-only page for each role, authorization boundaries, one pre-approved safe upload/download path, activity logging, and no unexpected external R2/Google Drive/email side effect. Record test accounts and objects by identifier only; do not record credentials. If no safe bypass exists, stop: do not remove maintenance mode merely to run tests while public traffic can reach Apache.

12. Compare error rate and response latency with the preflight baseline. Confirm queue workers and scheduled commands remain paused until their PHP 8.5 command paths/configuration are separately verified.

13. **Traffic-open approval gate:** only the change approver may authorize reopening after every health check passes. Then exit maintenance mode and confirm a normal external request.

    ```powershell
    & 'C:\xampp\php85\php.exe' artisan up
    if ($LASTEXITCODE -ne 0) { throw 'Failed to leave maintenance mode.' }
    ```

14. Resume queue workers, scheduled commands, webhooks, and other background writers with their approved PHP 8.5 configuration. Verify one safe job and record its outcome.

## Rollback triggers

Start the rollback decision gate for any of the following:

- application boot failure or persistent HTTP 500;
- Apache/PHP module-load failure;
- login failure or authorization regression;
- migration failure, partial migration, or unexpected schema/data change;
- missing or corrupt upload/download behavior;
- activity logging failure;
- unsafe external integration behavior;
- queue failure or repeated job exceptions;
- sustained application/PHP/Apache error-log growth;
- material response-latency regression;
- deployed commit, environment, or database-name mismatch; or
- a health check that cannot be completed before the rollback decision deadline.

No rollback is automatic. The deployment operator reports the trigger and current cutover stage; the change approver and database operator decide whether to correct forward or roll back. Default to keeping traffic closed while a decision is pending.

## Rollback

Rollback must restore a coherent Laravel 8/PHP 7.4 state. Apache must never serve Laravel 8 using the PHP 8.5 Apache configuration during the transition.

1. Keep or re-enter maintenance mode if the current application can boot, then stop Apache and all queue/scheduler/background writers. If the application cannot boot, leave Apache stopped and use the existing upstream maintenance response.

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -k shutdown
   Start-Sleep -Seconds 3
   Get-Process httpd -ErrorAction SilentlyContinue
   ```

   Do not continue while an application process can write to the database or `storage/app`.

2. Record the last completed cutover step, migration output, migration status, and whether any post-cutover write occurred. Classify database and storage independently:

   - `Database changed = No` only when the migration command was never started and no Laravel 13 process wrote data.
   - `Database changed = Yes/Unknown` when the migration command started, failed after starting, migration status changed, or Laravel 13 handled any write.
   - `Storage changed = Yes/Unknown` when an upload, integration, job, or operator action could have modified `storage/app`.

3. While Apache remains stopped, restore the previous Laravel 8 code release and its exact `composer.lock` from the verified archive/release mechanism. Preserve the production `.env` and maintenance marker. Do not restore caches, sessions, logs, `.tools`, tests, docs, staging artifacts, or credentials from the Laravel 13 artifact. Reinstall the locked Laravel 8 production dependencies with PHP 7.4 before Apache can restart.

   ```powershell
   Set-Location 'C:\xampp\htdocs\lavel-arsipin'
   & 'C:\xampp\php\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   if ($LASTEXITCODE -ne 0) { throw 'Laravel 8 dependency restore failed.' }
   ```

4. Restore the database dump **only** when `Database changed = Yes/Unknown`. If `Database changed = No`, leave the database intact. Database restoration is a separately approved DBA operation: re-check the target database name, verify the dump hash, keep all writers stopped, preserve and reconcile any legitimate transactions after the recovery point, and use the organization's tested restore procedure. Do not improvise destructive database commands or run application migrations backward.

5. Restore `storage/app` **only** when `Storage changed = Yes/Unknown`. If it changed, the change approver must decide how to retain and reconcile any legitimate files created after the backup before the storage operator uses the tested restore procedure. If it did not change, leave it intact.

6. While Apache is still stopped, restore the previously saved PHP 7.4 Apache configuration and validate it. Do not start Apache until both the Laravel 8 code and PHP 7.4 Apache configuration are restored.

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -t
   if ($LASTEXITCODE -ne 0) { throw 'Restored Apache configuration test failed.' }
   & 'C:\xampp\php\php.exe' -r "exit(PHP_VERSION_ID >= 70400 -and PHP_VERSION_ID < 80000 ? 0 : 1);"
   if ($LASTEXITCODE -ne 0) { throw 'PHP 7.4 runtime verification failed.' }
   ```

7. Clear Laravel caches with PHP 7.4 while Apache remains stopped, then start Apache. Confirm the Laravel 8 maintenance response and inspect logs before any bypass smoke check.

   ```powershell
   Set-Location 'C:\xampp\htdocs\lavel-arsipin'
   & 'C:\xampp\php\php.exe' artisan optimize:clear
   if ($LASTEXITCODE -ne 0) { throw 'Laravel 8 cache clear failed.' }
   Start-Process -FilePath 'C:\xampp\apache\bin\httpd.exe' -WorkingDirectory 'C:\xampp' -WindowStyle Hidden
   Start-Sleep -Seconds 3
   Get-Process httpd -ErrorAction Stop
   Get-Content -LiteralPath 'C:\xampp\apache\logs\error.log' -Tail 100
   ```

8. Run the Laravel 8 smoke checks while maintenance remains active: application boot, login, dashboard, one read-only page per role, authorization, upload/download read path, activity log, queue configuration, and database/storage consistency. Re-check that Apache is using PHP 7.4.

9. The change approver must sign the rollback validation. Only then reopen traffic and resume background writers with their previous PHP 7.4 configuration.

   ```powershell
   & 'C:\xampp\php\php.exe' artisan up
   if ($LASTEXITCODE -ne 0) { throw 'Failed to reopen the Laravel 8 application.' }
   ```

10. Confirm external availability and continue incident monitoring. Preserve the failed Laravel 13 logs and deployment journal without including secrets.

## Post-cutover monitoring

- Monitor application, PHP, and Apache logs; authentication; authorization failures; queue failures; uploads/downloads; R2 temporary URLs; Google Drive actions; email behavior; and response latency.
- Monitor continuously through the agreed high-attention period, then at the cadence recorded in the change ticket for the remainder of the observation window.
- Compare error rate, queue depth, and latency to the preflight baseline. Any rollback trigger reopens the human decision gate.
- Keep the verified code, database, and storage backups until the agreed observation window closes and the change owner approves retention disposal through the normal backup policy.
- Record the final migration status, deployed SHA, PHP version, Apache status, smoke-check evidence, background-worker status, monitoring outcome, and approver sign-off.

## Safety invariants

- This document is a checklist, not a script. No production step runs automatically.
- Production secrets are entered only through approved interactive prompts or the existing secret store; they are never command-line values or captured in evidence.
- There is exactly one schema migration execution command in this runbook, and it requires a second human approval immediately before execution.
- Database-name equality is checked from Laravel's effective configuration before migration.
- Destructive reset, database-drop, recursive-delete, and reverse-migration commands are prohibited.
- Apache stays stopped whenever application code and Apache PHP configuration do not belong to the same release.
- Database and storage restores are conditional, separately approved, and performed only by their responsible operators.
- `.env`, `.tools`, `.superpowers`, `docs`, tests, staging artifacts, caches, sessions, logs, and credentials are not deployed.
