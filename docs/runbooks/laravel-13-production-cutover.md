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
| Approved application host | |
| Approved database endpoint host | |
| Approved database endpoint port | |
| Approved production database name | |
| Approved database server hostname | |
| Approved database TLS mode/options | |
| Previous release SHA/archive | |
| Encrypted backup directory | |
| Backup encryption evidence | |
| Approved backup ACL owner/writer | |
| Upstream traffic/write-block procedure | |
| Cutover start time | |

The deployment operator owns the cutover journal. Record the result and timestamp of every numbered step. A failed check means **stop**, keep traffic closed, and ask the approver whether to correct the issue or start rollback.

## Approval gate

- [ ] The change owner and production approver have approved the maintenance window and this exact commit.
- [ ] Branch `codex/upgrade-laravel13` has passed the staging verification record, including 30 of 30 migrations, 32 tests, cache builds, Composer audit, and the production frontend build.
- [ ] The release artifact checksum matches the artifact built from the approved commit.
- [ ] A tested PHP 8.5 Apache module/configuration set is available but is not active.
- [ ] The current PHP 7.4 Apache configuration has a separately identifiable rollback copy.
- [ ] The operator has a tested Apache stop/start procedure and console access if Apache fails to restart.
- [ ] The pre-provisioned database and storage backup destination is encrypted at rest, outside `C:\xampp\htdocs`, restricted to the approved backup owner/service account, and has enough free space. Encryption keys/passphrases remain in the approved key store and are never command arguments.
- [ ] The database operator has confirmed the approved non-secret endpoint host, endpoint port, database name, server hostname, and TLS options out of band. Do not obtain or record a password in this document, a ticket comment, shell history, or a command argument.
- [ ] The operator has a pre-tested upstream traffic/write-block procedure that blocks public requests, webhooks, and other inbound writers while still permitting an approved local health-check path.
- [ ] Queue workers, scheduled commands, webhooks, and other background writers have an approved pause/resume procedure.
- [ ] Rollback owner, rollback decision deadline, and observation window are recorded.

Do not continue without every item above and explicit human approval. The commands below never substitute for approval.

## Read-only preflight

Run from the current production release before changing code, runtime, configuration, database, or storage.

1. Set only non-secret working values. The database identity values must be copied from the approved change record; they will be independently derived from Laravel configuration and verified against the live server before backup and migration.

   ```powershell
   $ReleaseRoot = 'C:\xampp\htdocs\lavel-arsipin'
   $WebRoot = 'C:\xampp\htdocs'
   $BackupRoot = 'D:\arsipin-backups\laravel13-cutover'
   $ReviewedReleaseRoot = [IO.Path]::GetFullPath((Read-Host 'Path to the extracted, checksummed Laravel 13 release artifact')).TrimEnd('\')
   $ApprovedCommit = Read-Host 'Approved Laravel 13 commit SHA'
   $ApprovedApplicationHost = Read-Host 'Approved production HTTP Host header'
   $ApprovedDatabaseHost = Read-Host 'Approved database endpoint host'
   $ApprovedDatabasePort = Read-Host 'Approved database endpoint port'
   $ApprovedDatabaseName = Read-Host 'Approved production database name'
   $ApprovedDatabaseServerHostname = Read-Host 'Approved database server hostname returned by @@hostname'
   $ApprovedDatabaseTlsMode = Read-Host 'Approved database TLS mode: verify-ca or approved-no-tls'
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
   if ([string]::IsNullOrWhiteSpace($ApprovedDatabaseHost) -or $ApprovedDatabasePort -notmatch '^\d{1,5}$') {
       throw 'Approved database host and numeric port are required.'
   }
   if ([int]$ApprovedDatabasePort -lt 1 -or [int]$ApprovedDatabasePort -gt 65535) {
       throw 'Approved database port is outside the valid range.'
   }
   if ([string]::IsNullOrWhiteSpace($ApprovedDatabaseServerHostname)) {
       throw 'Approved database server hostname is required.'
   }
   if ($ApprovedDatabaseTlsMode -eq 'verify-ca') {
       $ApprovedDatabaseCaPath = [IO.Path]::GetFullPath((Read-Host 'Approved database CA certificate path'))
       if (!(Test-Path -LiteralPath $ApprovedDatabaseCaPath -PathType Leaf)) {
           throw 'Approved database CA certificate does not exist.'
       }
       $ApprovedDatabaseTlsArguments = @('--ssl', "--ssl-ca=$ApprovedDatabaseCaPath", '--ssl-verify-server-cert')
   } elseif ($ApprovedDatabaseTlsMode -eq 'approved-no-tls') {
       $ApprovedDatabaseTlsArguments = @()
   } else {
       throw 'Database TLS mode must exactly match the approved change record.'
   }
   if (!(Test-Path -LiteralPath (Join-Path $ReviewedReleaseRoot 'composer.lock'))) {
       throw 'Reviewed release artifact does not contain composer.lock.'
   }
   if ($ReviewedReleaseRoot -eq $releaseFullPath) {
       throw 'Read-only artifact checks must not use the active production release directory.'
   }
   Set-Location $releaseFullPath
   ```

2. Derive the current database endpoint from Laravel 8's effective configuration and require exact equality with the approved endpoint before using any database client. Record the current versions, migration status, release SHA, and live database identity. These commands are read-only.

   ```powershell
   $PreflightDatabaseUser = Read-Host 'Database user'
   & 'C:\xampp\php\php.exe' artisan --version
   & 'C:\xampp\php\php.exe' -v
   & 'C:\xampp\php\php.exe' artisan migrate:status
   git rev-parse HEAD
   $CurrentDatabaseHost = (& 'C:\xampp\php\php.exe' artisan tinker --execute="echo config('database.connections.'.config('database.default').'.host');").Trim()
   $CurrentDatabasePort = (& 'C:\xampp\php\php.exe' artisan tinker --execute="echo config('database.connections.'.config('database.default').'.port');").Trim()
   $CurrentDatabaseName = (& 'C:\xampp\php\php.exe' artisan tinker --execute="echo config('database.connections.'.config('database.default').'.database');").Trim()
   "Current database endpoint: ${CurrentDatabaseHost}:${CurrentDatabasePort}/$CurrentDatabaseName"
   if ($CurrentDatabaseHost -ne $ApprovedDatabaseHost -or $CurrentDatabasePort -ne $ApprovedDatabasePort -or $CurrentDatabaseName -ne $ApprovedDatabaseName) {
       throw 'Current Laravel 8 database endpoint does not match the approved production identity.'
   }
   $LiveDatabaseIdentity = & 'C:\xampp\mysql\bin\mysql.exe' @ApprovedDatabaseTlsArguments --host=$ApprovedDatabaseHost --port=$ApprovedDatabasePort --database=$ApprovedDatabaseName --user=$PreflightDatabaseUser --password --batch --skip-column-names --execute="SELECT DATABASE(), @@hostname, @@port, VERSION(), (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'), (SELECT COUNT(*) FROM migrations);"
   if ($LASTEXITCODE -ne 0) { throw 'Live database identity query failed.' }
   $LiveDatabaseFields = @($LiveDatabaseIdentity -split "`t")
   if ($LiveDatabaseFields.Count -ne 6) { throw 'Unexpected live database identity response.' }
   $LiveDatabaseName = $LiveDatabaseFields[0]
   $LiveDatabaseServerHostname = $LiveDatabaseFields[1]
   $LiveDatabasePort = $LiveDatabaseFields[2]
   $LiveDatabaseVersion = $LiveDatabaseFields[3]
   $ExpectedBaseTableCount = [int]$LiveDatabaseFields[4]
   $ExpectedMigrationRowCount = [int]$LiveDatabaseFields[5]
   "Live database identity: ${ApprovedDatabaseHost}:${LiveDatabasePort}/$LiveDatabaseName on server $LiveDatabaseServerHostname; version $LiveDatabaseVersion"
   if ($LiveDatabaseName -ne $ApprovedDatabaseName -or $LiveDatabasePort -ne $ApprovedDatabasePort -or $LiveDatabaseServerHostname -ne $ApprovedDatabaseServerHostname) {
       throw 'Live database identity does not match the approved database/server identity.'
   }
   ```

   The database password must be entered only at the interactive prompt. The database operator must approve the mapping from Laravel's configured endpoint host to the live `@@hostname`, and sign the recorded `SELECT DATABASE(), @@hostname, @@port` result. Stop if the current runtime is not the expected PHP 7.4/Laravel 8 release, MariaDB is older than 10.3, or any identity field is unexpected.

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
   & 'C:\xampp\mysql\bin\mysql.exe' @ApprovedDatabaseTlsArguments --host=$ApprovedDatabaseHost --port=$ApprovedDatabasePort --database=$ApprovedDatabaseName --user=$DatabaseUser --password --execute="SELECT role, COUNT(*) AS total FROM users GROUP BY role; SELECT status_dokumen, COUNT(*) AS total FROM dokumen GROUP BY status_dokumen; SELECT risiko, status_dokumentasi, status_operasional, COUNT(*) AS total FROM alur_kerja GROUP BY risiko, status_dokumentasi, status_operasional; SELECT status, COUNT(*) AS total FROM sop_pengetahuan GROUP BY status; SELECT status, COUNT(*) AS total FROM jobdescs GROUP BY status;"
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

Database and storage snapshots must share a write-frozen recovery point. The upstream block, Laravel maintenance mode, and paused background writers remain active from step 1 until either the cutover traffic-open gate or a completed rollback. This is mandatory; do not take a live-write snapshot and do not offer reconciliation as a substitute.

1. Activate the pre-tested upstream traffic/write block before either snapshot. It must block public traffic, webhooks, health-check writers, and every other inbound path while retaining only the approved local operator health path. Do not enable an authenticated maintenance bypass until both snapshots and validation are complete. An independent verifier must confirm blocked read and write canaries from outside the host and correlate them with upstream access logs. Record the block rule/version, verifier, time, and result. A failed or unverifiable block stops the change.

2. With the upstream block active, enter Laravel maintenance mode using PHP 7.4, pause queue workers/schedulers/integrations, and verify there are no remaining application writers. Confirm that an unauthenticated external request is blocked upstream and the approved local path returns HTTP 503. The change approver must sign the write-freeze gate before any snapshot begins.

   ```powershell
   Set-Location 'C:\xampp\htdocs\lavel-arsipin'
   & 'C:\xampp\php\php.exe' artisan down --render='errors::503'
   if ($LASTEXITCODE -ne 0) { throw 'Failed to enter Laravel 8 maintenance mode.' }
   if (!(Test-Path -LiteralPath (Join-Path $ReleaseRoot 'storage\framework\down'))) {
       throw 'Laravel 8 maintenance marker is missing.'
   }
   $PreBackupMaintenanceStatus = & curl.exe --silent --output NUL --write-out '%{http_code}' --header "Host: $ApprovedApplicationHost" 'http://127.0.0.1/'
   if ($LASTEXITCODE -ne 0 -or $PreBackupMaintenanceStatus -ne '503') {
       throw "Expected local Laravel 8 maintenance status 503; received $PreBackupMaintenanceStatus."
   }
   ```

3. Validate the pre-provisioned encrypted backup root and its ACL **before** creating a recovery-point directory or artifact. The encryption control must already be active and independently approved; never provide an encryption key or passphrase on the command line. The ACL owner and writer/service account must match the approved record, and broad principals must not have write access.

   ```powershell
   $BackupEncryptionEvidence = Read-Host 'Approved encryption-at-rest evidence/change ID'
   $ApprovedBackupOwner = Read-Host 'Approved backup directory ACL owner'
   $ApprovedBackupWriter = Read-Host 'Approved backup writer/service account'
   if ([string]::IsNullOrWhiteSpace($BackupEncryptionEvidence)) {
       throw 'Approved encryption-at-rest evidence is required.'
   }
   if (!(Test-Path -LiteralPath $BackupRoot -PathType Container)) {
       throw 'Encrypted backup root must be pre-provisioned before this runbook.'
   }

   function Assert-RestrictedBackupAcl {
       param([string]$Path, [string]$ExpectedOwner, [string]$ExpectedWriter)
       $Acl = Get-Acl -LiteralPath $Path
       $Acl | Format-List Path, Owner, AccessToString
       if ($Acl.Owner -ne $ExpectedOwner) { throw "Unexpected ACL owner on $Path." }
       $WriteMask = [Security.AccessControl.FileSystemRights]::Write -bor [Security.AccessControl.FileSystemRights]::Modify -bor [Security.AccessControl.FileSystemRights]::FullControl
       $BroadSids = @('S-1-1-0', 'S-1-5-11', 'S-1-5-32-545')
       $BroadWritableRules = @($Acl.Access | Where-Object {
           try { $Sid = $_.IdentityReference.Translate([Security.Principal.SecurityIdentifier]).Value } catch { $Sid = $_.IdentityReference.Value }
           $_.AccessControlType -eq [Security.AccessControl.AccessControlType]::Allow -and ($_.FileSystemRights -band $WriteMask) -and $Sid -in $BroadSids
       })
       if ($BroadWritableRules.Count -gt 0) { throw "Broad principal has write access on $Path." }
       $WriterRules = @($Acl.Access | Where-Object {
           $_.IdentityReference.Value -eq $ExpectedWriter -and $_.AccessControlType -eq [Security.AccessControl.AccessControlType]::Allow -and ($_.FileSystemRights -band $WriteMask)
       })
       if ($WriterRules.Count -eq 0) { throw "Approved backup writer lacks explicit/inherited write access on $Path." }
   }

   Assert-RestrictedBackupAcl -Path $BackupRoot -ExpectedOwner $ApprovedBackupOwner -ExpectedWriter $ApprovedBackupWriter
   $RecoveryPointRoot = Join-Path $BackupRoot $Stamp
   New-Item -ItemType Directory -Path $RecoveryPointRoot | Out-Null
   Assert-RestrictedBackupAcl -Path $RecoveryPointRoot -ExpectedOwner $ApprovedBackupOwner -ExpectedWriter $ApprovedBackupWriter
   ```

4. Create a code archive in the restricted recovery-point directory. The archive deliberately excludes secrets, dependencies, staging/worktree material, user files, caches, logs, and sessions. Production `.env` remains managed by the existing secret-management procedure and must never be added to the release artifact.

   ```powershell
   $CodeArchive = Join-Path $RecoveryPointRoot "arsipin-code-$Stamp.tar.gz"
   tar.exe -czf $CodeArchive --exclude='.git' --exclude='.worktrees' --exclude='.env' --exclude='.tools' --exclude='vendor' --exclude='node_modules' --exclude='docs' --exclude='tests' --exclude='storage/app' --exclude='storage/framework/cache' --exclude='storage/framework/sessions' --exclude='storage/framework/views' --exclude='storage/logs' --exclude='bootstrap/cache/*.php' -C $ReleaseRoot .
   if ($LASTEXITCODE -ne 0 -or !(Test-Path -LiteralPath $CodeArchive)) {
       throw 'Code archive failed.'
   }
   ```

5. Reconfirm and record the upstream block/write-freeze immediately before the database snapshot. Create the database dump with the same approved host, port, database, and TLS arguments used for identity checks. The password is entered only at the interactive prompt.

   ```powershell
   $DatabaseDump = Join-Path $RecoveryPointRoot "arsipin-database-$Stamp.sql"
   $RecoveryPointIdentity = & 'C:\xampp\mysql\bin\mysql.exe' @ApprovedDatabaseTlsArguments --host=$ApprovedDatabaseHost --port=$ApprovedDatabasePort --database=$ApprovedDatabaseName --user=$DatabaseUser --password --batch --skip-column-names --execute="SELECT DATABASE(), @@hostname, @@port, (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'), (SELECT COUNT(*) FROM migrations);"
   if ($LASTEXITCODE -ne 0) { throw 'Recovery-point database identity query failed.' }
   $RecoveryPointFields = @($RecoveryPointIdentity -split "`t")
   if ($RecoveryPointFields.Count -ne 5 -or $RecoveryPointFields[0] -ne $ApprovedDatabaseName -or $RecoveryPointFields[1] -ne $ApprovedDatabaseServerHostname -or $RecoveryPointFields[2] -ne $ApprovedDatabasePort) {
       throw 'Recovery-point database identity does not match the approved production identity.'
   }
   $ExpectedBaseTableCount = [int]$RecoveryPointFields[3]
   $ExpectedMigrationRowCount = [int]$RecoveryPointFields[4]
   & 'C:\xampp\mysql\bin\mysqldump.exe' @ApprovedDatabaseTlsArguments --host=$ApprovedDatabaseHost --port=$ApprovedDatabasePort --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 --user=$DatabaseUser --password --result-file=$DatabaseDump $ApprovedDatabaseName
   if ($LASTEXITCODE -ne 0 -or !(Test-Path -LiteralPath $DatabaseDump) -or (Get-Item -LiteralPath $DatabaseDump).Length -eq 0) {
       throw 'Database dump failed or is empty.'
   }
   ```

6. Reconfirm and record the same upstream block/write-freeze immediately before archiving `storage/app`.

   ```powershell
   $StorageArchive = Join-Path $RecoveryPointRoot "arsipin-storage-app-$Stamp.tar.gz"
   tar.exe -czf $StorageArchive -C (Join-Path $ReleaseRoot 'storage') app
   if ($LASTEXITCODE -ne 0 -or !(Test-Path -LiteralPath $StorageArchive)) {
       throw 'Storage archive failed.'
   }
   ```

7. Fully exhaust both archive listings into protected evidence files before sampling any entries. This verifies that the archive reader reaches the end of each archive rather than stopping after the first few names. Require non-empty listings, then review samples separately. Confirm the SQL contains table definitions and a final transaction commit, and record SHA-256 hashes.

   ```powershell
   $CodeListing = Join-Path $RecoveryPointRoot "arsipin-code-$Stamp.list.txt"
   $StorageListing = Join-Path $RecoveryPointRoot "arsipin-storage-app-$Stamp.list.txt"
   tar.exe -tzf $CodeArchive | Set-Content -LiteralPath $CodeListing
   if ($LASTEXITCODE -ne 0 -or (Get-Content -LiteralPath $CodeListing | Measure-Object).Count -eq 0) {
       throw 'Full code archive listing failed or is empty.'
   }
   tar.exe -tzf $StorageArchive | Set-Content -LiteralPath $StorageListing
   if ($LASTEXITCODE -ne 0 -or (Get-Content -LiteralPath $StorageListing | Measure-Object).Count -eq 0) {
       throw 'Full storage archive listing failed or is empty.'
   }
   Get-Content -LiteralPath $CodeListing | Select-Object -First 20
   Get-Content -LiteralPath $StorageListing | Select-Object -First 20
   if (!(Select-String -LiteralPath $DatabaseDump -Pattern '^CREATE TABLE ' -Quiet)) {
       throw 'Database dump contains no table definition.'
   }
   if (!(Get-Content -LiteralPath $DatabaseDump -Tail 100 | Select-String -Pattern '^COMMIT;' -Quiet)) {
       throw 'Database dump has no final COMMIT marker.'
   }
   Get-FileHash -Algorithm SHA256 $CodeArchive, $DatabaseDump, $StorageArchive, $CodeListing, $StorageListing
   ```

8. The database operator must restore **this current dump** into a pre-provisioned, isolated non-production validation database before cutover. A previous restore test is not sufficient. The DBA's approved restore invocation must explicitly pass the validation `--host`, `--port`, `--database`, approved TLS arguments, and an interactive credential; the modifying restore command is intentionally not included here. For `mysqldump`, whose client does not support a singular `--database=<name>` option, the same approved database name is passed as its required final positional argument. Do not provision or clean up the validation database with an executable command from this runbook.

   Record the validation host, port, database, server hostname, TLS mode/options, and restore evidence. The validation database name must end in `_validation`, must not equal the production database name, and must be confirmed as non-production by the DBA. Build `$ApprovedValidationTlsArguments` from its approved record using the same `verify-ca` or separately approved no-TLS policy used above. Then run this read-only identity/schema check:

   ```powershell
   $ValidationDatabaseHost = Read-Host 'Approved validation database endpoint host'
   $ValidationDatabasePort = Read-Host 'Approved validation database endpoint port'
   $ValidationDatabaseName = Read-Host 'Approved validation database name ending in _validation'
   $ValidationDatabaseServerHostname = Read-Host 'Approved validation server hostname returned by @@hostname'
   $ValidationDatabaseTlsMode = Read-Host 'Approved validation TLS mode: verify-ca or approved-no-tls'
   $ValidationDatabaseUser = Read-Host 'Validation database user'
   if ($ValidationDatabasePort -notmatch '^\d{1,5}$' -or [int]$ValidationDatabasePort -lt 1 -or [int]$ValidationDatabasePort -gt 65535) {
       throw 'Validation database port is invalid.'
   }
   if ($ValidationDatabaseName -notmatch '_validation$' -or $ValidationDatabaseName -eq $ApprovedDatabaseName) {
       throw 'Validation database identity is not safely isolated from production.'
   }
   if ($ValidationDatabaseTlsMode -eq 'verify-ca') {
       $ValidationDatabaseCaPath = [IO.Path]::GetFullPath((Read-Host 'Approved validation CA certificate path'))
       if (!(Test-Path -LiteralPath $ValidationDatabaseCaPath -PathType Leaf)) {
           throw 'Approved validation CA certificate does not exist.'
       }
       $ApprovedValidationTlsArguments = @('--ssl', "--ssl-ca=$ValidationDatabaseCaPath", '--ssl-verify-server-cert')
   } elseif ($ValidationDatabaseTlsMode -eq 'approved-no-tls') {
       $ApprovedValidationTlsArguments = @()
   } else {
       throw 'Validation database TLS mode must exactly match its approved record.'
   }
   $ValidationIdentity = & 'C:\xampp\mysql\bin\mysql.exe' @ApprovedValidationTlsArguments --host=$ValidationDatabaseHost --port=$ValidationDatabasePort --database=$ValidationDatabaseName --user=$ValidationDatabaseUser --password --batch --skip-column-names --execute="SELECT DATABASE(), @@hostname, @@port, (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'), (SELECT COUNT(*) FROM migrations);"
   if ($LASTEXITCODE -ne 0) { throw 'Validation database query failed after restore.' }
   $ValidationFields = @($ValidationIdentity -split "`t")
   if ($ValidationFields.Count -ne 5) { throw 'Unexpected validation database response.' }
   if ($ValidationFields[0] -ne $ValidationDatabaseName -or $ValidationFields[1] -ne $ValidationDatabaseServerHostname -or $ValidationFields[2] -ne $ValidationDatabasePort) {
       throw 'Live validation database identity does not match its approved record.'
   }
   if ([int]$ValidationFields[3] -ne $ExpectedBaseTableCount -or [int]$ValidationFields[4] -ne $ExpectedMigrationRowCount) {
       throw 'Restored validation schema/table or migration-row count does not match the production recovery point.'
   }
   ```

9. Record the final backup paths, sizes, hashes, exhaustive-listing result, validation-restore result, identity/count comparison, and recovery-point timestamp. The database operator and approver must sign the backup gate before cutover. Keep the upstream block, Laravel maintenance mode, and all writer pauses active.

## Cutover

The site is intentionally unavailable during this sequence. The approved upstream traffic/write block, Laravel maintenance mode, and writer pauses established before backup remain active. Keep a second terminal open for logs. Run one step at a time, record the result, and stop at the first failure.

1. Reconfirm the upstream block and write-freeze, then stop Apache before replacing any source. Confirm no Apache process is serving requests before changing code or PHP configuration.

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -k shutdown
   Start-Sleep -Seconds 3
   Get-Process httpd -ErrorAction SilentlyContinue
   ```

   If an `httpd` process remains, stop and escalate; do not force-kill it without separate approval.

2. Deploy only the reviewed Laravel 13 application source, `composer.json`, `composer.lock`, and already-reviewed production frontend assets from the checksummed artifact. Preserve the production `.env` and persistent `storage/app`. Do **not** deploy `.env`, `.tools`, `.superpowers`, `docs`, tests, staging verification artifacts, caches, sessions, logs, credentials, or a worktree. The release mechanism must write a non-secret `.release-sha` metadata file from the artifact manifest. Verify it equals `$ApprovedCommit`; a mismatch requires rollback before Apache is restarted.

   ```powershell
   Set-Location 'C:\xampp\htdocs\lavel-arsipin'
   $DeployedCommit = (Get-Content -LiteralPath (Join-Path $ReleaseRoot '.release-sha') -Raw).Trim()
   if ($DeployedCommit -ne $ApprovedCommit) {
       throw "Deployed commit $DeployedCommit does not match approved commit $ApprovedCommit."
   }
   ```

3. Install locked production dependencies with PHP 8.5 and the host Composer PHAR. The staging `.tools` Composer copy is not a deployable artifact.

   ```powershell
   & 'C:\xampp\php85\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   if ($LASTEXITCODE -ne 0) { throw 'Composer production install failed.' }
   ```

4. Source replacement may replace or invalidate the Laravel 8 maintenance marker. While Apache remains stopped and after Laravel 13 dependencies exist, recreate maintenance mode with PHP 8.5 and confirm its marker. Do not start Apache if this check fails.

   ```powershell
   & 'C:\xampp\php85\php.exe' artisan down --render='errors::503'
   if ($LASTEXITCODE -ne 0) { throw 'Failed to recreate maintenance mode with PHP 8.5.' }
   if (!(Test-Path -LiteralPath (Join-Path $ReleaseRoot 'storage\framework\down'))) {
       throw 'Laravel 13 maintenance marker is missing.'
   }
   ```

5. While Apache remains stopped, activate the pre-reviewed PHP 8.5 Apache configuration using the host's approved configuration-switch procedure. Do not edit module paths ad hoc. Validate Apache syntax and the required PHP 8.5 CLI extensions; keep Apache stopped after validation.

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -t
   if ($LASTEXITCODE -ne 0) { throw 'Apache configuration test failed.' }
   & 'C:\xampp\php85\php.exe' -r "foreach (['curl','fileinfo','mbstring','openssl','pdo_mysql','pdo_sqlite','sqlite3','zip'] as `$extension) { if (!extension_loaded(`$extension)) { fwrite(STDERR, `$extension.PHP_EOL); exit(1); } }"
   if ($LASTEXITCODE -ne 0) { throw 'A required PHP 8.5 extension is missing.' }
   ```

6. Clear stale Laravel caches with PHP 8.5, then derive the effective environment and full database endpoint from Laravel 13. Require exact equality with the same approved host, port, and database used by every database client. Query and approve the live identity again before migration. These checks expose neither passwords nor connection tokens.

   ```powershell
   & 'C:\xampp\php85\php.exe' artisan optimize:clear
   if ($LASTEXITCODE -ne 0) { throw 'Laravel cache clear failed.' }
   $ActiveEnvironment = (& 'C:\xampp\php85\php.exe' artisan tinker --execute="echo app()->environment();").Trim()
   $ActiveDatabaseHost = (& 'C:\xampp\php85\php.exe' artisan tinker --execute="echo config('database.connections.'.config('database.default').'.host');").Trim()
   $ActiveDatabasePort = (& 'C:\xampp\php85\php.exe' artisan tinker --execute="echo config('database.connections.'.config('database.default').'.port');").Trim()
   $ActiveDatabaseName = (& 'C:\xampp\php85\php.exe' artisan tinker --execute="echo config('database.connections.'.config('database.default').'.database');").Trim()
   $ActiveDebug = (& 'C:\xampp\php85\php.exe' artisan tinker --execute="echo config('app.debug') ? 'true' : 'false';").Trim()
   "Environment: $ActiveEnvironment"
   "Database endpoint: ${ActiveDatabaseHost}:${ActiveDatabasePort}/$ActiveDatabaseName"
   "Debug: $ActiveDebug"
   if ($ActiveEnvironment -ne 'production') { throw 'APP_ENV is not production.' }
   if ($ActiveDatabaseHost -ne $ApprovedDatabaseHost -or $ActiveDatabasePort -ne $ApprovedDatabasePort -or $ActiveDatabaseName -ne $ApprovedDatabaseName) {
       throw 'Laravel 13 database endpoint does not match the approved production identity.'
   }
   if ($ActiveDebug -ne 'false') { throw 'APP_DEBUG must be false.' }
   $CutoverDatabaseUser = Read-Host 'Database user for final identity check'
   $CutoverDatabaseIdentity = & 'C:\xampp\mysql\bin\mysql.exe' @ApprovedDatabaseTlsArguments --host=$ApprovedDatabaseHost --port=$ApprovedDatabasePort --database=$ApprovedDatabaseName --user=$CutoverDatabaseUser --password --batch --skip-column-names --execute='SELECT DATABASE(), @@hostname, @@port;'
   if ($LASTEXITCODE -ne 0) { throw 'Final live database identity query failed.' }
   $CutoverDatabaseFields = @($CutoverDatabaseIdentity -split "`t")
   if ($CutoverDatabaseFields.Count -ne 3 -or $CutoverDatabaseFields[0] -ne $ApprovedDatabaseName -or $CutoverDatabaseFields[1] -ne $ApprovedDatabaseServerHostname -or $CutoverDatabaseFields[2] -ne $ApprovedDatabasePort) {
       throw 'Final live database identity does not match the approved database/server identity.'
   }
   & 'C:\xampp\php85\php.exe' artisan migrate:status
   ```

   Confirm that this definitive PHP 8.5 status lists all 30 reviewed migration files and that its pending set exactly matches the database operator's approved list.

7. **Second human approval gate:** the deployment operator and database operator must compare Laravel's host/port/name and live `SELECT DATABASE(), @@hostname, @@port` result to the approved identity, review the definitive PHP 8.5 migration status, reconfirm the upstream block/write-freeze, and explicitly authorize the single migration command below. Record the values, both approver names, and approval time. Do not continue on silence, assumption, status mismatch, write-freeze failure, or identity mismatch.

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

10. Reconfirm the upstream block is active, then start Apache under the pre-reviewed PHP 8.5 configuration **behind that block**. Confirm it starts cleanly and the approved local host path returns HTTP 503 from Laravel maintenance mode. Check the Apache and PHP logs for module-load errors before functional checks. Do not continue if the local response is not 503 or any public request reaches Apache.

    ```powershell
    Start-Process -FilePath 'C:\xampp\apache\bin\httpd.exe' -WorkingDirectory 'C:\xampp' -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Get-Process httpd -ErrorAction Stop
    Get-Content -LiteralPath 'C:\xampp\apache\logs\error.log' -Tail 100
    $MaintenanceStatus = & curl.exe --silent --output NUL --write-out '%{http_code}' --header "Host: $ApprovedApplicationHost" 'http://127.0.0.1/'
    if ($LASTEXITCODE -ne 0 -or $MaintenanceStatus -ne '503') {
        throw "Expected local Laravel maintenance status 503; received $MaintenanceStatus."
    }
    ```

11. While maintenance mode remains active, perform health checks through a pre-tested maintenance bypass provisioned by the approved secret-management procedure. Do not put a bypass secret on the command line or in the journal. Verify application boot, login, dashboard, one read-only page for each role, authorization boundaries, one pre-approved safe upload/download path, activity logging, and no unexpected external R2/Google Drive/email side effect. Record test accounts and objects by identifier only; do not record credentials. If no safe bypass exists, stop: do not remove maintenance mode merely to run tests while public traffic can reach Apache.

12. Compare error rate and response latency with the preflight baseline. Confirm queue workers and scheduled commands remain paused until their PHP 8.5 command paths/configuration are separately verified.

13. Keep the upstream block active. After all bypass health checks pass, obtain approval to leave Laravel maintenance mode for a local-only final health check, run `artisan up`, and verify the approved local path returns an expected healthy status. Public traffic must still be blocked.

    ```powershell
    & 'C:\xampp\php85\php.exe' artisan up
    if ($LASTEXITCODE -ne 0) { throw 'Failed to leave maintenance mode.' }
    $HealthyStatus = & curl.exe --silent --output NUL --write-out '%{http_code}' --header "Host: $ApprovedApplicationHost" 'http://127.0.0.1/'
    if ($LASTEXITCODE -ne 0 -or $HealthyStatus -notin @('200', '302')) {
        throw "Expected local healthy status 200 or 302; received $HealthyStatus."
    }
    ```

14. **Traffic-open approval gate:** only after the local check passes may the change approver authorize removal of the upstream traffic/write block. Remove it using the approved procedure, verify a normal external request, and record the exact unblock time. If the block cannot be removed cleanly, keep writers paused and enter the rollback decision gate.

15. Resume queue workers, scheduled commands, webhooks, and other background writers with their approved PHP 8.5 configuration. Verify one safe job and record its outcome.

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

Rollback must restore a coherent Laravel 8/PHP 7.4 state. Apache must never serve Laravel 8 using the PHP 8.5 Apache configuration during the transition. The upstream traffic/write block and all writer pauses remain active throughout rollback.

1. Activate or reconfirm the approved upstream traffic/write block first. If the Laravel 13 application can boot, re-enter maintenance mode with PHP 8.5; then stop Apache and all queue/scheduler/background writers. If the application cannot boot, leave Apache stopped and use the upstream maintenance response.

   ```powershell
   & 'C:\xampp\php85\php.exe' artisan down --render='errors::503'
   if ($LASTEXITCODE -ne 0) { Write-Warning 'Laravel maintenance command failed; upstream block must remain active.' }
   & 'C:\xampp\apache\bin\httpd.exe' -k shutdown
   Start-Sleep -Seconds 3
   Get-Process httpd -ErrorAction SilentlyContinue
   ```

   Do not continue while an application process can write to the database or `storage/app`.

2. Record the last completed cutover step, migration output, migration status, and whether any post-cutover write occurred. Classify database and storage independently:

   - `Database changed = No` only when the migration command was never started and no Laravel 13 process wrote data.
   - `Database changed = Yes/Unknown` when the migration command started, failed after starting, migration status changed, or Laravel 13 handled any write.
   - `Storage changed = Yes/Unknown` when an upload, integration, job, or operator action could have modified `storage/app`.

3. While Apache remains stopped, restore the previous Laravel 8 code release and its exact `composer.lock` from the verified archive/release mechanism. Preserve the production `.env` and persistent storage. Do not restore caches, sessions, logs, `.tools`, tests, docs, staging artifacts, or credentials from the Laravel 13 artifact. Reinstall the locked Laravel 8 production dependencies, recreate Laravel 8 maintenance mode, and confirm its marker with PHP 7.4 before Apache can restart.

   ```powershell
   Set-Location 'C:\xampp\htdocs\lavel-arsipin'
   & 'C:\xampp\php\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   if ($LASTEXITCODE -ne 0) { throw 'Laravel 8 dependency restore failed.' }
   & 'C:\xampp\php\php.exe' artisan down --render='errors::503'
   if ($LASTEXITCODE -ne 0 -or !(Test-Path -LiteralPath (Join-Path $ReleaseRoot 'storage\framework\down'))) {
       throw 'Laravel 8 maintenance mode could not be recreated.'
   }
   ```

4. Restore the database dump **only** when `Database changed = Yes/Unknown`. If `Database changed = No`, leave the database intact. Database restoration is a separately approved DBA operation: verify the dump hash, keep all writers stopped, preserve and reconcile any legitimate transactions after the recovery point, and use the organization's tested restore procedure. Immediately before authorization, run the read-only identity check below and require exact equality with the same approved endpoint host, port, database, server hostname, and TLS policy used for the dump. The DBA's approved restore invocation must explicitly pass `--host=$ApprovedDatabaseHost`, `--port=$ApprovedDatabasePort`, `--database=$ApprovedDatabaseName`, `@ApprovedDatabaseTlsArguments`, and an interactive credential. After restoration, repeat the same identity query and compare base-table and migration-row counts with `$ExpectedBaseTableCount` and `$ExpectedMigrationRowCount`. Do not improvise destructive database commands or run application migrations backward.

   ```powershell
   $RollbackDatabaseUser = Read-Host 'Database user for rollback identity check'
   $RollbackDatabaseIdentity = & 'C:\xampp\mysql\bin\mysql.exe' @ApprovedDatabaseTlsArguments --host=$ApprovedDatabaseHost --port=$ApprovedDatabasePort --database=$ApprovedDatabaseName --user=$RollbackDatabaseUser --password --batch --skip-column-names --execute="SELECT DATABASE(), @@hostname, @@port, (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'), (SELECT COUNT(*) FROM migrations);"
   if ($LASTEXITCODE -ne 0) { throw 'Rollback database identity query failed.' }
   $RollbackDatabaseFields = @($RollbackDatabaseIdentity -split "`t")
   if ($RollbackDatabaseFields.Count -ne 5 -or $RollbackDatabaseFields[0] -ne $ApprovedDatabaseName -or $RollbackDatabaseFields[1] -ne $ApprovedDatabaseServerHostname -or $RollbackDatabaseFields[2] -ne $ApprovedDatabasePort) {
       throw 'Rollback target does not match the approved production database/server identity.'
   }
   "Current rollback target counts: tables=$($RollbackDatabaseFields[3]); migration rows=$($RollbackDatabaseFields[4])"
   ```

   After the separately approved DBA restore finishes, run this read-only validation before continuing:

   ```powershell
   $RestoredDatabaseIdentity = & 'C:\xampp\mysql\bin\mysql.exe' @ApprovedDatabaseTlsArguments --host=$ApprovedDatabaseHost --port=$ApprovedDatabasePort --database=$ApprovedDatabaseName --user=$RollbackDatabaseUser --password --batch --skip-column-names --execute="SELECT DATABASE(), @@hostname, @@port, (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'), (SELECT COUNT(*) FROM migrations);"
   if ($LASTEXITCODE -ne 0) { throw 'Restored production database validation failed.' }
   $RestoredDatabaseFields = @($RestoredDatabaseIdentity -split "`t")
   if ($RestoredDatabaseFields.Count -ne 5 -or $RestoredDatabaseFields[0] -ne $ApprovedDatabaseName -or $RestoredDatabaseFields[1] -ne $ApprovedDatabaseServerHostname -or $RestoredDatabaseFields[2] -ne $ApprovedDatabasePort) {
       throw 'Restored production database identity does not match the approved identity.'
   }
   if ([int]$RestoredDatabaseFields[3] -ne $ExpectedBaseTableCount -or [int]$RestoredDatabaseFields[4] -ne $ExpectedMigrationRowCount) {
       throw 'Restored production schema/table or migration-row count does not match the recovery point.'
   }
   ```

5. Restore `storage/app` **only** when `Storage changed = Yes/Unknown`. If it changed, the change approver must decide how to retain and reconcile any legitimate files created after the backup before the storage operator uses the tested restore procedure. If it did not change, leave it intact.

6. While Apache is still stopped, restore the previously saved PHP 7.4 Apache configuration and validate it. Do not start Apache until both the Laravel 8 code and PHP 7.4 Apache configuration are restored.

   ```powershell
   & 'C:\xampp\apache\bin\httpd.exe' -t
   if ($LASTEXITCODE -ne 0) { throw 'Restored Apache configuration test failed.' }
   & 'C:\xampp\php\php.exe' -r "exit(PHP_VERSION_ID >= 70400 -and PHP_VERSION_ID < 80000 ? 0 : 1);"
   if ($LASTEXITCODE -ne 0) { throw 'PHP 7.4 runtime verification failed.' }
   ```

7. Clear Laravel caches with PHP 7.4 while Apache remains stopped, reconfirm the upstream block, then start Apache behind it. Confirm HTTP 503 on the approved local host path and inspect logs before any bypass smoke check.

   ```powershell
   Set-Location 'C:\xampp\htdocs\lavel-arsipin'
   & 'C:\xampp\php\php.exe' artisan optimize:clear
   if ($LASTEXITCODE -ne 0) { throw 'Laravel 8 cache clear failed.' }
   Start-Process -FilePath 'C:\xampp\apache\bin\httpd.exe' -WorkingDirectory 'C:\xampp' -WindowStyle Hidden
   Start-Sleep -Seconds 3
   Get-Process httpd -ErrorAction Stop
   Get-Content -LiteralPath 'C:\xampp\apache\logs\error.log' -Tail 100
   $RollbackMaintenanceStatus = & curl.exe --silent --output NUL --write-out '%{http_code}' --header "Host: $ApprovedApplicationHost" 'http://127.0.0.1/'
   if ($LASTEXITCODE -ne 0 -or $RollbackMaintenanceStatus -ne '503') {
       throw "Expected local Laravel 8 maintenance status 503; received $RollbackMaintenanceStatus."
   }
   ```

8. Run the Laravel 8 smoke checks while maintenance remains active: application boot, login, dashboard, one read-only page per role, authorization, upload/download read path, activity log, queue configuration, and database/storage consistency. Re-check that Apache is using PHP 7.4.

9. The change approver must sign the rollback validation. Keep the upstream block active, leave Laravel maintenance mode, and verify a healthy local response before public exposure.

   ```powershell
   & 'C:\xampp\php\php.exe' artisan up
   if ($LASTEXITCODE -ne 0) { throw 'Failed to reopen the Laravel 8 application.' }
   $RollbackHealthyStatus = & curl.exe --silent --output NUL --write-out '%{http_code}' --header "Host: $ApprovedApplicationHost" 'http://127.0.0.1/'
   if ($LASTEXITCODE -ne 0 -or $RollbackHealthyStatus -notin @('200', '302')) {
       throw "Expected local Laravel 8 healthy status 200 or 302; received $RollbackHealthyStatus."
   }
   ```

10. Only after the local health check passes may the change approver authorize removal of the upstream block. Remove it with the approved procedure, confirm external availability, then resume background writers with their previous PHP 7.4 configuration. Continue incident monitoring and preserve the failed Laravel 13 logs and deployment journal without including secrets.

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
- Laravel's database host, port, and name plus the live database name, server hostname, and port must match the approved identity. Every database client and DBA restore instruction uses the corresponding approved endpoint and TLS options.
- The upstream traffic/write block, Laravel maintenance mode, and background-writer pause start before database/storage snapshots and remain active through cutover or rollback validation.
- The backup root is pre-provisioned, encrypted, outside the web root, and ACL-restricted before any backup artifact is created; encryption material is never placed on the command line.
- Archive checks fully read each listing before sampling, and the current dump must pass an isolated validation restore with matching schema/table counts before cutover.
- Destructive reset, database-drop, recursive-delete, and reverse-migration commands are prohibited.
- Apache stays stopped whenever application code and Apache PHP configuration do not belong to the same release.
- Database and storage restores are conditional, separately approved, and performed only by their responsible operators.
- `.env`, `.tools`, `.superpowers`, `docs`, tests, staging artifacts, caches, sessions, logs, and credentials are not deployed.
