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
 * Post-installation script for the local_credentium plugin.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute post-installation tasks.
 *
 * @return bool
 */
function xmldb_local_credentium_install() {
    // Persist the claim banner default at install time. The setting lives on a
    // custom moodleform, so unlike an admin_setting its default is never written
    // automatically; runtime code treats an unset value as "on" anyway, but
    // persisting it keeps what the admin UI shows and what the site does in sync.
    set_config('showbanner', 1, 'local_credentium');

    // Everything else is managed through the standard Moodle permissions system
    // (Site Administration > Users > Permissions > Define roles).
    return true;
}
