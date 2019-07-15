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
 * Action for adding/editing a attempt.
 * replace i) MOD_pchat eg MOD_CST, then ii) pchat eg cst, then iii) attempt eg attempt
 *
 * @package mod_pchat
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

use \mod_pchat\constants;
use \mod_pchat\utils;

require_once("../../../config.php");
require_once($CFG->dirroot.'/mod/pchat/lib.php');


global $USER,$DB;

// first get the nfo passed in to set up the page
$attemptid = optional_param('attemptid',0 ,PARAM_INT);
$id     = required_param('id', PARAM_INT);         // Course Module ID
$type  = optional_param('type', constants::NONE, PARAM_INT);
$action = optional_param('action','edit',PARAM_TEXT);

// get the objects we need
$cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record(constants::M_MODNAME, array('id' => $cm->instance), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pchat:attemptedit', $context);

//set up the page object
$PAGE->set_url('/mod/pchat/attempt/manageattempts.php', array('attemptid'=>$attemptid, 'id'=>$id, 'type'=>$type));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//are we in new or edit mode?
if ($attemptid) {
    $attempt = $DB->get_record(constants::M_QTABLE, array('id'=>$attemptid,constants::M_MODNAME => $cm->instance), '*', MUST_EXIST);
	if(!$attempt){
		print_error('could not find attempt of id:' . $attemptid);
	}
    $type = $attempt->type;
    $edit = true;
} else {
    $edit = false;
}

//we always head back to the pchat attempts page
$redirecturl = new moodle_url('/mod/pchat/attempt/attempts.php', array('id'=>$cm->id));

	//handle delete actions
    if($action == 'confirmdelete'){
    	$usecount = $DB->count_records(constants::M_QTABLE,array(constants::M_MODNAME =>$cm->instance));

		$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
		$attempt_renderer = $PAGE->get_renderer(constants::M_COMPONENT,'attempt');
		echo $renderer->header($moduleinstance, $cm, 'attempts', null, get_string('confirmattemptdeletetitle', constants::M_COMPONENT));
		echo $attempt_renderer->confirm(get_string("confirmattemptdelete",constants::M_COMPONENT,$attempt->name),
			new moodle_url('/mod/pchat/attempt/manageattempts.php', array('action'=>'delete','id'=>$cm->id,'attemptid'=>$attemptid)),
			$redirecturl);
		echo $renderer->footer();
		return;

	/////// Delete attempt NOW////////
    }elseif ($action == 'delete'){
    	require_sesskey();
		$success = \mod_pchat\attempt\helper::delete_attempt($moduleinstance,$attemptid,$context);
        redirect($redirecturl);
    }elseif($action=="moveup" || $action=="movedown"){
        \mod_pchat\attempt\helper::move_attempt($moduleinstance,$attemptid,$action);
        redirect($redirecturl);
    }



//get filechooser and html editor options
//get filechooser and html editor options
$editoroptions = \mod_pchat\attempt\helper::fetch_editor_options($course, $context);
$filemanageroptions = \mod_pchat\attempt\helper::fetch_filemanager_options($course,1);
$siteconfig = get_config(constants::M_COMPONENT);
$token= utils::fetch_token($siteconfig->apiuser,$siteconfig->apisecret);


//get the mform for our attempt
switch($type){



    case constants::TYPE_AUDIORECORDING:
        $mform = new \mod_pchat\attempt\audiorecordingform(null,
        array('moduleinstance'=>$moduleinstance,
            'token'=>$token));
        break;

	case constants::NONE:
	default:
		print_error('No attempt type specifified');

}

//if the cancel button was pressed, we are out of here
if ($mform->is_cancelled()) {
    redirect($redirecturl);
    exit;
}

//if we have data, then our job here is to save it and return to the quiz edit page
if ($data = $mform->get_data()) {
		require_sesskey();
		
		$theattempt = $data;
        $theattempt->pchat = $moduleinstance->id;
        $theattempt->id = $data->attemptid;
        $theattempt->userid = $USER->id;
		$theattempt->modifiedby=$USER->id;
		$theattempt->timemodified=time();
		
		//first insert a new attempt if we need to
		//that will give us a attemptid, we need that for saving files
		if(!$edit){
			
			$theattempt->{constants::TEXTDESCR} = '';
			$theattempt->{constants::TEXTDESCR.'format'} = 0;
			$theattempt->timecreated=time();
			$theattempt->createdby=$USER->id;



			//try to insert it
			if (!$theattempt->id = $DB->insert_record(constants::M_QTABLE,$theattempt)){
					error("Could not insert pchat attempt!");
					redirect($redirecturl);
			}
		}			
		
		//handle all the text question
		//save the attempt text editor files (common to all types)
		$theattempt = file_postupdate_standard_editor( $theattempt, constants::TEXTDESCR, $editoroptions, $context,
								'mod_pchat', constants::TEXTDESCR_FILEAREA, $theattempt->id);
    //this is an array and we do not need to (or want ) to pass to DB
	//	unset($theattempt->{constants::TEXTDESCR . '_editor'});




    //DO any type specific settings
    switch($type) {

        case 'falsefalsefalse':
            $mform = new \mod_pchat\attempt\videouploadform(null,
                array('editoroptions' => $editoroptions,
                    'filemanageroptions' => $filemanageroptions)
            );
            break;


    }

		//now update the db once we have saved files and stuff
		if (!$DB->update_record(constants::M_QTABLE,$theattempt)){
				print_error("Could not update pchat attempt!");
				redirect($redirecturl);
		}

		//go back to edit quiz page
		redirect($redirecturl);
}


//if  we got here, there was no cancel, and no form data, so we are showing the form
//if edit mode load up the attempt into a data object
if ($edit) {
	$data = $attempt;
	$data->attemptid = $attempt->id;
}else{
	$data=new stdClass;
	$data->attemptid = null;
	$data->visible = 1;
	$data->type=$type;
}
		
	//init our attempt, we move the id fields around a little
    $data->id = $cm->id;
    $data = file_prepare_standard_editor($data, constants::TEXTDESCR, $editoroptions, $context, constants::M_COMPONENT,
		constants::TEXTDESCR_FILEAREA,  $data->attemptid);
		

	//Set up the attempt type specific parts of the form data
    $attemptrenderer = $PAGE->get_renderer('mod_pchat','attempt');
    $amd_data='';
	switch($type){
		case 'falsefalse':

            //save text answers
            /*
            for($anumber=1;$anumber<=constants::MAXANSWERS;$anumber++) {
                $data = file_prepare_standard_editor($data, constants::TEXTANSWER . $anumber, $editoroptions, $context,
                    constants::M_COMPONENT, constants::TEXTANSWER_FILEAREA. $anumber , $data->attemptid);
            }
            */
            break;


        case constants::TYPE_AUDIORECORDING:
            if(!$edit) {
                $amd_data = $attemptrenderer->fetch_recorder_amd($cm);
            }
		default:
	}
    $mform->set_data($data);
    $PAGE->navbar->add(get_string('edit'), new moodle_url('/mod/pchat/attempt/attempts.php', array('id'=>$id)));
    $PAGE->navbar->add(get_string('editingattempt', constants::M_COMPONENT, get_string($mform->typestring, constants::M_COMPONENT)));
	$renderer = $PAGE->get_renderer('mod_pchat');
	$mode='attempts';
	echo $renderer->header($moduleinstance, $cm,$mode, null, get_string('edit', constants::M_COMPONENT));
	$mform->display();
	echo $amd_data;
	echo $renderer->footer();