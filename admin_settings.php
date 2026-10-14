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
 * Global admin settings page for Credentium integration.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

admin_externalpage_setup('local_credentium');

$PAGE->set_url('/local/credentium/admin_settings.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_credentium'));
$PAGE->set_heading(get_string('globalsettings', 'local_credentium'));

/**
 * Global settings form.
 */
class local_credentium_admin_settings_form extends moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        // Introduction text.
        $mform->addElement(
            'static',
            'intro',
            '',
            '<div class="alert alert-info">' .
            get_string('settings_desc', 'local_credentium') .
            '</div>'
        );

        // Enable/disable plugin.
        $mform->addElement('checkbox', 'enabled', get_string('enabled', 'local_credentium'));
        $mform->addHelpButton('enabled', 'enabled', 'local_credentium');

        // Enable category mode (moved here, right after main enable checkbox).
        $mform->addElement('checkbox', 'categorymode', get_string('categorymode', 'local_credentium'));
        $mform->addHelpButton('categorymode', 'categorymode', 'local_credentium');
        $mform->hideIf('categorymode', 'enabled', 'notchecked');

        // Show informative message about category mode (always add, but hide if not enabled).
        $mform->addElement(
            'static',
            'categorymode_info',
            '',
            '<div class="alert alert-warning" id="categorymode_info_alert">' .
            '<strong>' . get_string('categorymode_info_heading', 'local_credentium') . '</strong><br>' .
            get_string('categorymode_info_text', 'local_credentium') .
            '</div>'
        );
        $mform->hideIf('categorymode_info', 'enabled', 'notchecked');
        $mform->hideIf('categorymode_info', 'categorymode', 'notchecked');

        // API URL.
        $mform->addElement('text', 'apiurl', get_string('apiurl', 'local_credentium'), ['size' => 60]);
        $mform->setType('apiurl', PARAM_URL);
        $mform->addHelpButton('apiurl', 'apiurl_global', 'local_credentium');
        $mform->hideIf('apiurl', 'enabled', 'notchecked');

        // API Key. Write-only: the stored key is never sent back into the form
        // HTML — an empty submission keeps it, a value replaces it.
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'local_credentium'), ['size' => 60]);
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey_global', 'local_credentium');
        $mform->hideIf('apikey', 'enabled', 'notchecked');
        if (local_credentium_get_global_apikey() !== '') {
            $mform->addElement(
                'static',
                'apikeykeep',
                '',
                html_writer::div(get_string('apikey_keep', 'local_credentium'), 'form-text text-muted')
            );
            $mform->hideIf('apikeykeep', 'enabled', 'notchecked');
        }

        // Check if API credentials are saved in the database.
        $savedapiurl = get_config('local_credentium', 'apiurl');
        $savedapikey = get_config('local_credentium', 'apikey');
        $credentialssaved = !empty($savedapiurl) && !empty($savedapikey);

        // Test connection button.
        if ($credentialssaved) {
            $testurl = new moodle_url('/local/credentium/testconnection.php', ['sesskey' => sesskey()]);
            $onclick = "window.open('{$testurl->out(false)}', '_blank'); return false;";
            $mform->addElement(
                'button',
                'testconnection',
                get_string('testconnection', 'local_credentium'),
                ['onclick' => $onclick]
            );
        } else {
            // JavaScript alert when clicking disabled button.
            $alertmessage = get_string('testconnection_disabled', 'local_credentium');
            $onclick = "alert(" . json_encode($alertmessage) . "); return false;";
            $mform->addElement(
                'button',
                'testconnection',
                get_string('testconnection', 'local_credentium'),
                ['onclick' => $onclick]
            );
        }
        $mform->hideIf('testconnection', 'enabled', 'notchecked');

        // Person identifier sent with each issuance (optional).
        $mform->addElement(
            'select',
            'externalidfield',
            get_string('externalidfield', 'local_credentium'),
            \local_credentium\local\person_external_id::options()
        );
        $mform->setType('externalidfield', PARAM_ALPHA);
        $mform->addHelpButton('externalidfield', 'externalidfield', 'local_credentium');
        $mform->hideIf('externalidfield', 'enabled', 'notchecked');

        // Claim reminders (absorbed from the former Credentium Claim plugin).
        $mform->addElement('header', 'claimsettings', get_string('claimsettings_heading', 'local_credentium'));
        $mform->setExpanded('claimsettings', true);
        $mform->addElement(
            'static',
            'claimintro',
            '',
            '<div class="alert alert-info">' .
            get_string('claimsettings_desc', 'local_credentium') .
            '</div>'
        );
        // The section header itself cannot be hidden via hideIf; hiding the intro
        // and every field leaves just the collapsed header while the master switch
        // is off, which is as close as moodleforms get.
        $mform->hideIf('claimintro', 'enabled', 'notchecked');

        $mform->addElement('checkbox', 'claimenabled', get_string('claimenabled', 'local_credentium'));
        $mform->addHelpButton('claimenabled', 'claimenabled', 'local_credentium');
        $mform->hideIf('claimenabled', 'enabled', 'notchecked');

        // How often the claim status-check task runs.
        $intervals = local_credentium_claim_sync_interval_options();
        if (local_credentium_get_claim_sync_interval() === null) {
            // The schedule was hand-edited under Server > Scheduled tasks; say so
            // rather than silently overwriting it with the nearest offered value.
            $intervals = ['' => get_string('syncinterval_custom', 'local_credentium')] + $intervals;
        }
        $mform->addElement('select', 'syncinterval', get_string('syncinterval', 'local_credentium'), $intervals);
        $mform->setType('syncinterval', PARAM_INT);
        $mform->addHelpButton('syncinterval', 'syncinterval', 'local_credentium');
        $mform->hideIf('syncinterval', 'enabled', 'notchecked');
        $mform->hideIf('syncinterval', 'claimenabled', 'notchecked');

        // Show the reminder banner.
        $mform->addElement('advcheckbox', 'showbanner', get_string('showbanner', 'local_credentium'));
        $mform->addHelpButton('showbanner', 'showbanner', 'local_credentium');
        $mform->setDefault('showbanner', 1);
        $mform->hideIf('showbanner', 'enabled', 'notchecked');
        $mform->hideIf('showbanner', 'claimenabled', 'notchecked');

        // Diagnostics and data retention.
        $mform->addElement('header', 'maintenancesettings', get_string('maintenance_heading', 'local_credentium'));
        $mform->setExpanded('maintenancesettings', true);

        // Debug logging.
        $mform->addElement('checkbox', 'debuglog', get_string('debuglog', 'local_credentium'));
        $mform->addHelpButton('debuglog', 'debuglog', 'local_credentium');
        $mform->hideIf('debuglog', 'enabled', 'notchecked');

        // Data retention (GDPR compliance).
        // Only allow days and weeks since cleanup runs daily at 2 AM.
        // Note: Moodle duration element doesn't support months or years as units.
        $mform->addElement(
            'duration',
            'dataretention',
            get_string('dataretention', 'local_credentium'),
            ['optional' => false, 'defaultunit' => DAYSECS, 'units' => [DAYSECS, WEEKSECS]]
        );
        $mform->addHelpButton('dataretention', 'dataretention', 'local_credentium');
        $mform->setDefault('dataretention', 365 * DAYSECS); // Default: 365 days.
        $mform->hideIf('dataretention', 'enabled', 'notchecked');

        // Action buttons.
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Server-side validation.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Field name => error message map.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['enabled'])) {
            // Only require global API credentials if category mode is disabled.
            // When category mode is enabled, credentials are configured per-category.
            $categorymode = !empty($data['categorymode']);

            if (!$categorymode) {
                // Validate API URL (required when not using category mode).
                if (empty($data['apiurl'])) {
                    $errors['apiurl'] = get_string('required');
                } else if (!filter_var($data['apiurl'], FILTER_VALIDATE_URL)) {
                    $errors['apiurl'] = get_string('error:invalidapiurl', 'local_credentium');
                }

                // Validate API Key: required only while no key is stored yet —
                // the field is write-only and an empty submission keeps the
                // saved key.
                if (empty($data['apikey']) && local_credentium_get_global_apikey() === '') {
                    $errors['apikey'] = get_string('required');
                }
            } else {
                // When category mode is enabled, validate URL format if provided (but not required).
                if (!empty($data['apiurl']) && !filter_var($data['apiurl'], FILTER_VALIDATE_URL)) {
                    $errors['apiurl'] = get_string('error:invalidapiurl', 'local_credentium');
                }
            }
        }

        return $errors;
    }
}

// Create form instance.
$mform = new local_credentium_admin_settings_form();

// Load existing config.
$config = new stdClass();
$config->enabled = get_config('local_credentium', 'enabled');
$config->apiurl = get_config('local_credentium', 'apiurl');
// The API key is deliberately NOT loaded into the form: the field is
// write-only, so the decrypted secret never round-trips through page HTML.
$config->categorymode = get_config('local_credentium', 'categorymode');
$config->externalidfield = (string) get_config('local_credentium', 'externalidfield');
$config->claimenabled = get_config('local_credentium', 'claimenabled');
// Same unset-means-on default the banner hook applies at render time.
$config->showbanner = local_credentium_show_banner() ? 1 : 0;
// Null means the cron schedule was hand-edited; '' selects the "custom" option.
$config->syncinterval = local_credentium_get_claim_sync_interval() ?? '';
$config->debuglog = get_config('local_credentium', 'debuglog');
$config->dataretention = get_config('local_credentium', 'dataretention');
// Set default if not configured yet.
if (empty($config->dataretention)) {
    $config->dataretention = 365 * DAYSECS;
}

$mform->set_data($config);

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/plugins.php', ['subtype' => 'local']));
} else if ($data = $mform->get_data()) {
    // Save settings.
    set_config('enabled', !empty($data->enabled) ? 1 : 0, 'local_credentium');
    set_config('apiurl', !empty($data->apiurl) ? trim($data->apiurl) : '', 'local_credentium');
    // Write-only field: an empty submission means "keep the saved key".
    if (!empty($data->apikey)) {
        local_credentium_set_global_apikey($data->apikey);
    }
    set_config('categorymode', !empty($data->categorymode) ? 1 : 0, 'local_credentium');
    set_config(
        'externalidfield',
        \local_credentium\local\person_external_id::normalise_field((string) ($data->externalidfield ?? '')),
        'local_credentium'
    );
    set_config('claimenabled', !empty($data->claimenabled) ? 1 : 0, 'local_credentium');
    set_config('showbanner', !empty($data->showbanner) ? 1 : 0, 'local_credentium');
    // An empty interval means "leave the hand-edited cron schedule alone".
    if (!empty($data->syncinterval)) {
        local_credentium_apply_claim_sync_interval((int) $data->syncinterval);
    }
    set_config('debuglog', !empty($data->debuglog) ? 1 : 0, 'local_credentium');
    set_config('dataretention', !empty($data->dataretention) ? (int)$data->dataretention : 365 * DAYSECS, 'local_credentium');

    // Clear caches.
    cache_helper::purge_by_definition('core', 'config');

    redirect(
        new moodle_url('/local/credentium/admin_settings.php'),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Output page.
echo $OUTPUT->header();

// The claim feature is built in since v3.0.0; a still-installed legacy Claim plugin
// was disabled by the upgrade, but only uninstalling it removes its dormant copy.
if (file_exists($CFG->dirroot . '/local/credentiumclaim/version.php')) {
    echo $OUTPUT->notification(
        get_string('uninstalllegacyclaim', 'local_credentium'),
        \core\output\notification::NOTIFY_WARNING
    );
}

echo $mform->render();
echo $OUTPUT->footer();
