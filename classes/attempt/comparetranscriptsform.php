<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:32
 */

namespace mod_pchat\attempt;

use \mod_pchat\constants;

class comparetranscriptsform extends baseform
{

    public $type = constants::TYPE_COMPARETRANSCRIPTS;
    public $typestring = constants::T_COMPARETRANSCRIPTS;
    public function custom_definition() {
        $this->selftranscript = $this->_customdata['selftranscript'];
        $this->autotranscript = $this->_customdata['autotranscript'];
        $this->stats = $this->_customdata['stats'];
        $this->add_comparison_field('comparetranscripts',get_string('transcriptscompare',constants::M_COMPONENT));
        $this->add_stats_field('stats',get_string('stats',constants::M_COMPONENT));

    }
    public function custom_definition_after_data() {


    }

}