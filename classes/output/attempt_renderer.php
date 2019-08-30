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
 public function add_edit_page_links($pchat) {
		global $CFG;
        $attemptid = 0;

        $output = $this->output->heading(get_string("whatdonow", "pchat"), 3);
        $parts = array();

     $addurl = new \moodle_url('/mod/pchat/attempt/manageattempts.php',
             array('id'=>$this->page->cm->id, 'attemptid'=>$attemptid, 'type'=>constants::TYPE_AUDIORECORDING));
     $parts[] = \html_writer::link($addurl, '<i class="fa fa-microphone"></i> ' . get_string('addaudiorecording', constants::M_COMPONENT),
             array('class'=>'btn ' . constants::M_COMPONENT .'_menubutton ' . constants::M_COMPONENT .'_audiombutton'));

     $addurl = new \moodle_url('/mod/pchat/attempt/manageattempts.php',
        array('id'=>$this->page->cm->id, 'attemptid'=>$attemptid, 'type'=>constants::TYPE_USERSELECTIONS));
     $parts[] = \html_writer::link($addurl, get_string('adduserselections', constants::M_COMPONENT),
        array('class'=>'btn ' . constants::M_COMPONENT .'_menubutton ' ));

     $addurl = new \moodle_url('/mod/pchat/attempt/manageattempts.php',
             array('id'=>$this->page->cm->id, 'attemptid'=>$attemptid, 'type'=>constants::TYPE_SELFTRANSCRIBE));
     $parts[] = \html_writer::link($addurl, get_string('addselftranscribe', constants::M_COMPONENT),
             array('class'=>'btn ' . constants::M_COMPONENT .'_menubutton ' ));

     $addurl = new \moodle_url('/mod/pchat/attempt/manageattempts.php',
             array('id'=>$this->page->cm->id, 'attemptid'=>$attemptid, 'type'=>constants::TYPE_COMPARETRANSCRIPTS));
     $parts[] = \html_writer::link($addurl, get_string('addcomparetranscripts', constants::M_COMPONENT),
             array('class'=>'btn ' . constants::M_COMPONENT .'_menubutton ' ));



    $buttonsdiv = \html_writer::div(implode('', $parts),constants::M_COMPONENT .'_mbuttons');
     return $this->output->box($output . $buttonsdiv, 'generalbox firstpageoptions');
    }
	
	/**
	 * Return the html table of attempts
	 * @param array homework objects
	 * @param integer $courseid
	 * @return string html of table
	 */
	function show_attempts_list($attempts,$tableid,$cm){
	
		if(!$attempts){
			return $this->output->heading(get_string('noattempts',constants::M_COMPONENT), 3, 'main');
		}
	
		$table = new \html_table();
		$table->id = $tableid;


		$table->head = array(
			get_string('username', constants::M_COMPONENT),
            get_string('timemodified', constants::M_COMPONENT),
			get_string('actions', constants::M_COMPONENT),
            ''
		);
		$table->headspan = array(1,1,1,1);
		$table->colclasses = array(
			'username','timemodified', 'edit','delete'
		);

		//sort by start date
		//core_collator::asort_objects_by_property($attempts,'timecreated',core_collator::SORT_NUMERIC);
		//core_collator::asort_objects_by_property($attempts,'name',core_collator::SORT_STRING);

		//loop through the attempts and add to table
        $currentattempt=0;
		foreach ($attempts as $attempt) {
            $currentattempt++;
            $row = new \html_table_row();

            //user name
            $attemptnamecell = new \html_table_cell($attempt->userid);


            //modify date
            $datecell_content = date("Y-m-d H:i:s",$attempt->timemodified);
            $attemptdatecell = new \html_table_cell($datecell_content);

            //attempt edit
            $actionurl = '/mod/pchat/attempt/manageattempts.php';

            //attempt part (stages) links
            $parts = array();

            $editurl = new \moodle_url($actionurl, array('id' => $cm->id, 'attemptid' => $attempt->id,'type'=>constants::TYPE_USERSELECTIONS));
            $edituserselections = \html_writer::link($editurl, get_string('editattempt_partone', constants::M_COMPONENT));
            $parts[] = $edituserselections;

            $editurl = new \moodle_url($actionurl, array('id' => $cm->id, 'attemptid' => $attempt->id,'type'=>constants::TYPE_AUDIORECORDING));
            $editaudio = \html_writer::link($editurl, get_string('editattempt_parttwo', constants::M_COMPONENT));
            $parts[] = $editaudio;

            $editurl = new \moodle_url($actionurl, array('id' => $cm->id, 'attemptid' => $attempt->id,'type'=>constants::TYPE_SELFTRANSCRIBE));
            $edittranscript = \html_writer::link($editurl, get_string('editattempt_partthree', constants::M_COMPONENT));
            $parts[] = $edittranscript;

            $editurl = new \moodle_url($actionurl, array('id' => $cm->id, 'attemptid' => $attempt->id,'type'=>constants::TYPE_COMPARETRANSCRIPTS));
            $comparetranscripts = \html_writer::link($editurl, get_string('editattempt_partfour', constants::M_COMPONENT));
            $parts[] = $comparetranscripts;

            $editcell = new \html_table_cell(implode(' ', $parts));

		    //attempt delete
			$deleteurl = new \moodle_url($actionurl, array('id'=>$cm->id,'attemptid'=>$attempt->id,'action'=>'confirmdelete'));
			$deletelink = \html_writer::link($deleteurl, get_string('deleteattempt', constants::M_COMPONENT));
			$deletecell = new \html_table_cell($deletelink);

			$row->cells = array(
				$attemptnamecell, $attemptdatecell, $editcell, $deletecell
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
        $tableprops['columns']=$columns;

        //default ordering
        $order = array();
        $order[0] =array(3, "desc");
        $tableprops['order']=$order;

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