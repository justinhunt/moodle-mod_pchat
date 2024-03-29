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
 * Provides the main page for pchat
 *
 * @package mod_pchat
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/pchat/lib.php');

use mod_pchat\constants;
use mod_pchat\utils;

$id = optional_param('id',0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // pchat instance ID
$reattempt = optional_param('reattempt',0, PARAM_INT);
$requeststep = optional_param('requeststep', constants::STEP_NONE, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record(constants::M_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record(constants::M_MODNAME, array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
    $id = $cm->id;
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

$attempthelper = new \mod_pchat\attempthelper($cm);
$attempts = $attempthelper->fetch_attempts();

//mode is necessary for tabs
$mode='attempts';
//Set page url before require login, so post login will return here
$PAGE->set_url(constants::M_URL . '/view.php', array('id'=>$cm->id,'mode'=>$mode));
$PAGE->force_settings_menu(true);


//require login for this page
require_login($course, false, $cm);
$context = context_module::instance($cm->id);


$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
$attempt_renderer = $PAGE->get_renderer(constants::M_COMPONENT,'attempt');


// We need view permission to be here
require_capability('mod/pchat:view', $context);

//Do we do continue an attempt or start a new one
$start_or_continue=false;
if(count($attempts)==0){
    $start_or_continue=true;
    $nextstep = constants::STEP_USERSELECTIONS;
    $attemptid = 0;
} elseif($reattempt==1){
    $start_or_continue=true;
    $nextstep = constants::STEP_USERSELECTIONS;
    $attemptid = 0;
}else{
    $latestattempt = $attempthelper->fetch_latest_attempt();
    if($latestattempt
        && $latestattempt->completedsteps >= $requeststep
        && $requeststep>constants::STEP_NONE
        && $requeststep <=  constants::STEP_SELFREVIEW){

        $start_or_continue=true;
        $nextstep=$requeststep;
        $attemptid=$latestattempt->id;

    } else if ($latestattempt && $latestattempt->completedsteps < constants::STEP_SELFREVIEW){
        $start_or_continue=true;
        $nextstep=$latestattempt->completedsteps+1;
        $attemptid=$latestattempt->id;
    }
}

//either redirect to a form handler for the attempt step, or show our attempt summary
if($start_or_continue) {

    // Set this modyle as viewed for Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    $redirecturl = new moodle_url(constants::M_URL . '/attempt/manageattempts.php',
            array('id'=>$cm->id, 'attemptid' => $attemptid, 'type' => $nextstep));
    redirect($redirecturl);


}else{

    //if we need datatables we need to set that up before calling $renderer->header
    $tableid = '' . constants::M_CLASS_ITEMTABLE . '_' . '_opts_9999';
    $attempt_renderer->setup_datatables($tableid);

    $PAGE->navbar->add(get_string('attempts', constants::M_COMPONENT));

    echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('attempts', constants::M_COMPONENT));


    $attempt = $attempthelper->fetch_latest_complete_attempt();
    if($attempt) {
        $stats=utils::fetch_stats($attempt);
        $aidata = $DB->get_record(constants::M_AITABLE,array('attemptid'=>$attempt->id));
        echo $attempt_renderer->show_summary($moduleinstance,$attempt,$aidata, $stats);
    }

    //necessary for M3.3
    require_once($CFG->libdir.'/gradelib.php');
    //rubric grades
    $gradinginfo = grade_get_grades($moduleinstance->course, 'mod', 'pchat', $moduleinstance->id, $USER->id);
    if(!empty($gradinginfo ) && $attempt->grade !=null) {
        //get feedback
        $feedback=$attempt->feedback;

        //get display grade
        $displaygrade='';
        $displaygrades = utils::make_grades_menu($moduleinstance->grade);
        if($attempt->grade===null){
            $displaygrade ='';
        }else{
            if(array_key_exists($attempt->grade,$displaygrades)){
                $displaygrade =$displaygrades[$attempt->grade];
                //In the case of decimals, they wont appear in the display grades list, so we mess around removing zeros and building our own equivalent
            }elseif (count($displaygrades)>1 &&
                (is_numeric($attempt->grade ) && is_string($attempt->grade))){
                $displaygrade = floatval($attempt->grade) . '/' . $moduleinstance->grade;
            }
        }

        //get rubric
        $rubricresults= utils::display_rubricgrade($context,$moduleinstance,$attempt,$gradinginfo );

        echo $attempt_renderer->show_teachereval( $rubricresults,$feedback, $displaygrade);

    }
    //myreports
    echo $attempt_renderer->show_myreports($moduleinstance,$cm);

    //all attempts by user table [good for debugging]
    // do not delete this I think
    // echo $attempt_renderer->show_attempts_list($attempts,$tableid,$cm);

    if(empty($gradinginfo ) && ($moduleinstance->multiattempts || has_capability('mod/pchat:manageattempts', $context) )){
        echo $attempt_renderer->fetch_reattempt_button($cm);
    }
    if($attempt) {
        if ($moduleinstance->postattemptedit || has_capability('mod/pchat:manageattempts', $context)) {
            echo $attempt_renderer->fetch_postattemptedit_link($cm, $attempt->id);
        }
    }
}
echo $renderer->footer();