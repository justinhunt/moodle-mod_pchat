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

/**
 * Action for adding/editing a topic.
 *
 * @package mod_pchat
 * @copyright  2019 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

use \mod_pchat\constants;
use \mod_pchat\utils;

require_once("../../../config.php");
require_once($CFG->dirroot.'/mod/pchat/lib.php');


global $USER,$DB;

// first get the nfo passed in to set up the page
$moduleid= required_param('moduleid',PARAM_INT);
$id     = optional_param('id',0, PARAM_INT);         // Course Module ID
$action = optional_param('action','edit',PARAM_TEXT);

// get the objects we need
$cm = get_coursemodule_from_instance(constants::M_MODNAME, $moduleid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record(constants::M_MODNAME, array('id' => $moduleid), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pchat:manage', $context);

//set up the page object
$PAGE->set_url('/mod/pchat/topic/managetopics.php', array('moduleid'=>$moduleid, 'id'=>$id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//are we in new or edit mode?
if ($id) {
    $topic = $DB->get_record(constants::M_TOPIC_TABLE, array('id'=>$id), '*', MUST_EXIST);
    if(!$topic){
        print_error('could not find topic of id:' . $topicid);
    }
    $edit = true;
} else {
    $edit = false;
}

//we always head back to the pchat topics page
$redirecturl = new moodle_url('/mod/pchat/topic/topics.php', array('id'=>$cm->id));

//prepare filemanager options
$filemanageroptions = pchat_filemanager_options($context);

//handle delete actions
if($action == 'confirmdelete'){
    $renderer = $PAGE->get_renderer(constants::M_COMPONENT);
    $topic_renderer = $PAGE->get_renderer(constants::M_COMPONENT,'topic');
    echo $renderer->header($moduleinstance, $cm, 'topics', null, get_string('confirmtopicdeletetitle', constants::M_COMPONENT));
    echo $topic_renderer->confirm(get_string("confirmtopicdelete",constants::M_COMPONENT,$topic->name),
            new moodle_url('/mod/pchat/topic/managetopics.php', array('action'=>'delete','moduleid'=>$moduleid,'id'=>$id)),
            $redirecturl);
    echo $renderer->footer();
    return;

    /////// Delete topic NOW////////
}elseif ($action == 'delete'){
    require_sesskey();
    $success = \mod_pchat\topic\helper::delete_topic($moduleinstance,$id,$context);
    redirect($redirecturl);
}

$siteconfig = get_config(constants::M_COMPONENT);

//get the mform for our topic
$mform = new \mod_pchat\topic\topicform(null, array('filemanageroptions'=>$filemanageroptions,
    'moduleinstance'=>$moduleinstance));

//if the cancel button was pressed, we are out of here
if ($mform->is_cancelled()) {
    redirect($redirecturl);
    exit;
}

//if we have data, then our job here is to save it and return to the quiz edit page
if ($data = $mform->get_data()) {
    require_sesskey();

    $thetopic = $data;

    //$thetopic->moduleid = $moduleinstance->id;
    //$thetopic->topiclevel = $data->topiclevel;
    //$thetopic->targetwords=  $data->targetwords;
    //$thetopic->fonticon=  $data->fonticon;
    $thetopic->timemodified=time();

    //first insert a new topic if we need to
    //that will give us a topicid, we need that for saving files
    if(!$edit){
        $thetopic->id = null;
        $thetopic->timecreated=time();

        //try to insert it
        if (!$thetopic->id = $DB->insert_record(constants::M_TOPIC_TABLE,$thetopic)){
            print_error("Could not insert pchat topic!");
            redirect($redirecturl);
        }else{
            //lets select it , because everyine seems to forget to otherwise
            \mod_pchat\utils::toggle_topic_selected($thetopic->id,$moduleinstance->id);
        }
    }else{
        //now update the db once we have saved files and stuff
        if (!$DB->update_record(constants::M_TOPIC_TABLE,$thetopic)){
            print_error("Could not update pchat topic!");
            redirect($redirecturl);
        }
    }

    //Media file
    if (property_exists($data, constants::M_TOPICMEDIA)) {
        file_save_draft_area_files($data->{constants::M_TOPICMEDIA},
            $context->id, constants::M_COMPONENT,
            constants::M_TOPICMEDIA, $thetopic->id,
            $filemanageroptions);
    }

    //if we got here we did achieve some update
    redirect($redirecturl);

}


//if  we got here, there was no cancel, and no form data, so we are showing the form
//if edit mode load up the topic into a data object
if ($edit) {
    $data = $topic;
    //init our itemmedia upload file field
    $draftitemid = file_get_submitted_draft_itemid(constants::M_TOPICMEDIA);
    file_prepare_draft_area($draftitemid, $context->id, constants::M_COMPONENT,
        constants::M_TOPICMEDIA, $data->id,
        $filemanageroptions);
    $data->{constants::M_TOPICMEDIA} = $draftitemid;
    $data->addmedia = $draftitemid;
    $data->addiframe = !empty($topic->topiciframe);

}else{
    $data=new stdClass;
    $data->id = null;
    $data->courseid=$course->id;
    $data->moduleid = $moduleid;
}


//Set up the topic type specific parts of the form data
$topicrenderer = $PAGE->get_renderer('mod_pchat','topic');
$mform->set_data($data);
$PAGE->navbar->add(get_string('edit'), new moodle_url('/mod/pchat/topic/topics.php', array('id'=>$moduleid)));
$PAGE->navbar->add(get_string('editingtopic', constants::M_COMPONENT));
$renderer = $PAGE->get_renderer('mod_pchat');
$mode='topics';
echo $renderer->header($moduleinstance, $cm,$mode, null, get_string('edit', constants::M_COMPONENT));
$mform->display();
echo $renderer->footer();