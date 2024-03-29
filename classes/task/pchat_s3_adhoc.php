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
 * @package    mod_pchat
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

         $attempt = $DB->get_record(constants::M_ATTEMPTSTABLE, array('id'=>$cd->attemptid));
         if($attempt){

             if(!$attempt->filename){
                 $this->do_retry('Audio file appears to not be ready yet',$trace,$cd);
                 return;
             }
             if($attempt->transcript){
                 //woa!! Its already been got. This can happen if user goes to selfreview page which will try and do the
                 //retrieve if transcripts are not back. It can also happen if streaming transcription is going
                 $trace->output("Transcript has already been fetched. Nothing to do");
                 return;
             }

             $attempt_with_transcripts = utils::retrieve_transcripts_from_s3($attempt);
             if($attempt_with_transcripts){
                 $trace->output("Transcripts are fetched for " . $cd->attemptid . " ...all ok");
                 //process transcripts (find matches etc)
                 $selftranscript='';
                 if(!empty($attempt_with_transcripts->selftranscript)){
                     $selftranscript=utils::extract_simple_transcript($attempt_with_transcripts->selftranscript);
                 }
                 if(!empty($selftranscript)) {
                     try {
                         $aitranscript = new \mod_pchat\aitranscript($attempt_with_transcripts->id,
                                 $cd->modulecontextid, $selftranscript,
                                $attempt_with_transcripts->transcript,
                                 $attempt_with_transcripts->jsontranscript);
                     }catch(\Exception $e){
                         $this->do_forever_fail('transcripts fetched but processing failed: ' .
                                 $e->getMessage() .": attemptid:"  . $cd->attemptid,$trace);
                     }
                 }

             }else{
                 $this->do_retry('Transcripts are not ready yet',$trace,$cd);
             }

         }else{
             $this->do_forever_fail('This attempt could not be found: ' . $cd->attemptid,$trace);
             return;
         }
	}

    protected function do_retry($reason, $trace, $customdata) {

        if($customdata->taskcreationtime + (HOURSECS * 24) < time()){
            //after 24 hours we give up
            $trace->output($reason . ": Its been more than 24 hours. Giving up on this transcript.");
            return;

        }elseif ($customdata->taskcreationtime + (MINSECS * 15) < time()) {
            //15 minute delay
            $delay = (MINSECS * 15);
        }else{
            //30 second delay
            $delay = 30;
        }
        $trace->output($reason . ": will try again next cron after $delay seconds");
        $s3_task = new \mod_pchat\task\pchat_s3_adhoc();
        $s3_task->set_component('mod_pchat');
        $s3_task->set_custom_data($customdata);
        //if we do not set the next run time it can extend the current cron job indef with a recurring task
        $s3_task->set_next_run_time(time()+$delay);
        // queue it
        \core\task\manager::queue_adhoc_task($s3_task);
    }

    protected function do_forever_fail($reason,$trace){
        $trace->output($reason . ": will not retry ");
	}
		
}

