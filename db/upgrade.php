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
 * Upgrade script for local_credentium.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool Always returns true
 */
function xmldb_local_credentium_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    // Moodle 4.5+ upgrade patterns.
    if ($oldversion < 2025012900) {
        // Initial version - no upgrades needed yet.
        
        // Example upgrade code for future versions:
        // Define table to be created.
        // $table = new xmldb_table('local_credentium_newfeature');
        
        // Adding fields to table.
        // $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        // $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        
        // Adding keys to table.
        // $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        // Conditionally launch create table.
        // if (!$dbman->table_exists($table)) {
        //     $dbman->create_table($table);
        // }
        
        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025012900, 'local', 'credentium');
    }
    
    if ($oldversion < 2025012935) {
        // Force refresh of scheduled tasks to register new task
        // Only reset tasks for our specific component to prevent any potential issues
        $component = 'local_credentium';
        // Validate component name to ensure it's safe
        if ($component === 'local_credentium' && core_component::is_valid_plugin_name('local', 'credentium')) {
            \core\task\manager::reset_scheduled_tasks_for_component($component);
        }
        
        upgrade_plugin_savepoint(true, 2025012935, 'local', 'credentium');
    }
    
    if ($oldversion < 2025012944) {
        // Fix database schema for templates cache table
        $table = new xmldb_table('local_credentium_templates_cache');
        
        // Rename metadata field to data
        $oldfield = new xmldb_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'description');
        $newfield = new xmldb_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null, 'description');
        if ($dbman->field_exists($table, $oldfield)) {
            $dbman->rename_field($table, $oldfield, 'data');
        }
        
        // Rename timecreated field to timecached
        $oldfield2 = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'active');
        if ($dbman->field_exists($table, $oldfield2)) {
            $dbman->rename_field($table, $oldfield2, 'timecached');
        }
        
        // Drop timemodified field if it exists
        $dropfield = new xmldb_field('timemodified');
        if ($dbman->field_exists($table, $dropfield)) {
            $dbman->drop_field($table, $dropfield);
        }
        
        upgrade_plugin_savepoint(true, 2025012944, 'local', 'credentium');
    }
    
    if ($oldversion < 2025073135) {
        // Add sendgrade field to course config table
        $table = new xmldb_table('local_credentium_course_config');
        $field = new xmldb_field('sendgrade', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'templateid');

        // Conditionally add field
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025073135, 'local', 'credentium');
    }

    if ($oldversion < 2025080100) {
        // Multi-tenant per-category configuration upgrade

        // Step 1: Add categoryid to issuances table for rate limiting tracking
        $table = new xmldb_table('local_credentium_issuances');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timeissued');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index for categoryid
        $index = new xmldb_index('categoryid', XMLDB_INDEX_NOTUNIQUE, ['categoryid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Step 2: Add inherit_category to course config table
        $table = new xmldb_table('local_credentium_course_config');
        $field = new xmldb_field('inherit_category', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'issuancetrigger');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index for inherit_category
        $index = new xmldb_index('inherit_category', XMLDB_INDEX_NOTUNIQUE, ['inherit_category']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Step 3: Modify templates cache for per-category isolation
        $table = new xmldb_table('local_credentium_templates_cache');

        // Drop old templateid unique index
        $oldindex = new xmldb_index('templateid', XMLDB_INDEX_UNIQUE, ['templateid']);
        if ($dbman->index_exists($table, $oldindex)) {
            $dbman->drop_index($table, $oldindex);
        }

        // Add categoryid field
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'active');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add new composite unique index (templateid + categoryid)
        $index = new xmldb_index('templateid-categoryid', XMLDB_INDEX_UNIQUE, ['templateid', 'categoryid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add categoryid index
        $index = new xmldb_index('categoryid', XMLDB_INDEX_NOTUNIQUE, ['categoryid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Step 4: Create category config table
        $table = new xmldb_table('local_credentium_category_config');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('apiurl', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('apikey', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('paused', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ratelimit', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('categoryid', XMLDB_KEY_FOREIGN_UNIQUE, ['categoryid'], 'course_categories', ['id']);

        $table->add_index('enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
        $table->add_index('paused', XMLDB_INDEX_NOTUNIQUE, ['paused']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Step 5: Clear template cache to force rebuild with new schema
        $DB->delete_records('local_credentium_templates_cache');

        upgrade_plugin_savepoint(true, 2025080100, 'local', 'credentium');
    }

    return true;
}