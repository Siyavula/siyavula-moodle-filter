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

use filter_siyavula\renderables\activity_renderable;
use filter_siyavula\renderables\get_activity_renderable;
use filter_siyavula\renderables\practice_activity_renderable;
use filter_siyavula\renderables\standalone_activity_renderable;
use filter_siyavula\renderables\standalone_list_activity_renderable;
use filter_siyavula\renderables\question_feedback_renderable;
use filter_siyavula\renderables\assignment_activity_renderable;

class filter_siyavula_renderer extends plugin_renderer_base {

    public function render_practice_activity(practice_activity_renderable $practiceactivityrenderable) {
        return $this->render_from_template('filter_siyavula/activity', $practiceactivityrenderable);
    }

    public function render_assignment_activity(assignment_activity_renderable $assignmentactivityrenderable) {
        return $this->render_from_template('filter_siyavula/activity', $assignmentactivityrenderable);
    }

    public function render_standalone_activity(standalone_activity_renderable $standaloneactivityrenderable) {
        return $this->render_from_template('filter_siyavula/activity', $standaloneactivityrenderable);
    }

    public function render_standalone_list_activity(standalone_list_activity_renderable $standalonelistactivityrenderable) {
        return $this->render_from_template('filter_siyavula/activity', $standalonelistactivityrenderable);
    }

    public function render_get_activity(get_activity_renderable $getactivityrenderable) {
        return $this->render_from_template('filter_siyavula/activity', $getactivityrenderable);
    }

    public function render_question_feedback(question_feedback_renderable $questionfeedbackrenderable) {
        return $this->render_from_template('filter_siyavula/question_feedback', $questionfeedbackrenderable);
    }

    /**
     * Get the token data for the Siyavula activities.
     *
     * @return object Configuration object containing token and base URL.
     */
    public function get_token_data() {
        global $CFG;

        $clientip = $_SERVER['REMOTE_ADDR'];
        $siyavulaconfig = get_config('filter_siyavula');
        $baseurl = $siyavulaconfig->url_base;
        $token = siyavula_get_user_token($siyavulaconfig, $clientip);
        $usertoken = siyavula_get_external_user_token($siyavulaconfig, $clientip, $token);

        $config = (object) [
            'token' => $token,
            'usertoken' => $usertoken?->token,
            'baseurl' => $baseurl,
            'wwwroot' => $CFG->wwwroot,
        ];

        return $config;
    }

    /**
     * Render the scripts for Siyavula activities.
     *
     * @param array $activitieslist List of activities to render.
     * @param object|null $config Configuration object, if null will use default config.
     * @return string Rendered HTML for the assets.
     */
    public function render_assets(array $activitieslist, $config=null) {

        if ($config === null) {
            $config = $this->get_token_data();
        }

        return $this->render_from_template('filter_siyavula/assets', [
            'activitieslist' => $activitieslist,
            'config' => $config
        ]);
    }
}
