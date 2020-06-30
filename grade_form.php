<?php

require_once("$CFG->libdir/formslib.php");
require_once($CFG->libdir . '/pear/HTML/QuickForm/input.php');

class grade_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('grading', 'advancedgrading' , 'Rubric', array('gradinginstance' => $this->_customdata['gradinginstance']));
        $mform->addElement('textarea', 'feedback', 'Feedback', 'wrap="virtual" style="width:100%;" rows="10" ');


       // $this->add_action_buttons(false);

    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
