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
 * Ajax helper for Read Seed
 *
 *
 * @package    mod_pchat
 * @copyright  Justin Hunt (justin@poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

use \mod_pchat\constants;
use \mod_pchat\utils;
use \mod_pchat\aigrade;

$cmid = required_param('cmid',  PARAM_INT); // course_module ID, or
//$sessionid = required_param('sessionid',  PARAM_INT); // course_module ID, or
$filename= optional_param('filename','',  PARAM_TEXT); // data baby yeah
$rectime= optional_param('rectime', 0, PARAM_INT);
$action= optional_param('action', 'readingresults', PARAM_TEXT);
$attemptid= optional_param('attemptid', 0, PARAM_INT);
$quizresults= optional_param('quizresults', '', PARAM_RAW);

$ret =new stdClass();

if ($cmid) {
    $cm         = get_coursemodule_from_id(constants::M_MODNAME, $cmid, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $pchat  = $DB->get_record(constants::M_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $ret->success=false;
    $ret->message="You must specify a course_module ID or an instance ID";
    return json_encode($ret);
}

require_login($course, false, $cm);
$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);

switch($action){
    case 'readingresults':
        process_reading_results($modulecontext,$filename,$rectime,$pchat);
        break;
    case 'quizresults':
        process_quizresults($modulecontext,$pchat, $quizresults, $attemptid);
}
return;

//save the data to Moodle.
function process_quizresults($modulecontext,$pchat,$quizresults,$attemptid)
{
    global $USER, $DB;

    $result=false;
    $message = '';
    $returndata=false;


    $attempt = $DB->get_record(constants::M_USERTABLE,array('id'=>$attemptid,'userid'=>$USER->id));
    if($attempt) {
        $useresults = json_decode($quizresults);
        //more data here
        if(isset($useresults->qanswer1)){$attempt->qanswer1=$useresults->qanswer1;}
        if(isset($useresults->qanswer2)){$attempt->qanswer2=$useresults->qanswer2;}
        if(isset($useresults->qanswer3)){$attempt->qanswer3=$useresults->qanswer3;}
        if(isset($useresults->qanswer4)){$attempt->qanswer4=$useresults->qanswer4;}
        if(isset($useresults->qanswer5)){$attempt->qanswer5=$useresults->qanswer5;}
        if(isset($useresults->qtextanswer1)){$attempt->qtextanswer1=$useresults->qtextanswer1;}
        //get users flower
        $flower = utils::fetch_newflower();
        if($flower) {
            $attempt->flowerid = $flower->id;
        }
        $result = $DB->update_record(constants::M_USERTABLE, $attempt);
        if($result) {
            $returndata= $flower;
        }else{
            $message = 'unable to update attempt record';
        }
    }else{
        $message='no attempt of that id for that user';
    }
    return_to_page($result,$message,$returndata);
}


function process_reading_results($modulecontext,$filename,$rectime,$pchat)
{
//make database items and adhoc tasks
    $success = false;
    $message = '';
    $returndata=false;

    $attemptid = save_readingresults_to_moodle($filename, $rectime, $pchat);
    if ($attemptid) {
        if (\mod_pchat\utils::can_transcribe($pchat)) {
            $success = register_aws_task($pchat->id, $attemptid, $modulecontext->id);
            if (!$success) {
                $message = "Unable to create adhoc task to fetch transcriptions";
            }
        } else {
            $success = true;
        }
    } else {
        $message = "Unable to add update database with submission";
    }
    if($success){$returndata=$attemptid;}
    return_to_page($success,$message,$returndata);
}

//save the data to Moodle.
function save_readingresults_to_moodle($filename,$rectime, $pchat){
    global $USER,$DB;

    //Add a blank attempt with just the filename  and essential details
    $newattempt = new stdClass();
    $newattempt->courseid=$pchat->course;
    $newattempt->pchatid=$pchat->id;
    $newattempt->userid=$USER->id;
    $newattempt->status=0;
    $newattempt->filename=$filename;
    $newattempt->sessionscore=0;
    //$newattempt->sessiontime=$rectime;  //.. this would work. But sessiontime is used as flag of human has graded ...so needs more thought
    $newattempt->sessionerrors='';
    $newattempt->errorcount=0;
    $newattempt->wpm=0;
    $newattempt->timecreated=time();
    $newattempt->timemodified=time();
    $attemptid = $DB->insert_record(constants::M_USERTABLE,$newattempt);
    if(!$attemptid){
        return false;
    }
    $newattempt->id = $attemptid;

    //if we are machine grading we need an entry to AI table too
    //But ... there is the chance a user will CHANGE this value after submissions have begun,
    //If they do, INNER JOIN SQL in grade related logic will mess up gradebook if aigrade record is not available.
    //So for prudence sake we ALWAYS create an aigrade record
    if(true || $pchat->machgrademethod == constants::MACHINEGRADE_MACHINE) {
        aigrade::create_record($newattempt, $pchat->timelimit);
    }

    //return the attempt id
    return $attemptid;
}

//register an adhoc task to pick up transcripts
function register_aws_task($activityid, $attemptid,$modulecontextid){
    $s3_task = new \mod_pchat\task\pchat_s3_adhoc();
    $s3_task->set_component('mod_pchat');

    $customdata = new \stdClass();
    $customdata->activityid = $activityid;
    $customdata->attemptid = $attemptid;
    $customdata->modulecontextid = $modulecontextid;

    $s3_task->set_custom_data($customdata);
    // queue it
    \core\task\manager::queue_adhoc_task($s3_task);
    return true;
}

//handle return to Moodle
function return_to_page($success, $message=false,$data=false)
{
    $ret = new stdClass();
    $ret->success = $success;
    $ret->data=$data;
    $ret->message = $message;
    echo json_encode($ret);
}