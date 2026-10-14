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
 * Credentium API client.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium\api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * HTTP client for the legacy Credentium issue API surface.
 *
 * Issues credentials and fetches/caches credential templates. The claim
 * feature's v2 surface (status polls, claim links) lives in {@see claim_client}.
 */
class client {
    /** @var string|null Base API URL. */
    private $apiurl;
    /** @var string|null API key sent as the API-KEY header. */
    private $apikey;
    /** @var int|null Category id used for template cache isolation (null = global cache). */
    private $categoryid;
    /** @var int Attempts per request. Set to 1 for easier debugging. */
    private const MAX_RETRIES = 1;
    /** @var int Seconds between retry attempts. */
    private const RETRY_DELAY = 2;

    /**
     * Constructor for API client.
     *
     * @param string|null $apiurl API URL (defaults to global config)
     * @param string|null $apikey API key (defaults to global config)
     * @param int|null $categoryid Category ID for cache isolation (null = global cache)
     */
    public function __construct($apiurl = null, $apikey = null, $categoryid = null) {
        require_once(__DIR__ . '/../../lib.php');
        $this->apiurl = $apiurl ?: get_config('local_credentium', 'apiurl');
        // The stored global key is encrypted at rest; the accessor decrypts it.
        $this->apikey = $apikey ?: local_credentium_get_global_apikey();
        $this->categoryid = $categoryid;

        // Validate API URL.
        if ($this->apiurl && !filter_var($this->apiurl, FILTER_VALIDATE_URL)) {
            throw new \moodle_exception('invalidapiurl', 'local_credentium');
        }

        // Ensure API key is a string - use PARAM_RAW_TRIMMED to preserve dots in the key
        // API keys use format: cred_xxxxx.yyyyy where the dot separates public ID from secret.
        if ($this->apikey) {
            $this->apikey = clean_param($this->apikey, PARAM_RAW_TRIMMED);
        }

        // Validate categoryid if provided.
        if ($this->categoryid !== null) {
            $this->categoryid = clean_param($this->categoryid, PARAM_INT);
        }
    }

    /**
     * Fetch the credential templates, from the per-category cache when fresh.
     *
     * @param bool $activeonly Only return templates that carry assessments.
     * @return array List of template objects.
     */
    public function get_templates($activeonly = true) {
        // Try to get from cache first.
        $cachedtemplates = $this->get_cached_templates($activeonly);
        if ($cachedtemplates !== null) {
            return $cachedtemplates;
        }

        // Cache miss, fetch from API.
        $params = $activeonly ? ['assessmentsOnly' => 'true'] : [];
        try {
            $response = $this->request('GET', '/credential-template', $params);
            $templates = is_array($response) ? $response : [];
            $this->cache_templates($templates);
            return $templates;
        } catch (\Exception $e) {
            // Re-throw the exception to be caught by the test page.
            throw $e;
        }
    }

    /**
     * Request the issuance of one credential for one user.
     *
     * @param string $templateid Credentium template id.
     * @param \stdClass $user Recipient (email, firstname, lastname, and the configured identifier are sent).
     * @param \stdClass $course Course the credential is for (not transmitted).
     * @param array $additionaldata Optional extras; 'grade' is sent when present.
     * @return \stdClass Normalised response carrying a credential_id property.
     */
    public function issue_credential($templateid, $user, $course, $additionaldata = []) {
        $data = self::build_issue_payload(
            $templateid,
            $user,
            $additionaldata,
            (string) get_config('local_credentium', 'externalidfield')
        );

        try {
            $response = $this->request('POST', '/credential/issue', [], $data);
            $result = new \stdClass();
            if (is_string($response)) {
                $result->credential_id = $response;
            } else if (isset($response->id)) {
                $result->credential_id = $response->id;
            } else if (!isset($response->credential_id)) {
                throw new \moodle_exception('invalidresponse', 'local_credentium', '', null, $response);
            } else {
                $result = $response;
            }
            return $result;
        } catch (\Exception $e) {
            throw new \moodle_exception('issuancefailed', 'local_credentium', '', $e->getMessage(), $e);
        }
    }

    /**
     * Body of one issue request.
     *
     * @param string $templateid Credentium template id.
     * @param \stdClass $user Recipient (email, firstname, lastname, and the configured identifier field).
     * @param array $additionaldata Optional extras; 'grade' is sent when present.
     * @param string $externalidfield The "person identifier" setting ('' sends none).
     * @return array
     */
    public static function build_issue_payload($templateid, \stdClass $user, array $additionaldata, string $externalidfield): array {
        $data = [
            'credentialTemplateId' => $templateid,
            'personEmail' => $user->email,
            'personGivenName' => $user->firstname,
            'personFamilyName' => $user->lastname,
        ];

        // The same identifier makes Credentium treat issuances as one person, even across e-mail changes.
        $externalid = \local_credentium\local\person_external_id::from_user($user, $externalidfield);
        if ($externalid !== null) {
            $data['personExternalId'] = $externalid;
        }

        // Add grade only if provided.
        if (isset($additionaldata['grade'])) {
            $data['grade'] = (string)$additionaldata['grade'];
        }

        return $data;
    }

    /**
     * Build and execute one API request and decode the JSON response.
     *
     * @param string $method HTTP method (GET or POST).
     * @param string $endpoint Path beginning with '/'.
     * @param array $params Query parameters.
     * @param array|null $data Request body (JSON-encoded when present).
     * @return mixed Decoded response ([] for an empty successful body).
     */
    private function request($method, $endpoint, $params = [], $data = null) {
        if (empty($this->apiurl) || empty($this->apikey)) {
            throw new \moodle_exception('apinotconfigured', 'local_credentium');
        }

        $url = rtrim($this->apiurl, '/') . '/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $headers = [
            'API-KEY: ' . $this->apikey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        // Create a fresh curl instance for each request to avoid state issues.
        $curl = new \curl(['debug' => false]);

        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_TIMEOUT' => 30,
        ];

        // Log API request without sensitive data.
        require_once(__DIR__ . '/../../lib.php');
        local_credentium_log("API Request: $method " . parse_url($url, PHP_URL_PATH));

        // Use appropriate method based on request type.
        if ($method === 'POST') {
            $curl->setopt($options);
            $postdata = json_encode($data);
            $responsebody = $curl->post($url, $postdata);
        } else {
            $curl->setopt($options);
            $responsebody = $curl->get($url);
        }
        $info = $curl->get_info();
        $httpcode = $info['http_code'];

        if ($httpcode >= 200 && $httpcode < 300) {
            if (empty($responsebody)) {
                return []; // Return empty array for empty successful response.
            }
            $decoded = json_decode($responsebody);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $debuginfo = "URL: {$url}\n";
                $debuginfo .= "HTTP Code: {$httpcode}\n";
                $debuginfo .= "Invalid JSON response: " . json_last_error_msg() . "\n";
                $debuginfo .= "Raw Response: " . s($responsebody);
                throw new \moodle_exception('invalidjsonresponse', 'local_credentium', '', null, $debuginfo);
            }
            return $decoded;
        } else {
            // If the request failed, package up all debug info into the exception.
            $debuginfo = "URL: {$url}\n";
            $debuginfo .= "HTTP Method: {$method}\n";
            $debuginfo .= "HTTP Code: {$httpcode}\n";
            $debuginfo .= "Request Headers:\n" . s($info['request_header'] ?? 'Not available') . "\n\n";
            $debuginfo .= "Raw Response Body:\n" . s($responsebody);

            // Log the error details immediately so they appear in mtrace output.
            local_credentium_log("API Request Failed", [
                'url' => parse_url($url, PHP_URL_PATH),
                'method' => $method,
                'http_code' => $httpcode,
            ]);
            mtrace("API ERROR - HTTP {$httpcode}");
            mtrace("URL: {$url}");
            mtrace("Response Body: " . s($responsebody));

            // Try to parse error response for better error messages.
            $errormessage = "API request failed with HTTP code {$httpcode}.";
            if (!empty($responsebody)) {
                $decoded = json_decode($responsebody);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded->errors)) {
                    // Extract validation errors.
                    if (isset($decoded->errors->Grade) && is_array($decoded->errors->Grade)) {
                        $errormessage = "Grade field is required by this credential template. "
                            . "Please enable 'Send grade with credential' in the course settings.";
                        mtrace("SOLUTION: Enable 'Send grade with credential' in course Credentium settings.");
                    } else {
                        // Generic validation error.
                        $errormessage = "Validation error: " . json_encode($decoded->errors);
                    }
                }
            }

            throw new \moodle_exception('apierror', 'local_credentium', '', $errormessage, $debuginfo);
        }
    }

    /**
     * Cache templates with category isolation.
     *
     * @param array $templates Templates to cache
     */
    private function cache_templates($templates) {
        global $DB;

        // Clear existing cache for this category only.
        // Handle NULL categoryid explicitly for MariaDB/MySQL compatibility
        // (these databases do not support IS NOT DISTINCT FROM syntax).
        if ($this->categoryid === null) {
            $DB->delete_records_select('local_credentium_templates_cache', 'categoryid IS NULL');
        } else {
            $DB->delete_records('local_credentium_templates_cache', ['categoryid' => $this->categoryid]);
        }

        // Cache new templates.
        foreach ($templates as $template) {
            $record = new \stdClass();
            $record->templateid = $template->id ?? '';
            // Use title from API, fallback to name if not available.
            $record->name = $template->title ?? $template->name ?? '';
            $record->description = $template->description ?? '';
            $record->active = isset($template->active) ? (int)$template->active : 1;
            $record->data = json_encode($template);
            $record->categoryid = $this->categoryid; // Store category for isolation.
            $record->timecached = time();

            $DB->insert_record('local_credentium_templates_cache', $record);
        }
    }

    /**
     * Get cached templates with category isolation.
     *
     * @param bool $activeonly Only return active templates
     * @return array|null Templates or null if cache miss
     */
    private function get_cached_templates($activeonly = true) {
        global $DB;

        // Check if cache exists for this category and is not older than 1 hour
        // Handle NULL categoryid comparison for cross-database compatibility.
        if ($this->categoryid === null) {
            $cachetime = $DB->get_field_sql(
                "SELECT MAX(timecached) FROM {local_credentium_templates_cache} WHERE categoryid IS NULL"
            );
        } else {
            $cachetime = $DB->get_field_sql(
                "SELECT MAX(timecached) FROM {local_credentium_templates_cache} WHERE categoryid = :categoryid",
                ['categoryid' => $this->categoryid]
            );
        }

        if ($cachetime && (time() - $cachetime < 3600)) {
            // Build SQL for category and active filter.
            // Handle NULL categoryid explicitly for MariaDB/MySQL compatibility
            // (these databases do not support IS NOT DISTINCT FROM syntax).
            if ($this->categoryid === null) {
                $wheresql = 'categoryid IS NULL';
                $params = [];
            } else {
                $wheresql = 'categoryid = :categoryid';
                $params = ['categoryid' => $this->categoryid];
            }
            if ($activeonly) {
                $wheresql .= ' AND active = :active';
                $params['active'] = 1;
            }

            $records = $DB->get_records_select('local_credentium_templates_cache', $wheresql, $params, 'name ASC');

            $templates = [];
            foreach ($records as $record) {
                $template = json_decode($record->data);
                // Ensure the cached template has a title property for consistency.
                if (!isset($template->title) && isset($record->name)) {
                    $template->title = $record->name;
                }
                $templates[] = $template;
            }

            return $templates;
        }

        return null; // Cache miss.
    }
}
