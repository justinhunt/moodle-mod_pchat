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
 * Defines all the backup steps that will be used by {@link backup_pchat_activity_task}
 *
 * @package     mod_pchat
 * @category    backup
 * @copyright   2015 Justin Hunt (poodllsupport@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_pchat\constants;

/**
 * Defines the complete webquest structure for backup, with file and id annotations
 *
 */
class backup_pchat_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the pchat element inside the webquest.xml file
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing pchat instance
        $oneactivity = new backup_nested_element(constants::M_MODNAME, array('id'), array(
            'course','name','intro','introformat','grade','gradeoptions','mingrade',
            'ttslanguage','enableai','expiredays','region','transcriber','timecreated','timemodified'
			));
		

        // attempt
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'),array(
            constants::M_MODNAME, 'name','userid', 'type','visible','filename', 'transcript','fulltranscript',
            'customtext1', 'customtext1format','customtext2', 'customtext2format','customtext3',
            'customtext3format','customtext4', 'customtext4format','currentint1','currentint2','currentint3','currentint4','currentint5',
            'timecreated','timemodified','createdby','modifiedby'));


		// Build the tree.
         //questions
        $oneactivity->add_child($attempts);
        $items->add_child($attempt);


        // Define sources.
        $oneactivity->set_source_table(constants::M_TABLE, array('id' => backup::VAR_ACTIVITYID));


        //sources if including user info
        if ($userinfo) {
            $item->set_source_table(constants::M_ATTEMPTSTABLE,
                array(constants::M_MODNAME => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $item->annotate_ids('user', 'userid');


        // Define file annotations.
        // intro file area has 0 itemid.
        $oneactivity->annotate_files(constants::M_COMPONENT, 'intro', null);
		
		//file annotation if including user info
        if ($userinfo) {
            $item->annotate_files(constants::M_COMPONENT, constants::M_FILEAREA_SUBMISSIONS, 'id');
        }
		
        // Return the root element, wrapped into standard activity structure.
        return $this->prepare_activity_structure($oneactivity);
		

    }
}
