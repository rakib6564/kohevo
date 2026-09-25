# Backups

**Status:** As built  
**Verified version:** `1.0.0`  
**Owner:** `plugins/backups`

## Purpose

Backups creates resumable archives containing a PHP-generated database dump and the complete `uploads/` tree, uploads the archive to Google Drive, and prunes older files in the configured Drive folder. It is a **super-admin-only** operational module.

## Surface and configuration

The module has no public or customer route. Super administrators use `admin/settings.php` to enable backups, connect or disconnect Google Drive, set retention, inspect the last 20 tenant-scoped runs, and start a manual run. OAuth returns through `admin/gdrive-callback.php` with a session nonce/state check.

Settings use the `backups.*` namespace. Secrets are encrypted with the application secret. The Drive scope is `drive.file`, and the module creates or reuses a Kohevo Backups folder.

## Lifecycle

`daily_cron` starts at most one pending run when backups are enabled and Drive is connected. `frequent_cron` advances the oldest active run within an approximately 18-second soft budget. States are `pending`, `dumping`, `zipping`, `uploading`, `pruning`, `done`, and `failed`. Database rows are processed in batches, and Drive uploads use resumable chunks.

A staging directory under `db_backups/run-<id>/` contains `dump.sql` and `backup.zip`. Successful completion removes staging before retention pruning. Caught failures mark the run failed, log the error, remove staging on a best-effort basis, and write an audit event. There is no one-click restore route; the operational restore path is documented in [`docs/13-Operations/backups.md`](../13-Operations/backups.md) and `bin/restore-backup.php`.

## Data and permissions

The module owns `backups_runs`, which stores tenant, state, progress JSON, local path, Drive identifiers, size, error, and timestamps. Settings store Google credentials, tokens, folder ID, enablement, and retention. The dump enumerates every base table in the active database, so a run record is tenant-scoped while the database archive is effectively whole-database.

The manifest declares `backups.manage`. Runtime checks require authentication and `Auth::isSuperAdmin()` for navigation and mutations. All settings POST actions use CSRF verification.

## Security and operational limits

The database dump and uploads may contain customer PII. Drive credentials and tokens are encrypted, but backup archives require the same protection as the live database. There is no transactionally consistent database snapshot; writes during a multi-tick dump can produce a logically mixed archive. There is no explicit size cap, retry/backoff policy, or automatic stale-session recovery.

Retention scans non-trashed files in the cached Drive folder. Keep that folder dedicated to Kohevo backups. Local cleanup is best effort, so operators should monitor disk usage and failed runs.

## Source map

See `Backups.php`, `BackupRunner.php`, `GoogleDriveClient.php`, `admin/settings.php`, `admin/gdrive-callback.php`, `install.sql`, and `uninstall.sql`.

## Known gaps

The README does not fully explain whole-database dumping, tenant-scoped run selection, Drive-folder pruning safety, consistency limits, or the absence of restore automation. `uninstall.sql` removes the run table but does not explicitly clear `backups.*` settings.
