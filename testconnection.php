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
 * Test connection to Credentium API.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

$categoryid = optional_param('categoryid', null, PARAM_INT);

require_login();
require_sesskey();

// Check capabilities based on context
if ($categoryid) {
    $context = context_coursecat::instance($categoryid);
    require_capability('local/credentium:managecategory', $context);
    $category = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);
    $heading = get_string('testconnection', 'local_credentium') . ' - ' . $category->name;
} else {
    $context = context_system::instance();
    require_capability('moodle/site:config', $context);
    $heading = get_string('testconnection', 'local_credentium');
}

$PAGE->set_context($context);
$PAGE->set_url('/local/credentium/testconnection.php', $categoryid ? ['categoryid' => $categoryid] : []);
$PAGE->set_title(get_string('testconnection', 'local_credentium'));
$PAGE->set_heading($heading);

echo $OUTPUT->header();

// Clear any cached config values to ensure we get fresh data
cache_helper::purge_by_definition('core', 'config');

// Get API credentials based on context
if ($categoryid) {
    // Get category-specific credentials
    $config = local_credentium_get_category_config($categoryid);
    if ($config && $config->enabled) {
        $apiurl = $config->apiurl;
        $apikey = $config->apikey;
        $source = get_string('testconnection_category_prefix', 'local_credentium', $category->name);
    } else {
        echo $OUTPUT->notification(get_string('testconnection_category_notfound', 'local_credentium'), 'notifyproblem');
        echo $OUTPUT->footer();
        die();
    }
} else {
    // Get global credentials
    $apiurl = get_config('local_credentium', 'apiurl');
    $apikey = get_config('local_credentium', 'apikey');
    $source = get_string('testconnection_global_config', 'local_credentium');
}

// Log connection test (without sensitive data)
local_credentium_log('Testing connection', [
    'source' => $source,
    'categoryid' => $categoryid,
    'api_url_set' => !empty($apiurl),
    'api_key_set' => !empty($apikey)
]);

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
echo html_writer::tag('p', html_writer::tag('strong', get_string('testconnection_credentials_source', 'local_credentium') . ' ') . s($source));
echo html_writer::tag('p', html_writer::tag('strong', get_string('testconnection_apiurl_label', 'local_credentium') . ' ') .
    (!empty($apiurl) ? s($apiurl) : html_writer::tag('em', get_string('testconnection_notconfigured', 'local_credentium'))));
echo html_writer::tag('p', html_writer::tag('strong', get_string('testconnection_apikey_label', 'local_credentium') . ' ') .
    (!empty($apikey) ? str_repeat('*', 20) . s(substr($apikey, -4)) : html_writer::tag('em', get_string('testconnection_notconfigured', 'local_credentium'))));
echo $OUTPUT->box_end();

try {
    echo html_writer::tag('p', get_string('testconnection_attempting', 'local_credentium'));

    // Explicitly pass the fresh config values to bypass any caching
    $client = new \local_credentium\api\client($apiurl, $apikey);

    // Clear template cache to force fresh API call
    global $DB;
    $DB->delete_records('local_credentium_templates_cache');

    $templates = $client->get_templates(false);
    $templatecount = count($templates);

    echo $OUTPUT->notification(get_string('testconnection_found_templates', 'local_credentium', $templatecount), 'notifysuccess');

    if ($templatecount > 0) {
        echo $OUTPUT->notification(get_string('connectionsuccessful', 'local_credentium'), 'notifysuccess');
        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
        echo html_writer::tag('h4', get_string('testconnection_templates_heading', 'local_credentium'));
        $list = [];
        foreach ($templates as $template) {
            $list[] = s($template->title) . ' (ID: ' . s($template->id) . ')';
        }
        echo html_writer::alist($list);
        echo $OUTPUT->box_end();
    } else {
        echo $OUTPUT->notification(get_string('testconnection_no_templates', 'local_credentium'), 'notifyproblem');
    }

} catch (\Exception $e) {
    echo $OUTPUT->notification(get_string('connectionfailed', 'local_credentium', $e->getMessage()), 'notifyproblem');

    $debuginfo = $e->debuginfo ?? null;
    if (!empty($debuginfo)) {
        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter error');
        echo html_writer::tag('h3', get_string('testconnection_debug_heading', 'local_credentium'));
        echo html_writer::tag('pre', s($debuginfo));
        echo $OUTPUT->box_end();
    }
}

// Back link based on context
if ($categoryid) {
    $backurl = new moodle_url('/local/credentium/category_settings.php', ['id' => $categoryid]);
} else {
    $backurl = new moodle_url('/local/credentium/admin_settings.php');
}

echo html_writer::tag('p', html_writer::link($backurl, get_string('back')));

echo $OUTPUT->footer();