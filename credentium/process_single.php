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
 * Process a single credential immediately (for debugging).
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/credentium/process_single.php', ['id' => $id]);
$PAGE->set_title('Process Single Credential');
$PAGE->set_heading('Process Single Credential');

echo $OUTPUT->header();

// Get the issuance
$issuance = $DB->get_record('local_credentium_issuances', ['id' => $id]);
if (!$issuance) {
    echo $OUTPUT->notification('Issuance not found', 'error');
    echo $OUTPUT->footer();
    die();
}

echo html_writer::tag('h3', 'Processing Credential Issuance #' . $id);

// Smart retry: Refresh grade if NULL and sendgrade is enabled
require_once(__DIR__ . '/lib.php');
$courseconfig = local_credentium_get_course_config($issuance->courseid);
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

// Show updated issuance
$updated = $DB->get_record('local_credentium_issuances', ['id' => $id]);
echo html_writer::tag('h4', 'Updated Status', ['class' => 'mt-4']);
$table = new html_table();
$table->data = [
    ['Status', $updated->status],
    ['Attempts', $updated->attempts],
    ['Error Code', $updated->errorcode ?? 'None'],
    ['Error Message', $updated->errormessage ?? 'None'],
    ['Credential ID', $updated->credentialid ?? 'None'],
];
echo html_writer::table($table);

echo html_writer::tag('p', '', ['class' => 'mt-4']);
echo html_writer::link(
    new moodle_url('/local/credentium/debug.php', ['id' => $id]),
    'Back to Debug View',
    ['class' => 'btn btn-secondary']
);

echo $OUTPUT->footer();