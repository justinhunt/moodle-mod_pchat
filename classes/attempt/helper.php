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

        //remove records
        if (!$DB->delete_records(constants::M_QTABLE, array('id' => $attemptid))) {
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
