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
 * Capability definitions for the local_credentium plugin.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Manage global Credentium settings.
    'local/credentium:manage' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/site:config',
    ],

    // Manage course-level Credentium settings.
    // Note: Only granted to managers by default. Assign the "Credentium Course Manager" role
    // to specific users at course or category level to grant this permission.
    'local/credentium:managecourse' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Manage category-level Credentium settings.
    'local/credentium:managecategory' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // View Credentium reports.
    'local/credentium:viewreports' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // View own credentials.
    'local/credentium:viewowncredentials' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
    ],
];