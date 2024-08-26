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
 * Personal Reports for pchat
 *
 *
 * @package    mod_pchat
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

use \mod_pchat\constants;
use \mod_pchat\utils;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // pchat instance ID
$format = optional_param('format', 'tabular', PARAM_TEXT); //export format csv or tabular or linechart
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

$PAGE->set_url(constants::M_URL . '/myreports.php',
	array('id' => $cm->id,'report'=>$showreport,'format'=>$format,'userid'=>$userid,'attemptid'=>$attemptid));
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/pchat:view', $modulecontext);

//Get an admin settings 
$config = get_config(constants::M_COMPONENT);

//set per page according to admin setting
if($paging->perpage==-1){
		$paging->perpage = $config->attemptsperpage;
}



// Trigger module viewed event.
$event = \mod_pchat\event\course_module_viewed::create(array(
   'objectid' => $moduleinstance->id,
   'context' => $modulecontext
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot(constants::M_MODNAME, $moduleinstance);
$event->trigger();



/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('incourse');
$PAGE->requires->jquery();

	

$aph_opts =Array();
//this inits the grading helper JS
$PAGE->requires->js_call_amd("mod_pchat/hiddenplayerhelper", 'init', array($aph_opts));


//This puts all our display logic into the renderer.php files in this plugin
$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
$reportrenderer = $PAGE->get_renderer(constants::M_COMPONENT,'report');

//From here we actually display the page.
//this is core renderer stuff
$mode = "reports";
$extraheader="";
switch ($showreport){



    case 'myattempts':
        $report = new \mod_pchat\report\myattempts($cm);
        $formdata = new stdClass();
        break;

    case 'myprogress':
        $report = new \mod_pchat\report\myprogress($cm);
        $formdata = new stdClass();
        break;


    case 'singleattempt':
        $attempt = $DB->get_record(constants::M_ATTEMPTSTABLE,array('id'=>$attemptid));
        if($attempt) {
            if($attempt->userid === $USER->id) {
                echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
                $stats = utils::fetch_stats($attempt);
                $aidata = $DB->get_record(constants::M_AITABLE, array('attemptid' => $attemptid));
                $attempt_renderer = $PAGE->get_renderer(constants::M_COMPONENT,'attempt');
                echo $attempt_renderer->show_userattemptsummary($moduleinstance, $attempt, $aidata, $stats);

                //grade info
                //necessary for M3.3
                require_once($CFG->libdir.'/gradelib.php');
                $gradinginfo = grade_get_grades($moduleinstance->course, 'mod', 'pchat', $moduleinstance->id, $attempt->userid);
                if(!empty($gradinginfo ) && $attempt->grade !=null) {
                    $rubricresults= utils::display_rubricgrade($modulecontext,$moduleinstance,$attempt,$gradinginfo );
                    $feedback=$attempt->feedback;
                    $displaygrade='';
                    $displaygrades = make_grades_menu($moduleinstance->grade);
                    if(array_key_exists($attempt->grade,$displaygrades)){
                        $displaygrade =$displaygrades[$attempt->grade];
                    }
                    echo $attempt_renderer->show_teachereval( $rubricresults,$feedback, $displaygrade);

                }

                $link = new \moodle_url(constants::M_URL . '/view.php', array('id' => $cm->id, 'n' => $moduleinstance->id));
                echo  \html_writer::link($link, get_string('returntotop', constants::M_COMPONENT));
                echo $renderer->footer();
                return;
            }
        }
        break;
		
	default:

        echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
		echo "unknown report type.";
		echo $renderer->footer();
		return;
}

/*
1) load the class
2) call report->process_raw_data
3) call $rows=report->fetch_formatted_records($withlinks=true(html) false(print/excel))
5) call $reportrenderer->render_section_html($sectiontitle, $report->name, $report->get_head, $rows, $report->fields);
*/

$report->process_raw_data($formdata);
$reportheading = $report->fetch_formatted_heading();

switch($format){

    case 'linechart':

        echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
        echo $extraheader;
        echo $reportrenderer->heading($reportheading, 4);
        switch($showreport) {
            case 'myprogress':
                $fields = array('pchatname', 'stats_turns', 'stats_avturn', 'stats_longestturn', 'stats_questions');
                echo $reportrenderer->render_linechart($report->fetch_chart_data($fields));
                $fields = array('pchatname', 'stats_aiaccuracy');
                echo $reportrenderer->render_linechart($report->fetch_chart_data($fields));
                $fields = array('pchatname', 'stats_words');
                echo $reportrenderer->render_linechart($report->fetch_chart_data($fields));
                break;
            case 'classprogress':
                $fields = array('pchatname', 'avturns', 'avatl', 'avltl', 'avtw', 'avq');
                echo $reportrenderer->render_linechart($report->fetch_chart_data($fields));
                $fields = array('pchatname', 'avacc');
                echo $reportrenderer->render_linechart($report->fetch_chart_data($fields));
                $fields = array('pchatname', 'avw');
                echo $reportrenderer->render_linechart($report->fetch_chart_data($fields));
                break;
        }
        $link = new \moodle_url(constants::M_URL . '/view.php', array('id' => $cm->id, 'n' => $moduleinstance->id));
        echo  \html_writer::link($link, get_string('returntotop', constants::M_COMPONENT));
        echo $renderer->footer();
        exit;

    case 'tabular':
	default:
		
		$reportrows = $report->fetch_formatted_rows(true,$paging);
		$allrowscount = $report->fetch_all_rows_count();

		if(constants::M_USE_DATATABLES){
		    //css must be required before header sent out
            $PAGE->requires->css( new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));
            echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
            echo $extraheader;
            echo $reportrenderer->render_hiddenaudioplayer();
            echo $reportrenderer->render_section_html($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows,
                    $report->fetch_fields());

        }else {

            $pagingbar = $reportrenderer->show_paging_bar($allrowscount, $paging, $PAGE->url);
            echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
            echo $extraheader;
            echo $reportrenderer->render_hiddenaudioplayer();
            echo $pagingbar;
            echo $reportrenderer->render_section_html($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows,
                    $report->fetch_fields());
            echo $pagingbar;
        }
        $link = new \moodle_url(constants::M_URL . '/view.php', array('id' => $cm->id, 'n' => $moduleinstance->id));
        echo  \html_writer::link($link, get_string('returntotop', constants::M_COMPONENT));
		echo $renderer->footer();
}