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

class filter_siyavula_renderer extends plugin_renderer_base {
    public function render_practice_activity(practice_activity_renderable $practiceactivityrenderable) {
        return $this->render_from_template('filter_siyavula/activity', $practiceactivityrenderable);
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
}
