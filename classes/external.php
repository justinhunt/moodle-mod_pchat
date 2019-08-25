<?php
/**
 * External.
 *
 * @package mod_pchat
 * @author  Justin Hunt - Poodll.com
 */


namespace mod_pchat;


/**
 * External class.
 *
 * @package mod_pchat
 * @author  Justin Hunt - Poodll.com
 */
class external extends \external_api {

    public static function toggle_topic_selected_parameters() {
        return new \external_function_parameters([
            'topicid' => new \external_value(PARAM_INT)
        ]);
    }

    public static function toggle_topic_selected($topicid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::toggle_topic_selected_parameters(), compact('topicid'));
        extract($params);

        $topic = $DB->get_record(constants::M_TOPIC_TABLE, ['id' => $topicid], '*', MUST_EXIST);
        $mod = $DB->get_record(constants::M_TABLE, ['id' => $topic->moduleid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance(constants::M_MODNAME, $topic->moduleid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        if (!has_capability('mod/pchat:selecttopics',$context)){
            return false;
        }

        // Require view and make sure the user did not previously mark as seen.
        $params = ['moduleid' => $topic->moduleid, 'topicid' => $topicid];
        $selected = $DB->record_exists(constants::M_TOPICSELECTED_TABLE, $params);

        if($selected){
            $DB->delete_records(constants::M_TOPICSELECTED_TABLE, $params);
        }else{
            $entry = new \stdClass();
            $entry->topicid=$topicid;
            $entry->moduleid=$topic->moduleid;
            $entry->timemodified=time();
            $entry->modifiedby=$USER->id;
            $entry->createdby=$USER->id;

            $DB->insert_record(constants::M_TOPICSELECTED_TABLE, $entry);
        }
        return true;
    }

    public static function toggle_topic_selected_returns() {
        return new \external_value(PARAM_BOOL);
    }
}
