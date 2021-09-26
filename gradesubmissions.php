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

// Course and course module data.
$cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, IGNORE_MISSING);
$course = $DB->get_record('course', array('id' => $cm->course), '*', IGNORE_MISSING);
$moduleinstance = $DB->get_record(constants::M_TABLE, array('id' => $cm->instance), '*', IGNORE_MISSING);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/pchat:grades', $modulecontext);

// Set page login data.
$PAGE->set_url(constants::M_URL . '/gradesubmissions.php',array('id'=>$id,'userid'=>$userid, 'attempt'=>$attempt));
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
}else{
    $groupid  = 0;
}

// Get student grade data.
$studentAndInterlocutors = $gradesubmissions->getStudentsToGrade($attempt,$moduleinstance, $groupid);
$studentAndInterlocutors = explode(',', current($studentAndInterlocutors)->students);
$studentsToGrade = new ArrayIterator(array_pad($studentAndInterlocutors, MAX_GRADE_DISPLAY, ''));



//get all enroled students for the course
if($groupid>0) {
    $submissionCandidates =  groups_get_members($groupid);
}else{
    $submissionCandidates = get_enrolled_users($modulecontext, 'mod/pchat:submit');
}

// Ensure selected items.
array_walk($submissionCandidates, function ($candidate) use ($studentAndInterlocutors) {
    if (in_array($candidate->id, $studentAndInterlocutors, true)) {
        $candidate->selected = "selected='selected'";
    }
});
$submissionCandidates = new ArrayIterator($submissionCandidates);

$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->requires->jquery();

// Render template and display page.
$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
$context = context_course::instance($course->id);

$gradesrenderer =
    $OUTPUT->render_from_template(
        constants::M_COMPONENT . '/gradesubmissions',
        array(
            'studentsToGrade' => $studentsToGrade,
            'submissionCandidates' => $submissionCandidates,
            'contextid' => $context->id,
            'cmid' => $cm->id,
            'attemptid' => $attempt,
            'grademethod'=>$grademethod
        )
    );

echo $renderer->header($moduleinstance, $cm, "gradesubmissions");
echo $groupmenu;
echo $gradesrenderer;
echo $renderer->footer();
