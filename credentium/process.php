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
 * Process pending credentials (for debugging/admin purposes).
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/credentium/process.php');
$PAGE->set_title(get_string('processcredentials', 'local_credentium'));
$PAGE->set_heading(get_string('processcredentials', 'local_credentium'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('processingcredentials', 'local_credentium'), 3);

// Get all pending and retrying credentials
$pendingissuances = $DB->get_records_select('local_credentium_issuances', 
    "status IN ('pending', 'retrying')", 
    null, 
    'timecreated ASC', 
    '*', 
    0, 
    10);

if (empty($pendingissuances)) {
    echo $OUTPUT->notification(get_string('nopendingcredentials', 'local_credentium'), 'info');
} else {
    echo html_writer::tag('p', get_string('processingcount', 'local_credentium', count($pendingissuances)));
    
    // Queue ad-hoc tasks for each pending issuance
    $queuedcount = 0;
    foreach ($pendingissuances as $issuance) {
        $user = $DB->get_record('user', ['id' => $issuance->userid]);
        $course = $DB->get_record('course', ['id' => $issuance->courseid]);
        
        echo html_writer::start_div('alert alert-info');
        echo "Queuing credential for " . fullname($user) . " in course " . $course->fullname . "... ";
        
        // Show previous error if retrying
        if ($issuance->status === 'retrying' && !empty($issuance->errormessage)) {
            echo html_writer::tag('small', ' (Previous error: ' . $issuance->errormessage . ')', ['class' => 'text-muted']);
        }
        flush();
        
        // Queue an ad-hoc task for this credential
        $task = new \local_credentium\task\issue_credential();
        $task->set_custom_data(['issuanceid' => $issuance->id]);
        \core\task\manager::queue_adhoc_task($task);
        
        echo html_writer::tag('strong', 'QUEUED', ['class' => 'text-success']);
        $queuedcount++;
        
        echo html_writer::end_div();
        flush();
    }
    
    echo html_writer::tag('p', get_string('bulkissuanceinitiated', 'local_credentium', $queuedcount), 
        ['class' => 'alert alert-success mt-3']);
}

echo html_writer::tag('p', html_writer::link(
    new moodle_url('/local/credentium/index.php'),
    get_string('backtoreport', 'local_credentium'),
    ['class' => 'btn btn-primary mt-3']
));

echo $OUTPUT->footer();