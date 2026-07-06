# Deployment Guide - Credentium® Moodle Plugin

**Copyright © 2025 CloudTeam Sp. z o.o.**
**Credentium® is a registered trademark**

## Quick Start

### Build Release Package

Run from the repository root (the plugin lives at the root of this repo):

```bash
cd /path/to/moodle-local_credentium
./deploy.sh
```

This creates (version read from `version.php`, e.g. `2.0.6`):
- `dist/local_credentium-<release>.zip` - Plugin package
- `dist/local_credentium-<release>.zip.sha256` - SHA256 checksum
- `dist/local_credentium-<release>.zip.md5` - MD5 checksum

> For an actual client release you normally do **not** build by hand — push a
> `vX.Y.Z` tag and CI builds and publishes the GitHub Release (see below).
> `./deploy.sh` is for inspecting the package locally before tagging.

### Install on Moodle

**Option 1: Web Interface**
1. Login as admin
2. Navigate to: Site Administration > Plugins > Install plugins
3. Upload the ZIP file
4. Click "Install plugin from ZIP file"
5. Confirm installation
6. Visit Site Administration > Notifications

**Option 2: Command Line**
```bash
cd /path/to/moodle
unzip -d local/ /path/to/local_credentium-2.0.0.zip
php admin/cli/upgrade.php
```

## Automated Deployment (GitHub Actions)

The repository includes a GitHub Actions workflow for automated builds.

### Create a Release

```bash
# Bump version.php first (see "Updating Version"), then tag:
git tag -a v2.0.7 -m "Release v2.0.7"
git push origin v2.0.7
```

GitHub Actions will automatically:
1. Verify the tag matches `$plugin->release` in `version.php` (fails the build otherwise)
2. Build the deployment package
3. Generate SHA256/MD5 checksums
4. Create a GitHub Release and attach the ZIP and checksum files

### Manual Workflow Trigger

You can also trigger the build manually:
1. Go to repository on GitHub
2. Navigate to Actions tab
3. Select "Build and Release Credentium® Plugin"
4. Click "Run workflow"

## Deployment Checklist

### Pre-Deployment

- [ ] **Backup Database**: Always backup Moodle database before upgrading
- [ ] **Staging Test**: Test upgrade in staging environment first
- [ ] **Version Check**: Verify version number in `version.php`
- [ ] **Changelog**: Update release notes with changes
- [ ] **Dependencies**: Ensure Moodle 4.5+ compatibility

### Building Package

- [ ] Run `./deploy.sh` to create ZIP
- [ ] Verify ZIP structure: `unzip -l dist/*.zip | head -40`
- [ ] Check ZIP size (should be ~50KB)
- [ ] Verify checksums are generated

### Testing Package

- [ ] Install on test Moodle instance
- [ ] Run database upgrade
- [ ] Check for PHP errors in logs
- [ ] Verify plugin settings page loads
- [ ] Test credential issuance
- [ ] Test category settings (if category mode enabled)

### Post-Deployment

- [ ] Monitor error logs for 24 hours
- [ ] Verify scheduled tasks are running
- [ ] Test credential issuance on sample course
- [ ] Check rate limiting is working
- [ ] Verify API connections

## Package Contents

The deployment package includes:

### Core Files
- `version.php` - Plugin metadata
- `lib.php` - Library functions
- `settings.php` - Global settings

### Database
- `db/install.xml` - Database schema
- `db/upgrade.php` - Upgrade scripts
- `db/access.php` - Capabilities
- `db/events.php` - Event observers
- `db/tasks.php` - Scheduled tasks
- `db/messages.php` - Notifications

### UI Pages
- `category_settings.php` - Category configuration
- `course_settings.php` - Course configuration
- `index.php` - Admin report
- `debug.php` - Debug interface
- `testconnection.php` - API test
- `view.php` - Credential view
- `process.php` - Bulk processing

### Classes
- `classes/api/client.php` - API client
- `classes/observer.php` - Event observer
- `classes/task/issue_credential.php` - Issuance task
- `classes/event/` - Custom events
- `classes/privacy/provider.php` - GDPR compliance

### Language
- `lang/en/local_credentium.php` - English strings
- `lang/pl/local_credentium.php` - Polish strings

### Tests
- `tests/` - PHPUnit tests (included in the package, per Moodle plugin conventions)

### Excluded from Package

The following are NOT included in the release ZIP (see the exclude list in `deploy.sh`):
- `deploy.sh` - Build script
- `.github/` - CI workflows
- `.git/`, `.gitignore` - Git repository
- `CLAUDE.md`, `DEPLOYMENT.md` - Development/maintainer docs
- `screenshots/` - Listing images (not needed at runtime)
- `dist/`, `*.zip` - Build artifacts
- `.DS_Store`, IDE folders - OS/editor cruft

## Version Management

### Version Numbers

The plugin uses Moodle's version numbering system:

- **$plugin->version**: YYYYMMDDXX (2025080100)
  - YYYY = Year
  - MM = Month
  - DD = Day
  - XX = Increment (00-99)

- **$plugin->release**: 'X.Y.Z' (2.0.0)
  - X = Major version
  - Y = Minor version
  - Z = Patch version

### Updating Version

When preparing a new release:

1. Edit `version.php`
2. Increment `$plugin->version` (e.g., 2025080100 → 2025080200)
3. Update `$plugin->release` (e.g., '2.0.0' → '2.0.1')
4. Update `db/upgrade.php` if schema changes
5. Run deployment script
6. Test package
7. Create git tag
8. Push tag to trigger CI/CD

## Troubleshooting

### ZIP Creation Fails

**Issue**: deploy.sh exits with error
**Solution**: Check that all critical files exist and are readable

### Incorrect ZIP Structure

**Issue**: ZIP extracts to wrong location
**Solution**: Ensure rsync is installed and working correctly

### Large ZIP Size

**Issue**: ZIP is much larger than expected
**Solution**: Verify the exclude list in `deploy.sh` is effective (no `.git/`,
`dist/`, `screenshots/`, or stray `*.zip` should be inside the package)

### Checksum Mismatch

**Issue**: SHA256/MD5 doesn't match after download
**Solution**: Re-download file, check for corruption in transit

### Installation Fails

**Issue**: Moodle rejects plugin package
**Solution**:
- Verify ZIP structure with `unzip -l`
- Check version.php is present
- Ensure compatible Moodle version (4.5+)

## Security Considerations

### Before Release

- [ ] Remove any test API keys from code
- [ ] Verify no sensitive data in repository
- [ ] Check all copyright headers are correct
- [ ] Ensure trademark symbols are present
- [ ] Review commit history for sensitive information

### Package Integrity

- [ ] Generate SHA256 checksum
- [ ] Generate MD5 checksum
- [ ] Sign release commits with GPG (optional)
- [ ] Use HTTPS for distribution

## Contact

**Developer:** CloudTeam Sp. z o.o.
**Repository:** https://github.com/cloudteampl/moodle-local_credentium
**Issues:** https://github.com/cloudteampl/moodle-local_credentium/issues

---

**Credentium® is a registered trademark. All rights reserved.**
**Copyright © 2025 CloudTeam Sp. z o.o.**
