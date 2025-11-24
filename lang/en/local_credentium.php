<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the local_credentium plugin.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Credentium® Integration';
$string['privacy:metadata'] = 'The Credentium® Integration plugin stores information about credential issuances locally and transmits user data to the external Credentium® service (a paid third-party API).';

// Local database storage.
$string['privacy:metadata:local_credentium_issuances'] = 'Information about credentials issued to users.';
$string['privacy:metadata:local_credentium_issuances:userid'] = 'The ID of the user who received the credential.';
$string['privacy:metadata:local_credentium_issuances:courseid'] = 'The ID of the course for which the credential was issued.';
$string['privacy:metadata:local_credentium_issuances:credentialid'] = 'The external ID of the credential in Credentium.';
$string['privacy:metadata:local_credentium_issuances:status'] = 'The status of the credential issuance.';
$string['privacy:metadata:local_credentium_issuances:timecreated'] = 'The time when the credential was issued.';

// External service - Credentium API (PAID commercial service).
$string['privacy:metadata:credentium_api'] = 'The Credentium® API is a paid third-party service (https://credentium.com) used to issue digital credentials. User data is transmitted to this external service.';
$string['privacy:metadata:credentium_api:email'] = 'The user\'s email address is sent to Credentium® to identify the credential recipient.';
$string['privacy:metadata:credentium_api:firstname'] = 'The user\'s first name is sent to Credentium® to personalize the credential.';
$string['privacy:metadata:credentium_api:lastname'] = 'The user\'s last name is sent to Credentium® to personalize the credential.';
$string['privacy:metadata:credentium_api:coursename'] = 'The course name is sent to Credentium® to identify which course the credential is for.';
$string['privacy:metadata:credentium_api:grade'] = 'The user\'s course grade (if enabled) is sent to Credentium® to be included on the credential.';
$string['privacy:metadata:credentium_api:templateid'] = 'The credential template ID is sent to Credentium® to specify which credential design to use.';

// Settings.
$string['settings'] = 'Credentium Settings';
$string['globalsettings'] = 'Credentium Global Settings';
$string['settings_desc'] = 'Configure the connection to Credentium API for issuing microcredentials.';
$string['enabled'] = 'Enable Credentium Integration';
$string['enabled_desc'] = 'Enable or disable the Credentium integration globally.';
$string['enabled_help'] = 'When enabled, the plugin will automatically issue digital credentials to students upon course completion (if the course is also configured individually).';
$string['apiurl'] = 'API URL';
$string['apiurl_desc'] = 'The URL of the Credentium API endpoint.';
$string['apiurl_global'] = 'API URL';
$string['apiurl_global_help'] = 'The URL of the Credentium API endpoint (e.g., https://api.credentium.com). This is used as the default when category mode is disabled or when courses don\'t have category-specific credentials.';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Your Credentium API key for authentication.';
$string['apikey_global'] = 'API Key';
$string['apikey_global_help'] = 'Your Credentium API key for authentication. This key is stored encrypted in the database.';
$string['testconnection'] = 'Test Connection';
$string['testconnection_heading'] = 'Connection Test';
$string['testconnection_desc'] = 'After entering your API URL and API Key above, click the button below to test the connection to Credentium API.';
$string['testconnection_disabled'] = 'Please save your API URL and API Key before testing the connection.';
$string['testconnection_category_prefix'] = 'Category: {$a}';
$string['testconnection_category_notfound'] = 'Category configuration not found or not enabled.';
$string['testconnection_global_config'] = 'Global Configuration';
$string['testconnection_credentials_source'] = 'Credentials Source:';
$string['testconnection_apiurl_label'] = 'API URL:';
$string['testconnection_apikey_label'] = 'API Key:';
$string['testconnection_notconfigured'] = 'Not configured';
$string['testconnection_attempting'] = 'Attempting to fetch templates from the API...';
$string['testconnection_found_templates'] = 'Found {$a} credential template(s).';
$string['testconnection_templates_heading'] = 'Templates Found:';
$string['testconnection_no_templates'] = 'The API connection was successful, but the server returned 0 templates. Please verify your API key has access to templates.';
$string['testconnection_debug_heading'] = 'Detailed Debug Information';
$string['connectionsuccessful'] = 'Connection to Credentium API successful!';
$string['connectionfailed'] = 'Connection to Credentium API failed: {$a}';

// Multi-tenant configuration.
$string['categorymode_heading'] = 'Multi-Tenant Configuration';
$string['categorymode_heading_desc'] = 'Enable per-category API credentials for multi-tenant deployments.';
$string['categorymode'] = 'Enable category mode';
$string['categorymode_desc'] = 'When enabled, you can configure separate API credentials for each course category, allowing multi-tenant deployments where different categories connect to different Credentium instances. When disabled, all courses use the global API credentials configured above.';
$string['categorymode_help'] = 'When enabled, you can configure separate API credentials for each course category, allowing multi-tenant deployments where different categories connect to different Credentium instances. A "Credentium Category Settings" link will appear in each category\'s settings page.';
$string['categorymode_info_heading'] = 'Category Mode is Enabled';
$string['categorymode_info_text'] = 'You need to configure API credentials for each course category separately. Navigate to any course category and access <strong>Credentium Category Settings</strong> to set up API URL and API Key for that category. The global credentials below are optional and will be used as a fallback when no category-specific credentials are configured.';

// Debug logging.
$string['debuglog_heading'] = 'Debug Logging';
$string['debuglog_heading_desc'] = 'Configure debug logging for troubleshooting and development.';
$string['debuglog'] = 'Enable debug logging';
$string['debuglog_desc'] = 'When enabled, the plugin will write detailed diagnostic information to the PHP error log. This is useful for troubleshooting issues but should be disabled in production environments to reduce log volume. Logs are written to your web server\'s error log (typically /var/log/apache2/error.log or /var/log/php-fpm/error.log).';
$string['debuglog_help'] = 'When enabled, detailed diagnostic information is written to the PHP error log (typically /var/log/apache2/error.log or /var/log/php-fpm/error.log). Useful for troubleshooting but should be disabled in production to reduce log volume.';

// Data retention (GDPR).
$string['dataretention'] = 'Data retention period';
$string['dataretention_help'] = 'How long to keep credential issuance records in the database before automatic deletion. Records older than this period will be permanently deleted by a scheduled task that runs daily at 2:00 AM (server time). This ensures GDPR compliance by implementing data minimization principles. Default: 365 days (1 year). Available units: days, weeks. Note: Since cleanup runs daily, the actual retention may be up to 24 hours longer than configured. Deletion applies to all issuance records regardless of status (issued, failed, or pending).';

// Course settings.
$string['coursesettings'] = 'Credentium Settings';
$string['courseenabled'] = 'Enable Credentium for this course';
$string['courseenabled_help'] = 'When enabled, students will receive Credentium credentials upon course completion.';
$string['credentialtemplate'] = 'Credential Template';
$string['credentialtemplate_help'] = 'Select the credential template to use for this course.';
$string['templaterequiresgrade'] = 'This template requires a grade';
$string['templaterequiresgrade_info'] = 'This credential template contains Learning Assessment claim(s), therefore the student\'s course grade will be included on the microcredential. The credential issuance will fail if the grade is not available.';
$string['templatenograderequired'] = 'This template does not contain Assessment claim(s)';
$string['templatenograderequired_info'] = 'This credential template does not contain Learning Assessment claim(s), so the course grade (even if available) will not be presented on the microcredential.';
$string['inherit_category'] = 'Use category API credentials';
$string['inherit_category_help'] = 'When enabled, this course will use API credentials configured at the category level (or global credentials if no category configuration is found). When disabled, you must configure course-specific API credentials.';
$string['categoryinfo'] = 'API Credentials Source';
$string['categoryinfo_global'] = 'This course will use the <strong>global API credentials</strong> configured in the plugin settings.';
$string['categoryinfo_inherited'] = 'This course will use API credentials from the <strong>{$a}</strong> category.';
$string['unknowncategory'] = 'Unknown Category';
$string['nocredentials_heading'] = 'Credentium Integration Not Configured';
$string['nocredentials_message'] = 'Credentium integration cannot be enabled for this course because API credentials have not been configured. Please contact your site administrator to configure Credentium API credentials either globally (Site Administration > Plugins > Local plugins > Credentium) or for this course\'s category (Category > Credentium Category Settings).';
$string['category_credentium_disabled_heading'] = 'Credentium Disabled for This Category';
$string['category_credentium_disabled'] = 'Credentium integration is disabled for the "{$a}" category. Please contact your site administrator to enable Credentium for this category at: Category > Credentium Category Settings.';

// Category settings.
$string['categorysettings'] = 'Credentium Category Settings';
$string['categorysettings_desc'] = 'Configure Credentium for this category. All courses in this category (and its subcategories) will inherit these settings.';
$string['category_enable_credentium'] = 'Enable Credentium for this category';
$string['category_enable_credentium_help'] = 'When enabled, Credentium integration becomes available for all courses in this category and its subcategories. When disabled, courses in this category cannot use Credentium (even if configured at the global level).';
$string['credentialsource'] = 'API Credentials Source';
$string['credentialsource_help'] = 'Choose whether to use the global API credentials configured at the site level, or configure custom API credentials specific to this category.';
$string['credentialsource_global'] = 'Use global API credentials';
$string['credentialsource_custom'] = 'Use custom API credentials for this category';
$string['globalcredentials_available'] = 'Global API credentials are configured';
$string['globalcredentials_available_desc'] = 'This category will use the global Credentium API credentials configured in Site Administration > Plugins > Local plugins > Credentium.';
$string['globalcredentials_notavailable'] = 'Global API credentials are not configured';
$string['globalcredentials_notavailable_desc'] = 'Global API credentials have not been configured. You must either configure them in Site Administration > Plugins > Local plugins > Credentium, or select "Use custom API credentials" above and configure category-specific credentials.';
$string['apicredentials'] = 'API Credentials';
$string['apiurl_help'] = 'The URL of the Credentium API endpoint for this category (e.g., https://api.credentium.com or https://tenant1.credentium.com).';
$string['apikey_help'] = 'Your Credentium API key for authentication. This key will be encrypted when saved.';
$string['operations'] = 'Operational Controls';
$string['paused'] = 'Pause all issuances';
$string['paused_help'] = 'When paused, no new credentials will be issued for courses using this category\'s configuration. Existing pending credentials will remain pending until unpaused.';
$string['ratelimit'] = 'Rate limit (credentials per hour)';
$string['ratelimit_help'] = 'Maximum number of credentials that can be issued per hour for all courses using this category\'s configuration. Leave empty for no limit.';

// Roles.
$string['credentiumcoursemanager'] = 'Credentium Course Manager';
$string['credentiumcoursemanager_desc'] = 'Can configure Credentium integration for courses. Assign this role to users at course or category level to grant them permission to enable and configure digital credentials.';

// Capabilities.
$string['credentium:manage'] = 'Manage Credentium settings';
$string['credentium:managecourse'] = 'Manage course Credentium settings';
$string['credentium:managecategory'] = 'Manage category Credentium settings';
$string['credentium:viewreports'] = 'View Credentium reports';
$string['credentium:viewowncredentials'] = 'View own credentials';

// Events.
$string['eventcredentialissued'] = 'Credential issued';
$string['eventcredentialfailed'] = 'Credential issuance failed';
$string['eventapierror'] = 'Credentium API error';

// Report.
$string['report'] = 'Credentium Report';
$string['issuancehistory'] = 'Credential Issuance History';
$string['user'] = 'User';
$string['course'] = 'Course';
$string['credentialid'] = 'Credential ID';
$string['status'] = 'Status';
$string['issuedate'] = 'Issue Date';
$string['actions'] = 'Actions';
$string['retry'] = 'Retry';
$string['view'] = 'View';
$string['export'] = 'Export';
$string['grade'] = 'Grade';

// Statuses.
$string['status_pending'] = 'Pending';
$string['status_issued'] = 'Issued';
$string['status_failed'] = 'Failed';
$string['status_retrying'] = 'Retrying';

// Errors.
$string['error:apinotconfigured'] = 'Credentium API is not properly configured.';
$string['error:coursenotfound'] = 'Course not found.';
$string['error:invalidtemplate'] = 'Invalid credential template selected.';
$string['error:issuancefailed'] = 'Failed to issue credential: {$a}';
$string['error:nopermission'] = 'You do not have permission to access this page.';
$string['error:notenabled'] = 'Credentium integration is not enabled.';
$string['error:categorymodedisabled'] = 'Category mode is not enabled. Please enable it in the global Credentium settings first.';
$string['error:invalidapiurl'] = 'Invalid API URL provided.';
$string['error:invalidratelimit'] = 'Rate limit must be a positive integer.';
$string['invalidapiurl'] = 'Invalid API URL provided.';

// Tasks.
$string['task:issueCredentials'] = 'Issue pending credentials';
$string['task:retryFailedIssuances'] = 'Retry failed credential issuances';
$string['task:syncTemplates'] = 'Synchronize credential templates';
$string['task:processpending'] = 'Process pending credentials';
$string['task:cleanupoldissuances'] = 'Clean up old credential issuance records (GDPR)';

// Template selection.
$string['selecttemplate'] = 'Select a template...';
$string['notemplates'] = 'No templates available';
$string['refreshtemplates'] = 'Refresh templates';
$string['sendgrade'] = 'Send grade with credential';
$string['sendgrade_help'] = 'When enabled, the student\'s final course grade will be included with the credential. If grade aggregation is still in progress when the course is completed, the system will retry to ensure the correct grade is included.';
$string['issuanceinfo'] = 'Automatic Credential Issuance';
$string['issuanceinfo_desc'] = '<strong>Important:</strong> When Credentium is enabled for this course, microcredentials will be automatically issued to all students who complete the course. Please ensure you have selected the appropriate credential template before enabling.';
$string['issuanceinfo_help'] = 'Credentials are issued automatically upon course completion. You can choose whether to include the course grade with the credential using the option above.';
$string['templaterefreshed'] = 'Templates refreshed successfully';

// Bulk operations.
$string['selectstudents'] = 'Select students';
$string['issueselected'] = 'Issue to selected students';
$string['bulkissuanceinitiated'] = 'Bulk credential issuance initiated for {$a} students.';

// Notifications.
$string['notification:credentialissued'] = 'Your credential for {$a} has been issued successfully!';
$string['notification:credentialfailed'] = 'There was an error issuing your credential for {$a}. We will retry automatically.';

// Course completion settings.
$string['completionrequired'] = 'Course completion required';
$string['completionrequired_help'] = 'Students must complete the course before receiving credentials.';
$string['graderequired'] = 'Minimum grade required';
$string['graderequired_help'] = 'Students must achieve this grade or higher to receive credentials.';

// View credential.
$string['viewcredential'] = 'View Credential';
$string['credentialdetails'] = 'Credential Details';
$string['viewcredentialexternal'] = 'View Credential on Credentium';
$string['retryscheduled'] = 'Retry has been scheduled.';
$string['recordnotfound'] = 'Record not found.';
$string['processcredentials'] = 'Process Credentials';
$string['processingcredentials'] = 'Processing Pending Credentials';
$string['nopendingcredentials'] = 'No pending credentials to process.';
$string['processingcount'] = 'Processing {$a} pending credential(s)...';
$string['backtoreport'] = 'Back to Report';
$string['processpending'] = 'Process {$a} Pending Credential(s)';
$string['refresh'] = 'Refresh';
$string['searchuser'] = 'Search user by name or email';
$string['all'] = 'All';
$string['search'] = 'Search';
$string['filter'] = 'Filter';
$string['reset'] = 'Reset';
$string['downloadcsv'] = 'Download as CSV';
$string['downloadexcel'] = 'Download as Excel';
$string['summary'] = 'Summary';
$string['total'] = 'Total';
$string['inprogress'] = 'In Progress';