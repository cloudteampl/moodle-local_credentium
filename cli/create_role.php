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
 * CLI script to create the Credentium Course Manager role.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Check if role already exists.
$existingrole = $DB->get_record('role', ['shortname' => 'credentiumcoursemanager']);

if ($existingrole) {
    cli_writeln("Role 'Credentium Course Manager' already exists (ID: {$existingrole->id})");
    exit(0);
}

cli_writeln("Creating 'Credentium Course Manager' role...");

// Create the role.
$roleid = create_role(
    get_string('credentiumcoursemanager', 'local_credentium'),
    'credentiumcoursemanager',
    get_string('credentiumcoursemanager_desc', 'local_credentium'),
    'editingteacher'
);

if (!$roleid) {
    cli_error("Failed to create role!");
}

cli_writeln("Role created with ID: {$roleid}");

// Set context levels where this role can be assigned.
set_role_contextlevels($roleid, [CONTEXT_COURSE, CONTEXT_COURSECAT]);
cli_writeln("Set context levels: COURSE and COURSECAT");

// Assign Credentium-specific capability to this role.
assign_capability('local/credentium:managecourse', CAP_ALLOW, $roleid, context_system::instance()->id);
cli_writeln("Assigned capability: local/credentium:managecourse");

cli_writeln("\nSuccess! Role 'Credentium Course Manager' has been created.");
cli_writeln("You can now assign this role to users at course or category level.");
cli_writeln("\nTo assign:");
cli_writeln("  - Course level: Course > Participants > Assign roles > Credentium Course Manager");
cli_writeln("  - Category level: Category > Assign roles > Credentium Course Manager");

exit(0);
