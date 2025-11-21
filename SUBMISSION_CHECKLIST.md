# Moodle Plugin Directory Submission Checklist

Complete step-by-step guide for submitting Credentium® Integration plugin to the official Moodle plugin directory.

**Plugin:** Credentium® Integration
**Version:** 2.1.1
**Developer:** CloudTeam Sp. z o.o.
**Date:** January 18, 2025

---

## PHASE 1: Complete LICENSE File (CRITICAL)

**Status:** ⚠️ TODO - Manual Download Required

### Steps:

1. **Download Full GPL v3 License**:
   ```bash
   curl https://www.gnu.org/licenses/gpl-3.0.txt -o LICENSE
   ```
   Or manually download from: https://www.gnu.org/licenses/gpl-3.0.txt

2. **Replace Current LICENSE File**:
   - Current file at: `/local/credentium/LICENSE`
   - Current file is only a summary with link
   - Replace with complete ~600-line GPL v3 text

3. **Verify**:
   - LICENSE file should be ~35KB
   - Contains full license text (not just summary)
   - Starts with "GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007"

---

## PHASE 2: Create Screenshots (CRITICAL)

**Status:** ⚠️ TODO - Requires Moodle Instance

### What You Need:

Create **5 screenshots** showing the plugin in action:

### Screenshot 1: Global Settings Page
- **File**: `screenshots/01-global-settings.png`
- **Navigate to**: Site Administration > Plugins > Local plugins > Credentium®
- **What to show**:
  - Plugin enabled checkbox
  - API URL field (filled with example: `https://api.credentium.com`)
  - API Key field (masked)
  - Category mode toggle
  - Test Connection button
- **Resolution**: Minimum 1280x720px
- **Format**: PNG or JPG

### Screenshot 2: Category Settings Page
- **File**: `screenshots/02-category-settings.png`
- **Navigate to**: Course Categories > [Any Category] > Credentium Category Settings
- **What to show**:
  - Enable for category checkbox
  - Category-specific API URL
  - Category-specific API Key (masked)
  - Pause issuances toggle
  - Rate limit field
  - Test Connection button
- **Resolution**: Minimum 1280x720px
- **Format**: PNG or JPG

### Screenshot 3: Course Settings Page
- **File**: `screenshots/03-course-settings.png`
- **Navigate to**: [Any Course] > Credentium Settings
- **What to show**:
  - Enable for course checkbox
  - Credential template dropdown (with templates loaded)
  - Send grade checkbox
  - Use category credentials checkbox
  - Information showing which category's credentials are used
- **Resolution**: Minimum 1280x720px
- **Format**: PNG or JPG

### Screenshot 4: Credential Report
- **File**: `screenshots/04-credential-report.png`
- **Navigate to**: Site Administration > Reports > Credentium Report
- **What to show**:
  - Table with issued credentials
  - Columns: User, Course, Template, Status, Date
  - Filter options
  - Some example data (use demo users/courses)
- **Resolution**: Minimum 1280x720px
- **Format**: PNG or JPG

### Screenshot 5: Test Connection Success
- **File**: `screenshots/05-test-connection.png`
- **Navigate to**: Settings page > Test Connection button
- **What to show**:
  - API URL displayed
  - API Key masked
  - Success message: "Connection to Credentium API successful!"
  - List of templates found
- **Resolution**: Minimum 1280x720px
- **Format**: PNG or JPG

### Screenshot Creation Tips:
- Use demo/test data (never real user data!)
- Crop to show relevant UI (remove unnecessary browser chrome)
- Ensure text is readable
- Use consistent window size
- No sensitive information visible

---

## PHASE 3: Set Up GitHub Repository (REQUIRED)

**Status:** ⚠️ TODO

### Steps:

1. **Create GitHub Repository**:
   ```
   Repository Name: moodle-local_credentium
   Description: Credentium® Integration for Moodle - Automatic digital credential issuance
   Visibility: Public
   License: GNU General Public License v3.0
   ```

2. **Initialize Repository**:
   ```bash
   cd /Users/michalkarski/git/ct-credentium-moodle/prod/local/credentium
   git init
   git add .
   git commit -m "Initial commit - Credentium Integration v2.1.1"
   git branch -M main
   git remote add origin https://github.com/cloudteampl/moodle-local_credentium.git
   git push -u origin main
   ```

3. **Create README.md for GitHub** (different from plugin README):
   - Add badges: ![License](https://img.shields.io/badge/license-GPLv3-blue.svg) ![Moodle](https://img.shields.io/badge/moodle-4.5%2B-orange.svg)
   - Link to Moodle plugin directory page (after submission)
   - Installation instructions
   - Link to full documentation

4. **Add .gitattributes**:
   ```
   # .gitattributes
   /.git* export-ignore
   /screenshots export-ignore
   /tests export-ignore
   SUBMISSION_CHECKLIST.md export-ignore
   ```

5. **Create GitHub Release for v2.1.1**:
   - Tag: `v2.1.1`
   - Title: "Credentium® Integration v2.1.1 - Plugin Directory Ready"
   - Description: Copy from CHANGELOG.md
   - Attach: `local_credentium-2.1.1.zip`

---

## PHASE 4: Register on Moodle.org (IF NOT ALREADY)

**Status:** ⚠️ TODO (if not registered)

### Steps:

1. **Create Account**:
   - Visit: https://moodle.org/
   - Click "Create new account"
   - Use company email (@cloudteam.pl domain recommended)
   - Verify email

2. **Complete Profile**:
   - Company: CloudTeam Sp. z o.o.
   - Country: Poland
   - Add profile picture (optional but professional)

---

## PHASE 5: Submit Plugin to Moodle Plugin Directory

**Status:** ⚠️ TODO - After completing phases 1-4

### Steps:

1. **Visit Plugin Upload Page**:
   - URL: https://moodle.org/plugins/upload.php
   - Login with your Moodle.org account

2. **Fill in Plugin Information**:

   **Basic Information:**
   - **Plugin name**: Credentium® Integration
   - **Plugin type**: Local
   - **Plugin component**: local_credentium
   - **Plugin short description**:
     ```
     Automatically issue digital microcredentials to students upon course completion through
     the Credentium® platform. Supports multi-tenant deployments with per-category configuration.

     ⚠️ Requires paid Credentium® subscription (plugin is free, service is paid).
     ```

   **Version Information:**
   - **Version number**: 2.1.1
   - **Moodle version compatibility**: 4.5, 5.0
   - **Maturity**: Stable
   - **Release notes**: Copy from CHANGELOG.md v2.1.1 section

   **Developer Information:**
   - **Developer name**: CloudTeam Sp. z o.o.
   - **Developer URL**: https://cloudteam.pl (or your company website)
   - **Support URL**: https://github.com/cloudteampl/moodle-local_credentium/issues

   **Repository Information:**
   - **Source control URL**: https://github.com/cloudteampl/moodle-local_credentium
   - **Bug tracker URL**: https://github.com/cloudteampl/moodle-local_credentium/issues
   - **Documentation URL**: https://github.com/cloudteampl/moodle-local_credentium#readme

3. **Upload Plugin Package**:
   - File: `local_credentium-2.1.1.zip` (from dist/ folder)
   - Verify file size: ~70KB
   - Upload screenshots (5 PNG/JPG files from screenshots/ folder)

4. **Plugin Description** (Long description in Markdown):
   ```markdown
   # Credentium® Integration for Moodle

   Automatically issue digital microcredentials to students upon course completion.

   ## ⚠️ IMPORTANT: Credentium® is a PAID SaaS Service

   This plugin is **free and open source**, but requires an active **paid subscription**
   to Credentium® (https://credentium.com) to function.

   ## Features

   ### Multi-Tenant Support
   - Per-category API configuration
   - Category tree inheritance
   - Template cache isolation
   - Rate limiting per category

   ### Core Functionality
   - Automatic credential issuance on course completion
   - Grade inclusion with retry logic
   - GDPR-compliant privacy implementation
   - Encrypted API key storage
   - Comprehensive admin reporting

   ## Requirements

   - Moodle 4.5 or later
   - Credentium® account (paid subscription required)
   - API credentials from Credentium®
   - PHP 7.4 or later
   - HTTPS enabled (recommended)

   ## Privacy & External Services

   This plugin transmits student data (name, email, grade) to the external Credentium®
   API (paid third-party service) to issue credentials. Please review Credentium®'s
   privacy policy before use.

   ## Documentation

   Full documentation available at:
   https://github.com/cloudteampl/moodle-local_credentium

   ## Support

   Report issues at: https://github.com/cloudteampl/moodle-local_credentium/issues
   ```

5. **Select Categories/Tags**:
   - **Category**: Authentication and enrolment > Credentials
   - **Tags**: credentials, badges, microcredentials, digital certificates, credentium

6. **License**:
   - Select: **GNU GPL v3 or later**

7. **Review and Submit**:
   - Preview plugin page
   - Check all screenshots are uploaded
   - Verify all links work
   - Click "Submit for approval"

---

## PHASE 6: Await Plugin Review

**Status:** 📋 AFTER SUBMISSION

### What Happens Next:

1. **Automated Checks** (within minutes):
   - ZIP file validation
   - version.php validation
   - Language strings check
   - Code style check (automated Moodle plugin CI)

2. **Manual Review** (1-7 days typically):
   - Moodle plugin reviewers will:
     - Review code for security issues
     - Check privacy compliance
     - Verify documentation completeness
     - Test plugin installation
     - Review screenshots

3. **Possible Outcomes**:
   - **Approved**: Plugin goes live immediately
   - **Revision Requested**: Make requested changes and resubmit
   - **Rejected**: Address major issues and resubmit

### During Review:
- Monitor email for reviewer feedback
- Check Moodle.org notifications
- Be prepared to make quick fixes if requested

---

## PHASE 7: Post-Approval Tasks

**Status:** 🎉 AFTER APPROVAL

### After Plugin is Approved:

1. **Add Plugin Directory Badge to GitHub README**:
   ```markdown
   [![Moodle Plugin](https://img.shields.io/badge/moodle-plugin_directory-orange.svg)](https://moodle.org/plugins/local_credentium)
   ```

2. **Update Repository Links**:
   - Add link to plugin directory page in README
   - Update issue template to mention plugin directory

3. **Announce Release**:
   - Post on CloudTeam website/blog
   - Social media announcement (if applicable)
   - Email existing Credentium® customers

4. **Monitor Feedback**:
   - Watch plugin ratings
   - Respond to user comments
   - Address bug reports promptly

---

## PHASE 8: Future Updates

**When You Release New Versions:**

1. **Prepare New Version**:
   - Update version.php
   - Update CHANGELOG.md
   - Create release notes

2. **Test Upgrade Path**:
   - Test upgrade from previous version
   - Verify database migrations work
   - Check all features still work

3. **Submit Update to Plugin Directory**:
   - Login to Moodle.org
   - Navigate to your plugin page
   - Click "Add new version"
   - Upload new ZIP file
   - Add release notes

4. **Create GitHub Release**:
   - Tag version
   - Attach ZIP file
   - Copy release notes

---

## QUICK REFERENCE CHECKLIST

### Before Submission:
- [⚠️] Replace LICENSE with full GPL v3 text
- [⚠️] Create 5 screenshots (1280x720px minimum)
- [⚠️] Create GitHub repository (cloudteampl/moodle-local_credentium)
- [⚠️] Create GitHub release v2.1.1
- [✅] README.md updated to v2.1.1
- [✅] Privacy API declares external service
- [✅] External services documented
- [✅] CHANGELOG.md updated
- [✅] PHPUnit tests added
- [✅] version.php shows 2.1.1
- [✅] Plugin icon present
- [✅] thirdpartylibs.xml present

### During Submission:
- [ ] Register on Moodle.org (if not already)
- [ ] Upload plugin ZIP
- [ ] Upload 5 screenshots
- [ ] Fill in all metadata
- [ ] Add comprehensive description
- [ ] Set license to GPL v3
- [ ] Add support/documentation URLs
- [ ] Submit for review

### After Approval:
- [ ] Add plugin directory badge to GitHub
- [ ] Announce release
- [ ] Monitor feedback
- [ ] Plan future updates

---

## IMPORTANT NOTES

⚠️ **CRITICAL**: The plugin is FREE, but Credentium® service is PAID. This must be clearly stated everywhere.

📝 **DOCUMENTATION**: All documentation must clearly state:
1. Plugin is free and open source (GPL v3)
2. Credentium® is a paid third-party service
3. Active Credentium® subscription required
4. User data is transmitted to external service

🔒 **PRIVACY**: Privacy policy must:
1. Declare external data sharing
2. List all data sent to Credentium®
3. Note that Credentium® has its own privacy policy
4. Comply with GDPR requirements

---

## NEED HELP?

**Moodle Plugin Contribution Guide:**
https://moodledev.io/general/community/plugincontribution

**Plugin Upload Page:**
https://moodle.org/plugins/upload.php

**Moodle Plugin Developer Forum:**
https://moodle.org/mod/forum/view.php?id=44

**CloudTeam Contact:**
support@cloudteam.pl

---

**Good luck with your submission! 🚀**

---

**Developed by CloudTeam Sp. z o.o.**
**Copyright © 2025 CloudTeam Sp. z o.o.**
