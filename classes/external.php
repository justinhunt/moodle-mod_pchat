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
            'topicid' => new \external_value(PARAM_INT),
             'activityid' => new \external_value(PARAM_INT)
        ]);
    }

    public static function toggle_topic_selected($topicid, $activityid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::toggle_topic_selected_parameters(),[
                'topicid'    => $topicid,
                'activityid'   => $activityid]);
        extract($params);

        $topic = $DB->get_record(constants::M_TOPIC_TABLE, ['id' => $topicid], '*', MUST_EXIST);
        $mod = $DB->get_record(constants::M_TABLE, ['id' => $activityid], '*', MUST_EXIST);
        if(!$topic || !$mod){
            return false;
        }
        $cm = get_coursemodule_from_instance(constants::M_MODNAME, $topic->moduleid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        if (!has_capability('mod/pchat:selecttopics',$context)){
            return false;
        }

        $success= utils::toggle_topic_selected($topic->id,$mod->id);
        return $success;
    }

    public static function toggle_topic_selected_returns() {
        return new \external_value(PARAM_BOOL);
    }



}
