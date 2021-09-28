<?php

namespace mod_pchat\report;

global $CFG;

use \mod_pchat\constants;
use \mod_pchat\utils;

require_once("$CFG->libdir/formslib.php");
require_once($CFG->libdir . '/pear/HTML/QuickForm/input.php');


class classprogress_form extends \moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $cm = $this->_customdata['cm'];
        $moduleinstance = $this->_customdata['moduleinstance'];

        $mform->addElement('hidden', 'format', 'linechart');
        $mform->setType('format',PARAM_TEXT);
        $mform->addElement('hidden', 'report', 'classprogress');
        $mform->setType('report',PARAM_TEXT);
        $mform->addElement('hidden', 'n', $moduleinstance->id);
        $mform->setType('n',PARAM_INT);
        $mform->addElement('hidden', 'id', $cm->id);
        $mform->setType('id',PARAM_INT);

       // $options = utils::get_grade_element_options();
        $courseid=$cm->course;
        $options = ['multiple'=>true,"size"=>"5"];
        $selectables = utils::fetch_course_instance_menu($courseid);
        $mform->addElement('autocomplete','selected',get_string('selectactivities', constants::M_COMPONENT), $selectables,$options);
        $mform->setDefault('selected',0);
        $this->add_action_buttons(false,get_string('showreport', constants::M_COMPONENT));
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
