<?php

namespace mod_pchat\topic;

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Forms for pchat Activity
 *
 * @package    mod_pchat
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

//why do we need to include this?
require_once($CFG->libdir . '/formslib.php');

use \mod_pchat\constants;
use \mod_pchat\utils;

/**
 * Abstract class that item type's inherit from.
 *
 * This is the abstract class that add item type forms must extend.
 *
 * @abstract
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topicform extends \moodleform {

    /**
     * An array of options used in the filemanager
     * @var array
     */
    protected $filemanageroptions = array();

    /**
     * The module instance
     * @var array
     */
    protected $moduleinstance = null;

	
    /**
     * True if this is a standard item of false if it does something special.
     * items are standard items
     * @var bool
     */
    protected $standard = true;

    /**
     * Each item type can and should override this to add any custom elements to
     * the basic form that they want
     */
    public function custom_definition() {}

    /**
     * Item types can override this to add any custom elements to
     * the basic form that they want
     */
   public function custom_definition_after_data() {}

    /**
     * Used to determine if this is a standard item or a special item
     * @return bool
     */
    public final function is_standard() {
        return (bool)$this->standard;
    }

    /**
     * Add the required basic elements to the form.
     *
     * This method adds the basic elements to the form including title and contents
     * and then calls custom_definition();
     */
    public final function definition() {
        global $CFG;

        $this->filemanageroptions = $this->_customdata['filemanageroptions'];

        $mform = $this->_form;
	
        $mform->addElement('header', 'topicheading', get_string('editingtopic', constants::M_COMPONENT, get_string('topicformtitle', constants::M_COMPONENT)));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'moduleid');
        $mform->setType('moduleid', PARAM_INT);

        //name
        $mform->addElement('text', 'name', get_string('topicname', constants::M_COMPONENT), array('size'=>70));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        //fonticon
        $mform->addElement('text', 'fonticon', get_string('topicicon', constants::M_COMPONENT));
        $mform->setType('fonticon', PARAM_TEXT);
        $mform->setDefault('fonticon', 'fa-star');
        $mform->addRule('fonticon', get_string('required'), 'required', null, 'client');
        $mform->addElement('static','fonticonexplanation','',get_string('fonticonexplanation',constants::M_COMPONENT));


        //targetwords
        $mform->addElement('textarea', 'targetwords', get_string('topictargetwords', constants::M_COMPONENT), 'wrap="virtual" rows="12" cols="50"');
        $mform->setType('targetwords', PARAM_TEXT);
        $mform->addRule('targetwords', get_string('required'), 'required', null, 'client');
        $mform->addElement('static','targetwordsexplanation','',get_string('targetwordsexplanation',constants::M_COMPONENT));

        //level
        $topiclevels = utils::fetch_topic_levels();
        $mform->addElement('select', 'topiclevel', get_string('topiclevel', constants::M_COMPONENT),$topiclevels,array());
        $mform->setType('topiclevel', PARAM_INT);
        $mform->addRule('topiclevel', get_string('required'), 'required', null, 'client');


        //display media options for speaking prompt
        $m35 = $CFG->version >= 2018051700;
        $togglearray=array();
        $togglearray[] =& $mform->createElement('advcheckbox','addmedia',get_string('addmedia',constants::M_COMPONENT),'');
        $togglearray[] =& $mform->createElement('advcheckbox','addiframe',get_string('addiframe',constants::M_COMPONENT),'');
        $mform->addGroup($togglearray, 'togglearray', get_string('mediaoptions', constants::M_COMPONENT), array(' '), false);

        //We assume they want to use some media
        $mform->setDefault('addmedia', 1);


        //Speaking topic upload
        $mform->addElement('filemanager',
            'topicmedia',
            get_string('topicmedia',constants::M_COMPONENT),
            null,
            $this->filemanageroptions
        );
        $mform->addHelpButton('topicmedia', 'topicmedia', constants::M_MODNAME);
        if($m35){
            $mform->hideIf('topicmedia', 'addmedia', 'neq', 1);
        }else {
            $mform->disabledIf('topicmedia', 'addmedia', 'neq', 1);
        }

        //Speaking topic iframe
        $mform->addElement('text', 'topiciframe', get_string('topiciframe', constants::M_COMPONENT), array('size'=>100));
        $mform->setType('topiciframe', PARAM_RAW);
        $mform->addHelpButton('topiciframe', 'topiciframe', constants::M_MODNAME);
        if($m35){
            $mform->hideIf('topiciframe','addiframe','neq', 1);
        }else {
            $mform->disabledIf( 'topiciframe','addiframe','neq', 1);
        }


        $this->custom_definition();

		//add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('savetopic', constants::M_COMPONENT));

    }

    public final function definition_after_data() {
        parent::definition_after_data();
        $this->custom_definition_after_data();
    }

    /**
     * A function that gets called upon init of this object by the calling script.
     *
     * This can be used to process an immediate action if required. Currently it
     * is only used in special cases by non-standard item types.
     *
     * @return bool
     */
    public function construction_override($itemid,  $pchat) {
        return true;
    }
}