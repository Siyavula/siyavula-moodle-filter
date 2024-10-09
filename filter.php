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

require_once($CFG->dirroot . '/filter/siyavula/lib.php');

use filter_siyavula\renderables\practice_activity_renderable;
use filter_siyavula\renderables\standalone_activity_renderable;
use filter_siyavula\renderables\standalone_list_activity_renderable;
use filter_siyavula\renderables\assignment_activity_renderable;

class filter_siyavula extends moodle_text_filter {

    public function get_activity_type($text) {
        if (strpos($text, '[[syp') !== false) {
            $activitytype = 'practice';
        } else if (strpos($text, '[[sya') !== false)  {
            $activitytype = 'assignment';
        } else if (strpos($text, '[[sy') !== false) {
            if (strpos($text, ',') == true) {
                $activitytype = 'standaloneList';
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

        // Verify if user not authenticated.
        $userauth = false;
        if (isguestuser() || $USER == null) {
            $userauth = true;
            header('Location: ' . $CFG->wwwroot . '/login/index.php');
            exit();
        }

        $activitytype = $this->get_activity_type($text);
        if (!$activitytype) {
            return $text;
        }

        $clientip = $_SERVER['REMOTE_ADDR'];
        $siyavulaconfig = get_config('filter_siyavula');
        $token = siyavula_get_user_token($siyavulaconfig, $clientip);
        $usertoken = siyavula_get_external_user_token($siyavulaconfig, $clientip, $token);
        $showbtnretry = $siyavulaconfig->showretry;
        $showlivepreview = $siyavulaconfig->showlivepreview;
        $baseurl = $siyavulaconfig->url_base;


        $result = '';

        if ($activitytype == 'standalone') {
            list($templateid, $randomseed) = $this->get_standalone_activity_data($text);

            $renderer = $PAGE->get_renderer('filter_siyavula');
            $activityrenderable = new standalone_activity_renderable();
            $activityrenderable->wwwroot = $CFG->wwwroot;
            $activityrenderable->baseurl = $baseurl;
            $activityrenderable->showlivepreview = $showlivepreview;
            $activityrenderable->token = $token;
            $activityrenderable->usertoken = $usertoken->token;
            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->templateid = $templateid;
            $activityrenderable->randomseed = $randomseed;

            $result .= $renderer->render_standalone_activity($activityrenderable);
        } else if ($activitytype == 'standaloneList') {
            $templatelist = $this->get_standalone_list_activity_data($text);

            $renderer = $PAGE->get_renderer('filter_siyavula');
            $activityrenderable = new standalone_activity_renderable();
            $activityrenderable->wwwroot = $CFG->wwwroot;
            $activityrenderable->baseurl = $baseurl;
            $activityrenderable->showlivepreview = $showlivepreview;
            $activityrenderable->token = $token;
            $activityrenderable->usertoken = $usertoken->token;
            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->templatelist = json_encode($templatelist);

            $result .= $renderer->render_standalone_activity($activityrenderable);
        } else if ($activitytype == 'practice') {
            $sectionid = $this->get_practice_activity_data($text);

            $renderer = $PAGE->get_renderer('filter_siyavula');
            $activityrenderable = new practice_activity_renderable();
            $activityrenderable->wwwroot = $CFG->wwwroot;
            $activityrenderable->baseurl = $baseurl;
            $activityrenderable->showlivepreview = $showlivepreview;
            $activityrenderable->token = $token;
            $activityrenderable->usertoken = $usertoken->token;
            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->sectionid = $sectionid;

            $result .= $renderer->render_practice_activity($activityrenderable);
        } else if ($activitytype == 'assignment') {
            $assignmentid = $this->get_assignment_activity_data($text);

            $renderer = $PAGE->get_renderer('filter_siyavula');
            $activityrenderable = new assignment_activity_renderable();
            $activityrenderable->wwwroot = $CFG->wwwroot;
            $activityrenderable->baseurl = $baseurl;
            $activityrenderable->showlivepreview = $showlivepreview;
            $activityrenderable->token = $token;
            $activityrenderable->usertoken = $usertoken->token;
            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->assignmentid = $assignmentid;

            $result .= $renderer->render_assignment_activity($activityrenderable);
        }

        // TODO: Refactor this (LC)
        // Strip HTML
        $newtext = strip_tags($text);
        if ($activitytype == 'practice') {
            $re = '/\[{2}[syp\-\d{1,},?|]*\]{2}/m';
        } else if ($activitytype == 'assignment') {
            $re = '/\[{2}[sya\-\d{1,},?|]*\]{2}/m';
        } else {
            $re = '/\[{2}[sy\-\d{1,},?|]*\]{2}/m';
        }
        // Find the [[sy{p}-]] filter
        preg_match_all($re, $newtext, $matches);
        // Define the correct match
        $text_to_replace_render = $matches[0][0];
        // Replace the raw filter text with the question's HTML
        $result = str_replace($text_to_replace_render, $result, $text);

        // Render questions not apply format siyavula.
        if (!empty($result)) {
            $PAGE->requires->js_call_amd('filter_siyavula/initmathjax', 'init');
            return $result;
        } else {
            return $text;
        }

    }
}
