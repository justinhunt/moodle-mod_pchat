<?php
global $CFG;

use \mod_pchat\constants;
use \mod_pchat\utils;

require_once("$CFG->libdir/formslib.php");
require_once($CFG->libdir . '/pear/HTML/QuickForm/input.php');


class simple_grade_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $scaleid = $this->_customdata['scaleid'];

       // $options = utils::get_grade_element_options();
        $options = make_grades_menu($scaleid);
        $mform->addElement('select','grade',get_string('grade', constants::M_COMPONENT), $options,array("size"=>"5"));
        $mform->setDefault('grade',0);

        $mform->addElement('textarea', 'feedback', 'Feedback', 'wrap="virtual" style="width:100%;" rows="10" ');
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
