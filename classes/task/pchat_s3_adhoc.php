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
 * A mod_pchat adhoc task
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pchat\task;

defined('MOODLE_INTERNAL') || die();

use \mod_pchat\constants;
use \mod_pchat\utils;


/**
 * A mod_pchat adhoc task to fetch back transcriptions from Amazon S3
 *
 * @package    mod_pchat
 * @since      Moodle 3.7
 * @copyright  2019 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pchat_s3_adhoc extends \core\task\adhoc_task {
                                                                     
   	 /**
     *  Run the tasks
     */
	 public function execute(){
	     global $DB;
		$trace = new \text_progress_trace();

		//CD should contain activityid / attemptid and modulecontextid
		$cd =  $this->get_custom_data();
		//$trace->output($cd->somedata)

         $activity = $DB->get_record(constants::M_TABLE,array('id'=>$cd->activityid));
         if(!\mod_pchat\utils::can_transcribe($activity)){
             $this->do_forever_fail('This activity does not support transcription',$trace);
             return;
         }

         $attempt = $DB->get_record(constants::M_QTABLE, array('id'=>$cd->attemptid));
         if($attempt){

             if(!$attempt->filename){
                 $this->do_retry_soon('Audio file appears to not be ready yet',$trace,$cd);
                 return;
             }

             $jsontranscripturl = $attempt->filename . '.json';
             $vtttranscripturl = $attempt->filename . '.vtt';
             $transcripturl = $attempt->filename . '.txt';
             $postdata = array();
             $jsontranscript = utils::curl_fetch($jsontranscripturl,$postdata);
             $vtttranscript = utils::curl_fetch($vtttranscripturl,$postdata);
             $transcript = utils::curl_fetch($transcripturl,$postdata);


             //if we got here, we have transcripts and we do not need to come back
             if($jsontranscript && $vtttranscript && $transcript) {
                 $updateattempt = new \stdClass();
                 $updateattempt->id=$attempt->id;
                 $updateattempt->jsontranscript = $jsontranscript;
                 $updateattempt->vtttranscript = $vtttranscript;
                 $updateattempt->transcript = $transcript;
                 $success = $DB->update_record(constants::M_QTABLE, $updateattempt);
                 if($success) {
                     $trace->output("Transcripts are fetched for " . $cd->attemptid . " ...all ok");
                 }else{
                     $this->do_retry_soon('Transcripts fetched but failed to save. Will retry.',$trace,$cd);
                 }
                 return;
             }else{
                 $this->do_retry_soon('Transcripts are not ready yet',$trace,$cd);
                 return;
             }



         }else{
             $this->do_forever_fail('This attempt could not be found: ' . $cd->attemptid,$trace);
             return;
         }
	}

    protected function do_retry_soon($reason,$trace,$customdata){
        if($customdata->taskcreationtime + (MINSECS * 15) < time()){
            $this->do_retry_delayed($reason,$trace);
        }else {
            $trace->output($reason . ": will try again next cron ");
            $s3_task = new \mod_pchat\task\pchat_s3_adhoc();
            $s3_task->set_component('mod_pchat');
            $s3_task->set_custom_data($customdata);
            // queue it
            \core\task\manager::queue_adhoc_task($s3_task);
        }
    }

    protected function do_retry_delayed($reason,$trace){
        $trace->output($reason . ": will retry after a delay ");
        throw new \file_exception('retrievefileproblem', 'could not fetch transcripts.');
    }

    protected function do_forever_fail($reason,$trace){
        $trace->output($reason . ": will not retry ");
	}
		
}

