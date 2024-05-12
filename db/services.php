<?php
/**
 * Services definition.
 *
 * @package mod_pchat
 * @author  Justin Hunt Poodll.com
 */

$functions = array(

        'mod_pchat_toggle_topic_selected' => array(
                'classname'   => 'mod_pchat_external',
                'methodname'  => 'toggle_topic_selected',
                'description' => 'Select/deselect a topic for a mod',
                'capabilities'=> 'mod/pchat:selecttopics',
                'type'        => 'read',
                'ajax'        => true,
        ),
        'mod_pchat_get_grade_submission' => array(
            'classname'   => 'mod_pchat_external',
            'methodname'  => 'get_grade_submission',
            'description' => 'Gets a pchat grade submission',
            'capabilities'=> 'mod/pchat:managegrades',
            'type'        => 'write',
            'ajax' => true,
        ),
        'mod_pchat_submit_rubric_grade_form' => array(
            'classname' => 'mod_pchat_external',
            'methodname' => 'submit_rubric_grade_form',
            'description' => 'Creates a grade from submitted form data',
            'ajax' => true,
            'type' => 'write',
            'capabilities' => 'mod/pchat:managegrades',
        ),
        'mod_pchat_submit_simple_grade_form' => array(
            'classname' => 'mod_pchat_external',
            'methodname' => 'submit_simple_grade_form',
            'description' => 'Creates a grade from submitted simple form',
            'ajax' => true,
            'type' => 'write',
            'capabilities' => 'mod/pchat:managegrades',
        ),

);
