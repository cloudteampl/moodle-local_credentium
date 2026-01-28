# Changelog

All notable changes to the Credentium® Integration plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.5] - 2026-01-28

### Fixed
- **Critical language strings bug**: Fixed missing English language strings that caused `[[globalsettings]]`,
  `[[dataretention]]` and `TODO: missing help string [apiurl_global_help]` errors on the settings page.
  The English language file was out of sync with the Polish translation and the admin_settings.php UI.
- **Missing admin_settings.php**: Restored the admin settings page file that was accidentally excluded from the repository.
- **settings.php mismatch**: Fixed settings.php to properly redirect to admin_settings.php for consistent UI.

### Technical Details
- Added 30+ missing English language strings to match Polish translations
- Strings added: `globalsettings`, `apiurl_global_help`, `apikey_global_help`, `dataretention`,
  `dataretention_help`, `categorymode_info_heading`, `categorymode_info_text`, `testconnection_disabled`,
  and many more testconnection_*, privacy:metadata:credentium_api:*, category settings strings

## [2.0.4] - 2026-01-27

### Fixed
- **Raw grade handling**: Credentials now send raw grade points to API instead of percentage.
  The API expects raw values and handles conversion internally.
- **Multi-assessment template restriction**: Templates with multiple Learning Assessments are now
  marked as unsupported and cannot be selected for courses. Only templates with 0 or 1 Learning Assessment are allowed.

### Added
- Plugin version display in global settings header for easier troubleshooting.

## [2.0.3] - 2026-01-21

### Fixed
- **Grade race condition**: Fixed issue where credentials were issued with stale grades.
  When a user improved their grade (e.g., from 15% to 80%), credentials could be issued
  with the old grade due to a race condition between course completion events and grade
  aggregation. The plugin now waits for grade aggregation to complete before issuing.

### Changed
- Grade is no longer fetched at course completion event time
- Added freshness check with 60s tolerance and gradebook needsupdate signal
- Implemented conditional regrade (only on first attempt or when course needs it)
- Changed retry mechanism to use `reschedule_or_queue_adhoc_task()` to prevent duplicate tasks
- Added locking in observer to prevent race condition duplicate issuances
- Added `timecompleted` column to track completion-event time

### Technical Details
- Max retry attempts: 5 (~44 minutes total wait)
- Backoff delays: 15s -> 60s -> 180s -> 600s -> 1800s
- Freshness tolerance: 60 seconds
- Gradebook settled (no needsupdate) accepted after first retry
- Grade stored as raw points; converted to percentage when sending to API

## [2.0.2] - 2026-01-20

### Fixed
- **API Key Authentication**: Fixed critical bug where dots (`.`) were being stripped from API keys during sanitization. Credentium API keys use the format `cred_xxxxx.yyyyy` where the dot separates the public ID from the secret. Changed `PARAM_ALPHANUMEXT` to `PARAM_RAW_TRIMMED` to preserve all characters in the API key.

## [2.1.1] - 2025-01-18

### Added
- **External service privacy declaration**: Privacy API now properly declares data sharing with Credentium® (paid third-party service)
- **Expanded PHPUnit tests**: Added lib_test.php and observer_test.php for improved test coverage
- **External services documentation**: README now clearly documents data transmission to Credentium® API
- **Paid service notice**: README prominently states Credentium® is a PAID SaaS service (plugin is free, service is paid)
- **Supported Moodle versions declaration**: Added explicit Moodle 4.5-5.0 support to version.php

### Changed
- **Privacy metadata**: Updated all privacy language strings to clarify external API data sharing
- **README.md**: Updated version to 2.1.1 and added comprehensive privacy/external services section

### Moodle Plugin Directory Compliance
- Addressed critical gaps from Moodle plugin contribution checklist
- External service communication now properly documented
- Privacy implications clearly stated for GDPR compliance
- Ready for Moodle plugin directory submission (pending screenshots)

## [2.1.0] - 2025-01-18

### Added
- LICENSE file with GPLv3 license
- CHANGELOG.md for version history tracking
- thirdpartylibs.xml declaration
- Plugin icon (pix/icon.svg)
- Screenshots folder for Moodle plugin directory
- Basic PHPUnit test skeleton for privacy provider
- Developer documentation (DEVELOPMENT.md)

### Changed
- Improved developer documentation structure

### Removed
- Deprecated `$plugin->cron` line from version.php (deprecated in Moodle 4.5+)

### Plugin Directory
- Plugin is now ready for submission to the official Moodle plugin directory

## [2.0.6] - 2024-11-18

### Fixed
- Removed duplicate "Credentium Settings" heading on global settings page
- Added missing help tooltips for categorymode and debuglog settings

## [2.0.5] - 2024-11-18

### Added
- Custom admin settings page (admin_settings.php) with Moodle form for consistent UI
- Help tooltips for all settings fields

### Changed
- Global settings page now uses same visual style as category settings page
- Converted settings.php to use admin_externalpage for better UI consistency

## [2.0.4] - 2024-11-18

### Added
- Client-side validation for Test Connection button in both global and category settings
- JavaScript validation prevents testing with empty API URL or API Key fields

### Changed
- Test Connection button now validates fields before opening test page

## [2.0.3] - 2024-11-18

### Added
- Help tooltips for API URL and API Key fields in category settings form

### Fixed
- SECURITY: Test Connection now uses category-specific credentials instead of global credentials
- Test Connection page now properly handles categoryid parameter
- Capability checks based on context (category vs global)
- Back link returns to correct settings page based on context

## [2.0.2] - 2024-11-18

### Fixed
- Fixed navigation callback function name from `local_credentium_extend_navigation_category()` to `local_credentium_extend_navigation_category_settings()`
- Category settings link now appears in category navigation menu when category mode is enabled

## [2.0.1] - 2024-11-18

### Fixed
- Removed duplicate title on Test Connection page
- Fixed MariaDB/MySQL compatibility by replacing PostgreSQL-specific `IS NOT DISTINCT FROM` with conditional NULL-safe comparisons
- Fixed SQL syntax errors in client.php and issue_credential.php

## [2.0.0] - 2024-11-06

### Added - Multi-Tenant Support
- **Per-category API configuration**: Each course category can have its own API URL and API key
- **Category tree inheritance**: Courses automatically inherit credentials from parent categories (up to 10 levels)
- **Course-level override option**: Courses can optionally use category or global credentials
- **Template cache isolation**: Each category maintains its own template cache to prevent cross-tenant data leakage
- **Rate limiting per category**: Configurable hourly rate limits to prevent API abuse
- **Operational controls**: Pause credential issuance per category without affecting others
- **Feature flag**: Category mode can be enabled/disabled globally for gradual rollout
- **API key encryption**: Category and global API keys are encrypted at rest using Moodle's encryption API

### Added - New Database Tables
- `local_credentium_category_config`: Stores category-level configuration (apiurl, apikey, paused, ratelimit)
- Added `categoryid` field to `local_credentium_issuances` for rate limiting tracking
- Added `inherit_category` field to `local_credentium_course_config` (default=1)
- Modified `local_credentium_templates_cache` with composite unique index (templateid, categoryid)

### Added - Configuration Resolution
- `local_credentium_resolve_course_config()`: Merges course settings with category credentials
- `local_credentium_resolve_category_config()`: Walks category tree to find nearest enabled configuration
- Static caching prevents repeated database queries within the same request

### Added - New UI Pages
- `category_settings.php`: Category-level configuration interface with proper form validation
- Navigation link appears in category settings when category mode is enabled

### Added - Encryption Support
- `local_credentium_encrypt_apikey()`: Encrypts API keys using Moodle's encryption API
- `local_credentium_decrypt_apikey()`: Decrypts API keys with fallback to plain text for migration
- `local_credentium_save_category_config()`: Handles encryption automatically
- `local_credentium_get_category_config()`: Handles decryption automatically

### Changed
- API client now accepts optional categoryid parameter for cache isolation
- Template cache queries use categoryid for multi-tenant isolation
- Rate limit checking now uses categoryid to track per-category limits
- Test connection page supports both global and category contexts

### Database Migration
- Upgrade script (version 2025080100) handles migration from v1.1.8
- Clears template cache to force rebuild with new schema
- Backward compatibility maintained - category mode disabled by default

### Security
- API keys encrypted at rest with automatic encryption/decryption
- Cross-database compatibility (MariaDB, PostgreSQL, MySQL)
- Proper capability checks for category management

## [1.1.8] - 2024-10-XX

### Added
- Initial stable release
- Course completion event observer
- Automatic credential issuance upon course completion
- Template caching (1-hour TTL)
- Grade inclusion support with retry logic
- Adhoc task for asynchronous processing
- Privacy API implementation (GDPR compliance)
- Admin report interface
- Course-level configuration
- Test connection functionality
- Debug logging option

### Features
- Event-driven credential issuance
- Retry logic with exponential backoff for grade aggregation
- Comprehensive error handling and logging
- Security: encrypted API keys, parameterized queries, capability checks
- Multi-language support (English)

---

**Developed by CloudTeam Sp. z o.o.**

For more information, visit: https://github.com/cloudteam-pl/moodle-local_credentium
