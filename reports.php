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

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // pchat instance ID
$format = optional_param('format', 'tabular', PARAM_TEXT); //export format csv or tabular or linechart
$showreport = optional_param('report', 'menu', PARAM_TEXT); // report type
$userid = optional_param('userid', 0, PARAM_INT); // report type
$attemptid = optional_param('attemptid', 0, PARAM_INT); // report type
$selectedactivities = optional_param('selectedactivities', '*', PARAM_TEXT); // selected activities for class progress
$selected = optional_param_array('selected', [], PARAM_TEXT);

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

if(is_array($selected)){
    if(count($selected)== 0 || (count($selected)==1 && $selected[0]=='' )){
        //do nothing, we will use whatever is in selected activities
    }else {
        $selectedactivities = implode(',', $selected);
    }
}

$PAGE->set_url(constants::M_URL . '/reports.php',
	array('id' => $cm->id,'report'=>$showreport,'format'=>$format,'userid'=>$userid,'attemptid'=>$attemptid,'selectedactivities'=>$selectedactivities));
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/pchat:viewreports', $modulecontext);

//Get an admin settings 
$config = get_config(constants::M_COMPONENT);

//set per page according to admin setting
if(constants::M_USE_DATATABLES){
    $paging=false;
}elseif($paging->perpage==-1){
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

	//not a true report, separate implementation in renderer
	case 'menu':
		echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
        echo $reportrenderer->render_menuinstructions();
		echo $reportrenderer->render_reportmenu($moduleinstance,$cm);
		// Finish the page
		echo $renderer->footer();
		return;

	case 'basic':
		$report = new \mod_pchat\report\basic($cm);
		//formdata should only have simple values, not objects
		//later it gets turned into urls for the export buttons
		$formdata = new stdClass();
		break;

	case 'attempts':
		$report = new \mod_pchat\report\attempts($cm);
		$formdata = new stdClass();
        $formdata->groupmenu = true;
		$formdata->pchatid = $moduleinstance->id;
        $formdata->activityname = $moduleinstance->name;
		break;

    case 'detailedattempts':
        $report = new \mod_pchat\report\detailedattempts($cm);
        $formdata = new stdClass();
        $formdata->groupmenu = true;
        $formdata->pchatid = $moduleinstance->id;
        $formdata->activityname = $moduleinstance->name;
        //$formdata->modulecontextid = $modulecontext->id;
        break;

    case 'classprogress':
        $report = new \mod_pchat\report\classprogress($cm);
        $formdata = new stdClass();
        $formdata->groupmenu = true;
        $formdata->selectedactivities =$selectedactivities ;
        // this is also possible, but painful on group selection
    /*
        $cp_form = new \mod_pchat\report\classprogress_form(null,
            array('cm'=>$cm,'moduleinstance'=>$moduleinstance));
        if($cp_form_data=$cp_form->get_data()) {
            $formdata->selectedactivities = implode(',',$cp_form_data->selectedactivities);
        }
    */
        $formdata->klassname = "The Class";
        break;

    case 'userattempts':
        $report = new \mod_pchat\report\userattempts($cm);
        $formdata = new stdClass();
        $formdata->userid = $userid;
        break;

    case 'myattempts':
        $report = new \mod_pchat\report\myattempts($cm);
        $formdata = new stdClass();
        break;

    case 'myprogress':
        $report = new \mod_pchat\report\myprogress($cm);
        $formdata = new stdClass();
        if($userid > 0 && has_capability('mod/pchat:grades', $modulecontext)){
            $formdata->userid=$userid;
        }
        break;

    case 'downloadaudio':
        $report = new \mod_pchat\report\downloadaudio($cm);
        $formdata = new stdClass();
        $formdata->pchatid = $moduleinstance->id;
        $formdata->activityname = $moduleinstance->name;
        break;


    case 'singleattempt':
        $attempt = $DB->get_record(constants::M_ATTEMPTSTABLE,array('id'=>$attemptid));
        if($attempt) {
            if($attempt->userid === $USER->id ||  has_capability('mod/pchat:manageattempts', $modulecontext)) {
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
                    $displaygrade='';
                    $displaygrades = make_grades_menu($moduleinstance->grade);
                    if(array_key_exists($attempt->grade,$displaygrades)){
                        $displaygrade =$displaygrades[$attempt->grade];
                    }
                    $feedback=$attempt->feedback;
                    echo $attempt_renderer->show_teachereval( $rubricresults,$feedback, $displaygrade);

                }

                $link = new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'menu', 'id' => $cm->id, 'n' => $moduleinstance->id));
                $returnbutton =   \html_writer::link($link, get_string('returntoreports', constants::M_COMPONENT),array('class'=>"btn btn-secondary"));
                echo \html_writer::div($returnbutton,"mod_pchat_textcenter");
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

// fetch groupmode/menu/id for this activity
$groupmenu = '';
if(isset($formdata->groupmenu)){
    // fetch groupmode/menu/id for this activity
    if ($groupmode = groups_get_activity_groupmode($cm)) {
        $groupmenu = groups_print_activity_menu($cm, $PAGE->url, true);
        $groupmenu .= ' ';
        $formdata->groupid = groups_get_activity_group($cm);
    }else{
        $formdata->groupid  = 0;
    }
}else{
    $formdata->groupid  = 0;
}

$report->process_raw_data($formdata);
$reportheading = $report->fetch_formatted_heading();

switch($format){
    case 'filedownload':
        $reportrows = $report->fetch_formatted_rows(false);
        //first check if we actually have some data, if not we just show an "empty" message
        if(count($reportrows)>0) {
            $reportrenderer->render_file_download($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows,
                    $report->fetch_fields());
        }else{
            echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
            echo $extraheader;
            echo $reportrenderer->heading($reportheading, 4);
            echo $reportrenderer->render_empty_section_html($reportheading, 4);
            $showexport =false;
            echo $reportrenderer->show_reports_footer($moduleinstance,$cm,$formdata,$showreport, $format,$showexport);
            echo $renderer->footer();
        }
        exit;

	case 'csv':
	    $withlinks=false;
		$reportrows = $report->fetch_formatted_rows($withlinks);
        //first check if we actually have some data, if not we just show an "empty" message
        if(count($reportrows)>0) {
            $exportfields=true;
            $reportrenderer->render_section_csv($reportheading, $report->fetch_name(), $report->fetch_head($withlinks), $reportrows,
                    $report->fetch_export_fields());
        }else{
            echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
            echo $extraheader;
            echo $groupmenu;
            echo $reportrenderer->heading($reportheading, 4);
            echo $reportrenderer->render_empty_section_html($reportheading, 4);
            $showexport =false;
            echo $reportrenderer->show_reports_footer($moduleinstance,$cm,$formdata,$showreport, $format,$showexport);
            echo $renderer->footer();
        }
		exit;

    case 'linechart':

        echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
        echo $extraheader;
        echo $groupmenu;
        echo $reportrenderer->heading($reportheading, 4);


        //first check if we actually have some data, if not we just show an "empty" message
        $fields = array('pchatname');
        if($report->fetch_chart_data($fields)!==false) {
            $showexport = true;
            echo $reportrenderer->show_reports_footer($moduleinstance,$cm,$formdata,$showreport, $format,$showexport);

            switch ($showreport) {
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

        }else{
            $showexport = false;
            echo $reportrenderer->render_empty_section_html($reportheading, 4);
        }
        echo $reportrenderer->show_reports_footer($moduleinstance,$cm,$formdata,$showreport, $format,$showexport);
        echo $renderer->footer();
        exit;

    case 'tabular':
	default:
		
		$reportrows = $report->fetch_formatted_rows(true,$paging);
		$allrowscount = $report->fetch_all_rows_count();
		$showexport=true;

		if(constants::M_USE_DATATABLES){

		    //css must be required before header sent out
            $PAGE->requires->css( new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));
            echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
            echo $extraheader;
            echo $reportrenderer->show_reports_footer($moduleinstance,$cm,$formdata,$showreport, $format,$showexport);
            echo $groupmenu;
            echo $reportrenderer->render_hiddenaudioplayer();
            echo $reportrenderer->render_section_html($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows,
                    $report->fetch_fields());

        }else {

            $pagingbar = $reportrenderer->show_paging_bar($allrowscount, $paging, $PAGE->url);
            echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('reports', constants::M_COMPONENT));
            echo $extraheader;
            echo $reportrenderer->show_reports_footer($moduleinstance,$cm,$formdata,$showreport, $format,$showexport);
            echo $groupmenu;
            echo $reportrenderer->render_hiddenaudioplayer();
            echo $pagingbar;
            echo $reportrenderer->render_section_html($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows,
                    $report->fetch_fields());
            echo $pagingbar;
        }
        $showexport =count($reportrows)>0;
		echo $reportrenderer->show_reports_footer($moduleinstance,$cm,$formdata,$showreport, $format,$showexport);
		echo $renderer->footer();
}