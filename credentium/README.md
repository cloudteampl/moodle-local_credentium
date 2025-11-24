# Credentium® Integration for Moodle

**Version:** 2.0.0
**Author:** CloudTeam Sp. z o.o.
**License:** GNU GPL v3 or later (Plugin is FREE)
**Moodle Compatibility:** 4.5+ and 5.0

## About

The Credentium® Integration plugin automatically issues digital microcredentials to students upon course completion through the Credentium® platform.

**⚠️ IMPORTANT: Credentium® is a PAID SaaS Service**

This plugin is **free and open source**, but it requires an active **paid subscription** to the Credentium® service to function. You must have:
- A Credentium® account at https://credentium.com
- An active paid subscription
- API credentials (API URL and API Key) from your Credentium® account

**Credentium® is a registered trademark.**

## Features

### Core Functionality
- **Automatic Credential Issuance**: Triggered when students complete a course
- **Smart Grade Handling**: Automatically retries if grade aggregation is delayed (exponential backoff up to 1 hour)
- **Template-Based Credentials**: Select from your Credentium® templates for each course
- **Comprehensive Admin Reporting**: View, filter, and export issuance history
- **User Notifications**: Students receive notifications when credentials are issued or failed

### Multi-Tenant Support (v2.0)
- **Per-Category Configuration**: Each course category can connect to separate Credentium® instances with their own API credentials
- **Category Tree Inheritance**: Courses automatically inherit credentials from parent categories (walks up to 10 levels)
- **Template Cache Isolation**: Each category maintains its own template cache to prevent cross-tenant data leakage
- **Rate Limiting**: Set maximum credentials per hour per category to prevent API abuse
- **Operational Controls**: Pause credential issuance per category without affecting others
- **API Key Encryption**: All API keys encrypted at rest using Moodle's encryption API
- **Feature Flag**: Category mode can be enabled/disabled globally for gradual rollout

### GDPR Compliance
- **Automated Data Cleanup**: Scheduled task runs daily at 2:00 AM to delete records older than configured retention period (default: 365 days)
- **User Deletion Observer**: Automatically deletes all credential issuance records when a user is deleted from Moodle
- **Privacy Provider**: Implements Moodle's privacy API for data export and deletion requests
- **Data Minimization**: Only stores essential data (userid, courseid, status, timestamps)
- **Configurable Retention**: Administrators can set retention period in days or weeks
- **Transparent Data Sharing**: Clear privacy metadata about data sent to external Credentium® API

## How It Works

### Credential Issuance Flow

1. **Course Completion Event**: When a student completes a course, Moodle triggers `\core\event\course_completed`

2. **Event Observer** (`classes/observer.php`):
   - Checks if Credentium is enabled globally and for the course
   - Resolves API credentials (category or global)
   - Checks if category is paused
   - Attempts to fetch course grade from gradebook
   - Creates pending issuance record in database
   - Queues adhoc task for asynchronous processing

3. **Adhoc Task** (`classes/task/issue_credential.php`):
   - Checks rate limits (if configured)
   - Retries grade fetch if needed (up to 10 attempts with exponential backoff)
   - Initializes API client with category-specific or global credentials
   - Sends credential issuance request to Credentium® API
   - Updates issuance status (issued/failed)
   - Triggers events and sends user notifications
   - Implements retry logic with exponential backoff on API errors (max 3 attempts)

4. **API Client** (`classes/api/client.php`):
   - Manages HTTP communication with Credentium® API
   - Caches templates per category (1-hour TTL)
   - Handles request/response formatting
   - Provides detailed error messages for troubleshooting

### Configuration Hierarchy

**Credential Resolution Order:**
1. Course-level settings (enabled, templateid, sendgrade)
2. Category-level API credentials (if category mode enabled and category configured)
3. Parent category credentials (walks up tree up to 10 levels)
4. Global API credentials (fallback)

This allows flexible multi-tenant deployments where different organizational units can use different Credentium® instances.

### GDPR Data Lifecycle

**Local Storage:**
- Issuance records stored in `local_credentium_issuances` table
- Includes: userid, courseid, templateid, status, grade, timestamps, error details
- Automatically deleted after retention period (default: 365 days)
- Immediately deleted when user is deleted from Moodle

**External Data Sharing:**
When a credential is issued, the following data is sent to Credentium® API:
- Student email address
- Student first name
- Student last name
- Course grade (if enabled and available)
- Credential template ID

**Data Retention:** External data retention is controlled by Credentium®. Review their privacy policy at https://legal.cloudteam.global/credentium/privacy-policy

## Installation

### Fresh Installation

1. Download the latest release ZIP from [GitHub Releases](https://github.com/cloudteampl/moodle-local_credentium/releases)
2. Extract to `{moodle_root}/local/credentium/`
3. Visit **Site Administration > Notifications** to complete installation
4. Configure global settings at **Site Administration > Plugins > Local plugins > Credentium®**

### Upgrade from v1.x

1. **Backup** your Moodle database before upgrading
2. Replace the plugin files with v2.0.0
3. Visit **Site Administration > Notifications** to run database upgrades
4. Optionally enable category mode in plugin settings
5. Test credential issuance in a staging course

**Note:** Downgrading from v2.0 to v1.x is not supported due to database schema changes.

## Permissions & Capabilities

The plugin defines five capabilities to control access to different features.

**IMPORTANT: No roles are created automatically during installation.** All capabilities are assigned to the "Manager" archetype by default. You must manually grant permissions to other roles (like Teacher) if needed.

### Capabilities Overview

| Capability | Description | Default Role | Context Level |
|------------|-------------|--------------|---------------|
| `local/credentium:manage` | Manage global plugin settings | Manager | System |
| `local/credentium:managecourse` | Configure credentials for courses | Manager | Course |
| `local/credentium:managecategory` | Configure credentials for categories | Manager | Category |
| `local/credentium:viewreports` | View credential issuance reports | Manager | System |
| `local/credentium:viewowncredentials` | View own issued credentials | User (all authenticated users) | User |

### Default Permissions

**By default, only site administrators and managers can configure the plugin:**

- **Site Administrators** (site config capability): Full access to all plugin features
- **Managers**: Can configure global settings, course settings, category settings, and view reports
- **Teachers**: No access by default (cannot configure courses)
- **All Users**: Can view their own issued credentials

### Granting Permissions to Teachers

If you want teachers to configure Credentium for their courses:

**Option 1: Assign capability at course level**
1. Go to a course
2. Navigate to: Participants > Permissions
3. Search for "credentium"
4. Click on `local/credentium:managecourse`
5. Grant "Allow" to the "Teacher" role

**Option 2: Assign capability at category level (affects all courses)**
1. Go to a course category
2. Navigate to: Permissions
3. Search for "credentium"
4. Click on `local/credentium:managecourse`
5. Grant "Allow" to the "Teacher" role

**Option 3: Modify the Teacher role globally**
1. Go to: Site Administration > Users > Permissions > Define roles
2. Edit the "Teacher" role
3. Search for "credentium"
4. Grant "Allow" to `local/credentium:managecourse`
5. Save changes

### Creating Custom Roles

You can create specialized roles for credential management.

**Option 1: Using CLI Script (Recommended)**

The plugin includes a CLI script to automatically create a "Credentium Course Manager" role:

```bash
cd /path/to/moodle
php local/credentium/cli/create_role.php
```

This creates a role with:
- Short name: `credentiumcoursemanager`
- Full name: `Credentium Course Manager`
- Description: `Can configure digital credentials for courses`
- Capability: `local/credentium:managecourse`
- Assignable at: Course and Category levels

**Option 2: Manual Creation via Web Interface**

1. Go to: Site Administration > Users > Permissions > Define roles
2. Click "Add a new role"
3. Use existing role: "Teacher"
4. Set Short name: `credentiumcoursemanager`
5. Set Custom full name: `Credentium Course Manager`
6. Set Description: `Can configure digital credentials for courses`
7. Grant only: `local/credentium:managecourse`
8. Save changes
9. Set context levels: Check "Course" and "Category"

Then assign this role to specific users at course or category level.

### Multi-Tenant Permission Model (v2.0)

When **category mode** is enabled:

- **Category Managers**: Need `local/credentium:managecategory` to configure category-specific API credentials
- **Course Managers**: Need `local/credentium:managecourse` to select credential templates
- Courses automatically inherit API credentials from their parent category (up to 10 levels)
- Category credentials are encrypted at rest for security

### Troubleshooting Permissions

**Issue**: "You do not have permission to configure Credentium for this course"

**Solution**: The user needs `local/credentium:managecourse` capability at the course level. Assign it using one of the options above.

**Issue**: Teachers can't see Credentium menu in course navigation

**Solution**: Check that:
1. Plugin is enabled globally
2. User has `local/credentium:managecourse` capability
3. Course navigation cache may need clearing (Site Administration > Development > Purge all caches)

## Configuration

### Global Settings
**Location:** Site Administration > Plugins > Local plugins > Credentium®

- **Enable Credentium Integration**: Master toggle for the entire plugin
- **Enable category mode**: Turn on per-category configuration (default: OFF)
- **API URL**: Credentium® API endpoint (used when category mode disabled or as fallback)
- **API Key**: Authentication key (used when category mode disabled or as fallback)
- **Enable debug logging**: Write detailed diagnostic information to PHP error log
- **Data retention period**: How long to keep issuance records before automatic deletion (default: 365 days)
- **Test Connection**: Button to verify API credentials work correctly

### Category Settings (v2.0)
**Location:** Category > Credentium® Category Settings
**Requires:** `local/credentium:managecategory` capability
**Availability:** Only when category mode is enabled

- **Enable Credentium for this category**: Make Credentium available for courses in this category
- **API Credentials Source**: Choose between global credentials or custom category-specific credentials
- **API URL**: Category-specific Credentium® endpoint (when using custom credentials)
- **API Key**: Category-specific authentication key (encrypted at rest)
- **Pause all issuances**: Temporarily halt credential issuance without disabling
- **Rate limit**: Maximum credentials per hour (optional, prevents API abuse)

### Course Settings
**Location:** Course > Credentium® Settings
**Requires:** `local/credentium:managecourse` capability

- **Enable Credentium for this course**: Turn on automatic credential issuance
- **Credential Template**: Select template from available templates in Credentium®
  - Templates are fetched via API and cached for 1 hour
  - Shows whether template requires grade or not
  - Automatically determines if grade should be sent based on template
- **Use category API credentials**: Always enabled in v2.0 (courses always inherit from category or global)

**Display:** Shows which category's credentials are being used with informative alert.

## Database Schema

### Tables

1. **local_credentium_issuances**: Tracks all credential issuance attempts
   - Fields: id, userid, courseid, templateid, categoryid, status, grade, credentialid, attempts, errorcode, errormessage, timecreated, timemodified, timeissued

2. **local_credentium_course_config**: Course-level configuration
   - Fields: id, courseid, enabled, templateid, sendgrade, mingrade, issuancetrigger, inherit_category, timecreated, timemodified

3. **local_credentium_templates_cache**: Cached credential templates
   - Fields: id, templateid, categoryid, name, description, active, data, timecached
   - Composite unique index on (templateid, categoryid) for multi-tenant isolation

4. **local_credentium_category_config**: Category-level configuration
   - Fields: id, categoryid, enabled, apiurl, apikey (encrypted), paused, ratelimit, timecreated, timemodified

## Scheduled Tasks

1. **Process Pending Credentials** (`process_pending_credentials`):
   - Runs every 5 minutes
   - Processes any pending or retrying credentials that may have been stuck

2. **Clean Up Old Issuance Records** (`cleanup_old_issuances`):
   - Runs daily at 2:00 AM (server time)
   - Deletes records older than configured retention period
   - Deletes ALL records regardless of status (issued, failed, pending)
   - Logs deletion count for audit trail

## Security

- **API Key Encryption**: All API keys encrypted at rest using Moodle's `\core\encryption` API
- **Parameterized Queries**: All database queries use placeholders to prevent SQL injection
- **CSRF Protection**: All forms include sesskey validation
- **Capability Checks**: Strict access control on all admin interfaces
- **Input Validation**: All user input sanitized using Moodle's `clean_param()` functions
- **Secure Logging**: Debug logs never expose sensitive data (API keys, personal data) to web output
- **HTTPS Communication**: All API requests to Credentium® use secure HTTPS

## Privacy & External Services

### Data Sent to Credentium® API

This plugin communicates with the **Credentium® API** (a paid third-party commercial service) to issue digital credentials.

**Data Transmitted:**
- Student email address
- Student first name
- Student last name
- Course grade (percentage, if enabled)
- Credential template ID

**Purpose:** To create and issue a digital microcredential certificate

**Service Provider:** Credentium® (https://credentium.com) - Commercial SaaS platform

**Privacy Policy:** https://legal.cloudteam.global/credentium/privacy-policy

**Data Retention:**
- **Local (Moodle)**: Configurable retention period (default: 365 days), automatically deleted
- **External (Credentium®)**: Controlled by Credentium®'s data retention policies

## Troubleshooting

### Enable Debug Logging

1. Go to **Site Administration > Plugins > Local plugins > Credentium®**
2. Check "Enable debug logging"
3. Save changes
4. Monitor your PHP error log (typically `/var/log/apache2/error.log` or `/var/log/php-fpm/error.log`)

### Test API Connection

1. Configure your API URL and API Key
2. Save the settings
3. Click "Test Connection" button
4. Review the result message

### Common Issues

**Credentials not issued:**
- Check if plugin is enabled globally
- Check if course is enabled for Credentium
- Check if category is paused
- Review issuance report for error messages

**Grade not included:**
- Template may not require grade
- Grade aggregation may still be in progress (task will retry automatically)
- Check if grade is available in gradebook

**Template not found:**
- Template cache may be stale (refreshes every hour)
- Click "Refresh templates" button in course settings
- Verify template exists in your Credentium® account

## Support

**Developer:** CloudTeam Sp. z o.o.
**Repository:** https://github.com/cloudteampl/moodle-local_credentium
**Issues:** https://github.com/cloudteampl/moodle-local_credentium/issues

For Credentium® service support, contact https://credentium.com

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Moodle. If not, see <http://www.gnu.org/licenses/>.

## Trademarks

Credentium® is a registered trademark. All rights reserved.

---

**Copyright © 2025 CloudTeam Sp. z o.o.**
