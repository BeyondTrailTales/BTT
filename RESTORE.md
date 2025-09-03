# Restore Guide for BTT snapshot 2025-09-03-173040

This branch and tag capture a 100 percent working state with user authentication and database data.

Branch: WORKING-BACKUP-2025-09-03-173040
Tag:    v1.0-working-with-auth

Database backup file included in this commit:
- storage\sqlite\backups\btt-2025-09-03-173040.db

## Restore using the branch:
1) git fetch origin
2) git checkout WORKING-BACKUP-2025-09-03-173040
3) Copy the backup DB over your working DB:
   - Windows PowerShell:
     Copy-Item "storage\sqlite\backups\btt-2025-09-03-173040.db" "storage\sqlite\btt.db" -Force
4) Restart your local server and clear any caches your app uses.

## Restore using the tag:
1) git fetch origin --tags
2) git checkout v1.0-working-with-auth
3) Copy the backup DB as described above.
4) Restart services and verify login and protected routes.

## Notes:
- The test directory is included for reproducibility.
- All configuration files required to run locally are included.
- This is a 100% working snapshot with full authentication system operational.
