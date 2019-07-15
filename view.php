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
 * Prints a particular instance of pchat
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_pchat
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
use \mod_pchat\constants;




$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$retake = optional_param('retake', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // pchat instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('pchat', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record('pchat', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record('pchat', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('pchat', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$PAGE->set_url('/mod/pchat/view.php', array('id' => $cm->id));
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

// Trigger module viewed event.
$event = \mod_pchat\event\course_module_viewed::create(array(
   'objectid' => $moduleinstance->id,
   'context' => $modulecontext
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('pchat', $moduleinstance);
$event->trigger();


//if we got this far, we can consider the activity "viewed"
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

//are we a teacher or a student?
$mode= "view";

/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');



//Get an admin settings 
$config = get_config(constants::M_COMPONENT);

//Get our renderers
$renderer = $PAGE->get_renderer('mod_pchat');


//From here we actually display the page.
//this is core renderer stuff

//if we are teacher we see tabs. If student we just see the quiz
//if(has_capability('mod/pchat:preview',$modulecontext)){
	echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', constants::M_COMPONENT));
//}else{
//	echo $renderer->notabsheader();
//}

//fetch token
$token = \mod_pchat\utils::fetch_token($config->apiuser,$config->apisecret);


//show all the main parts. Many will be hidden and displayed by JS
echo $renderer->show_intro($moduleinstance,$cm);
//echo $renderer->show_recorder($moduleinstance,$token);

//the module AMD code
echo $renderer->fetch_activity_amd($cm, $moduleinstance);

// Finish the page
echo $renderer->footer();
