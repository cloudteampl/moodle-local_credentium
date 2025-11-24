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
 * Credential failed event.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a credential issuance fails.
 */
class credential_failed extends \core\event\base {
    
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_credentium_issuances';
    }
    
    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcredentialfailed', 'local_credentium');
    }
    
    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $error = isset($this->other['error']) ? ": {$this->other['error']}" : '';
        return "Failed to issue credential for user with id '$this->userid' " .
               "for course with id '$this->courseid'{$error}";
    }
    
    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/credentium/index.php', 
            ['action' => 'view', 'id' => $this->objectid]);
    }
    
    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        
        if (!isset($this->other['templateid'])) {
            throw new \coding_exception('The \'templateid\' value must be set in other.');
        }
    }
    
    /**
     * Create instance of event.
     *
     * @param \stdClass $issuance The issuance record
     * @param \context_course $context The course context
     * @param string $error Optional error message
     * @return credential_failed
     */
    public static function create_from_issuance($issuance, $context, $error = '') {
        $data = [
            'context' => $context,
            'objectid' => $issuance->id,
            'courseid' => $issuance->courseid,
            'relateduserid' => $issuance->userid,
            'other' => [
                'templateid' => $issuance->templateid,
                'attempts' => $issuance->attempts,
                'error' => $error,
            ],
        ];
        
        return self::create($data);
    }
    
    /**
     * Get objectid mapping.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'local_credentium_issuances', 'restore' => 'local_credentium_issuance'];
    }
    
    /**
     * Get other mapping.
     *
     * @return array
     */
    public static function get_other_mapping() {
        $othermapped = [];
        $othermapped['templateid'] = ['db' => 'local_credentium_templates_cache', 'restore' => \core\event\base::NOT_MAPPED];
        
        return $othermapped;
    }
}