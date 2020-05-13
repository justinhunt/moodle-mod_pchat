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
 * Reports for pchat
 *
 *
 * @package    mod_pchat
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

use \mod_pchat\constants;
use \mod_pchat\utils;
global $DB;
$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // pchat instance ID
$format = optional_param('format', 'html', PARAM_TEXT); //export format csv or html
$showreport = optional_param('report', 'menu', PARAM_TEXT); // report type
$userid = optional_param('userid', 0, PARAM_INT); // report type
$attemptid = optional_param('attemptid', 0, PARAM_INT); // report type

//paging details
$paging = new stdClass();
$paging->perpage = optional_param('perpage',-1, PARAM_INT);
$paging->pageno = optional_param('pageno',0, PARAM_INT);
$paging->sort  = optional_param('sort','iddsc', PARAM_TEXT);


if ($id) {
    $cm         = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record(constants::M_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record(constants::M_TABLE, array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$PAGE->set_url(constants::M_URL . '/grades.php');
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/pchat:grades', $modulecontext);

//Get an admin settings
$config = get_config(constants::M_COMPONENT);

//set per page according to admin setting
if($paging->perpage==-1){
		$paging->perpage = $config->attemptsperpage;
}

$sql = "select pa.id, u.lastname, u.firstname, p.name, p.transcriber, turns, avturn, par.accuracy
from mdl_pchat_attempts pa
    inner join mdl_user u on pa.userid = u.id
    inner join mdl_pchat p on p.id = pa.pchat
    inner join mdl_pchat_attemptstats pat on pat.attemptid = pa.id and pat.userid = u.id
    left outer join mdl_pchat_ai_result par on par.attemptid = pa.id and par.courseid = p.course
where p.course = 2";

$data = $DB->get_records_sql($sql);
$data = current($data);
$gradesrenderer =
    $OUTPUT->render_from_template(constants::M_COMPONENT . '/grades', array ('data' => $data));


/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->requires->jquery();



$aph_opts =Array();
//this inits the grading helper JS
$PAGE->requires->js_call_amd("mod_pchat/hiddenplayerhelper", 'init', array($aph_opts));


//This puts all our display logic into the renderer.php files in this plugin
$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
$reportrenderer = $PAGE->get_renderer(constants::M_COMPONENT,'report');

//From here we actually display the page.
//this is core renderer stuff
$mode = "grades";
$extraheader="";

echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grades', constants::M_COMPONENT));
echo $extraheader;
echo $gradesrenderer;
echo $renderer->footer();