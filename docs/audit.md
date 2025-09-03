# Repository Audit Report

**Date**: 2025-09-02  
**Branch**: refactor/cleanup-sqlite

## Files to Delete

### Duplicate/Old Files
| File | Reason | Action |
|------|--------|--------|
| `public/backpacks-old.php` | Duplicate old version | Delete after verification |
| `public/backpacks_old.php` | Another duplicate old version | Delete after verification |
| `public/trips-old.php` | Old trips page | Delete after verification |
| `public/trips-old-overlay.php` | Old overlay version | Delete |
| `public/backpacks-inline.php` | Duplicate/test version | Delete |
| `public/backpacks_enhanced.php` | Duplicate enhanced version | Delete |
| `public/backpacks_new.php` | Duplicate new version | Delete |
| `public/trips-enhanced.php` | Duplicate enhanced version | Delete |

### Test Browser Profiles (7.5MB+ of unnecessary data!)
| Directory | Size | Action |
|-----------|------|--------|
| `test/.chrome-profile/` | ~7.5MB | Delete entire directory |
| - Contains Default/, Extensions/, Cache/ | | Browser profile data should not be in repo |

### JSON Storage Files (to be migrated)
| File | Purpose | Action |
|------|---------|--------|
| `storage/json/backpacks.json` | Backpack data | Migrate to SQLite, then archive |
| `storage/json/trips.json` | Trip data | Migrate to SQLite, then archive |
| `storage/json/gear.json` | Gear data | Migrate to SQLite, then archive |
| `storage/json/gamification_*.json` | Gamification data | Delete (unused feature) |
| `storage/json/migrate_backpacks.php` | Migration script in wrong location | Move to scripts/ |

### Backend Directory
| Item | Reason | Action |
|------|--------|--------|
| `backend/` directory | Duplicate API implementation | Delete entire directory after verification |

## Files to Relocate

### Test Files
| Current Location | Target Location | Reason |
|-----------------|-----------------|--------|
| Root level test files | `test/` | Organize all tests in dedicated folder |
| `public/test/` | `test/e2e/` | Move to proper test structure |

### Documentation Files in Root
| File | Target | Reason |
|------|--------|--------|
| All `*.md` files in root (30+ files) | `docs/` | Keep root clean |
| `architecture.md` | `docs/` | Documentation |
| `tasklist.md` | `docs/` | Documentation |

## Summary Statistics
- **Duplicate Files**: 8 PHP files
- **Browser Profile Data**: ~7.5MB across 500+ files
- **JSON Files**: 13 files to migrate or delete
- **Documentation Files**: 30+ markdown files in root
- **Total Files to Remove**: ~550+
- **Estimated Space Savings**: ~10MB+

## Priority Actions
1. Delete all browser profile data immediately (test/.chrome-profile/)
2. Remove duplicate PHP files after content verification
3. Create proper test structure
4. Migrate JSON data to SQLite
5. Move documentation to docs/ folder
6. Clean up root directory
