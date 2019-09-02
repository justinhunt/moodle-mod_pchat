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
$type  = optional_param('type', constants::STEP_NONE, PARAM_INT);
$action = optional_param('action','edit',PARAM_TEXT);

// get the objects we need
$cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record(constants::M_MODNAME, array('id' => $cm->instance), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pchat:view', $context);

//set up the page object
$PAGE->set_url('/mod/pchat/attempt/manageattempts.php', array('attemptid'=>$attemptid, 'id'=>$id, 'type'=>$type));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//are we in new or edit mode?
$attempt=false;
if ($attemptid) {
    $attempt = $DB->get_record(constants::M_ATTEMPTSTABLE, array('id'=>$attemptid,constants::M_MODNAME => $cm->instance), '*', MUST_EXIST);
    if(!$attempt){
        print_error('could not find attempt of id:' . $attemptid);
    }
    //This wopuld force a step, if we needed to
    $lateststep = $attempt->completedsteps;
    $edit = true;
} else {
    $lateststep = constants::STEP_NONE;
    $edit = false;
}

//we always head back to the pchat attempts page
$redirecturl = new moodle_url('/mod/pchat/view.php', array('id'=>$cm->id));
//just init this when we need it.
$topichelper=false;

//handle delete actions
if($action == 'confirmdelete'){
    $usecount = $DB->count_records(constants::M_ATTEMPTSTABLE,array(constants::M_MODNAME =>$cm->instance));

    $renderer = $PAGE->get_renderer(constants::M_COMPONENT);
    $attempt_renderer = $PAGE->get_renderer(constants::M_COMPONENT,'attempt');
    echo $renderer->header($moduleinstance, $cm, 'attempts', null, get_string('confirmattemptdeletetitle', constants::M_COMPONENT));
    echo $attempt_renderer->confirm(get_string("confirmattemptdelete",constants::M_COMPONENT),
            new moodle_url('/mod/pchat/attempt/manageattempts.php', array('action'=>'delete','id'=>$cm->id,'attemptid'=>$attemptid)),
            $redirecturl);
    echo $renderer->footer();
    return;

    /////// Delete attempt NOW////////
}elseif ($action == 'delete'){
    require_sesskey();
    $success = \mod_pchat\attempt\helper::delete_attempt($moduleinstance,$attemptid,$context);
    redirect($redirecturl);
}

$siteconfig = get_config(constants::M_COMPONENT);
$token= utils::fetch_token($siteconfig->apiuser,$siteconfig->apisecret);

//get the mform for our attempt
switch($type){

    case constants::STEP_AUDIORECORDING:
        $targetwords = $attempt ? $attempt->topictargetwords : '';
        $targetwords .= $attempt ? PHP_EOL . $attempt->mywords : '';
        $mform = new \mod_pchat\attempt\audiorecordingform(null,
                array('moduleinstance'=>$moduleinstance,
                        'token'=>$token,
                        'targetwords'=>$targetwords));
        break;

    case constants::STEP_USERSELECTIONS:
        if(!$topichelper) {
            $topichelper = new \mod_pchat\topichelper($cm);
        }
        $topics = $topichelper->fetch_selected_topics();
        $users = get_enrolled_users($context);
        $targetwords = $attempt ? $attempt->topictargetwords : '';
        $mform = new \mod_pchat\attempt\userselectionsform(null,
                array('moduleinstance'=>$moduleinstance,
                        'topics'=>$topics,
                        'users'=>$users,
                        'targetwords'=>$targetwords));
        break;

    case constants::STEP_SELFTRANSCRIBE:
        $audiofilename = '';
        if($attempt){
            $audiofilename =$attempt->filename;
        }
        $mform = new \mod_pchat\attempt\selftranscribeform(null,
                array('moduleinstance'=>$moduleinstance,'filename'=>$audiofilename));
        break;

    case constants::STEP_SELFREVIEW:
        $selftranscript='';
        $autotranscript='';
        $stats = false;
        if($attempt){
            //try to pull transcripts if we have none. Why wait for cron?
            if(empty($attempt->transcript)){
                $with_transcripts_attempt = utils::retrieve_transcripts($attempt);
                if($with_transcripts_attempt){
                    $autotranscript=$with_transcripts_attempt->transcript;
                }
            }else{
                $autotranscript=$attempt->transcript;
            }
            if(empty($autotranscript)){$autotranscript=get_string('transcriptnotready',constants::M_COMPONENT);}
            if(!empty($attempt->selftranscript)){$selftranscript=utils::extract_simple_transcript($attempt->selftranscript);}
            $stats =utils::fetch_stats($attempt);
        }
        $mform = new \mod_pchat\attempt\selfreviewform(null,
                array('moduleinstance'=>$moduleinstance,
                        'selftranscript'=>$selftranscript,
                        'autotranscript'=>$autotranscript,
                        'stats'=>$stats));
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
    $theattempt->userid = $USER->id;
    $theattempt->modifiedby=$USER->id;
    $theattempt->timemodified=time();

    //first insert a new attempt if we need to
    //that will give us a attemptid, we need that for saving files
    if($edit) {
        $theattempt->id = $data->attemptid;
    }else{
        $theattempt->timecreated=time();
        $theattempt->createdby=$USER->id;

        //try to insert it
        if (!$theattempt->id = $DB->insert_record(constants::M_ATTEMPTSTABLE,$theattempt)){
            print_error("Could not insert pchat attempt!");
            redirect($redirecturl);
        }
    }


    //type specific settings
    switch($type) {
        case constants::STEP_USERSELECTIONS:
            if($data->topicid) {
                if(!$topichelper) {
                    $topichelper = new \mod_pchat\topichelper($cm);
                }
                $topic = $topichelper->fetch_topic($data->topicid);
                if($topic) {
                    $theattempt->topicname = $topic->name;
                    $theattempt->topicfonticon = $topic->fonticon;
                    $theattempt->topictargetwords = $topic->targetwords;
                }
            }
            break;

        case constants::STEP_AUDIORECORDING:
            if(!empty($theattempt->filename)) {
                utils::register_aws_task($moduleinstance->id, $theattempt->id, $context->id);
            }
            break;
        case constants::STEP_SELFTRANSCRIBE:
            //if the user has altered their self transcript, we ought to recalc all the stats
            $st_altered = $attempt && $theattempt->selftranscript
                    && $attempt->selftranscript != $theattempt->selftranscript;
            if($st_altered) {
                $stats = utils::calculate_stats($theattempt->selftranscript, $attempt);
                if ($stats) {
                    utils::save_stats($stats, $attempt);
                }
            }
            break;
        case constants::STEP_SELFREVIEW:
        default:
    }

    //Set the last completed stage
    if($lateststep < $type){
        $theattempt->completedsteps = $type;
    }

    //now update the db
    if (!$DB->update_record(constants::M_ATTEMPTSTABLE,$theattempt)){
        print_error("Could not update pchat attempt!");
        redirect($redirecturl);
    }

    //go back to top page
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
}
$data->type=$type;

//init our attempt, we move the id fields around a little
$data->id = $cm->id;

//Set up the attempt type specific parts of the form data
$attempt_renderer = $PAGE->get_renderer('mod_pchat','attempt');
$amd_data='';
switch($type){
    case constants::STEP_AUDIORECORDING:
    case constants::STEP_USERSELECTIONS:
    case constants::STEP_SELFTRANSCRIBE:
    case constants::STEP_SELFREVIEW:
    default:
}
$mform->set_data($data);
$PAGE->navbar->add(get_string('edit'), new moodle_url('/mod/pchat/view.php', array('id'=>$id)));
$PAGE->navbar->add(get_string('editingattempt', constants::M_COMPONENT, get_string($mform->typestring, constants::M_COMPONENT)));
$renderer = $PAGE->get_renderer('mod_pchat');
$mode='attempts';
echo $renderer->header($moduleinstance, $cm,$mode, null, get_string('edit', constants::M_COMPONENT));
echo $attempt_renderer->add_edit_page_links($moduleinstance, $attempt,$type);
$mform->display();
echo $amd_data;
echo $renderer->footer();