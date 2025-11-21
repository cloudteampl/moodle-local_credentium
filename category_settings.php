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

        // Enable/disable for category.
        $mform->addElement('checkbox', 'enabled', get_string('categoryenabled', 'local_credentium'));
        $mform->addHelpButton('enabled', 'categoryenabled', 'local_credentium');

        // API credentials section.
        $mform->addElement('header', 'apicredentials', get_string('apicredentials', 'local_credentium'));

        // API URL.
        $mform->addElement('text', 'apiurl', get_string('apiurl', 'local_credentium'), ['size' => 60]);
        $mform->setType('apiurl', PARAM_URL);
        $mform->addHelpButton('apiurl', 'apiurl', 'local_credentium');
        $mform->disabledIf('apiurl', 'enabled', 'notchecked');

        // API Key.
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'local_credentium'), ['size' => 60]);
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey', 'local_credentium');
        $mform->disabledIf('apikey', 'enabled', 'notchecked');

        // Test connection button with validation.
        $testurl = new moodle_url('/local/credentium/testconnection.php',
            ['categoryid' => $categoryid, 'sesskey' => sesskey()]);

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

        // Operational controls section.
        $mform->addElement('header', 'operations', get_string('operations', 'local_credentium'));

        // Paused checkbox.
        $mform->addElement('checkbox', 'paused', get_string('paused', 'local_credentium'));
        $mform->addHelpButton('paused', 'paused', 'local_credentium');
        $mform->disabledIf('paused', 'enabled', 'notchecked');

        // Rate limit.
        $mform->addElement('text', 'ratelimit', get_string('ratelimit', 'local_credentium'), ['size' => 10]);
        $mform->setType('ratelimit', PARAM_INT);
        $mform->addHelpButton('ratelimit', 'ratelimit', 'local_credentium');
        $mform->disabledIf('ratelimit', 'enabled', 'notchecked');

        // Hidden elements.
        $mform->addElement('hidden', 'categoryid', $categoryid);
        $mform->setType('categoryid', PARAM_INT);

        // Buttons.
        $this->add_action_buttons();
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
    $config->apiurl = !empty($data->apiurl) ? trim($data->apiurl) : null;
    $config->apikey = !empty($data->apikey) ? $data->apikey : null;
    $config->paused = !empty($data->paused) ? 1 : 0;
    $config->ratelimit = !empty($data->ratelimit) ? (int)$data->ratelimit : null;

    // Save config (encryption happens in save function).
    $success = local_credentium_save_category_config($config);

    local_credentium_log('Category config saved', [
        'categoryid' => $categoryid,
        'success' => $success,
        'enabled' => $config->enabled
    ]);

    // Redirect with success message.
    redirect(new moodle_url('/course/index.php', ['categoryid' => $categoryid]),
        get_string('changessaved'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('categorysettings', 'local_credentium'));
echo $mform->render();
echo $OUTPUT->footer();
