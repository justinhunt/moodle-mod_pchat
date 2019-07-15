<?php

namespace mod_pchat\attempt;

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
abstract class baseform extends \moodleform {

    /**
     * This is used to identify this itemtype.
     * @var string
     */
    public $type;

    /**
     * The simple string that describes the item type e.g. audioitem, textitem
     * @var string
     */
    public $typestring;

	
    /**
     * An array of options used in the htmleditor
     * @var array
     */
    protected $editoroptions = array();

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
     * The cloudppoodll token
     * @var array
     */
    protected $token = 'notoken';
	
	
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
        $mform = $this->_form;
      //  $this->editoroptions = $this->_customdata['editoroptions'];
	  // $this->filemanageroptions = $this->_customdata['filemanageroptions'];
        $this->token = $this->_customdata['token'];
        $this->moduleinstance = $this->_customdata['moduleinstance'];
	
        $mform->addElement('header', 'typeheading', get_string('createaitem', constants::M_COMPONENT, get_string($this->typestring, constants::M_COMPONENT)));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        //visibility
        $mform->addElement('hidden', 'visible', true);
        $mform->setType('visible', PARAM_INT);

        if ($this->standard === true) {
            $mform->addElement('hidden', 'type');
            $mform->setType('type', PARAM_INT);


        }


        $this->custom_definition();

		//add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('saveitem', constants::M_COMPONENT));

    }

    public final function definition_after_data() {
        parent::definition_after_data();
        $this->custom_definition_after_data();
    }

    protected final function add_recordingurl_field() {
        $this->_form->addElement('hidden', constants::RECORDINGURLFIELD,null,array('id'=>constants::M_WIDGETID . constants::RECORDINGURLFIELD));
        $this->_form->setType(constants::RECORDINGURLFIELD, PARAM_TEXT);
        $this->_form->addElement('static', constants::RECORDERORPLAYERFIELD, '', '','class="blahblahblah"');
    }

    protected final function add_audio_upload($name, $label = null, $required = false)
    {
        $recordingurlfield =& $this->_form->getElement(constants::RECORDINGURLFIELD);
        $recordingorplayerfield =& $this->_form->getElement(constants::RECORDERORPLAYERFIELD);
        if ($recordingurlfield) {
            $recordingurl = $this->_form->getElementValue(constants::RECORDINGURLFIELD);
        }else{
            $recordingurl=false;
        }

        if($recordingurl && !empty($recordingurl)){
            $player_html = "<audio src='" . $recordingurl . "' controls></audio>";
            $recordingorplayerfield->setValue($player_html);

        }else{
            $width=360;
            $height=210;
            $recorder_html = $this->fetch_recorder_html($this->moduleinstance,'audio','upload',$this->token, $width,$height);
            $recordingorplayerfield->setValue($recorder_html);
        }

	}
    protected final function add_audio_recording($name, $label = null, $required = false) {
        $recordingurlfield =& $this->_form->getElement(constants::RECORDINGURLFIELD);
        $recordingorplayerfield =& $this->_form->getElement(constants::RECORDERORPLAYERFIELD);
        if ($recordingurlfield) {
            $recordingurl = $this->_form->getElementValue(constants::RECORDINGURLFIELD);
        }else{
            $recordingurl=false;
        }

        if($recordingurl && !empty($recordingurl)){
            $player_html = "<audio src='" . $recordingurl . "' controls></audio>";
            $recordingorplayerfield->setValue($player_html);

        }else{
            $width=450;
            $height=380;
            $recorder_html = $this->fetch_recorder_html($this->moduleinstance,'audio','fresh',$this->token, $width,$height);
            $recordingorplayerfield->setValue($recorder_html);
        }

    }
    protected final function add_video_recording($name, $label = null, $required = false) {
        $recordingurlfield =& $this->_form->getElement(constants::RECORDINGURLFIELD);
        $recordingorplayerfield =& $this->_form->getElement(constants::RECORDERORPLAYERFIELD);
        if ($recordingurlfield) {
            $recordingurl = $this->_form->getElementValue(constants::RECORDINGURLFIELD);
        }else{
            $recordingurl=false;
        }

        if($recordingurl && !empty($recordingurl)){
            $player_html = "<video src='" . $recordingurl . "' controls></video>";
            $recordingorplayerfield->setValue($player_html);

        }else{
            $width=410;
            $height=450;
            $recorder_html = $this->fetch_recorder_html($this->moduleinstance,'video','bmr',$this->token, $width,$height);
            $recordingorplayerfield->setValue($recorder_html);
        }

    }
    protected final function add_video_upload($name, $label = null, $required = false) {
        $recordingurlfield =& $this->_form->getElement(constants::RECORDINGURLFIELD);
        $recordingorplayerfield =& $this->_form->getElement(constants::RECORDERORPLAYERFIELD);
        if ($recordingurlfield) {
            $recordingurl = $this->_form->getElementValue(constants::RECORDINGURLFIELD);
        }else{
            $recordingurl=false;
        }

        if($recordingurl && !empty($recordingurl)){
            $player_html = "<video src='" . $recordingurl . "' controls></video>";
            $recordingorplayerfield->setValue($player_html);

        }else{
            $width=360;
            $height=210;
            $recorder_html = $this->fetch_recorder_html($this->moduleinstance,'video','upload',$this->token, $width,$height);
            $recordingorplayerfield->setValue($recorder_html);
        }

    }

    /**
     * The html part of the recorder (js is in the fetch_activity_amd)
     * PARAM $media one of audio, video
     * PARAM $recordertype something like "upload" or "fresh" or "bmr"
     */
    public function fetch_recorder_html($moduleinstance, $media, $recordertype, $token,$width,$height){
        global $CFG;

        //recorder
        //=======================================
       // $hints = new \stdClass();
       // $hints->allowearlyexit = $moduleinstance->allowearlyexit;
       // $string_hints = base64_encode (json_encode($hints));
        $can_transcribe = utils::can_transcribe($moduleinstance);
        $transcribe = "0";//$can_transcribe  ? "1" : "0";
        $recorderdiv= \html_writer::div('', constants::M_CLASS  . '_center',
            array('id'=>constants::M_WIDGETID . '_recorderdiv',
                'data-id'=>'therecorder',
                'data-parent'=>$CFG->wwwroot,
                'data-localloading'=>'auto',
                'data-localloader'=> constants::M_URL . '/poodllloader.html',
                'data-media'=>$media,
                'data-appid'=>constants::M_COMPONENT,
                'data-type'=>$recordertype,
                'data-width'=>$width,
                'data-height'=>$height,
                //'data-iframeclass'=>"letsberesponsive",
               // 'data-updatecontrol'=>constants::M_WIDGETID . constants::RECORDINGURLFIELD,
              //  'data-timelimit'=> $moduleinstance->timelimit,
                'data-transcode'=>"1",
                'data-transcribe'=>$transcribe,
                'data-language'=>$moduleinstance->ttslanguage,
                'data-expiredays'=>$moduleinstance->expiredays,
                'data-region'=>$moduleinstance->region,
                'data-fallback'=>'warning',
                //'data-hints'=>$string_hints,
                'data-token'=>$token //localhost
                //'data-token'=>"643eba92a1447ac0c6a882c85051461a" //cloudpoodll
            )
        );
        $containerdiv= \html_writer::div($recorderdiv,constants::M_CLASS . '_recordercontainer'  . " " . constants::M_CLASS  . '_center',
            array('id'=>constants::M_WIDGETID . '_recordercontainer'));
        //=======================================


        $recordingdiv = \html_writer::div($containerdiv ,constants::M_CLASS . '_recordingcontainer');

        //prepare output
        $ret = "";
        $ret .=$recordingdiv;
        //return it
        return $ret;
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