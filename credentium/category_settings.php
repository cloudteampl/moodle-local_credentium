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
 * Category settings page for Credentium integration.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

$categoryid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$category = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);
$context = context_coursecat::instance($category->id);

require_login();
require_capability('local/credentium:managecategory', $context);

$PAGE->set_url('/local/credentium/category_settings.php', ['id' => $categoryid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('categorysettings', 'local_credentium'));
$PAGE->set_heading($category->name);
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('categorysettings', 'local_credentium'));

// Check if plugin is enabled globally.
if (!get_config('local_credentium', 'enabled')) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error:notenabled', 'local_credentium'), 'notifyproblem');
    echo $OUTPUT->footer();
    die();
}

// Check if category mode is enabled.
if (!get_config('local_credentium', 'categorymode')) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error:categorymodedisabled', 'local_credentium'), 'notifyproblem');
    echo $OUTPUT->footer();
    die();
}

/**
 * Category settings form.
 */
class local_credentium_category_settings_form extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;
        $categoryid = $this->_customdata['categoryid'];

        // Introduction text.
        $mform->addElement('static', 'intro', '',
            '<div class="alert alert-info">' .
            get_string('categorysettings_desc', 'local_credentium') .
            '</div>');

        // Step 1: Enable/Disable Credentium for this category.
        $mform->addElement('checkbox', 'enabled', get_string('category_enable_credentium', 'local_credentium'));
        $mform->addHelpButton('enabled', 'category_enable_credentium', 'local_credentium');

        // Step 2: Choose credential source (only visible when enabled).
        $global_apiurl = get_config('local_credentium', 'apiurl');
        $global_apikey = get_config('local_credentium', 'apikey');
        $global_configured = !empty($global_apiurl) && !empty($global_apikey);

        $credentialoptions = [];
        $credentialoptions[] = $mform->createElement('radio', 'credentialsource', '',
            get_string('credentialsource_global', 'local_credentium'), 'global');
        $credentialoptions[] = $mform->createElement('radio', 'credentialsource', '',
            get_string('credentialsource_custom', 'local_credentium'), 'custom');

        $mform->addGroup($credentialoptions, 'credentialsource_group',
            get_string('credentialsource', 'local_credentium'), '<br>', false);
        $mform->addHelpButton('credentialsource_group', 'credentialsource', 'local_credentium');
        $mform->setDefault('credentialsource', 'global');
        $mform->hideIf('credentialsource_group', 'enabled', 'notchecked');

        // Show global credentials status (when global is selected).
        if ($global_configured) {
            $mform->addElement('static', 'globalcredentials_info', '',
                '<div class="alert alert-success">' .
                '<strong>' . get_string('globalcredentials_available', 'local_credentium') . '</strong><br>' .
                get_string('globalcredentials_available_desc', 'local_credentium') .
                '</div>');
        } else {
            $mform->addElement('static', 'globalcredentials_info', '',
                '<div class="alert alert-warning">' .
                '<strong>' . get_string('globalcredentials_notavailable', 'local_credentium') . '</strong><br>' .
                get_string('globalcredentials_notavailable_desc', 'local_credentium') .
                '</div>');
        }
        $mform->hideIf('globalcredentials_info', 'enabled', 'notchecked');
        $mform->hideIf('globalcredentials_info', 'credentialsource', 'eq', 'custom');

        // Custom API credentials (when custom is selected).
        $mform->addElement('text', 'apiurl', get_string('apiurl', 'local_credentium'), ['size' => 60]);
        $mform->setType('apiurl', PARAM_URL);
        $mform->addHelpButton('apiurl', 'apiurl', 'local_credentium');
        $mform->hideIf('apiurl', 'enabled', 'notchecked');
        $mform->hideIf('apiurl', 'credentialsource', 'eq', 'global');

        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'local_credentium'), ['size' => 60]);
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey', 'local_credentium');
        $mform->hideIf('apikey', 'enabled', 'notchecked');
        $mform->hideIf('apikey', 'credentialsource', 'eq', 'global');

        // Operational controls.
        $mform->addElement('checkbox', 'paused', get_string('paused', 'local_credentium'));
        $mform->addHelpButton('paused', 'paused', 'local_credentium');
        $mform->hideIf('paused', 'enabled', 'notchecked');

        $mform->addElement('text', 'ratelimit', get_string('ratelimit', 'local_credentium'), ['size' => 10]);
        $mform->setType('ratelimit', PARAM_INT);
        $mform->addHelpButton('ratelimit', 'ratelimit', 'local_credentium');
        $mform->hideIf('ratelimit', 'enabled', 'notchecked');

        // Test connection button (only for custom credentials that are saved).
        $saved_config = local_credentium_get_category_config($categoryid);
        $credentials_saved = !empty($saved_config) && !empty($saved_config->apiurl) && !empty($saved_config->apikey);

        if ($credentials_saved) {
            $testurl = new moodle_url('/local/credentium/testconnection.php',
                ['categoryid' => $categoryid, 'sesskey' => sesskey()]);
            $onclick = "window.open('{$testurl->out(false)}', '_blank'); return false;";
            $mform->addElement('button', 'testconnection',
                get_string('testconnection', 'local_credentium'),
                ['onclick' => $onclick]);
        } else {
            $alertmessage = get_string('testconnection_disabled', 'local_credentium');
            $onclick = "alert(" . json_encode($alertmessage) . "); return false;";
            $mform->addElement('button', 'testconnection',
                get_string('testconnection', 'local_credentium'),
                ['onclick' => $onclick]);
        }
        $mform->hideIf('testconnection', 'enabled', 'notchecked');
        $mform->hideIf('testconnection', 'credentialsource', 'eq', 'global');

        // Hidden elements.
        $mform->addElement('hidden', 'categoryid', $categoryid);
        $mform->setType('categoryid', PARAM_INT);

        // Action buttons.
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['enabled'])) {
            // Only validate custom credentials if custom source is selected
            if (!empty($data['credentialsource']) && $data['credentialsource'] === 'custom') {
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

            // Validate rate limit if provided
            if (!empty($data['ratelimit']) && $data['ratelimit'] < 1) {
                $errors['ratelimit'] = get_string('error:invalidratelimit', 'local_credentium');
            }
        }

        if (!empty($errors)) {
            local_credentium_log('Category form validation errors', ['errors' => array_keys($errors)]);
        }

        return $errors;
    }
}

// Create form instance.
$formurl = new moodle_url('/local/credentium/category_settings.php', ['id' => $categoryid]);
$mform = new local_credentium_category_settings_form($formurl, ['categoryid' => $categoryid]);

// Load existing config.
$config = local_credentium_get_category_config($categoryid);
if ($config) {
    local_credentium_log('Loading existing category config', ['categoryid' => $categoryid]);

    // Determine credentialsource based on whether custom credentials are configured
    if (!empty($config->apiurl) && !empty($config->apikey)) {
        $config->credentialsource = 'custom';
    } else {
        $config->credentialsource = 'global';
    }

    $mform->set_data($config);
}

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/index.php', ['categoryid' => $categoryid]));
} else if ($data = $mform->get_data()) {
    local_credentium_log('Form data received for category', ['categoryid' => $categoryid]);

    // Prepare config object.
    $config = new stdClass();
    $config->categoryid = $data->categoryid ?? $categoryid;
    $config->enabled = !empty($data->enabled) ? 1 : 0;

    // Only save custom credentials if custom source is selected
    if (!empty($data->credentialsource) && $data->credentialsource === 'custom') {
        $config->apiurl = !empty($data->apiurl) ? trim($data->apiurl) : null;
        $config->apikey = !empty($data->apikey) ? $data->apikey : null;
    } else {
        // Clear custom credentials when using global
        $config->apiurl = null;
        $config->apikey = null;
    }

    $config->paused = !empty($data->paused) ? 1 : 0;
    $config->ratelimit = !empty($data->ratelimit) ? (int)$data->ratelimit : null;

    // Save config (encryption happens in save function).
    $success = local_credentium_save_category_config($config);

    local_credentium_log('Category config saved', [
        'categoryid' => $categoryid,
        'success' => $success,
        'enabled' => $config->enabled
    ]);

    // Redirect back to the same settings page with success message.
    redirect(new moodle_url('/local/credentium/category_settings.php', ['id' => $categoryid]),
        get_string('changessaved'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('categorysettings', 'local_credentium'));
echo $mform->render();
echo $OUTPUT->footer();
