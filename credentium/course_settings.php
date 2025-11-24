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
        global $DB, $PAGE;

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        // Check if category has Credentium enabled (if category mode is enabled).
        $course = $DB->get_record('course', ['id' => $courseid], 'id, category', MUST_EXIST);
        $category_enabled = true; // Default to true if no category or category mode disabled
        $category_disabled_message = '';

        if (get_config('local_credentium', 'categorymode') && !empty($course->category)) {
            // Walk up category tree to find enabled category config
            $categoryconfig = local_credentium_get_category_config($course->category);

            // Check if this category or any parent has Credentium enabled
            if (!$categoryconfig || empty($categoryconfig->enabled)) {
                // Try to find enabled config in parent categories
                $parent_categoryid = $course->category;
                $found_enabled = false;
                $levels_checked = 0;
                $max_levels = 10;

                while ($parent_categoryid && $levels_checked < $max_levels) {
                    $parent_config = local_credentium_get_category_config($parent_categoryid);
                    if ($parent_config && !empty($parent_config->enabled)) {
                        $found_enabled = true;
                        break;
                    }

                    $parent_cat = $DB->get_record('course_categories', ['id' => $parent_categoryid], 'parent');
                    $parent_categoryid = $parent_cat ? $parent_cat->parent : null;
                    $levels_checked++;
                }

                if (!$found_enabled) {
                    $category_enabled = false;
                    $coursecategory = $DB->get_record('course_categories', ['id' => $course->category], 'name');
                    $category_disabled_message = get_string('category_credentium_disabled', 'local_credentium',
                        $coursecategory->name ?? get_string('unknowncategory', 'local_credentium'));
                }
            }
        }

        // If category has Credentium disabled, show error and return.
        if (!$category_enabled) {
            $mform->addElement('static', 'category_disabled_error', '',
                '<div class="alert alert-danger">' .
                '<strong>' . get_string('category_credentium_disabled_heading', 'local_credentium') . '</strong><br>' .
                $category_disabled_message .
                '</div>');

            // Add disabled checkbox just for display.
            $mform->addElement('checkbox', 'enabled', get_string('courseenabled', 'local_credentium'));
            $mform->hardFreeze('enabled');

            // Hidden elements.
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);

            return; // Stop here - don't show any other fields.
        }

        // Check if API credentials are configured (category or global).
        $categoryconfig = local_credentium_resolve_category_config($courseid);
        $has_credentials = !empty($categoryconfig->apiurl) && !empty($categoryconfig->apikey);

        // If no credentials, show error message and disable everything.
        if (!$has_credentials) {
            $mform->addElement('static', 'no_credentials_error', '',
                '<div class="alert alert-danger">' .
                '<strong>' . get_string('nocredentials_heading', 'local_credentium') . '</strong><br>' .
                get_string('nocredentials_message', 'local_credentium') .
                '</div>');

            // Add disabled checkbox just for display.
            $mform->addElement('checkbox', 'enabled', get_string('courseenabled', 'local_credentium'));
            $mform->hardFreeze('enabled');

            // Hidden elements.
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);

            return; // Stop here - don't show any other fields.
        }

        // Enable/disable for course with auto-save.
        $mform->addElement('checkbox', 'enabled', get_string('courseenabled', 'local_credentium'),
            '', ['id' => 'id_enabled']);
        $mform->addHelpButton('enabled', 'courseenabled', 'local_credentium');

        // Add JavaScript for auto-save when checkbox changes.
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                $('#id_enabled').change(function() {
                    // Auto-submit the form when enabled checkbox changes
                    $(this).closest('form').submit();
                });
            });
        ");

        // Show which category configuration will be used (always show, not dependent on enable checkbox).
        if (get_config('local_credentium', 'categorymode')) {
            $categoryinfo = $this->get_category_info($courseid);
            if ($categoryinfo) {
                $mform->addElement('static', 'categoryinfo', get_string('categoryinfo', 'local_credentium'),
                    '<div class="alert alert-info">' . $categoryinfo . '</div>');
            }
        }

        // Get templates from API or cache.
        $templates = $this->get_templates();
        $templateoptions = ['' => get_string('selecttemplate', 'local_credentium')];
        $templatesdata = []; // Store template data for JavaScript
        foreach ($templates as $template) {
            $templateoptions[$template->id] = $template->title;
            $templatesdata[$template->id] = [
                'requiresGrade' => $template->requiresGrade ?? false
            ];
        }

        local_credentium_log('Templates loaded for course form', ['count' => count($templates)]);

        // Template selection.
        $mform->addElement('select', 'templateid', get_string('credentialtemplate', 'local_credentium'), $templateoptions);
        $mform->addHelpButton('templateid', 'credentialtemplate', 'local_credentium');
        $mform->hideIf('templateid', 'enabled', 'notchecked');

        // Refresh templates button.
        $refreshurl = new moodle_url('/local/credentium/course_settings.php',
            ['id' => $courseid, 'action' => 'refreshtemplates', 'sesskey' => sesskey()]);
        $mform->addElement('button', 'refreshtemplates', get_string('refreshtemplates', 'local_credentium'),
            ['onclick' => "window.location.href='{$refreshurl->out(false)}'"]);
        $mform->hideIf('refreshtemplates', 'enabled', 'notchecked');

        // Grade requirement information (shown dynamically based on selected template)
        $mform->addElement('static', 'graderequired_yes', '',
            '<div class="alert alert-warning">' .
            '<strong>' . get_string('templaterequiresgrade', 'local_credentium') . '</strong><br>' .
            get_string('templaterequiresgrade_info', 'local_credentium') .
            '</div>');
        $mform->hideIf('graderequired_yes', 'enabled', 'notchecked');

        $mform->addElement('static', 'graderequired_no', '',
            '<div class="alert alert-info">' .
            '<strong>' . get_string('templatenograderequired', 'local_credentium') . '</strong><br>' .
            get_string('templatenograderequired_info', 'local_credentium') .
            '</div>');
        $mform->hideIf('graderequired_no', 'enabled', 'notchecked');

        // Add JavaScript to show/hide grade requirements based on selected template
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                var templatesData = " . json_encode($templatesdata) . ";

                function updateGradeRequirement() {
                    var selectedTemplate = $('#id_templateid').val();
                    var gradeRequired = templatesData[selectedTemplate] ? templatesData[selectedTemplate].requiresGrade : false;

                    if (selectedTemplate === '') {
                        // No template selected - hide both
                        $('#fitem_id_graderequired_yes').hide();
                        $('#fitem_id_graderequired_no').hide();
                    } else if (gradeRequired) {
                        // Template requires grade
                        $('#fitem_id_graderequired_yes').show();
                        $('#fitem_id_graderequired_no').hide();
                    } else {
                        // Template does not require grade
                        $('#fitem_id_graderequired_yes').hide();
                        $('#fitem_id_graderequired_no').show();
                    }
                }

                // Update on template change
                $('#id_templateid').change(updateGradeRequirement);

                // Initial update
                updateGradeRequirement();
            });
        ");

        // Important information about automatic issuance.
        $mform->addElement('static', 'issuanceinfo', get_string('issuanceinfo', 'local_credentium'),
            '<div class="alert alert-info">' .
            get_string('issuanceinfo_desc', 'local_credentium') .
            '</div>');
        $mform->addHelpButton('issuanceinfo', 'issuanceinfo', 'local_credentium');
        $mform->hideIf('issuanceinfo', 'enabled', 'notchecked');

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

            // Check if API credentials are actually configured.
            if (empty($categoryconfig->apiurl) || empty($categoryconfig->apikey)) {
                local_credentium_log('No API credentials configured', [
                    'courseid' => $courseid,
                    'categoryid' => $categoryconfig->categoryid ?? null
                ]);
                return [];
            }

            // Create client with category-specific credentials and categoryid for cache isolation.
            $client = new \local_credentium\api\client(
                $categoryconfig->apiurl,
                $categoryconfig->apikey,
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

    // Determine sendgrade automatically based on selected template's requiresGrade field
    $sendgrade = 0; // Default
    if (!empty($data->templateid)) {
        // Get template from cache to check requiresGrade
        $categoryconfig = local_credentium_resolve_category_config($courseid);
        try {
            $client = new \local_credentium\api\client(
                $categoryconfig->apiurl,
                $categoryconfig->apikey,
                $categoryconfig->categoryid ?? null
            );
            $templates = $client->get_templates(false);

            foreach ($templates as $template) {
                if ($template->id === $data->templateid) {
                    $sendgrade = !empty($template->requiresGrade) ? 1 : 0;
                    local_credentium_log('Template requiresGrade determined', [
                        'templateid' => $template->id,
                        'requiresGrade' => $template->requiresGrade ?? false,
                        'sendgrade' => $sendgrade
                    ]);
                    break;
                }
            }
        } catch (Exception $e) {
            local_credentium_log('Failed to determine template grade requirement', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // Prepare config object.
    $config = new stdClass();
    $config->courseid = $data->courseid ?? $courseid; // Use form data courseid, fallback to URL param
    $config->enabled = !empty($data->enabled) ? 1 : 0;
    $config->templateid = !empty($data->templateid) ? $data->templateid : null;
    $config->sendgrade = $sendgrade;
    $config->mingrade = null;
    $config->issuancetrigger = 'completion';
    $config->inherit_category = 1;

    // Save config.
    $success = local_credentium_save_course_config($config);

    local_credentium_log('Course config saved', [
        'courseid' => $courseid,
        'success' => $success,
        'enabled' => $config->enabled
    ]);

    // Redirect back to same page (stay on settings page).
    redirect(new moodle_url('/local/credentium/course_settings.php', ['id' => $courseid]),
        get_string('changessaved'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Output page.
echo $OUTPUT->header();
echo $mform->render();
echo $OUTPUT->footer();