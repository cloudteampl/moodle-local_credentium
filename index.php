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
 * Admin report page for Credentium.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);
$filter = optional_param('filter', '', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

require_login();
$context = context_system::instance();
require_capability('local/credentium:viewreports', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/credentium/index.php', [
    'action' => $action,
    'page' => $page,
    'perpage' => $perpage,
    'filter' => $filter,
    'courseid' => $courseid,
    'userid' => $userid,
    'search' => $search,
]);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('report', 'local_credentium'));
$PAGE->set_heading(get_string('report', 'local_credentium'));

// Handle actions.
if ($action === 'retry' && $id) {
    require_sesskey();
    $issuance = $DB->get_record('local_credentium_issuances', ['id' => $id]);
    
    if (!$issuance) {
        redirect(new moodle_url('/local/credentium/index.php'), 
            get_string('recordnotfound', 'local_credentium'), 
            null, 
            \core\output\notification::NOTIFY_ERROR);
    }
    
    $issuance->status = 'pending';
    $issuance->attempts = 0;
    $issuance->errorcode = null;
    $issuance->errormessage = null;
    $issuance->timemodified = time();
    $DB->update_record('local_credentium_issuances', $issuance);
    
    $task = new \local_credentium\task\issue_credential();
    $task->set_custom_data(['issuanceid' => $issuance->id]);
    \core\task\manager::queue_adhoc_task($task);
    
    redirect(new moodle_url('/local/credentium/index.php'), 
        get_string('retryscheduled', 'local_credentium'), 
        null, 
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

    // Add buttons for processing credentials and refreshing the page.
    echo html_writer::start_div('mb-3');

    // Check if there are pending or retrying credentials
    $pendingcount = $DB->count_records_select('local_credentium_issuances', 
        "status IN ('pending', 'retrying')");
    if ($pendingcount > 0) {
        $processurl = new moodle_url('/local/credentium/process.php', ['sesskey' => sesskey()]);
        echo $OUTPUT->single_button($processurl, get_string('processpending', 'local_credentium', $pendingcount), 'post', ['class' => 'btn btn-primary']);
    }

    $refreshurl = new moodle_url($PAGE->url);
    echo $OUTPUT->single_button($refreshurl, get_string('refresh'), 'get');

    echo html_writer::end_div();

    // Add filter form
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url, 'class' => 'form-inline mb-3']);
echo html_writer::start_div('', ['style' => 'display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 20px;']);

// Status filter
echo html_writer::start_div('');
echo html_writer::label(get_string('status', 'local_credentium') . ': ', 'filter', true, ['class' => 'mr-2']);
$statusoptions = [
    '' => get_string('all'),
    'pending' => get_string('status_pending', 'local_credentium'),
    'issued' => get_string('status_issued', 'local_credentium'),
    'failed' => get_string('status_failed', 'local_credentium'),
    'retrying' => get_string('status_retrying', 'local_credentium'),
];
echo html_writer::select($statusoptions, 'filter', $filter, null, ['class' => 'form-control']);
echo html_writer::end_div();

// Course filter
echo html_writer::start_div('');
echo html_writer::label(get_string('course') . ': ', 'courseid', true, ['class' => 'mr-2']);
$courses = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.fullname 
    FROM {course} c
    JOIN {local_credentium_issuances} lci ON lci.courseid = c.id
    ORDER BY c.fullname
    LIMIT 100
");
$courseoptions = ['' => get_string('all')];
foreach ($courses as $course) {
    $courseoptions[$course->id] = $course->fullname;
}
// If current courseid is not in the list, add it
if ($courseid && !isset($courseoptions[$courseid])) {
    $currentcourse = $DB->get_record('course', ['id' => $courseid]);
    if ($currentcourse) {
        $courseoptions[$courseid] = $currentcourse->fullname;
    }
}
echo html_writer::select($courseoptions, 'courseid', $courseid, null, ['class' => 'form-control']);
echo html_writer::end_div();

// Search box
echo html_writer::start_div('');
echo html_writer::label(get_string('search') . ': ', 'search', true, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'id' => 'search',
    'value' => $search,
    'placeholder' => get_string('searchuser', 'local_credentium'),
    'class' => 'form-control'
]);
echo html_writer::end_div();

// Submit buttons
echo html_writer::start_div('');
echo html_writer::tag('button', get_string('filter'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/credentium/index.php'), get_string('reset'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_tag('form');

// Create and setup table
$table = new flexible_table('local-credentium-report');
$table->define_baseurl($PAGE->url);

// Define table columns and headers
$columns = ['user', 'course', 'template', 'status', 'grade', 'timecreated', 'actions'];
$headers = [
    get_string('user'),
    get_string('course'),
    get_string('credentialtemplate', 'local_credentium'),
    get_string('status', 'local_credentium'),
    get_string('grade', 'local_credentium'),
    get_string('issuedate', 'local_credentium'),
    get_string('actions', 'local_credentium'),
];
$table->define_columns($columns);
$table->define_headers($headers);
$table->sortable(true, 'timecreated', SORT_DESC);
$table->no_sorting('actions');
$table->collapsible(true);
$table->initialbars(true);
$table->pageable(true);
$table->setup();

$select = "lci.id, lci.userid, lci.courseid, lci.templateid, lci.status, lci.grade, lci.timecreated, 
           lci.timemodified, lci.attempts, lci.errorcode, lci.errormessage, lci.credentialid,
           u.firstname, u.lastname, u.email, c.fullname as coursename";
$from = "{local_credentium_issuances} lci JOIN {user} u ON u.id = lci.userid JOIN {course} c ON c.id = lci.courseid";
$where = "1=1";
$params = [];

// Apply filters
if (!empty($filter)) {
    $where .= " AND lci.status = :status";
    $params['status'] = $filter;
}

if (!empty($courseid)) {
    $where .= " AND lci.courseid = :courseid";
    $params['courseid'] = $courseid;
}

if (!empty($userid)) {
    $where .= " AND lci.userid = :userid";
    $params['userid'] = $userid;
}

if (!empty($search)) {
    // Use CONCAT for cross-database compatibility
    $searchsql = $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname'), ':search1', false, false) . " OR " .
                 $DB->sql_like('u.firstname', ':search2', false, false) . " OR " .
                 $DB->sql_like('u.lastname', ':search3', false, false) . " OR " .
                 $DB->sql_like('u.email', ':search4', false, false);
    $where .= " AND ($searchsql)";
    $searchparam = '%' . $DB->sql_like_escape($search) . '%';
    $params['search1'] = $searchparam;
    $params['search2'] = $searchparam;
    $params['search3'] = $searchparam;
    $params['search4'] = $searchparam;
}

$totalcount = $DB->count_records_sql("SELECT COUNT(*) FROM $from WHERE $where", $params);
$table->pagesize($perpage, $totalcount);

// Build sort SQL safely
$sort = $table->get_sql_sort();

// Map column names to their SQL aliases for security
if (!empty($sort)) {
    // Parse the sort string to extract column and direction
    $sortparts = explode(' ', trim($sort));
    $sortcolumn = $sortparts[0] ?? '';
    $sortdirection = $sortparts[1] ?? 'ASC';
    
    // Whitelist of allowed sort columns with their SQL aliases
    $sortablecolumns = [
        'user' => 'u.lastname',
        'course' => 'c.fullname', 
        'template' => 'lci.templateid',
        'status' => 'lci.status',
        'grade' => 'lci.grade',
        'timecreated' => 'lci.timecreated'
    ];
    
    // Build ORDER BY clause with whitelisted columns only
    if (isset($sortablecolumns[$sortcolumn])) {
        $sortdirection = ($sortdirection === 'DESC') ? 'DESC' : 'ASC';
        $orderby = " ORDER BY " . $sortablecolumns[$sortcolumn] . " " . $sortdirection;
    } else {
        $orderby = '';
    }
} else {
    $orderby = '';
}

$sql = "SELECT $select FROM $from WHERE $where" . $orderby;
$records = $DB->get_records_sql($sql, $params, $table->get_page_start(), $table->get_page_size());

foreach ($records as $record) {
    $row = [];
    // Create a user object for fullname function
    $userobj = new \stdClass();
    $userobj->firstname = $record->firstname;
    $userobj->lastname = $record->lastname;
    $row[] = html_writer::link(new moodle_url('/user/profile.php', ['id' => $record->userid]), fullname($userobj));
    $row[] = html_writer::link(new moodle_url('/course/view.php', ['id' => $record->courseid]), $record->coursename);
    $row[] = $record->templateid;
    
    // Status with error info
    $statustext = get_string('status_' . $record->status, 'local_credentium');
    if (($record->status === 'failed' || $record->status === 'retrying') && !empty($record->errormessage)) {
        $statustext .= ' ' . html_writer::tag('i', '', [
            'class' => 'fa fa-info-circle',
            'title' => $record->errormessage,
            'data-toggle' => 'tooltip',
        ]);
    }
    $row[] = html_writer::tag('span', $statustext, ['class' => 'badge badge-' . 
        ($record->status === 'issued' ? 'success' : 
        ($record->status === 'failed' ? 'danger' : 'warning'))]);
    
    $row[] = $record->grade ?? '-';
    $row[] = userdate($record->timecreated);
    
    $actions = [];
    if ($record->status === 'failed' || $record->status === 'pending' || $record->status === 'retrying') {
        $retryurl = new moodle_url($PAGE->url, ['action' => 'retry', 'id' => $record->id, 'sesskey' => sesskey()]);
        $actions[] = html_writer::link($retryurl, get_string('retry', 'local_credentium'), ['class' => 'btn btn-sm btn-warning']);
    }
    
    // Add debug link for all records
    $debugurl = new moodle_url('/local/credentium/debug.php', ['id' => $record->id]);
    $actions[] = html_writer::link($debugurl, 'Debug', ['class' => 'btn btn-sm btn-info']);
    
    $row[] = implode(' ', $actions);
    
    $table->add_data($row);
}

$table->print_html();

// Show summary statistics - need to use the same FROM clause as the main query
$stats = $DB->get_record_sql("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN lci.status = 'issued' THEN 1 ELSE 0 END) as issued,
        SUM(CASE WHEN lci.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN lci.status = 'failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN lci.status = 'retrying' THEN 1 ELSE 0 END) as retrying
    FROM $from
    WHERE $where
", $params);

echo html_writer::start_div('generalbox', ['style' => 'margin-top: 20px;']);
echo html_writer::tag('h3', get_string('summary', 'local_credentium'));
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-around; text-align: center;']);

// Total
echo html_writer::start_div('');
echo html_writer::tag('h3', $stats->total ?? 0);
echo html_writer::tag('p', get_string('total'));
echo html_writer::end_div();

// Issued
echo html_writer::start_div('');
echo html_writer::tag('h3', $stats->issued ?? 0, ['style' => 'color: #28a745;']);
echo html_writer::tag('p', get_string('status_issued', 'local_credentium'));
echo html_writer::end_div();

// Pending + Retrying
echo html_writer::start_div('');
echo html_writer::tag('h3', ($stats->pending ?? 0) + ($stats->retrying ?? 0), ['style' => 'color: #ffc107;']);
echo html_writer::tag('p', get_string('inprogress', 'local_credentium'));
echo html_writer::end_div();

// Failed
echo html_writer::start_div('');
echo html_writer::tag('h3', $stats->failed ?? 0, ['style' => 'color: #dc3545;']);
echo html_writer::tag('p', get_string('status_failed', 'local_credentium'));
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();