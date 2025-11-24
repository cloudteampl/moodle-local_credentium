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
 * Settings for the local_credentium plugin.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Use external page instead of admin_settingpage for better UI consistency with category settings.
    $settings = new admin_externalpage(
        'local_credentium',
        get_string('pluginname', 'local_credentium'),
        new moodle_url('/local/credentium/admin_settings.php'),
        'moodle/site:config'
    );

    // Add the settings page to the 'Local plugins' category.
    $ADMIN->add('localplugins', $settings);

    // Also add the report page to the admin tree.
    if (isset($ADMIN)) {
        $ADMIN->add('reports', new admin_externalpage(
            'local_credentium_report',
            get_string('report', 'local_credentium'),
            new moodle_url('/local/credentium/index.php'),
            'local/credentium:viewreports'
        ));
    }
}