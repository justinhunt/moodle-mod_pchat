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
 * The main pchat configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_pchat
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

use \mod_pchat\constants;
use \mod_pchat\utils;

/**
 * Module instance settings form
 */
class mod_pchat_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
    	global $CFG, $COURSE;

        $mform = $this->_form;
        $config = get_config(constants::M_COMPONENT);

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('pchatname', constants::M_COMPONENT), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'pchatname', constants::M_COMPONENT);

         // Adding the standard "intro" and "introformat" fields
        if($CFG->version < 2015051100){
        	$this->add_intro_editor();
        }else{
        	$this->standard_intro_elements();
		}

        //Enable multiple attempts (or not)
        $mform->addElement('advcheckbox', 'multiattempts', get_string('multiattempts', constants::M_COMPONENT), get_string('multiattempts_details', constants::M_COMPONENT));
        $mform->setDefault('multipleattempts',true);

        //allow post attempt edit
        $mform->addElement('advcheckbox', 'postattemptedit', get_string('postattemptedit', constants::M_COMPONENT), get_string('postattemptedit_details', constants::M_COMPONENT));
        $mform->setDefault('postattemptedit',false);

        //time limits
        $options = utils::get_conversationlength_options();
        //the size attribute doesn't work because the attributes are applied on the div container holding the select
        $mform->addElement('select','convlength',get_string('convlength', constants::M_COMPONENT), $options,array("size"=>"5"));
        $mform->setDefault('convlength',constants::DEF_CONVLENGTH);

        //Allow student override time limit
        $mform->addElement('advcheckbox', 'userconvlength', get_string('userconvlength', constants::M_COMPONENT), get_string('userconvlength_details', constants::M_COMPONENT));
        $mform->setDefault('userconvlength',true);

        // Adding the revq 1 field
        $mform->addElement('textarea', 'revq1', get_string('revq', constants::M_COMPONENT, '1'),  array('rows'=>'3', 'cols'=>'80'));
        $mform->setType('revq1', PARAM_TEXT);
        $mform->addElement('textarea', 'revq2', get_string('revq', constants::M_COMPONENT, '2'),  array('rows'=>'3', 'cols'=>'80'));
        $mform->setType('revq2', PARAM_TEXT);
        $mform->addElement('textarea', 'revq3', get_string('revq', constants::M_COMPONENT, '3'),  array('rows'=>'3', 'cols'=>'80'));
        $mform->setType('revq3', PARAM_TEXT);

        //add tips field
        $edoptions = pchat_editor_no_files_options($this->context);
        $opts = array('rows'=>'5', 'columns'=>'80');
        $mform->addElement('editor','tips_editor',get_string('tips', constants::M_COMPONENT),$opts,$edoptions);
        $mform->setDefault('tips_editor',array('text'=>$config->speakingtips, 'format'=>FORMAT_HTML));
        $mform->setType('tips_editor',PARAM_RAW);

        //Enable AI
        $mform->addElement('advcheckbox', 'enableai', get_string('enableai', constants::M_COMPONENT), get_string('enableai_details', constants::M_COMPONENT));
        $mform->setDefault('enableai',$config->enableai);

        //tts options
        $langoptions = \mod_pchat\utils::get_lang_options();
        $mform->addElement('select', 'ttslanguage', get_string('ttslanguage', constants::M_COMPONENT), $langoptions);
        $mform->setDefault('ttslanguage',$config->ttslanguage);


        //transcriber options
        $name = 'transcriber';
        $label = get_string($name, constants::M_COMPONENT);
        $options = \mod_pchat\utils::fetch_options_transcribers();
        $mform->addElement('select', $name, $label, $options);
        $mform->setDefault($name,constants::TRANSCRIBER_AMAZONTRANSCRIBE);// $config->{$name});

        //region
        $regionoptions = \mod_pchat\utils::get_region_options();
        $mform->addElement('select', 'region', get_string('region', constants::M_COMPONENT), $regionoptions);
        $mform->setDefault('region',$config->awsregion);

        //expiredays
        $expiredaysoptions = \mod_pchat\utils::get_expiredays_options();
        $mform->addElement('select', 'expiredays', get_string('expiredays', constants::M_COMPONENT), $expiredaysoptions);
        $mform->setDefault('expiredays',$config->expiredays);

        // Grade.
        $this->standard_grading_coursemodule_elements();

        //grade options
        //for now we hard code this to latest attempt
        $mform->addElement('hidden', 'gradeoptions',constants::M_GRADELATEST);
        $mform->setType('gradeoptions', PARAM_INT);

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }


	public function data_preprocessing(&$form_data) {
		//$edfileoptions = pchat_editor_with_files_options($this->context);
		$ednofileoptions = pchat_editor_no_files_options($this->context);
		$editors  = pchat_get_editornames();
		 if ($this->current->instance) {
			$itemid = 0;
			foreach($editors as $editor){
				$form_data = file_prepare_standard_editor((object)$form_data,$editor, $ednofileoptions, $this->context,constants::M_COMPONENT,$editor, $itemid);
			}
		}
	}


}
