<?php
/**
 * External.
 *
 * @package mod_pchat
 * @author  Justin Hunt - Poodll.com
 */


namespace mod_pchat;


use context_module;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
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
            'moduleid' => new external_value(PARAM_INT),
            'cmid' => new external_value(PARAM_INT),
        ]);
    }

    public static function get_grade_submission($userid, $moduleid, $cmid) {
        $gradesubmissions = new gradesubmissions();
            return ['response' => $gradesubmissions->getSubmissionData($userid, $moduleid, $cmid)];
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
                    'turns' => new external_value(PARAM_TEXT, 'Turns', VALUE_OPTIONAL),
                    'avturn' => new external_value(PARAM_TEXT, 'AV Turn', VALUE_OPTIONAL),
                    'accuracy' => new external_value(PARAM_TEXT, 'Accuracy', VALUE_OPTIONAL),
                    'chatid' => new external_value(PARAM_INT, 'Chat ID', VALUE_OPTIONAL),
                    'filename' => new external_value(PARAM_TEXT, 'File name', VALUE_OPTIONAL),
                    'transcript' => new external_value(PARAM_TEXT, 'Transcript', VALUE_OPTIONAL),
                    'jsontranscript' => new external_value(PARAM_TEXT, 'JSON transcript', VALUE_OPTIONAL),
                    'words' => new external_value(PARAM_TEXT, 'Words', VALUE_OPTIONAL),
                    'longestturn' => new external_value(PARAM_TEXT, 'Longest Turn', VALUE_OPTIONAL),
                    'targetwords' => new external_value(PARAM_TEXT, 'Target Words', VALUE_OPTIONAL),
                    'totaltargetwords' => new external_value(PARAM_TEXT, 'Total Target Words', VALUE_OPTIONAL),
                    'questions' => new external_value(PARAM_TEXT, 'Questions', VALUE_OPTIONAL),
                    'aiaccuracy' => new external_value(PARAM_TEXT, 'AI Accuracy', VALUE_OPTIONAL),
                    'rubricscore' => new external_value(PARAM_TEXT, 'Rubrics score', VALUE_OPTIONAL),
                    'remark' => new external_value(PARAM_TEXT, 'Remark', VALUE_OPTIONAL),
                    'feedback' => new external_value(PARAM_TEXT, 'Feedback', VALUE_OPTIONAL),
                ])
            )
        ]);
    }

    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_group_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_create_group_form($contextid, $jsonformdata) {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/group/group_form.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_group_form_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], IGNORE_MISSING);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        require_capability('moodle/course:managegroups', $context);

        list($ignored, $course) = get_context_info_array($context->id);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();

        $editoroptions = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $course->maxbytes,
            'trust' => false,
            'context' => $context,
            'noclean' => true,
            'subdirs' => false
        ];
        $group = new stdClass();
        $group->courseid = $course->id;
        $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);

        // The last param is the ajax submitted data.
        $mform = new group_form(null, array('editoroptions' => $editoroptions), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();

        if ($validateddata) {
            // Do the action.
            $groupid = groups_create_group($validateddata, $mform, $editoroptions);
        } else {
            // Generate a warning.
            throw new moodle_exception('erroreditgroup', 'group');
        }

        return $groupid;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_group_form_returns() {
        return new external_value(PARAM_INT, 'group id');
    }

}
