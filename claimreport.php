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
 * Claim status report for Credentium (admin).
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

use local_credentium\local\claimable;
use local_credentium\local\claim_config;
use local_credentium\task\sync_claim_status;

// Login, capability, URL, title and heading are all handled by the call below.
// Overriding the page heading here would render the report title twice.
admin_externalpage_setup('local_credentium_claimreport');

echo $OUTPUT->header();
echo local_credentium_report_tabs('claims');
echo $OUTPUT->heading(get_string('report_heading', 'local_credentium'));
echo html_writer::tag('p', get_string('report_intro', 'local_credentium'));

// The claim feature is built in since v3.0.0; a still-installed legacy Claim plugin
// was disabled by the upgrade, but only uninstalling it removes its dormant copy.
if (file_exists($CFG->dirroot . '/local/credentiumclaim/version.php')) {
    echo $OUTPUT->notification(
        get_string('uninstalllegacyclaim', 'local_credentium'),
        \core\output\notification::NOTIFY_WARNING
    );
}

// Blocking conditions first: they explain an otherwise mysteriously idle report.
if (!local_credentium_claim_enabled()) {
    echo $OUTPUT->notification(
        get_string('report_plugindisabled', 'local_credentium'),
        \core\output\notification::NOTIFY_WARNING
    );
} else if (!claim_config::is_usable()) {
    echo $OUTPUT->notification(
        get_string('report_connection_missing', 'local_credentium'),
        \core\output\notification::NOTIFY_ERROR
    );
}

// Status breakdown.
$counts = $DB->get_records_sql(
    'SELECT remotestatus, COUNT(*) AS cnt FROM {local_credentium_claims} GROUP BY remotestatus'
);

$total = 0;
$table = new html_table();
$table->head = [
    get_string('report_status', 'local_credentium'),
    get_string('report_count', 'local_credentium'),
];
$table->attributes['class'] = 'generaltable';

$statuses = [
    claimable::STATUS_PROCESSING,
    claimable::STATUS_ISSUED,
    claimable::STATUS_CLAIMED,
    claimable::STATUS_FAILED,
    claimable::STATUS_UNKNOWN,
];
foreach ($statuses as $status) {
    $cnt = isset($counts[$status]) ? (int) $counts[$status]->cnt : 0;
    $total += $cnt;
    $table->data[] = [get_string('claimstatus_' . $status, 'local_credentium'), $cnt];
}

echo html_writer::table($table);
echo html_writer::tag('p', get_string('report_total', 'local_credentium') . ': ' . $total);

// Diagnostics: what the sync task last did, and when it will run again.
echo $OUTPUT->heading(get_string('report_diagnostics', 'local_credentium'), 3);

$diagnostics = new html_table();
$diagnostics->attributes['class'] = 'generaltable';
$diagnostics->head = [
    get_string('report_property', 'local_credentium'),
    get_string('report_value', 'local_credentium'),
];

$never = get_string('report_never', 'local_credentium');

// API connection in use.
$credentials = claim_config::global_credentials();
$connectionvalue = ($credentials === null)
    ? get_string('report_connection_missing', 'local_credentium')
    : get_string('report_connection_ok', 'local_credentium', s($credentials->apiurl));
$diagnostics->data[] = [get_string('connection', 'local_credentium'), $connectionvalue];

// Schedule, plus the task's own last/next run times.
$interval = local_credentium_get_claim_sync_interval();
$schedule = ($interval === null)
    ? get_string('report_schedule_custom', 'local_credentium')
    : get_string('report_schedule_every', 'local_credentium', format_time($interval * MINSECS));
$diagnostics->data[] = [get_string('syncinterval', 'local_credentium'), $schedule];

$task = local_credentium_get_claim_sync_task();
if ($task !== null) {
    $lastrun = $task->get_last_run_time();
    $diagnostics->data[] = [
        get_string('report_tasklastrun', 'local_credentium'),
        empty($lastrun) ? $never : userdate($lastrun),
    ];
    $diagnostics->data[] = [
        get_string('report_tasknextrun', 'local_credentium'),
        userdate($task->get_next_scheduled_time()),
    ];
}

// Outcome of the most recent run, recorded by the task itself.
$lastrunresult = get_config('local_credentium', 'claimlastrunresult');
$lastrunerror = (string) get_config('local_credentium', 'claimlastrunerror');
switch ($lastrunresult) {
    case sync_claim_status::RESULT_OK:
        $counters = (object) [
            'polled' => (int) get_config('local_credentium', 'claimlastrunpolled'),
            'updated' => (int) get_config('local_credentium', 'claimlastrunupdated'),
        ];
        $outcome = get_string('report_result_ok', 'local_credentium', $counters);
        break;
    case sync_claim_status::RESULT_DISABLED:
        $outcome = get_string('report_result_disabled', 'local_credentium');
        break;
    case sync_claim_status::RESULT_NOTCONFIGURED:
        $outcome = get_string('report_result_notconfigured', 'local_credentium');
        break;
    case sync_claim_status::RESULT_ERROR:
        $outcome = get_string('report_result_error', 'local_credentium', s($lastrunerror));
        break;
    default:
        $outcome = $never;
}
$lastrun = (int) get_config('local_credentium', 'claimlastrun');
$diagnostics->data[] = [
    get_string('report_lastrun', 'local_credentium'),
    empty($lastrun) ? $never : userdate($lastrun),
];
$diagnostics->data[] = [get_string('report_lastresult', 'local_credentium'), $outcome];

// Bell notifications sent on the last run (confirms the "ready to claim" nudge fired).
$diagnostics->data[] = [
    get_string('report_notified', 'local_credentium'),
    (int) get_config('local_credentium', 'claimlastrunnotified'),
];

// Per-credential freshness.
$lastcheck = $DB->get_field_sql('SELECT MAX(timechecked) FROM {local_credentium_claims}');
$diagnostics->data[] = [
    get_string('report_lastsync', 'local_credentium'),
    empty($lastcheck) ? $never : userdate($lastcheck),
];

echo html_writer::table($diagnostics);

// Actionable warnings about the last run.
$unmatched = (int) get_config('local_credentium', 'claimlastrununmatched');
if ($lastrunresult === sync_claim_status::RESULT_ERROR) {
    // An API failure explains the missing statuses on its own; blaming the
    // identifiers here would send the admin looking in the wrong place.
    // The table above already carries the raw failure, so this box carries the part
    // an admin cannot derive from it: which of the five causes it was, and what to do.
    echo $OUTPUT->notification(
        local_credentium_claim_run_error_message(),
        \core\output\notification::NOTIFY_ERROR
    );
} else if ($unmatched > 0) {
    echo $OUTPUT->notification(
        get_string('report_unmatched', 'local_credentium', $unmatched),
        \core\output\notification::NOTIFY_WARNING
    );
}
$pending = (int) get_config('local_credentium', 'claimlastrununanswered');
if ($lastrunresult !== sync_claim_status::RESULT_ERROR && $pending > 0) {
    // A run can leave credentials unchecked without failing — it can simply run out
    // of its time budget. Saying so beats a clean "Completed" that quietly did less.
    echo $OUTPUT->notification(
        get_string('report_error_pending', 'local_credentium', $pending),
        \core\output\notification::NOTIFY_WARNING
    );
}
$unresolved = (int) get_config('local_credentium', 'claimlastrununresolved');
if ($unresolved > 0) {
    echo $OUTPUT->notification(
        get_string('report_unresolved', 'local_credentium', $unresolved),
        \core\output\notification::NOTIFY_WARNING
    );
}
$notifyfailed = (int) get_config('local_credentium', 'claimlastrunnotifyfailed');
if ($notifyfailed > 0) {
    echo $OUTPUT->notification(
        get_string('report_notifyfailed', 'local_credentium', $notifyfailed),
        \core\output\notification::NOTIFY_WARNING
    );
}

// Run the check on demand (site administrators only).
if (has_capability('moodle/site:config', context_system::instance())) {
    $form = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => (new moodle_url('/local/credentium/claimsyncnow.php'))->out(false),
        'class' => 'mt-3',
    ]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $form .= html_writer::tag(
        'button',
        get_string('report_checknow', 'local_credentium'),
        ['type' => 'submit', 'class' => 'btn btn-secondary']
    );
    $form .= html_writer::end_tag('form');
    echo $form;
}

echo $OUTPUT->footer();
