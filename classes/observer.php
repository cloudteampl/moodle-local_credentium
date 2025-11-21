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
 * Event observer for local_credentium.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium;

defined('MOODLE_INTERNAL') || die();

class observer {
    
    public static function course_completed(\core\event\course_completed $event) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/credentium/lib.php');

        if (!get_config('local_credentium', 'enabled')) {
            return;
        }

        $courseid = $event->courseid;
        $userid = $event->relateduserid;

        // Validate event data
        if (empty($courseid) || empty($userid)) {
            local_credentium_log('Invalid event data - missing courseid or userid');
            return;
        }

        // Get resolved course config with category inheritance
        $config = local_credentium_resolve_course_config($courseid);

        if (!$config || !$config->enabled) {
            return;
        }

        // Check if category config is paused
        if (!empty($config->paused)) {
            local_credentium_log('Issuance skipped - category is paused', [
                'courseid' => $courseid,
                'categoryid' => $config->categoryid ?? null
            ]);
            return;
        }

        // Always use completion trigger now
        if ($config->issuancetrigger !== 'completion') {
            return;
        }

        // Check if already issued
        $existing = $DB->get_record('local_credentium_issuances', [
            'userid' => $userid,
            'courseid' => $courseid,
            'status' => 'issued',
        ]);

        if ($existing) {
            return;
        }

        // Get the course grade using grade_get_course_grade
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');

        // Try to get the course grade
        try {
            $course_grade = \grade_get_course_grade($userid, $courseid);
        } catch (\Exception $e) {
            local_credentium_log('Error getting course grade', ['error' => $e->getMessage()]);
            $course_grade = null;
        }
        
        $issuance = new \stdClass();
        $issuance->userid = $userid;
        $issuance->courseid = $courseid;
        $issuance->templateid = $config->templateid;
        $issuance->status = 'pending';
        $issuance->attempts = 0;
        $issuance->categoryid = $config->categoryid ?? null; // Store category for rate limiting
        $issuance->timecreated = time();
        $issuance->timemodified = time();

        // Store the grade if available
        if ($course_grade && isset($course_grade->grade)) {
            $issuance->grade = $course_grade->grade;
        } else {
            // Grade might not be ready yet due to aggregation timing
            $issuance->grade = null;
        }

        $issuance->id = $DB->insert_record('local_credentium_issuances', $issuance);

        // Queue the task
        $task = new \local_credentium\task\issue_credential();
        $task->set_custom_data([
            'issuanceid' => $issuance->id,
            'categoryid' => $issuance->categoryid
        ]);

        // Only add delay if grade is needed but not available yet
        if (($config->sendgrade ?? false) && is_null($issuance->grade)) {
            $task->set_next_run_time(time() + 30); // 30 second delay for grade aggregation
        }

        \core\task\manager::queue_adhoc_task($task);
    }

}