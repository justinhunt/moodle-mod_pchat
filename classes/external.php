<?php
/**
 * External.
 *
 * @package mod_pchat
 * @author  Justin Hunt - Poodll.com
 */


namespace mod_pchat;

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_module;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use mod_pchat\grades\gradesubmissions;

/**
 * External class.
 *
 * @package mod_pchat
 * @author  Justin Hunt - Poodll.com
 */
class external extends external_api {

    public static function toggle_topic_selected($topicid, $activityid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::toggle_topic_selected_parameters(), [
            'topicid' => $topicid,
            'activityid' => $activityid]);
        extract($params);

        $topic = $DB->get_record(constants::M_TOPIC_TABLE, ['id' => $topicid], '*', MUST_EXIST);
        $mod = $DB->get_record(constants::M_TABLE, ['id' => $activityid], '*', MUST_EXIST);
        if (!$topic || !$mod) {
            return false;
        }
        $cm = get_coursemodule_from_instance(constants::M_MODNAME, $topic->moduleid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        self::validate_context($context);
        if (!has_capability('mod/pchat:selecttopics', $context)) {
            return false;
        }

        $success = utils::toggle_topic_selected($topic->id, $mod->id);
        return $success;
    }

    public static function toggle_topic_selected_parameters() {
        return new external_function_parameters([
            'topicid' => new external_value(PARAM_INT),
            'activityid' => new external_value(PARAM_INT)
        ]);
    }

    public static function toggle_topic_selected_returns() {
        return new external_value(PARAM_BOOL);
    }

    public static function get_grade_submission_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT),
            'cmid' => new external_value(PARAM_INT),
        ]);
    }

    public static function get_grade_submission($userid,  $cmid) {
        $gradesubmissions = new gradesubmissions();
            return ['response' => $gradesubmissions->getSubmissionData($userid,$cmid)];
    }

    public static function get_grade_submission_returns() {
        return new external_function_parameters([
            'response' => new external_multiple_structure(
                new external_single_structure([

                    'id' => new external_value(PARAM_INT, 'ID'),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                    'firstname' => new external_value(PARAM_TEXT, 'First name'),
                    'name' => new external_value(PARAM_TEXT, 'Name'),
                    'transcriber' => new external_value(PARAM_TEXT, 'Transcriber'),
                    'tt' => new external_value(PARAM_TEXT, 'Turns', VALUE_OPTIONAL),
                    'atl' => new external_value(PARAM_TEXT, 'AV Turn', VALUE_OPTIONAL),
                    'accuracy' => new external_value(PARAM_TEXT, 'Accuracy', VALUE_OPTIONAL),
                    'chatid' => new external_value(PARAM_INT, 'Chat ID', VALUE_OPTIONAL),
                    'filename' => new external_value(PARAM_TEXT, 'File name', VALUE_OPTIONAL),
                    'transcript' => new external_value(PARAM_TEXT, 'Transcript', VALUE_OPTIONAL),
                    'jsontranscript' => new external_value(PARAM_TEXT, 'JSON transcript', VALUE_OPTIONAL),
                    'selftranscript' => new external_value(PARAM_TEXT, 'Self Transcript', VALUE_OPTIONAL),
                    'tw' => new external_value(PARAM_TEXT, 'Words', VALUE_OPTIONAL),
                    'ltl' => new external_value(PARAM_TEXT, 'Longest Turn', VALUE_OPTIONAL),
                    'tv' => new external_value(PARAM_TEXT, 'Target Words', VALUE_OPTIONAL),
                    'ttv' => new external_value(PARAM_TEXT, 'Total Target Words', VALUE_OPTIONAL),
                    'qa' => new external_value(PARAM_TEXT, 'Questions', VALUE_OPTIONAL),
                    'aia' => new external_value(PARAM_TEXT, 'AI Accuracy', VALUE_OPTIONAL),
                    'rubricscore' => new external_value(PARAM_TEXT, 'Rubrics score', VALUE_OPTIONAL),
                    'remark' => new external_value(PARAM_TEXT, 'Remark', VALUE_OPTIONAL),
                    'feedback' => new external_value(PARAM_TEXT, 'Feedback', VALUE_OPTIONAL),
                    'revq1' => new external_value(PARAM_TEXT, 'revq1', VALUE_OPTIONAL),
                    'revq2' => new external_value(PARAM_TEXT, 'revq2', VALUE_OPTIONAL),
                    'revq3' => new external_value(PARAM_TEXT, 'revq3', VALUE_OPTIONAL)
                ])
            )
        ]);
    }

    /**
     * Describes the parameters for submit_rubric_grade_form webservice.
     * @return external_function_parameters
     */
    public static function submit_rubric_grade_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create grade form, encoded as a json array'),
                'studentid' => new external_value(PARAM_INT, 'The id for the student', false),
                'cmid' => new external_value(PARAM_INT, 'The course module id for the item', false),
            )
        );
    }

    /**
     * Submit the rubric grade form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @param $studentid
     * @param $cmid
     * @return int new grade id.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     * @throws \restricted_context_exception
     */
    public static function submit_rubric_grade_form($contextid, $jsonformdata, $studentid, $cmid) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/pchat/rubric_grade_form.php');
        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_once($CFG->dirroot . '/mod/pchat/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_rubric_grade_form_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        require_capability('moodle/course:managegroups', $context);

        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $modulecontext = context_module::instance($cmid);
        $cm = get_coursemodule_from_id(constants::M_MODNAME, $cmid, 0, false, MUST_EXIST);
        $attempthelper = new \mod_pchat\attempthelper($cm);
        $attempt= $attempthelper->fetch_latest_complete_attempt($studentid);

        if (!$attempt) { return 0; }

        $moduleinstance = $DB->get_record(constants::M_TABLE, array('id'=>$attempt->pchat));
        $gradingdisabled=false;
        $gradinginstance = utils::get_grading_instance($attempt->id, $gradingdisabled,$moduleinstance, $modulecontext);

        $mform = new \rubric_grade_form(null, array('gradinginstance' => $gradinginstance), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();

        if ($validateddata) {
            // Insert rubric
            if (!empty($validateddata->advancedgrading['criteria'])) {
                $thegrade=null;
                if (!$gradingdisabled) {
                    if ($gradinginstance) {
                        $thegrade = $gradinginstance->submit_and_get_grade($validateddata->advancedgrading,
                            $attempt->id);
                    }
                }
            }

            //if no grading was done, eg just a comment, then we should null not zero
            if($thegrade && $thegrade < 0){
                $thegrade=null;
            }

            $feedbackobject = new \stdClass();
            $feedbackobject->id = $attempt->id;
            $feedbackobject->feedback = $validateddata->feedback;
            $feedbackobject->grade = $thegrade;
            $DB->update_record('pchat_attempts', $feedbackobject);
            $grade = new \stdClass();
            $grade->userid = $studentid;
            $grade->rawgrade = $thegrade;
            \pchat_grade_item_update($moduleinstance,$grade);
        } else {
            // Generate a warning.
            throw new \moodle_exception('erroreditgroup', 'group');
        }

        return 1;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_value
     * @since Moodle 3.0
     */
    public static function submit_rubric_grade_form_returns() {
        return new external_value(PARAM_INT, 'grade id');
    }

    /**
     * Describes the parameters for submit_simple_grade_form webservice.
     * @return external_function_parameters
     */
    public static function submit_simple_grade_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create grade form, encoded as a json array'),
                'studentid' => new external_value(PARAM_INT, 'The id for the student', false),
                'cmid' => new external_value(PARAM_INT, 'The course module id for the item', false),
            )
        );
    }

    /**
     * Submit the simple grade form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @param $studentid
     * @param $cmid
     * @return int new grade id.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     * @throws \restricted_context_exception
     */
    public static function submit_simple_grade_form($contextid, $jsonformdata, $studentid, $cmid) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/pchat/simple_grade_form.php');
        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_once($CFG->dirroot . '/mod/pchat/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_simple_grade_form_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        require_capability('moodle/course:managegroups', $context);

        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $cm = get_coursemodule_from_id(constants::M_MODNAME, $cmid, 0, false, MUST_EXIST);
        $attempthelper = new \mod_pchat\attempthelper($cm);
        $attempt= $attempthelper->fetch_latest_complete_attempt($studentid);

        if (!$attempt) { return 0; }

        $moduleinstance = $DB->get_record(constants::M_TABLE, array('id'=>$attempt->pchat));

        $mform = new \simple_grade_form(null, array('scaleid'=>$moduleinstance->grade), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();

        if ($validateddata) {
            $feedbackobject = new \stdClass();
            $feedbackobject->id = $attempt->id;
            $feedbackobject->feedback = $validateddata->feedback;
            $feedbackobject->manualgraded = 1;
            $feedbackobject->grade = $validateddata->grade;
            $DB->update_record(constants::M_ATTEMPTSTABLE, $feedbackobject);
            $grade = new \stdClass();
            $grade->userid = $studentid;
            $grade->rawgrade = $validateddata->grade;
            \pchat_grade_item_update($moduleinstance,$grade);
        } else {
            // Generate a warning.
            throw new \moodle_exception('erroreditgroup', 'group');
        }

        return 1;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_value
     * @since Moodle 3.0
     */
    public static function submit_simple_grade_form_returns() {
        return new external_value(PARAM_INT, 'grade id');
    }



}
