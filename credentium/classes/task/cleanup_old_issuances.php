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
 * Scheduled task to clean up old credential issuance records (GDPR compliance).
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Cleanup old issuance records task.
 *
 * This task runs daily to delete credential issuance records older than
 * the configured retention period. This ensures GDPR compliance by implementing
 * data minimization and storage limitation principles.
 */
class cleanup_old_issuances extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:cleanupoldissuances', 'local_credentium');
    }

    /**
     * Execute the cleanup task.
     *
     * Deletes issuance records (regardless of status) that are older than
     * the configured data retention period.
     */
    public function execute() {
        global $DB;

        // Get the configured retention period (in seconds).
        $retentionperiod = get_config('local_credentium', 'dataretention');

        // Default to 365 days if not configured.
        if (empty($retentionperiod)) {
            $retentionperiod = 365 * DAYSECS;
            mtrace('Data retention period not configured, using default: 365 days');
        }

        // Calculate the cutoff timestamp.
        $cutofftime = time() - $retentionperiod;
        $cutoffdate = userdate($cutofftime, get_string('strftimedatetime', 'core_langconfig'));

        mtrace("Starting cleanup of credential issuance records older than: {$cutoffdate}");
        mtrace("Retention period: " . format_time($retentionperiod));

        // Count records to be deleted (for logging).
        $count = $DB->count_records_select('local_credentium_issuances',
            'timecreated < :cutoff',
            ['cutoff' => $cutofftime]
        );

        if ($count == 0) {
            mtrace('No old records found to delete.');
            return;
        }

        mtrace("Found {$count} issuance record(s) to delete.");

        // Delete old records.
        $deleted = $DB->delete_records_select('local_credentium_issuances',
            'timecreated < :cutoff',
            ['cutoff' => $cutofftime]
        );

        if ($deleted) {
            mtrace("Successfully deleted {$count} old issuance record(s).");
        } else {
            mtrace('Warning: Deletion may have failed. Please check database logs.');
        }

        mtrace('Cleanup task completed.');
    }
}
