<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:32
 */

namespace mod_pchat\attempt;

use \mod_pchat\constants;

class selftranscribeform extends baseform
{

    public $type = constants::STEP_SELFTRANSCRIBE;
    public $typestring = constants::T_SELFTRANSCRIBE;
    public function custom_definition() {
        //we need the filename to make a player for the transcribing
        $this->filename = $this->_customdata['filename'];

        //we set the title and instructions
        $this->add_title(get_string('attempt_partthree', constants::M_COMPONENT));
        $this->add_instructions(get_string('attempt_partthree_instructions', constants::M_COMPONENT));

        // $this->add_audio_player('audioplayer','audioplayer');

        $this->add_transcript_editor('selftranscript',get_string('transcripteditor', constants::M_COMPONENT));
    }
    public function custom_definition_after_data() {


    }


}