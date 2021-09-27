<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:32
 */

namespace mod_pchat\attempt;

use \mod_pchat\constants;

class audiorecordingform extends baseform
{

    public $type = constants::STEP_AUDIORECORDING;
    public $typestring = constants::T_AUDIORECORDING;
    public function custom_definition() {
        //we need the token to set up the cloud poodll recorder
        $this->token = $this->_customdata['token'];
        $this->attempt = $this->_customdata['attempt'];
        $targetwords = $this->_customdata['targetwords'];
        $this->topic = $this->_customdata['topic'];

        //we set the title and instructions
        $this->add_title(get_string('attempt_parttwo', constants::M_COMPONENT));
        $this->add_instructions(get_string('attempt_parttwo_instructions', constants::M_COMPONENT));

        //add tips
        $this->add_tips_field();

        //show our target words
        $this->add_targetwords_display($targetwords) ;

        //add topic media content
        $this->add_topicmediacontent();

        //we add the recording hidden and visible fields
        $this->add_recordingurl_field();

    }
    public function custom_definition_after_data() {
        $this->add_audio_recording(get_string('audiorecording',constants::M_COMPONENT));

    }
    public function get_savebutton_text(){
        return get_string('saveandnext', constants::M_COMPONENT);
    }
}