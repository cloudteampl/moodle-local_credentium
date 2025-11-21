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
        $source = "Category: {$category->name}";
    } else {
        echo $OUTPUT->notification('Category configuration not found or not enabled.', 'notifyproblem');
        echo $OUTPUT->footer();
        die();
    }
} else {
    // Get global credentials
    $apiurl = get_config('local_credentium', 'apiurl');
    $apikey = get_config('local_credentium', 'apikey');
    $source = "Global Configuration";
}

// Log connection test (without sensitive data)
local_credentium_log('Testing connection', [
    'source' => $source,
    'categoryid' => $categoryid,
    'api_url_set' => !empty($apiurl),
    'api_key_set' => !empty($apikey)
]);

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
echo html_writer::tag('p', html_writer::tag('strong', 'Credentials Source: ') . s($source));
echo html_writer::tag('p', html_writer::tag('strong', 'API URL: ') .
    (!empty($apiurl) ? s($apiurl) : html_writer::tag('em', 'Not configured')));
echo html_writer::tag('p', html_writer::tag('strong', 'API Key: ') .
    (!empty($apikey) ? str_repeat('*', 20) . s(substr($apikey, -4)) : html_writer::tag('em', 'Not configured')));
echo $OUTPUT->box_end();

try {
    echo html_writer::tag('p', 'Attempting to fetch templates from the API...');

    // Explicitly pass the fresh config values to bypass any caching
    $client = new \local_credentium\api\client($apiurl, $apikey);
    
    // Clear template cache to force fresh API call
    global $DB;
    $DB->delete_records('local_credentium_templates_cache');
    
    $templates = $client->get_templates(false);
    $templatecount = count($templates);

    echo $OUTPUT->notification("Found {$templatecount} credential template(s).", 'notifysuccess');

    if ($templatecount > 0) {
        echo $OUTPUT->notification(get_string('connectionsuccessful', 'local_credentium'), 'notifysuccess');
        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
        echo html_writer::tag('h4', 'Templates Found:');
        $list = [];
        foreach ($templates as $template) {
            $list[] = s($template->title) . ' (ID: ' . s($template->id) . ')';
        }
        echo html_writer::alist($list);
        echo $OUTPUT->box_end();
    } else {
        echo $OUTPUT->notification("The API connection was successful, but the server returned 0 templates. Please verify your API key has access to templates.", 'notifyproblem');
    }

} catch (\Exception $e) {
    echo $OUTPUT->notification(get_string('connectionfailed', 'local_credentium', $e->getMessage()), 'notifyproblem');

    $debuginfo = $e->debuginfo ?? null;
    if (!empty($debuginfo)) {
        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter error');
        echo html_writer::tag('h3', 'Detailed Debug Information');
        echo html_writer::tag('pre', s($debuginfo));
        echo $OUTPUT->box_end();
    }
}

// Back link based on context
if ($categoryid) {
    $backurl = new moodle_url('/local/credentium/category_settings.php', ['id' => $categoryid]);
} else {
    $backurl = new moodle_url('/admin/settings.php', ['section' => 'local_credentium']);
}

echo html_writer::tag('p', html_writer::link($backurl, get_string('back')));

echo $OUTPUT->footer();