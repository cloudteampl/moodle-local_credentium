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
$string['privacy:metadata'] = 'The Credentium® Integration plugin stores information about credential issuances.';
$string['privacy:metadata:local_credentium_issuances'] = 'Information about credentials issued to users.';
$string['privacy:metadata:local_credentium_issuances:userid'] = 'The ID of the user who received the credential.';
$string['privacy:metadata:local_credentium_issuances:courseid'] = 'The ID of the course for which the credential was issued.';
$string['privacy:metadata:local_credentium_issuances:credentialid'] = 'The external ID of the credential in Credentium.';
$string['privacy:metadata:local_credentium_issuances:status'] = 'The status of the credential issuance.';
$string['privacy:metadata:local_credentium_issuances:timecreated'] = 'The time when the credential was issued.';

// Settings.
$string['settings'] = 'Credentium Settings';
$string['settings_desc'] = 'Configure the connection to Credentium API for issuing microcredentials.';
$string['pluginversion'] = 'Plugin Version';
$string['pluginversion_info'] = 'Version <strong>{$a->release}</strong> (build {$a->version})';
$string['enabled'] = 'Enable Credentium Integration';
$string['enabled_desc'] = 'Enable or disable the Credentium integration globally.';
$string['apiurl'] = 'API URL';
$string['apiurl_desc'] = 'The URL of the Credentium API endpoint.';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Your Credentium API key for authentication.';
$string['testconnection'] = 'Test Connection';
$string['testconnection_heading'] = 'Connection Test';
$string['testconnection_desc'] = 'After entering your API URL and API Key above, click the button below to test the connection to Credentium API.';
$string['connectionsuccessful'] = 'Connection to Credentium API successful!';
$string['connectionfailed'] = 'Connection to Credentium API failed: {$a}';

// Multi-tenant configuration.
$string['categorymode_heading'] = 'Multi-Tenant Configuration';
$string['categorymode_heading_desc'] = 'Enable per-category API credentials for multi-tenant deployments.';
$string['categorymode'] = 'Enable category mode';
$string['categorymode_desc'] = 'When enabled, you can configure separate API credentials for each course category, allowing multi-tenant deployments where different categories connect to different Credentium instances. When disabled, all courses use the global API credentials configured above.';

// Debug logging.
$string['debuglog_heading'] = 'Debug Logging';
$string['debuglog_heading_desc'] = 'Configure debug logging for troubleshooting and development.';
$string['debuglog'] = 'Enable debug logging';
$string['debuglog_desc'] = 'When enabled, the plugin will write detailed diagnostic information to the PHP error log. This is useful for troubleshooting issues but should be disabled in production environments to reduce log volume. Logs are written to your web server\'s error log (typically /var/log/apache2/error.log or /var/log/php-fpm/error.log).';

// Course settings.
$string['coursesettings'] = 'Credentium Settings';
$string['courseenabled'] = 'Enable Credentium for this course';
$string['courseenabled_help'] = 'When enabled, students will receive Credentium credentials upon course completion.';
$string['credentialtemplate'] = 'Credential Template';
$string['credentialtemplate_help'] = 'Select the credential template to use for this course.';
$string['inherit_category'] = 'Use category API credentials';
$string['inherit_category_help'] = 'When enabled, this course will use API credentials configured at the category level (or global credentials if no category configuration is found). When disabled, you must configure course-specific API credentials.';
$string['categoryinfo'] = 'API Credentials Source';
$string['categoryinfo_global'] = 'This course will use the <strong>global API credentials</strong> configured in the plugin settings.';
$string['categoryinfo_inherited'] = 'This course will use API credentials from the <strong>{$a}</strong> category.';
$string['unknowncategory'] = 'Unknown Category';

// Category settings.
$string['categorysettings'] = 'Credentium Category Settings';
$string['categorysettings_desc'] = 'Configure Credentium API credentials for this category. All courses in this category (and its subcategories) can inherit these settings.';
$string['categoryenabled'] = 'Enable Credentium for this category';
$string['categoryenabled_help'] = 'When enabled, courses in this category can use these API credentials. Each course must still be individually enabled in its course settings.';
$string['apicredentials'] = 'API Credentials';
$string['operations'] = 'Operational Controls';
$string['paused'] = 'Pause all issuances';
$string['paused_help'] = 'When paused, no new credentials will be issued for courses using this category\'s configuration. Existing pending credentials will remain pending until unpaused.';
$string['ratelimit'] = 'Rate limit (credentials per hour)';
$string['ratelimit_help'] = 'Maximum number of credentials that can be issued per hour for all courses using this category\'s configuration. Leave empty for no limit.';

// Capabilities.
$string['credentium:manage'] = 'Manage Credentium settings';
$string['credentium:managecourse'] = 'Manage course Credentium settings';
$string['credentium:managecategory'] = 'Manage category Credentium settings';
$string['credentium:viewreports'] = 'View Credentium reports';

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
$string['error:template_multiple_assessments'] = 'Selected template has multiple Learning Assessments and is not supported. Please choose a template with at most 1 Learning Assessment.';
$string['invalidapiurl'] = 'Invalid API URL provided.';

// Template warnings.
$string['template_unsupported_suffix'] = '(unsupported)';
$string['template_unsupported_warning'] = 'This template has {$a} Learning Assessments. The plugin currently supports only templates with at most 1 Learning Assessment. Please select a different template.';

// Tasks.
$string['task:issueCredentials'] = 'Issue pending credentials';
$string['task:retryFailedIssuances'] = 'Retry failed credential issuances';
$string['task:syncTemplates'] = 'Synchronize credential templates';
$string['task:processpending'] = 'Process pending credentials';

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