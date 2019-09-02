<?php

namespace mod_pchat\output;

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


defined('MOODLE_INTERNAL') || die();

use \mod_pchat\constants;
use \mod_pchat\utils;

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_pchat
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_renderer extends \plugin_renderer_base {

 /**
 * Return HTML to display add first page links
 * @param lesson $lesson
 * @return string
 */
 public function add_edit_page_links($pchat, $latestattempt=false, $thisstep) {
		global $CFG;


        //$output = $this->output->heading(get_string("letsgetstarted", "pchat"), 3);
        $output = '';
        $parts = array();
        $buttonclass = 'btn ' . constants::M_COMPONENT .'_menubutton ' . constants::M_COMPONENT;

        //Set the attempt id
        $attemptid = 0;
        //because of the way attemot/data are managed in form handler (manageattempts.php) the true attemptid is at 'attemptid' not 'id'
        if($latestattempt){$attemptid = $latestattempt->attemptid;}


        //Step One Button (user selections)
        $addurl = new \moodle_url(constants::M_URL . '/attempt/manageattempts.php',
                array('id' => $this->page->cm->id, 'attemptid' => $attemptid, 'type' => constants::STEP_USERSELECTIONS));
        $buttonopts =  array('class'=>$buttonclass . ($thisstep == constants::STEP_USERSELECTIONS ? '_activemenubutton' : '_completemenubutton'));
         $parts[] = \html_writer::link($addurl, get_string('attempt_partone', constants::M_COMPONENT), $buttonopts);

     //Step Two Button (conversation recording)
     if($latestattempt && $latestattempt->completedsteps>=constants::STEP_USERSELECTIONS) {
         $addurl = new \moodle_url('/mod/pchat/attempt/manageattempts.php',
                 array('id' => $this->page->cm->id, 'attemptid' => $attemptid, 'type' => constants::STEP_AUDIORECORDING));
         $buttonopts =  array('class'=>$buttonclass . ($thisstep == constants::STEP_AUDIORECORDING ? '_activemenubutton' : '_completemenubutton'));
     }else{
         $addurl="#";
         $buttonopts =  array('class'=>$buttonclass .'_deadmenubutton ');
     }
     $parts[] = \html_writer::link($addurl,get_string('attempt_parttwo', constants::M_COMPONENT), $buttonopts);

     //Step Three Button (self transcribe)
     if($latestattempt && $latestattempt->completedsteps>=constants::STEP_AUDIORECORDING) {
         $addurl = new \moodle_url('/mod/pchat/attempt/manageattempts.php',
                 array('id' => $this->page->cm->id, 'attemptid' => $attemptid, 'type' => constants::STEP_SELFTRANSCRIBE));
         $buttonopts =  array('class'=>$buttonclass . ($thisstep == constants::STEP_SELFTRANSCRIBE ? '_activemenubutton' : '_completemenubutton'));
     }else{
         $addurl="#";
         $buttonopts =  array('class'=>$buttonclass .'_deadmenubutton ');
     }
     $parts[] = \html_writer::link($addurl, get_string('attempt_partthree', constants::M_COMPONENT), $buttonopts);

     //Step Four Button (self REVIEW)
     if($latestattempt && $latestattempt->completedsteps>=constants::STEP_SELFTRANSCRIBE) {
         $addurl = new \moodle_url('/mod/pchat/attempt/manageattempts.php',
                 array('id' => $this->page->cm->id, 'attemptid' => $attemptid, 'type' => constants::STEP_SELFREVIEW));
         $buttonopts =  array('class'=>$buttonclass . ($thisstep == constants::STEP_SELFREVIEW ? '_activemenubutton' : '_completemenubutton'));
     }else{
         $addurl="#";
         $buttonopts =  array('class'=>$buttonclass .'_deadmenubutton ');
     }
     $parts[] = \html_writer::link($addurl, get_string('attempt_partfour', constants::M_COMPONENT), $buttonopts);


    //$glue = '<i class="fa fa-arrow-right"></i>';
     $glue = ' ';

    $buttonsdiv = \html_writer::div(implode($glue, $parts),constants::M_COMPONENT .'_mbuttons');
     return $this->output->box($output . $buttonsdiv, 'generalbox firstpageoptions');
    }

    /**
     *
     */
    public function fetch_reattempt_button($cm){

        $button = $this->output->single_button(new \moodle_url(constants::M_URL . '/view.php',
                array('id'=>$cm->id,'reattempt'=>1)),get_string('reattempt',constants::M_COMPONENT));

        $ret = \html_writer::div($button ,constants::M_CLASS  . '_reattempt_cont');
        return $ret;

    }

    function show_summary($attempt,$stats){
        $attempt->targetwords = utils::fetch_targetwords($attempt);
        $attempt->interlocutornames = utils::fetch_interlocutor_names($attempt);
        $attempt->selftranscriptparts = utils::fetch_selftranscript_parts($attempt);
        $ret = $this->output->render_from_template( constants::M_COMPONENT . '/summaryheader', $attempt);
        $ret .= $this->output->render_from_template( constants::M_COMPONENT . '/summarychoices', $attempt);
        $tdata=array('a'=>$attempt, 's'=>$stats);
        $ret .= $this->output->render_from_template( constants::M_COMPONENT . '/summaryresults', $tdata);
        return $ret;
    }
	
	/**
	 * Return the html table of attempts
	 * @param array homework objects
	 * @param integer $courseid
	 * @return string html of table
	 */
	function show_attempts_list($attempts,$tableid,$cm){
	    global $DB;


		if(!$attempts){
			return $this->output->heading(get_string('noattempts',constants::M_COMPONENT), 3, 'main');
		}
	
		$table = new \html_table();
		$table->id = $tableid;


		$table->head = array(
            get_string('timemodified', constants::M_COMPONENT),
            get_string('topic', constants::M_COMPONENT),
            get_string('users', constants::M_COMPONENT),
			get_string('actions', constants::M_COMPONENT),
            ''
		);
		$table->headspan = array(1,1,1,1,1);
		$table->colclasses = array(
                'timemodified','topic','users', 'actions','actions'
		);

		//loop through the attempts and add to table
        $currentattempt=0;
		foreach ($attempts as $attempt) {
            $currentattempt++;
            $row = new \html_table_row();

            //modify date
            $datecell_content = date("Y-m-d H:i:s",$attempt->timemodified);
            $attemptdatecell = new \html_table_cell($datecell_content);

            //user names
            $usernames = utils::fetch_interlocutor_names($attempt);
            $usernamescell = new \html_table_cell(implode('<br>',$usernames));

            //topic cell
            $topiccell = new \html_table_cell($attempt->topicname);


            //attempt edit
            $actionurl = constants::M_URL . '/attempt/manageattempts.php';

            //attempt part (stages) links
            $parts = array();

            $itemtitle = get_string('attempt_partone', constants::M_COMPONENT);
            if($attempt->completedsteps >= constants::STEP_NONE) {
                $editurl = new \moodle_url($actionurl,
                        array('id' => $cm->id, 'attemptid' => $attempt->id, 'type' => constants::STEP_USERSELECTIONS));
                $edituserselections = \html_writer::link($editurl, $itemtitle);
                $parts[] = $edituserselections;
            }else{
                $parts[] = $itemtitle;
            }

            $itemtitle = get_string('attempt_parttwo', constants::M_COMPONENT);
            if($attempt->completedsteps >= constants::STEP_USERSELECTIONS) {
                $editurl = new \moodle_url($actionurl,
                        array('id' => $cm->id, 'attemptid' => $attempt->id, 'type' => constants::STEP_AUDIORECORDING));
                $editaudio = \html_writer::link($editurl,$itemtitle);
                $parts[] = $editaudio;
            }else{
                $parts[] = $itemtitle;
            }

            $itemtitle = get_string('attempt_partthree', constants::M_COMPONENT);
            if($attempt->completedsteps >= constants::STEP_AUDIORECORDING) {
                    $editurl = new \moodle_url($actionurl,
                            array('id' => $cm->id, 'attemptid' => $attempt->id, 'type' => constants::STEP_SELFTRANSCRIBE));
                    $edittranscript = \html_writer::link($editurl, $itemtitle);
                    $parts[] = $edittranscript;
            }else{
                $parts[] = $itemtitle;
            }

            $itemtitle = get_string('attempt_partfour', constants::M_COMPONENT);
            if($attempt->completedsteps >= constants::STEP_SELFTRANSCRIBE) {
                    $editurl = new \moodle_url($actionurl,
                            array('id' => $cm->id, 'attemptid' => $attempt->id, 'type' => constants::STEP_SELFREVIEW));
                    $comparetranscripts = \html_writer::link($editurl, $itemtitle);
                    $parts[] = $comparetranscripts;
            }else{
                $parts[] = $itemtitle;
            }


            $editcell = new \html_table_cell(implode('<br />', $parts));

		    //attempt delete
			$deleteurl = new \moodle_url($actionurl, array('id'=>$cm->id,'attemptid'=>$attempt->id,'action'=>'confirmdelete'));
			$deletelink = \html_writer::link($deleteurl, get_string('deleteattempt', constants::M_COMPONENT));
			$deletecell = new \html_table_cell($deletelink);

			$row->cells = array(
                    $attemptdatecell, $topiccell, $usernamescell, $editcell, $deletecell
			);
			$table->data[] = $row;
		}

		return \html_writer::table($table);

	}

    function setup_datatables($tableid){
        global $USER;

        $tableprops = array();
        $columns = array();
        //for cols .. .'attemptname', 'attempttype','timemodified', 'edit','delete'
        $columns[0]=null;
        $columns[1]=null;
        $columns[2]=null;
        $columns[3]=null;
        $columns[4]=null;
        $tableprops['columns']=$columns;

        //default ordering
        $order = array();
        //$order[0] =array(3, "desc");
       //$tableprops['order']=$order;

        //here we set up any info we need to pass into javascript
        $opts =Array();
        $opts['tableid']=$tableid;
        $opts['tableprops']=$tableprops;
        $this->page->requires->js_call_amd("mod_pchat/datatables", 'init', array($opts));
        $this->page->requires->css( new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));
    }

    function fetch_recorder_amd($cm){
        global $USER;

        $widgetid = constants::M_WIDGETID;
        //any html we want to return to be sent to the page
        $ret_html = '';

        //here we set up any info we need to pass into javascript
        $recopts =Array();
        $recopts['recorderid']=$widgetid . '_recorderdiv';


        //this inits the M.mod_pchat thingy, after the page has loaded.
        //we put the opts in html on the page because moodle/AMD doesn't like lots of opts in js
        $jsonstring = json_encode($recopts);
        $opts_html = \html_writer::tag('input', '', array('id' => 'amdopts_' . $widgetid, 'type' => 'hidden', 'value' => $jsonstring));

        //the recorder div
        $ret_html = $ret_html . $opts_html;

        $opts=array('cmid'=>$cm->id,'widgetid'=>$widgetid);
        $this->page->requires->js_call_amd("mod_pchat/recordercontroller", 'init', array($opts));

        //these need to be returned and echo'ed to the page
        return $ret_html;
    }

}