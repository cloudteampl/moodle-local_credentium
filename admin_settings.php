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

require_login();
require_capability('moodle/site:config', context_system::instance());

admin_externalpage_setup('local_credentium');

$PAGE->set_url('/local/credentium/admin_settings.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_credentium'));
$PAGE->set_heading(get_string('settings', 'local_credentium'));

/**
 * Global settings form.
 */
class local_credentium_admin_settings_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        // Introduction text.
        $mform->addElement('static', 'intro', '',
            '<div class="alert alert-info">' .
            get_string('settings_desc', 'local_credentium') .
            '</div>');

        // Enable/disable plugin.
        $mform->addElement('checkbox', 'enabled', get_string('enabled', 'local_credentium'));
        $mform->addHelpButton('enabled', 'enabled', 'local_credentium');

        // API credentials section.
        $mform->addElement('header', 'apicredentials', get_string('apicredentials', 'local_credentium'));

        // API URL.
        $mform->addElement('text', 'apiurl', get_string('apiurl', 'local_credentium'), ['size' => 60]);
        $mform->setType('apiurl', PARAM_URL);
        $mform->addHelpButton('apiurl', 'apiurl_global', 'local_credentium');
        $mform->disabledIf('apiurl', 'enabled', 'notchecked');

        // API Key.
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'local_credentium'), ['size' => 60]);
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey_global', 'local_credentium');
        $mform->disabledIf('apikey', 'enabled', 'notchecked');

        // Test connection button with validation.
        $testurl = new moodle_url('/local/credentium/testconnection.php', ['sesskey' => sesskey()]);

        // JavaScript to validate before opening test connection
        $onclick = <<<JS
(function() {
    var apiurl = document.querySelector('input[name="apiurl"]');
    var apikey = document.querySelector('input[name="apikey"]');

    if (!apiurl || !apiurl.value.trim()) {
        alert('Please enter an API URL before testing the connection.');
        if (apiurl) apiurl.focus();
        return false;
    }

    if (!apikey || !apikey.value.trim()) {
        alert('Please enter an API Key before testing the connection.');
        if (apikey) apikey.focus();
        return false;
    }

    window.open('{$testurl->out(false)}', '_blank');
})();
return false;
JS;

        $mform->addElement('button', 'testconnection', get_string('testconnection', 'local_credentium'),
            ['onclick' => $onclick]);

        // Multi-tenant configuration section.
        $mform->addElement('header', 'multitenant', get_string('categorymode_heading', 'local_credentium'));

        $mform->addElement('checkbox', 'categorymode', get_string('categorymode', 'local_credentium'));
        $mform->addHelpButton('categorymode', 'categorymode', 'local_credentium');

        // Debug logging section.
        $mform->addElement('header', 'debugsettings', get_string('debuglog_heading', 'local_credentium'));

        $mform->addElement('checkbox', 'debuglog', get_string('debuglog', 'local_credentium'));
        $mform->addHelpButton('debuglog', 'debuglog', 'local_credentium');

        // Buttons.
        $this->add_action_buttons(false, get_string('savechanges'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['enabled'])) {
            // Validate API URL
            if (empty($data['apiurl'])) {
                $errors['apiurl'] = get_string('required');
            } else if (!filter_var($data['apiurl'], FILTER_VALIDATE_URL)) {
                $errors['apiurl'] = get_string('error:invalidapiurl', 'local_credentium');
            }

            // Validate API Key
            if (empty($data['apikey'])) {
                $errors['apikey'] = get_string('required');
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
$config->apikey = get_config('local_credentium', 'apikey');
$config->categorymode = get_config('local_credentium', 'categorymode');
$config->debuglog = get_config('local_credentium', 'debuglog');

$mform->set_data($config);

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', ['section' => 'localplugins']));
} else if ($data = $mform->get_data()) {
    // Save settings
    set_config('enabled', !empty($data->enabled) ? 1 : 0, 'local_credentium');
    set_config('apiurl', !empty($data->apiurl) ? trim($data->apiurl) : '', 'local_credentium');
    set_config('apikey', !empty($data->apikey) ? $data->apikey : '', 'local_credentium');
    set_config('categorymode', !empty($data->categorymode) ? 1 : 0, 'local_credentium');
    set_config('debuglog', !empty($data->debuglog) ? 1 : 0, 'local_credentium');

    // Clear caches
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
echo $mform->render();
echo $OUTPUT->footer();
