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
 * Scheduled task to process pending credentials.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to process pending credentials.
 */
class process_pending_credentials extends \core\task\scheduled_task {
    
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:processpending', 'local_credentium');
    }
    
    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        
        if (!get_config('local_credentium', 'enabled')) {
            mtrace('Credentium integration is disabled.');
            return;
        }
        
        // Get pending credentials (limit to prevent timeout)
        // Only fetch required columns for better performance
        $pendingissuances = $DB->get_records('local_credentium_issuances', 
            ['status' => 'pending'], 
            'timecreated ASC', 
            'id, userid, courseid', 
            0, 
            50
        );
        
        if (empty($pendingissuances)) {
            mtrace('No pending credentials to process.');
            return;
        }
        
        mtrace('Processing ' . count($pendingissuances) . ' pending credential(s)...');
        
        foreach ($pendingissuances as $issuance) {
            // Queue an ad-hoc task for each pending credential
            $task = new issue_credential();
            $task->set_custom_data(['issuanceid' => $issuance->id]);
            \core\task\manager::queue_adhoc_task($task);
            
            mtrace("Queued credential issuance for user {$issuance->userid} in course {$issuance->courseid}");
        }
        
        mtrace('Finished queueing pending credentials.');
    }
}