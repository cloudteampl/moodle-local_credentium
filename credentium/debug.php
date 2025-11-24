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
 * Debug view for credential issuance.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

require_login();
require_capability('local/credentium:viewreports', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/credentium/debug.php', ['id' => $id]);
$PAGE->set_title('Debug Credential Issuance');
$PAGE->set_heading('Debug Credential Issuance');

echo $OUTPUT->header();

// Get the issuance record
$issuance = $DB->get_record('local_credentium_issuances', ['id' => $id]);
if (!$issuance) {
    echo $OUTPUT->notification('Issuance record not found for ID: ' . (int)$id, 'error');
    
    // Show all issuance records for debugging
    $allissuances = $DB->get_records('local_credentium_issuances', null, 'id ASC', 'id, userid, courseid, status', 0, 10);
    echo html_writer::tag('p', 'Available issuance records:');
    foreach ($allissuances as $iss) {
        echo html_writer::tag('p', "ID: {$iss->id}, User: {$iss->userid}, Course: {$iss->courseid}, Status: {$iss->status}");
    }
    
    echo $OUTPUT->footer();
    die();
}

// Get related records
$user = $DB->get_record('user', ['id' => $issuance->userid]);
$course = $DB->get_record('course', ['id' => $issuance->courseid]);
$courseconfig = $DB->get_record('local_credentium_course_config', ['courseid' => $issuance->courseid]);

echo html_writer::tag('h3', 'Issuance Details');
$table = new html_table();
$table->data = [
    ['ID', $issuance->id],
    ['User', fullname($user) . ' (ID: ' . $user->id . ')'],
    ['Course', $course->fullname . ' (ID: ' . $course->id . ')'],
    ['Template ID', $issuance->templateid],
    ['Status', get_string('status_' . $issuance->status, 'local_credentium')],
    ['Attempts', $issuance->attempts],
    ['Grade', $issuance->grade ?? 'N/A'],
    ['Time Created', userdate($issuance->timecreated)],
    ['Time Modified', userdate($issuance->timemodified)],
    ['Time Issued', $issuance->timeissued ? userdate($issuance->timeissued) : 'Not issued'],
    ['Credential ID', $issuance->credentialid ?? 'None'],
    ['Error Code', $issuance->errorcode ?? 'None'],
    ['Error Message', $issuance->errormessage ?? 'None'],
];
echo html_writer::table($table);

// Course configuration
echo html_writer::tag('h3', 'Course Configuration', ['class' => 'mt-4']);
if ($courseconfig) {
    $table = new html_table();
    $table->data = [
        ['Enabled', $courseconfig->enabled ? 'Yes' : 'No'],
        ['Template ID', $courseconfig->templateid],
        ['Min Grade', $courseconfig->mingrade ?? 'None'],
        ['Issuance Trigger', $courseconfig->issuancetrigger],
    ];
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('No course configuration found', 'warning');
}

// Execute Task Section - Same detailed output as process_single.php
$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'execute' && confirm_sesskey()) {
    echo html_writer::tag('h3', 'Task Execution', ['class' => 'mt-4']);

    // Smart retry: Refresh grade if NULL and sendgrade is enabled
    require_once(__DIR__ . '/lib.php');
    $sendgrade = $courseconfig && !empty($courseconfig->sendgrade);

    if ($sendgrade && is_null($issuance->grade)) {
        echo html_writer::tag('p', 'Attempting to refresh grade from gradebook...');

        // Attempt to fetch grade from gradebook
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');
        $course_grade = \grade_get_course_grade($issuance->userid, $issuance->courseid);

        if ($course_grade && isset($course_grade->grade)) {
            $issuance->grade = $course_grade->grade;
            $issuance->timemodified = time();
            $DB->update_record('local_credentium_issuances', $issuance);
            echo $OUTPUT->notification("Grade refreshed from gradebook: {$course_grade->grade}", 'success');
        } else {
            echo $OUTPUT->notification('Warning: Grade still not available in gradebook', 'warning');
        }
    }

    // Create and execute the task directly
    $task = new \local_credentium\task\issue_credential();
    $task->set_custom_data(['issuanceid' => $issuance->id]);

    echo html_writer::tag('p', 'Executing task for issuance ID: ' . $issuance->id);

    // Capture mtrace output
    ob_start();

    // Execute the task directly
    try {
        $task->execute();
        $output = ob_get_clean();

        echo html_writer::tag('h4', 'Task Output', ['class' => 'mt-3']);
        echo html_writer::tag('pre', s($output), ['class' => 'border p-3 bg-light', 'style' => 'max-height: 400px; overflow-y: auto;']);

        // Check if output contains errors to determine actual success
        if (stripos($output, 'ERROR:') !== false || stripos($output, 'Error issuing') !== false) {
            echo $OUTPUT->notification('Task completed but credential issuance failed - see output above for details', 'error');
        } else if (stripos($output, 'Successfully issued credential') !== false) {
            echo $OUTPUT->notification('Credential issued successfully!', 'success');
        } else {
            echo $OUTPUT->notification('Task completed - check output above for details', 'info');
        }
    } catch (Exception $e) {
        $output = ob_get_clean();

        echo html_writer::tag('h4', 'Task Output', ['class' => 'mt-3']);
        echo html_writer::tag('pre', s($output), ['class' => 'border p-3 bg-light', 'style' => 'max-height: 400px; overflow-y: auto;']);

        echo $OUTPUT->notification('Task execution failed with exception: ' . $e->getMessage(), 'error');
        echo html_writer::tag('h4', 'Exception Details', ['class' => 'mt-3']);
        echo html_writer::tag('pre', s($e->getTraceAsString()), ['class' => 'border p-3 bg-light', 'style' => 'max-height: 400px; overflow-y: auto;']);
    }

    // Reload updated issuance record
    $issuance = $DB->get_record('local_credentium_issuances', ['id' => $id]);
    echo html_writer::tag('h4', 'Updated Status', ['class' => 'mt-4']);
    $statustable = new html_table();
    $statustable->data = [
        ['Status', get_string('status_' . $issuance->status, 'local_credentium')],
        ['Attempts', $issuance->attempts],
        ['Error Code', $issuance->errorcode ?? 'None'],
        ['Error Message', $issuance->errormessage ?? 'None'],
        ['Credential ID', $issuance->credentialid ?? 'None'],
    ];
    echo html_writer::table($statustable);

    echo html_writer::tag('hr', '', ['class' => 'my-4']);
}

// Test API connection
echo html_writer::tag('h3', 'API Connection Test', ['class' => 'mt-4']);
try {
    require_once(__DIR__ . '/lib.php');
    $resolvedconfig = local_credentium_resolve_category_config($issuance->courseid);

    $client = new \local_credentium\api\client(
        $resolvedconfig->apiurl ?? null,
        $resolvedconfig->apikey ?? null,
        $resolvedconfig->categoryid ?? null
    );
    echo $OUTPUT->notification('API client initialized successfully', 'success');

    // Try to get templates
    echo html_writer::tag('p', 'Testing template fetch...');
    $templates = $client->get_templates(false);
    echo $OUTPUT->notification('Found ' . count($templates) . ' templates', 'success');

    // Check if template exists
    $templatefound = false;
    foreach ($templates as $template) {
        if ($template->id == $issuance->templateid) {
            $templatefound = true;
            echo $OUTPUT->notification('Template ' . s($issuance->templateid) . ' found: ' . s($template->title), 'success');
            break;
        }
    }
    if (!$templatefound) {
        echo $OUTPUT->notification('Template ' . s($issuance->templateid) . ' NOT found in available templates!', 'error');
    }

} catch (Exception $e) {
    echo $OUTPUT->notification('API Error: ' . s($e->getMessage()), 'error');
    if (isset($e->debuginfo) && !empty($e->debuginfo)) {
        echo html_writer::tag('pre', s($e->debuginfo), ['class' => 'alert alert-warning']);
    }
}

// Actions
echo html_writer::tag('h3', 'Actions', ['class' => 'mt-4']);

// Execute Task Now button (shows detailed output on same page)
if ($issuance->status !== 'issued') {
    echo html_writer::link(
        new moodle_url('/local/credentium/debug.php', ['id' => $issuance->id, 'action' => 'execute', 'sesskey' => sesskey()]),
        'Execute Task Now (Show Details)',
        ['class' => 'btn btn-warning']
    );
    echo ' ';
}

echo html_writer::link(
    new moodle_url('/local/credentium/index.php'),
    'Back to Report',
    ['class' => 'btn btn-secondary']
);

echo $OUTPUT->footer();