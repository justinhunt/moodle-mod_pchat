<?php
/**
 * Services definition.
 *
 * @package mod_pchat
 * @author  Justin Hunt Poodll.com
 */

$functions = array(

        'mod_pchat_toggle_topic_selected' => array(
                'classname'   => '\mod_pchat\external',
                'methodname'  => 'toggle_topic_selected',
                'description' => 'Select/deselect a topic for a mod',
                'capabilities'=> 'mod/pchat:selecttopics',
                'type'        => 'write',
                'ajax'        => true,
        )

);
