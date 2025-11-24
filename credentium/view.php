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
 * View credential details.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

// Get issuance record.
$issuance = $DB->get_record('local_credentium_issuances', ['id' => $id], '*', MUST_EXIST);

// Check permissions.
$context = context_course::instance($issuance->courseid);
if ($issuance->userid != $USER->id) {
    require_capability('local/credentium:viewreports', context_system::instance());
}

$PAGE->set_context($context);
$PAGE->set_url('/local/credentium/view.php', ['id' => $id]);
$PAGE->set_title(get_string('viewcredential', 'local_credentium'));
$PAGE->set_heading(get_string('viewcredential', 'local_credentium'));

// Get additional data.
$user = $DB->get_record('user', ['id' => $issuance->userid], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $issuance->courseid], '*', MUST_EXIST);
$template = $DB->get_record('local_credentium_templates_cache', ['templateid' => $issuance->templateid]);

echo $OUTPUT->header();

// Display credential information.
echo html_writer::start_div('card');
echo html_writer::tag('h5', get_string('credentialdetails', 'local_credentium'), ['class' => 'card-header']);
echo html_writer::start_div('card-body');

// Basic information.
echo html_writer::tag('p', html_writer::tag('strong', get_string('user') . ':') . ' ' . fullname($user));
echo html_writer::tag('p', html_writer::tag('strong', get_string('course') . ':') . ' ' . format_string($course->fullname));
echo html_writer::tag('p', html_writer::tag('strong', get_string('credentialtemplate', 'local_credentium') . ':') . ' ' . 
    ($template ? format_string($template->title) : $issuance->templateid));
echo html_writer::tag('p', html_writer::tag('strong', get_string('credentialid', 'local_credentium') . ':') . ' ' . 
    $issuance->credentialid);
echo html_writer::tag('p', html_writer::tag('strong', get_string('status', 'local_credentium') . ':') . ' ' . 
    get_string('status_' . $issuance->status, 'local_credentium'));

if (!is_null($issuance->grade)) {
    $gradeitem = \grade_item::fetch_course_item($issuance->courseid);
    if ($gradeitem && $gradeitem->grademax > 0) {
        $percentage = ($issuance->grade / $gradeitem->grademax) * 100;
        $gradetext = format_float($percentage, 2) . '%';
    } else {
        $gradetext = format_float($issuance->grade, 2);
    }
    echo html_writer::tag('p', html_writer::tag('strong', get_string('grade') . ':') . ' ' . $gradetext);
}

echo html_writer::tag('p', html_writer::tag('strong', get_string('issuedate', 'local_credentium') . ':') . ' ' . 
    userdate($issuance->timeissued ?: $issuance->timecreated));

// Try to get credential details from API if issued.
if ($issuance->status === 'issued' && $issuance->credentialid) {
    try {
        $client = new \local_credentium\api\client();
        $credential = $client->get_credential($issuance->credentialid);
        
        if ($credential && isset($credential->public_url)) {
            echo html_writer::tag('p', html_writer::link(
                $credential->public_url,
                get_string('viewcredentialexternal', 'local_credentium'),
                ['class' => 'btn btn-primary', 'target' => '_blank']
            ));
        }
    } catch (Exception $e) {
        // Silently fail if API is not available.
    }
}

echo html_writer::end_div();
echo html_writer::end_div();

// Back button.
$backurl = new moodle_url('/local/credentium/index.php');
echo html_writer::tag('p', html_writer::link($backurl, get_string('back'), ['class' => 'btn btn-secondary mt-3']));

echo $OUTPUT->footer();