<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:32
 */

namespace mod_pchat\attempt;

use \mod_pchat\constants;

class userselectionsform extends baseform
{
    public $type = constants::TYPE_USERSELECTIONS;
    public $typestring = constants::T_USERSELECTIONS;
    public function custom_definition() {
        $this->topics = $this->_customdata['topics'];
        $this->users = $this->_customdata['users'];

        //user combo
        $name='interlocutors';
        $label=get_string('chooseusers',constants::M_COMPONENT);
        $this->add_usercombo_field($name,$label);

        //Selected Topic
        $name='topicid';
        $label=get_string('choosetopic',constants::M_COMPONENT);
        $this->add_fontawesomecombo_field($name,$label);

        //Conversation length
        $this->add_conversationlength_field();

        //add words and tips
        $this->add_wordsandtips_fields();
    }
    public function custom_definition_after_data() {

       $this->set_targetwords();

    }

}