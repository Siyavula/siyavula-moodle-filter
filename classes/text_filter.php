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
 * Siyavula filter base.
 */

namespace filter_siyavula;

require_once($CFG->dirroot . '/filter/siyavula/lib.php');

use filter_siyavula\renderables\practice_activity_renderable;
use filter_siyavula\renderables\standalone_activity_renderable;
use filter_siyavula\renderables\standalone_list_activity_renderable;
use filter_siyavula\renderables\assignment_activity_renderable;

if (class_exists('\core_filters\text_filter')) {
    class_alias('\core_filters\text_filter', 'siyavula_moodle_text_filter');
} else {
    class_alias('\moodle_text_filter', 'siyavula_moodle_text_filter');
}

/**
 * Siyavula text filter.
 */
class text_filter extends \siyavula_moodle_text_filter {

    public function get_activity_type($text) {

        if (strpos($text, '[[syp') !== false) {
            $activitytype = 'practice';
        } else if (strpos($text, '[[sya') !== false)  {
            $activitytype = 'assignment';
        } else if (strpos($text, '[[sy') !== false) {
            if (strpos($text, ',') == true) {
                $activitytype = 'standalone-list';
            } else {
                $activitytype = 'standalone';
            }
        } else {
            $activitytype = null;
        }
        return $activitytype;
    }

    public function parse_filter_text($text) {
        // Get the text
        if (preg_match('/\[\[(.*?)\]\]/', $text, $matches)) {
            $text = $matches[1];
        } else {
            $text = "";
        }
        // Strip whitespace.
        $text = preg_replace("/\s+/", "", $text);
        // Strip "sy-" and "syp-" identifiers.
        $text = str_replace("sy-", "", $text);
        $text = str_replace("syp-", "", $text);
        $text = str_replace("sya-", "", $text);
        // Convert filter string to array.
        $textarray = explode(",", $text);

        // Parse the text into an array with the structure
        // [[template_id,random_seed(optional)]]
        // i.e: [[1220, 458724], [1221]].
        $templatelist = [];
        foreach ($textarray as $key => $item) {
            if (strpos($text, '|') == true) {
                $item = explode("|", $item);
                // Strip all non-numeric characters.
                $item[0] = preg_replace('/[^0-9]/', '', $item[0]);
                $item[1] = preg_replace('/[^0-9]/', '', $item[1]);
                // Convert to integer.
                $item[0] = (int)$item[0];
                $item[1] = (int)$item[1];
            } else {
                // Strip all non-numeric characters.
                $item = preg_replace('/[^0-9]/', '', $item);
                // Convert to integer.
                $item = [(int)$item];
            }

            array_push($templatelist, $item);
        }

        return $templatelist;
    }

    public function get_standalone_activity_data($text) {
        $templatelist = $this->parse_filter_text($text)[0];
        $templateid = $templatelist[0];
        $randomseed = (isset($templatelist[1]) ? $templatelist[1] : rand(1, 99999));

        return array($templateid, $randomseed);
    }

    public function get_standalone_list_activity_data($text) {
        return $this->parse_filter_text($text);
    }

    public function get_practice_activity_data($text) {
        $templatelist = $this->parse_filter_text($text)[0];
        $sectionid = $templatelist[0];

        return $sectionid;
    }

    public function get_assignment_activity_data($text) {
        $templatelist = $this->parse_filter_text($text)[0];
        $assignmentid = $templatelist[0];

        return $assignmentid;
    }


    public function filter($text, array $options = array()) {

        global $OUTPUT, $USER, $PAGE, $CFG, $DB;

        // Fetch the list of shortcodes.
        $matches = [];
        preg_match_all('/\[\[(sy(?:a|p)?)-([0-9]+(?:\s*,\s*[0-9]+)*)\]\]/', $text, $matches);

        if (empty($matches[0])) {
            return $text;
        }

        // If user is not authenticated, show a message instead of rendering activities.
        if (isguestuser() || $USER == null) {
            $loginurl = $CFG->wwwroot . '/login/index.php';
            $message = '<div class="alert alert-info" role="alert">' .
                       '<strong>Siyavula Activity:</strong> Please <a href="' . $loginurl . '">log in</a> to access this content.' .
                       '</div>';

            // Replace all shortcodes with the login message.
            foreach ($matches[0] as $code) {
                $text = str_replace($code, $message, $text);
            }

            return $text;
        }

        // If the shortcode is found, we can proceed.
        $codeslist = $matches[0];

        // Siyavula Config.
        $clientip = $_SERVER['REMOTE_ADDR'];
        $siyavulaconfig = get_config('filter_siyavula');
        $token = siyavula_get_user_token($siyavulaconfig, $clientip);
        $usertoken = siyavula_get_external_user_token($siyavulaconfig, $clientip, $token);
        $showbtnretry = $siyavulaconfig->showretry;
        $showlivepreview = $siyavulaconfig->showlivepreview;
        $baseurl = $siyavulaconfig->url_base;

        $config = (object) [
            'token' => $token,
            'usertoken' => $usertoken->token,
            'baseurl' => $baseurl,
            'showlivepreview' => $showlivepreview,
            'showbtnretry' => $showbtnretry,
            'wwwroot' => $CFG->wwwroot,
        ];

        $activitieslist = [];

        foreach ($codeslist as $code) {
            $text = $this->render_activity($activitieslist, $code, $text, $config);
        }

        $renderer = $PAGE->get_renderer('filter_siyavula');
        $text .= $renderer->render_assets($activitieslist, $config);

        return $text;
    }

    /**
     * Render the activity based on the activity code.
     *
     * Store the activity renderable in the activities list for later js rendering.
     *
     * @param array $activitieslist List of activities to render.
     * @param string $code The shortcode to process.
     * @param string $text The original text containing the shortcode.
     * @param object $config Configuration object containing token and base URL.
     * @return string Rendered HTML for the activity.
     */
    public function render_activity(array &$activitieslist, $code, $text) {

        global $OUTPUT, $USER, $PAGE, $CFG, $DB;

        $activitytype = $this->get_activity_type($code);

        if (!$activitytype) {
            return $text;
        }

        $result = '';

        if ($activitytype == 'standalone') {
            list($templateid, $randomseed) = $this->get_standalone_activity_data($text);

            $renderer = $PAGE->get_renderer('filter_siyavula');

            $activityrenderable = new standalone_activity_renderable();
            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->templateid = $templateid;
            $activityrenderable->randomseed = $randomseed;
            $activityrenderable->uniqueid = 'sy-' . $templateid . '-' . $randomseed . '-' . time();

            $result .= $renderer->render_standalone_activity($activityrenderable);

        } else if ($activitytype == 'standalone-list') {

            $templatelist = $this->get_standalone_list_activity_data($text);

            $renderer = $PAGE->get_renderer('filter_siyavula');
            $activityrenderable = new standalone_activity_renderable();

            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->templatelist = json_encode($templatelist);
            $activityrenderable->uniqueid = 'sy-' . implode('-', array_column($templatelist, 0)) . '-' . time();

            $result .= $renderer->render_standalone_activity($activityrenderable);
        } else if ($activitytype == 'practice') {
            $sectionid = $this->get_practice_activity_data($text);
            $renderer = $PAGE->get_renderer('filter_siyavula');

            $activityrenderable = new practice_activity_renderable();

            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->sectionid = $sectionid;
            $activityrenderable->uniqueid = 'sy-' . $sectionid . '-' . time();

            $result .= $renderer->render_practice_activity($activityrenderable);
        } else if ($activitytype == 'assignment') {
            $assignmentid = $this->get_assignment_activity_data($text);

            $renderer = $PAGE->get_renderer('filter_siyavula');
            $activityrenderable = new assignment_activity_renderable();

            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->assignmentid = $assignmentid;
            $activityrenderable->uniqueid = 'sy-' . $assignmentid . '-' . time();

            $result .= $renderer->render_assignment_activity($activityrenderable);
        }

        if (!empty($activityrenderable)) {
            array_push($activitieslist, $activityrenderable);
        }

        // Define the correct match
        // $text_to_replace_render = $matches[0][0];
        // Replace the raw filter text with the question's HTML
        $result = str_replace($code, $result, $text);

        // Render questions not apply format siyavula.
        if (!empty($result)) {
            // Current version is Moodle 4.0 or higher use the event types. Otherwise use the older versions.
            if ($CFG->version >= 2022041912) {
                $PAGE->requires->js_call_amd('filter_siyavula/initmathjax', 'init', ['issupported' => $CFG->version <= 2025040100]);
            } else {
                $PAGE->requires->js_call_amd('filter_siyavula/initmathjax-backward', 'init');
            }

            return $result;

        } else {
            return $text;
        }

    }
}
