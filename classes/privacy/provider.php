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
 * Privacy provider for local_credentium.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation for local_credentium.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        // Local database storage.
        $collection->add_database_table(
            'local_credentium_issuances',
            [
                'userid' => 'privacy:metadata:local_credentium_issuances:userid',
                'courseid' => 'privacy:metadata:local_credentium_issuances:courseid',
                'credentialid' => 'privacy:metadata:local_credentium_issuances:credentialid',
                'status' => 'privacy:metadata:local_credentium_issuances:status',
                'timecreated' => 'privacy:metadata:local_credentium_issuances:timecreated',
            ],
            'privacy:metadata:local_credentium_issuances'
        );

        // External service - Credentium API (PAID commercial service).
        $collection->add_external_location_link(
            'credentium_api',
            [
                'email' => 'privacy:metadata:credentium_api:email',
                'firstname' => 'privacy:metadata:credentium_api:firstname',
                'lastname' => 'privacy:metadata:credentium_api:lastname',
                'coursename' => 'privacy:metadata:credentium_api:coursename',
                'grade' => 'privacy:metadata:credentium_api:grade',
                'templateid' => 'privacy:metadata:credentium_api:templateid',
            ],
            'privacy:metadata:credentium_api'
        );

        return $collection;
    }
    
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        
        // Add course contexts where user has credential issuances.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_credentium_issuances} lci ON lci.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND lci.userid = :userid";
        
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];
        
        $contextlist->add_from_sql($sql, $params);
        
        // Also add user context.
        $contextlist->add_user_context($userid);
        
        return $contextlist;
    }
    
    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        
        if ($context->contextlevel == CONTEXT_COURSE) {
            // Get users with credential issuances in this course.
            $sql = "SELECT DISTINCT userid
                      FROM {local_credentium_issuances}
                     WHERE courseid = :courseid";
            
            $params = ['courseid' => $context->instanceid];
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }
    
    /**
     * Export personal data for the given approved_contextlist.
     *
     * @param approved_contextlist $contextlist A list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        
        if (empty($contextlist->count())) {
            return;
        }
        
        $userid = $contextlist->get_user()->id;
        
        // Process each context.
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                // Export credential issuances for this course.
                $sql = "SELECT lci.*, lctc.name as templatename
                          FROM {local_credentium_issuances} lci
                     LEFT JOIN {local_credentium_templates_cache} lctc ON lctc.templateid = lci.templateid
                         WHERE lci.userid = :userid
                           AND lci.courseid = :courseid
                      ORDER BY lci.timecreated";
                
                $params = [
                    'userid' => $userid,
                    'courseid' => $context->instanceid,
                ];
                
                $issuances = $DB->get_records_sql($sql, $params);
                
                if ($issuances) {
                    $data = [];
                    foreach ($issuances as $issuance) {
                        $data[] = [
                            'templatename' => $issuance->templatename,
                            'credentialid' => $issuance->credentialid,
                            'status' => get_string('status_' . $issuance->status, 'local_credentium'),
                            'grade' => $issuance->grade,
                            'timecreated' => transform::datetime($issuance->timecreated),
                            'timeissued' => $issuance->timeissued ? transform::datetime($issuance->timeissued) : '',
                        ];
                    }
                    
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'local_credentium'), get_string('issuancehistory', 'local_credentium')],
                        (object)['issuances' => $data]
                    );
                }
            } else if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $userid) {
                // Export all credential issuances for this user.
                $sql = "SELECT lci.*, lctc.name as templatename, c.fullname as coursename
                          FROM {local_credentium_issuances} lci
                     LEFT JOIN {local_credentium_templates_cache} lctc ON lctc.templateid = lci.templateid
                     LEFT JOIN {course} c ON c.id = lci.courseid
                         WHERE lci.userid = :userid
                      ORDER BY lci.timecreated";
                
                $params = ['userid' => $userid];
                $issuances = $DB->get_records_sql($sql, $params);
                
                if ($issuances) {
                    $data = [];
                    foreach ($issuances as $issuance) {
                        $data[] = [
                            'coursename' => $issuance->coursename,
                            'templatename' => $issuance->templatename,
                            'credentialid' => $issuance->credentialid,
                            'status' => get_string('status_' . $issuance->status, 'local_credentium'),
                            'grade' => $issuance->grade,
                            'timecreated' => transform::datetime($issuance->timecreated),
                            'timeissued' => $issuance->timeissued ? transform::datetime($issuance->timeissued) : '',
                        ];
                    }
                    
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'local_credentium'), get_string('issuancehistory', 'local_credentium')],
                        (object)['issuances' => $data]
                    );
                }
            }
        }
    }
    
    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        
        if ($context->contextlevel == CONTEXT_COURSE) {
            // Delete all credential issuances for this course.
            $DB->delete_records('local_credentium_issuances', ['courseid' => $context->instanceid]);
        }
    }
    
    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist A list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        
        if (empty($contextlist->count())) {
            return;
        }
        
        $userid = $contextlist->get_user()->id;
        
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                // Delete credential issuances for this user in this course.
                $DB->delete_records('local_credentium_issuances', [
                    'userid' => $userid,
                    'courseid' => $context->instanceid,
                ]);
            } else if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $userid) {
                // Delete all credential issuances for this user.
                $DB->delete_records('local_credentium_issuances', ['userid' => $userid]);
            }
        }
    }
    
    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        
        $context = $userlist->get_context();
        
        if ($context->contextlevel == CONTEXT_COURSE) {
            $userids = $userlist->get_userids();
            if (!empty($userids)) {
                list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
                $params['courseid'] = $context->instanceid;
                
                $DB->delete_records_select('local_credentium_issuances',
                    "userid $insql AND courseid = :courseid", $params);
            }
        }
    }
}