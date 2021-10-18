<?php

namespace mod_pchat\attempt;

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
 * Internal library of functions for module pchat
 *
 * All the pchat specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_pchat
 * @copyright  COPYRIGHTNOTICE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_pchat\constants;

class helper
{


    public static function delete_attempt($pchat, $attemptid, $context)
    {
        global $DB;
        $ret = false;

        //get the old attempt
        $attempt = $DB->get_record(constants::M_ATTEMPTSTABLE, array('id' => $attemptid));


        //remove records
        if (!$DB->delete_records(constants::M_ATTEMPTSTABLE, array('id' => $attemptid))) {
            print_error("Could not delete attempt");
            return $ret;
        }

        //remove files
        $fs = get_file_storage();

        $fileareas = array(constants::TEXTPROMPT_FILEAREA,
            constants::TEXTPROMPT_FILEAREA . '1',
            constants::TEXTPROMPT_FILEAREA . '2',
            constants::TEXTPROMPT_FILEAREA . '3',
            constants::TEXTPROMPT_FILEAREA . '4',
            constants::AUDIOPROMPT_FILEAREA,
            constants::AUDIOPROMPT_FILEAREA . '1',
            constants::AUDIOPROMPT_FILEAREA . '2',
            constants::AUDIOPROMPT_FILEAREA . '3',
            constants::AUDIOPROMPT_FILEAREA . '4',
            constants::PICTUREPROMPT_FILEAREA,
            constants::PICTUREPROMPT_FILEAREA . '1',
            constants::PICTUREPROMPT_FILEAREA . '2',
            constants::PICTUREPROMPT_FILEAREA . '3',
            constants::PICTUREPROMPT_FILEAREA . '4');
        foreach ($fileareas as $filearea) {
            $fs->delete_area_files($context->id, 'mod_pchat', $filearea, $attemptid);
        }

        //remove the gradebook entry ... maybe
        if($attempt) {
            $previousattempt = false;
            //if we have graded newer attempts with a higher id
            $otherattempts = $DB->get_records(constants::M_ATTEMPTSTABLE, array('userid' => $attempt->userid, 'pchat'=>$attempt->pchat));
            if($otherattempts){
                foreach($otherattempts as $otherattempt){
                    if(($otherattempt->id > $attempt->id) && $otherattempt->grade!==null){
                        //we have a newer graded attempt that should be in the gradebook we dont want to delete the grade
                        $ret = true;
                        return $ret;
                    }elseif($otherattempt->grade!==null){
                        if(!$previousattempt){
                            $previousattempt = $otherattempt;
                        }elseif($previousattempt->id < $otherattempt->id){
                            $previousattempt = $otherattempt;
                        }
                    }
                }
            }
            //newgrade as previous attempt grade or as null (to be cleared)
            if($previousattempt){
                $newgrade = $previousattempt->grade;
            }else{
                $newgrade=null;
            }
            $grade = new \stdClass();
            $grade->userid = $attempt->userid;
            $grade->rawgrade = $newgrade;
            \pchat_grade_item_update($pchat, $grade);
        }

        $ret = true;
        return $ret;
    }


    public static function fetch_editor_options($course, $modulecontext)
    {
        $maxfiles = 99;
        $maxbytes = $course->maxbytes;
        return array('trusttext' => true, 'subdirs' => true, 'maxfiles' => $maxfiles,
            'maxbytes' => $maxbytes, 'context' => $modulecontext);
    }

    public static function fetch_filemanager_options($course, $maxfiles = 1)
    {
        $maxbytes = $course->maxbytes;
        return array('subdirs' => true, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'accepted_types' => array('audio', 'image'));
    }

}
