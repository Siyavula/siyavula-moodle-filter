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

class filter_siyavula extends moodle_text_filter {

    public function filter($text, array $options = array()) {

        global $OUTPUT, $USER, $PAGE, $CFG, $DB;

        // Verify if user not authenticated.
        $userauth = false;
        if (isguestuser() || $USER == null) {
            $userauth = true;
            header('Location: ' . $CFG->wwwroot . '/login/index.php');
            exit();
        }

        if (strpos($text, 'syp') == true) {
            $activitytype = 'practice';
        } else if (strpos($text, 'sy') == true) {
            $activitytype = 'standalone';
        } else {
            return $text;
        }

        $clientip       = $_SERVER['REMOTE_ADDR'];
        $siyavulaconfig = get_config('filter_siyavula');
        $token = siyavula_get_user_token($siyavulaconfig, $clientip);
        $usertoken = siyavula_get_external_user_token($siyavulaconfig, $clientip, $token);
        $showbtnretry = $siyavulaconfig->showretry;
        $baseurl = $siyavulaconfig->url_base;

        $result = $PAGE->requires->js_call_amd('filter_siyavula/initmathjax', 'init');

        if ($activitytype == 'standalone') {
            preg_match_all('!\d+!', $text, $matches);
            $templateid  = $matches[0][0];
            $randomseed = (isset($seed) ? $seed : rand(1, 99999));

            $renderer = $PAGE->get_renderer('filter_siyavula');
            $activityrenderable = new standalone_activity_renderable();
            $activityrenderable->baseurl = $baseurl;
            $activityrenderable->token = $token;
            $activityrenderable->usertoken = $usertoken->token;
            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->templateid = $templateid;
            $activityrenderable->randomseed = $randomseed;

            return $renderer->render_standalone_activity($activityrenderable);
        } else if ($activitytype == 'practice') {
            preg_match_all('!\d+!', $text, $matches);
            $sectionid = $matches[0][0];

            $renderer = $PAGE->get_renderer('filter_siyavula');
            $activityrenderable = new practice_activity_renderable();
            $activityrenderable->baseurl = $baseurl;
            $activityrenderable->token = $token;
            $activityrenderable->usertoken = $usertoken->token;
            $activityrenderable->activitytype = $activitytype;
            $activityrenderable->sectionid = $sectionid;

            return $renderer->render_practice_activity($activityrenderable);
        }

        // Render questions not apply format siyavula.
        if (!empty($result)) {
            return $result;
        } else {
            return $text;
        }
    }
}
