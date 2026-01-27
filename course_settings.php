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
 * Course settings page for Credentium integration.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/credentium:managecourse', $context);

$PAGE->set_url('/local/credentium/course_settings.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('coursesettings', 'local_credentium'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('coursesettings', 'local_credentium'));

// Check if plugin is enabled globally.
if (!get_config('local_credentium', 'enabled')) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error:notenabled', 'local_credentium'), 'notifyproblem');
    echo $OUTPUT->footer();
    die();
}

/**
 * Course settings form.
 */
class local_credentium_course_settings_form extends moodleform {
    
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        
        // Enable/disable for course.
        $mform->addElement('checkbox', 'enabled', get_string('courseenabled', 'local_credentium'));
        $mform->addHelpButton('enabled', 'courseenabled', 'local_credentium');

        // Category inheritance (only show if category mode is enabled).
        if (get_config('local_credentium', 'categorymode')) {
            $mform->addElement('checkbox', 'inherit_category', get_string('inherit_category', 'local_credentium'));
            $mform->addHelpButton('inherit_category', 'inherit_category', 'local_credentium');
            $mform->setDefault('inherit_category', 1);
            $mform->disabledIf('inherit_category', 'enabled', 'notchecked');

            // Show which category configuration will be used.
            $categoryinfo = $this->get_category_info($courseid);
            if ($categoryinfo) {
                $mform->addElement('static', 'categoryinfo', get_string('categoryinfo', 'local_credentium'),
                    '<div class="alert alert-info">' . $categoryinfo . '</div>');
            }
        }

        // Get templates from API or cache.
        $templates = $this->get_templates();
        $templateoptions = ['' => get_string('selecttemplate', 'local_credentium')];
        $unsupportedtemplates = [];
        foreach ($templates as $template) {
            $assessmentcount = $template->learningAssessmentCount ?? 0;
            if ($assessmentcount > 1) {
                // Mark as unsupported but still show (so admin knows it exists).
                $templateoptions[$template->id] = $template->title . ' ' .
                    get_string('template_unsupported_suffix', 'local_credentium');
                $unsupportedtemplates[$template->id] = $assessmentcount;
            } else {
                $templateoptions[$template->id] = $template->title;
            }
        }
        // Store for use in validation.
        $this->unsupportedtemplates = $unsupportedtemplates;

        local_credentium_log('Templates loaded for course form', [
            'count' => count($templates),
            'unsupported' => count($unsupportedtemplates),
        ]);

        // Template selection.
        $mform->addElement('select', 'templateid', get_string('credentialtemplate', 'local_credentium'), $templateoptions);
        $mform->addHelpButton('templateid', 'credentialtemplate', 'local_credentium');
        $mform->disabledIf('templateid', 'enabled', 'notchecked');

        // Show warning if currently selected template is unsupported.
        $currentconfig = local_credentium_get_course_config($courseid);
        if ($currentconfig && !empty($currentconfig->templateid) && isset($unsupportedtemplates[$currentconfig->templateid])) {
            $assessmentcount = $unsupportedtemplates[$currentconfig->templateid];
            $mform->addElement('static', 'template_warning', '',
                '<div class="alert alert-warning">' .
                get_string('template_unsupported_warning', 'local_credentium', $assessmentcount) .
                '</div>');
        }

        // Refresh templates button.
        $refreshurl = new moodle_url('/local/credentium/course_settings.php',
            ['id' => $courseid, 'action' => 'refreshtemplates', 'sesskey' => sesskey()]);
        $mform->addElement('button', 'refreshtemplates', get_string('refreshtemplates', 'local_credentium'),
            ['onclick' => "window.location.href='{$refreshurl->out(false)}'"]);
        
        // Send grade checkbox.
        $mform->addElement('checkbox', 'sendgrade', get_string('sendgrade', 'local_credentium'));
        $mform->addHelpButton('sendgrade', 'sendgrade', 'local_credentium');
        $mform->setDefault('sendgrade', 1); // Default to sending grade
        $mform->disabledIf('sendgrade', 'enabled', 'notchecked');
        
        // Important information about automatic issuance.
        $mform->addElement('static', 'issuanceinfo', get_string('issuanceinfo', 'local_credentium'),
            '<div class="alert alert-info">' . 
            get_string('issuanceinfo_desc', 'local_credentium') . 
            '</div>');
        $mform->addHelpButton('issuanceinfo', 'issuanceinfo', 'local_credentium');
        
        // Hidden elements.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        
        // Buttons.
        $this->add_action_buttons();
    }
    
    /**
     * Get category information to display.
     *
     * @param int $courseid The course ID
     * @return string|null Category information HTML or null
     */
    private function get_category_info($courseid) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], 'id, category', MUST_EXIST);
        if (empty($course->category)) {
            return get_string('categoryinfo_global', 'local_credentium');
        }

        // Get the category configuration that will be used.
        $categoryconfig = local_credentium_resolve_category_config($courseid);

        if ($categoryconfig && !empty($categoryconfig->categoryid)) {
            $category = $DB->get_record('course_categories', ['id' => $categoryconfig->categoryid], 'name');
            $categoryname = $category ? $category->name : get_string('unknowncategory', 'local_credentium');
            return get_string('categoryinfo_inherited', 'local_credentium', $categoryname);
        } else {
            return get_string('categoryinfo_global', 'local_credentium');
        }
    }

    /**
     * Get templates from API or cache.
     *
     * @return array
     */
    private function get_templates() {
        try {
            // Get category configuration for this course to use correct API credentials.
            $courseid = $this->_customdata['courseid'];
            $categoryconfig = local_credentium_resolve_category_config($courseid);

            // Create client with category-specific credentials and categoryid for cache isolation.
            $client = new \local_credentium\api\client(
                $categoryconfig->apiurl ?? null,
                $categoryconfig->apikey ?? null,
                $categoryconfig->categoryid ?? null
            );

            // Pass false to get all templates, not just assessment ones.
            $templates = $client->get_templates(false);

            local_credentium_log('API get_templates() succeeded', ['count' => count($templates)]);

            return $templates;
        } catch (Exception $e) {
            local_credentium_log('API get_templates() failed', ['error' => $e->getMessage()]);
            // Return empty array if API fails.
            return [];
        }
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['enabled'])) {
            if (empty($data['templateid'])) {
                $errors['templateid'] = get_string('required');
            } else {
                // Check if selected template has multiple Learning Assessments (unsupported).
                $templates = $this->get_templates();
                foreach ($templates as $template) {
                    if ($template->id === $data['templateid']) {
                        $assessmentcount = $template->learningAssessmentCount ?? 0;
                        if ($assessmentcount > 1) {
                            $errors['templateid'] = get_string('error:template_multiple_assessments', 'local_credentium');
                        }
                        break;
                    }
                }
            }
        }

        if (!empty($errors)) {
            local_credentium_log('Form validation errors', ['errors' => array_keys($errors)]);
        }

        return $errors;
    }
}

// Handle refresh templates action.
if ($action === 'refreshtemplates') {
    require_sesskey();
    try {
        // Get category configuration for this course.
        $categoryconfig = local_credentium_resolve_category_config($courseid);

        // Create client with category-specific credentials.
        $client = new \local_credentium\api\client(
            $categoryconfig->apiurl ?? null,
            $categoryconfig->apikey ?? null,
            $categoryconfig->categoryid ?? null
        );
        $client->get_templates(true); // This will refresh the cache.
        redirect(new moodle_url('/local/credentium/course_settings.php', ['id' => $courseid]),
            get_string('templaterefreshed', 'local_credentium'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (Exception $e) {
        redirect(new moodle_url('/local/credentium/course_settings.php', ['id' => $courseid]),
            get_string('error:issuancefailed', 'local_credentium', $e->getMessage()), null,
            \core\output\notification::NOTIFY_ERROR);
    }
}

// Create form instance.
$formurl = new moodle_url('/local/credentium/course_settings.php', ['id' => $courseid]);
$mform = new local_credentium_course_settings_form($formurl, ['courseid' => $courseid]);

// Load existing config.
$config = local_credentium_get_course_config($courseid);
if ($config) {
    local_credentium_log('Loading existing course config', ['courseid' => $courseid]);

    // Ensure templateid is properly set for form
    $formdata = (array)$config;
    if (!empty($formdata['templateid'])) {
        $formdata['templateid'] = (string)$formdata['templateid'];
    }

    $mform->set_data($formdata);
}

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else if ($data = $mform->get_data()) {
    local_credentium_log('Form data received for course', ['courseid' => $courseid]);

    // Prepare config object.
    $config = new stdClass();
    $config->courseid = $data->courseid ?? $courseid; // Use form data courseid, fallback to URL param
    $config->enabled = !empty($data->enabled) ? 1 : 0;
    $config->templateid = !empty($data->templateid) ? $data->templateid : null;
    $config->sendgrade = !empty($data->sendgrade) ? 1 : 0;
    $config->mingrade = null; // Not used anymore, but kept for DB compatibility
    $config->issuancetrigger = 'completion'; // Always use completion trigger
    $config->inherit_category = isset($data->inherit_category) ? (int)$data->inherit_category : 1; // Default to 1

    // Save config.
    $success = local_credentium_save_course_config($config);

    local_credentium_log('Course config saved', [
        'courseid' => $courseid,
        'success' => $success,
        'enabled' => $config->enabled
    ]);

    // Redirect with success message.
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]),
        get_string('changessaved'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Output page.
echo $OUTPUT->header();
echo $mform->render();
echo $OUTPUT->footer();