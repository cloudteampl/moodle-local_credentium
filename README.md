# Credentium® Integration for Moodle

**Version:** 2.0.0alpha
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
- Automatic credential issuance upon course completion
- Grade inclusion with automatic retry if grade aggregation is pending
- Comprehensive admin reporting and monitoring
- GDPR-compliant privacy implementation

### v2.0.0 Multi-Tenant Support
- **Per-Category Configuration**: Each course category can connect to separate Credentium® instances
- **Category Tree Inheritance**: Automatic credential resolution up category hierarchy
- **Template Cache Isolation**: Multi-tenant template caching prevents data leakage
- **Rate Limiting**: Per-category hourly credential issuance limits
- **Operational Controls**: Pause/resume credential issuance per category
- **API Key Encryption**: Secure storage of API credentials at rest
- **Feature Flag**: Category mode can be enabled/disabled globally

## Installation

1. Download the latest release ZIP from the repository
2. Extract to `{moodle_root}/local/credentium/`
3. Visit **Site Administration > Notifications** to complete installation
4. Configure global settings at **Site Administration > Plugins > Local plugins > Credentium®**

## Configuration

### Global Settings
**Location:** Site Administration > Plugins > Local plugins > Credentium®

- **Enable Plugin**: Master toggle for the plugin
- **API URL**: Credentium® API endpoint (used when category mode disabled)
- **API Key**: Authentication key (used when category mode disabled)
- **Category Mode**: Enable per-category configuration (default: OFF)
- **Debug Logging**: Enable detailed diagnostic logging

### Category Settings (v2.0)
**Location:** Category > Credentium® Category Settings
**Requires:** `local/credentium:managecategory` capability

- **Enable for Category**: Enable Credentium® for this category
- **API URL**: Category-specific Credentium® endpoint
- **API Key**: Category-specific authentication key (encrypted at rest)
- **Pause Issuances**: Temporarily pause credential issuance
- **Rate Limit**: Maximum credentials per hour (optional)

### Course Settings
**Location:** Course > Credentium® Settings
**Requires:** `local/credentium:managecourse` capability

- **Enable for Course**: Enable credential issuance for this course
- **Credential Template**: Select template from Credentium®
- **Send Grade**: Include course grade with credential
- **Use Category Credentials**: Inherit API credentials from category (v2.0)

## External Services & Privacy

### Data Sharing with Credentium® (Third-Party Service)

This plugin communicates with the **Credentium® API** (a paid third-party service) to issue digital credentials. When a student completes a course, the following data is sent to Credentium®:

**Data Sent to Credentium® API:**
- Student email address
- Student first name
- Student last name
- Course name
- Course grade (if enabled)
- Credential template ID

**Purpose:** To create and issue a digital microcredential certificate

**Service Provider:** Credentium® (https://credentium.com) - Commercial SaaS platform

**Privacy:** The Credentium® service operates independently with its own privacy policy. Please review Credentium®'s privacy policy at https://credentium.com/privacy before using this plugin.

**Data Retention:** Credential issuance records are stored locally in your Moodle database for reporting purposes. External data retention is controlled by Credentium®.

## Security

- API keys encrypted at rest using Moodle's encryption API
- All database queries use parameterized placeholders
- CSRF protection via sesskey on all forms
- Capability checks enforce access control
- Input validation with proper sanitization
- Debug logging never exposes sensitive data to web output
- Secure HTTPS communication with Credentium® API

## Development

### File Structure
```
credentium/
├── classes/
│   ├── api/client.php              # API client with caching
│   ├── event/                      # Custom events
│   ├── observer.php                # Event observer
│   ├── privacy/provider.php        # GDPR implementation
│   └── task/issue_credential.php   # Adhoc task
├── db/
│   ├── access.php                  # Capabilities
│   ├── install.xml                 # Database schema
│   ├── upgrade.php                 # Upgrade logic
│   ├── events.php                  # Event observers
│   ├── messages.php                # Notifications
│   └── tasks.php                   # Scheduled tasks
├── lang/en/local_credentium.php    # Language strings
├── category_settings.php           # Category configuration UI
├── course_settings.php             # Course configuration UI
├── settings.php                    # Global admin settings
├── version.php                     # Plugin metadata
└── lib.php                         # Library functions
```

### Building for Deployment

Use the provided deployment script:

```bash
./deploy.sh
```

This creates `dist/local_credentium-2.0.0alpha.zip` ready for installation.

## Support

**Developer:** CloudTeam Sp. z o.o.
**Repository:** https://github.com/cloudteampl/moodle-local_credentium

For issues and feature requests, please use the GitHub issue tracker.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

## Trademarks

Credentium® is a registered trademark. All rights reserved.

---

**Copyright © 2025 CloudTeam Sp. z o.o.**
