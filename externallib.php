<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class filter_siyavula_external extends external_api {

    public static function save_activity_data_parameters() {
        return new external_function_parameters(
            array(
                'questionid' => new external_value(PARAM_RAW, ''),
                'activityid' => new external_value(PARAM_RAW, ''),
                'responseid' => new external_value(PARAM_RAW, '')
            )
        );
    }

    /**
     * Function get courses in tgas relations, event gallery for webservice.
     * @return external_function_parameters
     */
    public static function save_activity_data($questionid, $activityid, $responseid) {
        global $DB;

        if ($options = $DB->get_record('question_siyavulaqt', array('question' => $questionid))) {
            $options->activityid  = $activityid;
            $options->responseid = $responseid;
            $DB->update_record('question_siyavulaqt', $options);
        }

        return array('response' => 'success');
    }

    /**
     * Return info data tags and course info
     * @return tag_courses_returns
     */
    public static function save_activity_data_returns(){
        return new external_single_structure(
            array(
                'response' => new external_value(PARAM_RAW, '')
            )
        );
    }
}
