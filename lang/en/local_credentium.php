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
$string['privacy:metadata'] = 'The Credentium® Integration plugin stores information locally about credential issuances and transmits user data to the external Credentium® service (paid third-party API).';

// Local database storage.
$string['privacy:metadata:local_credentium_issuances'] = 'Information about credentials issued to users.';
$string['privacy:metadata:local_credentium_issuances:userid'] = 'The ID of the user who received the credential.';
$string['privacy:metadata:local_credentium_issuances:courseid'] = 'The ID of the course for which the credential was issued.';
$string['privacy:metadata:local_credentium_issuances:credentialid'] = 'The external ID of the credential in Credentium.';
$string['privacy:metadata:local_credentium_issuances:status'] = 'The status of the credential issuance.';
$string['privacy:metadata:local_credentium_issuances:timecreated'] = 'The time when the credential was issued.';

// External service - Credentium API (PAID commercial service).
$string['privacy:metadata:credentium_api'] = 'The Credentium® API is a paid third-party service (https://credentium.com) for issuing digital credentials. User data is transmitted to this external service.';
$string['privacy:metadata:credentium_api:email'] = 'The user\'s email address is sent to Credentium® to identify the credential recipient.';
$string['privacy:metadata:credentium_api:firstname'] = 'The user\'s first name is sent to Credentium® for credential personalization.';
$string['privacy:metadata:credentium_api:lastname'] = 'The user\'s last name is sent to Credentium® for credential personalization.';
$string['privacy:metadata:credentium_api:coursename'] = 'The course name is sent to Credentium® to identify the course the credential relates to.';
$string['privacy:metadata:credentium_api:grade'] = 'The user\'s course grade (if enabled) is sent to Credentium® to be included on the credential.';
$string['privacy:metadata:credentium_api:templateid'] = 'The credential template ID is sent to Credentium® to specify which credential design to use.';

// Settings.
$string['settings'] = 'Credentium Settings';
$string['globalsettings'] = 'Credentium Global Settings';
$string['settings_desc'] = 'Configure the connection to Credentium API for issuing microcredentials.';
$string['pluginversion'] = 'Plugin Version';
$string['pluginversion_info'] = 'Version <strong>{$a->release}</strong> (build {$a->version})';
$string['enabled'] = 'Enable Credentium Integration';
$string['enabled_desc'] = 'Enable or disable the Credentium integration globally.';
$string['enabled_help'] = 'When enabled, the plugin will automatically issue digital credentials to students upon course completion (if the course is also individually configured).';
$string['apiurl'] = 'API URL';
$string['apiurl_desc'] = 'The URL of the Credentium API endpoint.';
$string['apiurl_global'] = 'API URL';
$string['apiurl_global_help'] = 'The URL of the Credentium API endpoint (e.g., https://api.credentium.com). This is used by default when category mode is disabled or when courses don\'t have category-specific credentials.';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Your Credentium API key for authentication.';
$string['apikey_global'] = 'API Key';
$string['apikey_global_help'] = 'Your Credentium API key for authentication. The key is stored encrypted in the database.';
$string['testconnection'] = 'Test Connection';
$string['testconnection_heading'] = 'Connection Test';
$string['testconnection_desc'] = 'After entering your API URL and API Key above, click the button below to test the connection to Credentium API.';
$string['testconnection_disabled'] = 'Save the API URL and API Key before testing the connection.';
$string['testconnection_category_prefix'] = 'Category: {$a}';
$string['testconnection_category_notfound'] = 'Category configuration not found or not enabled.';
$string['testconnection_global_config'] = 'Global configuration';
$string['testconnection_credentials_source'] = 'Credentials source:';
$string['testconnection_apiurl_label'] = 'API URL:';
$string['testconnection_apikey_label'] = 'API Key:';
$string['testconnection_notconfigured'] = 'Not configured';
$string['testconnection_attempting'] = 'Attempting to fetch templates from API...';
$string['testconnection_found_templates'] = 'Found {$a} credential template(s).';
$string['testconnection_templates_heading'] = 'Found templates:';
$string['testconnection_no_templates'] = 'Connection to API successful, but the server returned 0 templates. Check that your API key has access to templates.';
$string['testconnection_debug_heading'] = 'Detailed debug information';
$string['connectionsuccessful'] = 'Connection to Credentium API successful!';
$string['connectionfailed'] = 'Connection to Credentium API failed: {$a}';

// Multi-tenant configuration.
$string['categorymode_heading'] = 'Multi-Tenant Configuration';
$string['categorymode_heading_desc'] = 'Enable per-category API credentials for multi-tenant deployments.';
$string['categorymode'] = 'Enable category mode';
$string['categorymode_desc'] = 'When enabled, you can configure separate API credentials for each course category, allowing multi-tenant deployments where different categories connect to different Credentium instances. When disabled, all courses use the global API credentials configured above.';
$string['categorymode_help'] = 'When enabled, you can configure separate API credentials for each course category, allowing multi-tenant deployments where different categories connect to different Credentium instances. A "Credentium Category Settings" link will appear on each category\'s settings page.';
$string['categorymode_info_heading'] = 'Category mode is enabled';
$string['categorymode_info_text'] = 'You need to configure API credentials for each course category separately. Go to any course category and access <strong>Credentium Category Settings</strong> to configure the API URL and API Key for that category. The global credentials below are optional and will be used as fallback when no category-specific credentials are configured.';

// Debug logging.
$string['debuglog_heading'] = 'Debug Logging';
$string['debuglog_heading_desc'] = 'Configure debug logging for troubleshooting and development.';
$string['debuglog'] = 'Enable debug logging';
$string['debuglog_desc'] = 'When enabled, the plugin will write detailed diagnostic information to the PHP error log. This is useful for troubleshooting issues but should be disabled in production environments to reduce log volume. Logs are written to your web server\'s error log (typically /var/log/apache2/error.log or /var/log/php-fpm/error.log).';
$string['debuglog_help'] = 'When enabled, detailed diagnostic information is written to the PHP error log (typically /var/log/apache2/error.log or /var/log/php-fpm/error.log). Useful for troubleshooting but should be disabled in production environments to reduce log volume.';

// Data retention (GDPR).
$string['dataretention'] = 'Data retention period';
$string['dataretention_help'] = 'How long to keep credential issuance records in the database before automatic deletion. Records older than this period will be permanently deleted by a scheduled task that runs daily at 2:00 AM (server time). This ensures GDPR compliance by implementing data minimization principles. Default: 365 days (1 year). Available units: days, weeks. Note: Since cleanup runs daily, actual retention may be up to 24 hours longer than configured. Deletion applies to all credential issuance records regardless of status (issued, failed, or pending).';

// Course settings.
$string['coursesettings'] = 'Credentium Settings';
$string['courseenabled'] = 'Enable Credentium for this course';
$string['courseenabled_help'] = 'When enabled, students will receive Credentium credentials upon course completion.';
$string['credentialtemplate'] = 'Credential Template';
$string['credentialtemplate_help'] = 'Select the credential template to use for this course.';
$string['templaterequiresgrade'] = 'This template requires a grade';
$string['templaterequiresgrade_info'] = 'This credential template contains Learning Assessment claim(s), therefore the student\'s course grade will be included on the microcredential. Credential issuance will fail if no grade is available.';
$string['templatenograderequired'] = 'This template does not contain grade claims';
$string['templatenograderequired_info'] = 'This credential template does not contain Learning Assessment claims, so the course grade (even if available) will not be presented on the microcredential.';
$string['inherit_category'] = 'Use category API credentials';
$string['inherit_category_help'] = 'When enabled, this course will use API credentials configured at the category level (or global credentials if no category configuration is found). When disabled, you must configure course-specific API credentials.';
$string['categoryinfo'] = 'API Credentials Source';
$string['categoryinfo_global'] = 'This course will use the <strong>global API credentials</strong> configured in the plugin settings.';
$string['categoryinfo_inherited'] = 'This course will use API credentials from the <strong>{$a}</strong> category.';
$string['unknowncategory'] = 'Unknown Category';
$string['nocredentials_heading'] = 'Credentium integration not configured';
$string['nocredentials_message'] = 'Credentium integration cannot be enabled for this course because no API credentials have been configured. Contact the site administrator to configure Credentium API credentials globally (Site Administration > Plugins > Local plugins > Credentium) or for this course\'s category (Category > Credentium Category Settings).';
$string['category_credentium_disabled_heading'] = 'Credentium disabled for this category';
$string['category_credentium_disabled'] = 'Credentium integration is disabled for the "{$a}" category. Contact the site administrator to enable Credentium for this category at: Category > Credentium Category Settings.';

// Category settings.
$string['categorysettings'] = 'Credentium Category Settings';
$string['categorysettings_desc'] = 'Configure Credentium for this category. All courses in this category (and its subcategories) will inherit these settings.';
$string['category_enable_credentium'] = 'Enable Credentium for this category';
$string['category_enable_credentium_help'] = 'When enabled, Credentium integration becomes available for all courses in this category and its subcategories. When disabled, courses in this category cannot use Credentium (even if configured globally).';
$string['credentialsource'] = 'API Credentials Source';
$string['credentialsource_help'] = 'Choose whether to use global API credentials configured at the site level, or configure custom API credentials specific to this category.';
$string['credentialsource_global'] = 'Use global API credentials';
$string['credentialsource_custom'] = 'Use custom API credentials for this category';
$string['globalcredentials_available'] = 'Global API credentials are configured';
$string['globalcredentials_available_desc'] = 'This category will use the global Credentium API credentials configured at Site Administration > Plugins > Local plugins > Credentium® Integration.';
$string['globalcredentials_notavailable'] = 'Global API credentials are not configured';
$string['globalcredentials_notavailable_desc'] = 'Global API credentials have not been configured. You must either configure them at Site Administration > Plugins > Local plugins > Credentium® Integration, or select "Use custom API credentials" above and configure category-specific credentials.';
$string['categoryenabled'] = 'Enable Credentium for this category';
$string['categoryenabled_help'] = 'When enabled, courses in this category can use these API credentials. Each course must still be individually enabled in its course settings.';
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
$string['messageprovider:credentialissuance'] = 'Digital credential notifications';
$string['notification:credentialissued_subject'] = 'You\'ve earned a digital credential';
$string['notification:credentialissued'] = 'Congratulations! You have completed {$a} and we have requested your digital credential.

Your credential is normally ready within 15 minutes. You will receive a separate notification as soon as it is ready to claim.';
$string['notification:credentialfailed_subject'] = 'Problem with your digital credential';
$string['notification:credentialfailed'] = 'We were unable to request your digital credential for {$a}.

Please contact your teacher or the site administrator so they can look into it.';

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

// Exception messages the API client raises (identifiers without a lang string
// would render as placeholders on error pages).
$string['apierror'] = 'Credentium® API error: {$a}';
$string['apinotconfigured'] = 'The Credentium® API is not configured.';
$string['invalidjsonresponse'] = 'The Credentium® API returned an invalid response.';
$string['invalidresponse'] = 'The Credentium® API returned an unexpected response.';
$string['issuancefailed'] = 'Failed to issue the credential: {$a}';

// Claim feature (absorbed from the former Credentium® Claim plugin in v3.0.0).
$string['claimsettings_heading'] = 'Claim reminders';
$string['claimsettings_desc'] = 'Check with Credentium® whether issued credentials have been claimed, and remind learners — by notification, user-menu entry and banner — about any credential still waiting to be collected.';
$string['claimenabled'] = 'Enable claim status checks and reminders';
$string['claimenabled_help'] = 'When enabled, the plugin periodically checks the claim status of issued credentials and shows learners a reminder to claim any credential that is still unclaimed.';
$string['maintenance_heading'] = 'Diagnostics and data retention';
$string['syncinterval'] = 'Status check interval';
$string['syncinterval_custom'] = 'Custom (keep current schedule)';
$string['syncinterval_help'] = 'How often the scheduled task asks Credentium® whether issued credentials are ready to claim or have been claimed. Shorter intervals keep statuses fresher at the cost of more API calls, and are useful while testing. Changing this rewrites the schedule of the "Synchronise Credentium credential claim statuses" task, which can also be edited under Server > Scheduled tasks.';
$string['showbanner'] = 'Show reminder banner';
$string['showbanner_help'] = 'When enabled, a dismissible banner is shown at the top of every page to learners who have an unclaimed credential. When disabled, learners can still claim credentials from the "My credentials" page.';
$string['uninstalllegacyclaim'] = 'The separate Credentium® Claim plugin (local_credentiumclaim) is still installed. Its functionality is built into this plugin since version 3.0.0 and its data and settings have been migrated, so it has been disabled — please uninstall it under Site administration > Plugins > Plugins overview.';

// Claim: learner-facing surfaces.
$string['banner_cta'] = 'Claim now';
$string['banner_dismiss'] = 'Dismiss';
$string['banner_message'] = 'Credentium® has issued you {$a} digital credential(s) that you have not claimed yet.';
$string['banner_title'] = 'You have a new digital credential to claim';
$string['cachedef_claimable'] = 'Per-user count of unclaimed Credentium® credentials';
$string['claim'] = 'Claim';
$string['claim_alreadyclaimed'] = 'You have already claimed this credential. It is available in your Credentium® Wallet.';
$string['claim_backtolist'] = 'Back to my credentials';
$string['claim_error'] = 'We could not open the claim link right now. Please try again later.';
$string['claim_heading'] = 'Claim your credential';
$string['claim_notready'] = 'This credential is still being prepared. Please try again in a few minutes.';
$string['col_action'] = 'Action';
$string['col_course'] = 'Course';
$string['col_status'] = 'Status';
$string['connection'] = 'API connection';
$string['credentium:claim'] = 'Claim own Credentium® credentials';
$string['message_ready_body'] = 'Credentium® has issued you a digital credential that is ready to claim. Open "My credentials" to save it to your Credentium® Wallet.';
$string['message_ready_body_course'] = 'Credentium® has issued you a digital credential for "{$a->course}" that is ready to claim. Open "My credentials" to save it to your Credentium® Wallet.';
$string['message_ready_linktext'] = 'Go to My credentials';
$string['message_ready_small'] = 'You have a Credentium® credential ready to claim.';
$string['message_ready_subject'] = 'You have a credential to claim';
$string['messageprovider:credentialready'] = 'A digital credential is ready to claim';
$string['mycredentials'] = 'My credentials';
$string['mycredentials_empty'] = 'No Credentium® credentials have been issued to you yet.';
$string['mycredentials_heading'] = 'My Credentium® credentials';
$string['mycredentials_intro'] = 'These digital credentials have been issued to you. Click "Claim" to save one to your Credentium® Wallet. The ones you have already collected stay listed below — "View in wallet" opens any of them again.';
$string['nav_mycredentials'] = 'My credentials';
$string['nav_mycredentials_count'] = 'My credentials ({$a})';
$string['viewinwallet'] = 'View in wallet';

// Claim: status vocabulary (distinct from the issuance status_* strings above).
$string['claimstatus_claimed'] = 'Claimed';
$string['claimstatus_failed'] = 'Failed';
$string['claimstatus_issued'] = 'Ready to claim';
$string['claimstatus_processing'] = 'Processing';
$string['claimstatus_unknown'] = 'Unknown';

// Claim: status report and diagnostics.
$string['claimreport'] = 'Credentium® Claim status';
$string['report_checknow'] = 'Check status now';
$string['report_checknow_busy'] = 'A status check is already running (started by cron or another administrator). Try again in a moment.';
$string['report_checknow_failed'] = 'The status check could not be completed. Enable debug logging to see the reason, or check the last run outcome below.';
$string['report_connection_missing'] = 'No API credentials are configured, so claim statuses cannot be checked.';
$string['report_connection_ok'] = 'Configured ({$a})';
$string['report_count'] = 'Count';
$string['report_diagnostics'] = 'Diagnostics';
$string['report_error_auth'] = 'Credentium® refused the API key. In the plugin settings, check that the key is still valid and that it carries the "credentials:read" scope — a key that may only issue credentials cannot read their status.';
$string['report_error_busy'] = 'Credentium® asked the plugin to slow down, or timed out while answering. The plugin backs off and retries on its own, so no action is normally needed. If it happens on every run, raise the status check interval in the plugin settings.';
$string['report_error_client'] = 'Credentium® rejected the request itself. This normally means the plugin and the Credentium® API are out of step: check whether a plugin update is available, and quote the technical detail above if you contact support.';
$string['report_error_generic'] = 'The last status check failed. Quote the technical detail above if you contact support.';
$string['report_error_network'] = 'The Credentium® API could not be reached at all. Check that this server can make outbound HTTPS requests to the configured address — a proxy, a firewall rule or DNS is the usual cause.';
$string['report_error_pending'] = '{$a} tracked credential(s) could not be checked on this run; they keep their last known status and are re-checked automatically.';
$string['report_error_server'] = 'Credentium® accepted the request but could not complete it. This is a fault on the Credentium® side, not in this site\'s configuration. The plugin already retried, and will try again at the next scheduled check — statuses catch up on their own once the service recovers. If it keeps failing, contact Credentium® support and quote the technical detail above.';
$string['report_heading'] = 'Credentium® Claim status report';
$string['report_intro'] = 'Overview of tracked credential claim statuses across all learners.';
$string['report_lastresult'] = 'Last run outcome';
$string['report_lastrun'] = 'Last sync run';
$string['report_lastsync'] = 'Most recent credential check';
$string['report_never'] = 'Never';
$string['report_notified'] = 'Notifications sent on the last run';
$string['report_notifyfailed'] = 'Could not send {$a} "ready to claim" notification(s) on the last run (a learner may be suspended, or a delivery channel may have failed). Those learners still see the reminder in the user menu and banner when they log in.';
$string['report_plugindisabled'] = 'Claim status checks are disabled, so learners are not being reminded about unclaimed credentials.';
$string['report_property'] = 'Property';
$string['report_result_disabled'] = 'Skipped: claim status checks are disabled.';
$string['report_result_error'] = 'Failed: {$a}';
$string['report_result_notconfigured'] = 'Skipped: no API credentials are configured.';
$string['report_result_ok'] = 'Completed: {$a->polled} credential(s) checked, {$a->updated} updated.';
$string['report_schedule_custom'] = 'Custom schedule (edited under Server > Scheduled tasks)';
$string['report_schedule_every'] = 'Every {$a}';
$string['report_status'] = 'Status';
$string['report_tasklastrun'] = 'Scheduled task last ran';
$string['report_tasknextrun'] = 'Scheduled task next runs';
$string['report_total'] = 'Total tracked credentials';
$string['report_unmatched'] = 'Credentium® did not recognise {$a} tracked credential(s). This usually means the API key belongs to a different organisation than the one that issued them.';
$string['report_unresolved'] = '{$a} tracked credential(s) were skipped because no API credentials apply to their course.';
$string['report_value'] = 'Value';
$string['task_syncclaimstatus'] = 'Synchronise Credentium credential claim statuses';
$string['testconnection_readscope_error'] = 'Status-read access could not be verified right now. This may be a temporary problem rather than a configuration one. Details: {$a}';
$string['testconnection_readscope_fail'] = 'The API key was accepted but cannot read credential statuses. The claim feature needs the credentials:read scope on top of the scopes issuing uses — add it to the key in Credentium®. Details: {$a}';
$string['testconnection_readscope_ok'] = 'The API key can read credential statuses (credentials:read scope present).';

// Claim: errors.
$string['error:claimapifailed'] = 'The Credentium® API returned an error.';
$string['error:claimnotconfigured'] = 'Claim reminders are not enabled. Check the Credentium® plugin settings.';
$string['error:credentialnotfound'] = 'The requested credential could not be found.';
$string['error:invalidjsonresponse'] = 'The Credentium® API returned an invalid response.';

// Claim: privacy.
$string['privacy:metadata:credentium_api:issuerequestid'] = 'The Credentium® issue-request identifier for the credential being checked or claimed.';
$string['privacy:metadata:credentium_api:locale'] = 'The language in which the claim experience should be presented.';
$string['privacy:metadata:local_credentium_claims'] = 'Tracking information about issued Credentium® credentials and whether the learner has claimed them.';
$string['privacy:metadata:local_credentium_claims:courseid'] = 'The ID of the course the credential was issued for.';
$string['privacy:metadata:local_credentium_claims:credentialid'] = 'The Credentium® identifier of the credential itself, kept so a support query can be matched to Credentium®\'s records.';
$string['privacy:metadata:local_credentium_claims:credentialkey'] = 'The Credentium® issue-request identifier for the credential.';
$string['privacy:metadata:local_credentium_claims:remotestatus'] = 'The last known claim status of the credential.';
$string['privacy:metadata:local_credentium_claims:timecreated'] = 'The time the tracking row was created.';
$string['privacy:metadata:local_credentium_claims:userid'] = 'The ID of the user the credential belongs to.';
