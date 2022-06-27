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
 * Grades submission page for pchat
 *
 * @package    mod_pchat
 * @copyright  2020 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/grade/grading/lib.php');
require_once('rubric_grade_form.php');
require_once('simple_grade_form.php');

use mod_pchat\constants;
use mod_pchat\grades\gradesubmissions as gradesubmissions;

global $DB;

// Page level constants
// Min and max number of grades to display on a page; 0 based.
define('MIN_GRADE_DISPLAY', 0);
define('MAX_GRADE_DISPLAY', 3);

// Page classes
$gradesubmissions = new gradesubmissions();

// Course module ID.
$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$attempt = required_param('attempt', PARAM_INT);
$pagestyle = optional_param('pagestyle',constants::M_CONVGROUP,PARAM_INT);

// Course and course module data.
$cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, IGNORE_MISSING);
$course = $DB->get_record('course', array('id' => $cm->course), '*', IGNORE_MISSING);
$moduleinstance = $DB->get_record(constants::M_TABLE, array('id' => $cm->instance), '*', IGNORE_MISSING);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/pchat:grades', $modulecontext);

// Set page login data.
$PAGE->set_url(constants::M_URL . '/gradesubmissions.php',array('id'=>$id,'userid'=>$userid, 'attempt'=>$attempt, 'pagestyle'=>$pagestyle));
require_login($course, true, $cm);

$gradingmanager = get_grading_manager($modulecontext, 'mod_pchat', 'pchat');
$grademethod = $gradingmanager->get_active_method();
if($grademethod!=='rubric'){
    $grademethod='simple';
}

// fetch groupmode/menu/id for this activity
$groupmenu = '';
if ($groupmode = groups_get_activity_groupmode($cm)) {
    $groupmenu = groups_print_activity_menu($cm, $PAGE->url, true);
    $groupmenu .= ' ';
    $groupid = groups_get_activity_group($cm);
    $groupname = groups_get_group_name($groupid);
}else{
    $groupid  = 0;
    $groupname ='';
}

// Get student grade data.
$studentAndInterlocutors = $gradesubmissions->getStudentsToGrade($moduleinstance, $groupid);
$thestudents=[];
$convgroups = [];
$processedstudents = [];
$currentgrouppage = -1;
$currentconvgroup=false;

//make conversation groups for navaigating through
foreach($studentAndInterlocutors as $convgroup){
    if(in_array($convgroup->userid, $processedstudents)){continue;}
    $thestudents = explode(',', $convgroup->students);
    $processedstudents =  array_merge($processedstudents  , $thestudents);
    $convgroups[] = $thestudents;//new ArrayIterator(array_pad($thestudents, MAX_GRADE_DISPLAY, ''));
    if(in_array($userid, $thestudents)) {
        $currentconvgroup = $thestudents;
        $currentgrouppage=count($convgroups)-1;
    }
}

//make pages of a single user for navigating through
$perpage=1;
list($pagesofstudents,$currentstudentpage) = $gradesubmissions->getPageOfStudents($studentAndInterlocutors,$userid,$perpage);



//get all eligible students for the course, and then create a conv group if they exist
if($groupid>0) {
    $submissionCandidates =  groups_get_members($groupid);
}else{
    $submissionCandidates = get_enrolled_users($modulecontext, 'mod/pchat:submit');
}

// Ensure selected items of the current group
if($currentconvgroup) {
    array_walk($submissionCandidates, function ($candidate) use ($currentconvgroup) {
        if (in_array($candidate->id, $currentconvgroup, true)) {
            $candidate->selected = "selected='selected'";
        }
    });
}


$submissionCandidates = new ArrayIterator($submissionCandidates);

$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->requires->jquery();

// Render template and display page.
$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
$context = context_course::instance($course->id);

switch($pagestyle){
    case constants::M_SINGLES:
        $currentpage = $currentstudentpage;
        if($currentpage < 0){
            echo $renderer->header($moduleinstance, $cm, "gradesubmissions");
        // not implemented
       //     echo $groupmenu;
            if(!empty($groupname)){echo $groupname . '<br>';}
            echo $renderer->show_nosubmissions_message();
            echo $renderer->footer();
            return;
        }
        $studentsToGrade = $pagesofstudents[$currentstudentpage];
        $pages = json_encode($pagesofstudents);
        break;
    case constants::M_CONVGROUP:
    default:
        if($currentgrouppage<0){
            echo $renderer->header($moduleinstance, $cm, "gradesubmissions");
            // not implemented
       //     echo $groupmenu;
            if(!empty($groupname)){echo $groupname . '<br>';}
            echo $renderer->show_nosubmissions_message();
            echo $renderer->footer();
            return;
        }
        $studentsToGrade = $convgroups[$currentgrouppage];
        $currentpage = $currentgrouppage;
        $pages = json_encode( $convgroups);

}

$templatedata =  array(
    'studentsToGrade' => $studentsToGrade,
    'submissionCandidates' => $submissionCandidates,
    'contextid' => $context->id,
    'cmid' => $cm->id,
    'attemptid' => $attempt,
    'grademethod'=>$grademethod,
    'currentpage'=>$currentpage,
    'pages'=>$pages
);
if($pagestyle== constants::M_SINGLES){
    $templatedata['singlemode']=true;
    $templatedata['revq1']=nl2br($moduleinstance->revq1);
    $templatedata['revq2']=nl2br($moduleinstance->revq2);
    $templatedata['revq3']=nl2br($moduleinstance->revq3);
}

$gradesrenderer =
    $OUTPUT->render_from_template(
        constants::M_COMPONENT . '/gradesubmissions',
       $templatedata
    );

echo $renderer->header($moduleinstance, $cm, "gradesubmissions");
echo $groupmenu;
echo $gradesrenderer;
echo $renderer->footer();
