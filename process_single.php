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

// Create and execute the task directly
$task = new \local_credentium\task\issue_credential();
$task->set_custom_data(['issuanceid' => $issuance->id]);

echo html_writer::tag('p', 'Executing task for issuance ID: ' . $issuance->id);

// Execute the task directly
try {
    $task->execute();
    echo $OUTPUT->notification('Task executed successfully', 'success');
} catch (Exception $e) {
    echo $OUTPUT->notification('Task execution failed: ' . $e->getMessage(), 'error');
    echo html_writer::tag('pre', s($e->getTraceAsString()));
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