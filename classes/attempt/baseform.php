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
 * @copyright  2019 Justin Hunt
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

        $this->moduleinstance = $this->_customdata['moduleinstance'];
        $this->cm = $this->_customdata['moduleinstance'];

	
       // $mform->addElement('header', 'typeheading', get_string('createattempt', constants::M_COMPONENT, get_string($this->typestring, constants::M_COMPONENT)));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'attemptid');
        $mform->setType('attemptid', PARAM_INT);

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

    protected final function add_fontawesomecombo_field($name, $label) {
        global $PAGE;

        $radios = array();
        $this->_form->addElement('hidden', $name);
        $this->_form->setType($name,PARAM_TEXT);

        $radiotemplate = '<label data-id="@@value@@" data-name="@@name@@" class="btn btn-secondary fonttoggleitem"><input type="radio" name="@@name@@-dummyradio">'
                . '<span>@@topicname@@<span><br/><i class="fa @@fontcode@@ fa-2x"></i></label>';

        foreach ($this->topics as $topic){
            $oneradio = str_replace('@@name@@',$name,$radiotemplate);
            $oneradio = str_replace('@@fontcode@@',$topic->fonticon,$oneradio);
            $oneradio = str_replace('@@topicname@@',$topic->name,$oneradio);
            $oneradio = str_replace('@@value@@',$topic->id,$oneradio);
            $radios[] = $oneradio;
        }

        $staticcontent = \html_writer::div(implode(' ',$radios),'btn-group btn-group-toggle fonttogglegroup',array('data-toggle'=>'buttons'));
        $this->_form->addElement('static', 'combo_' . $name, $label, $staticcontent);

        $opts =Array();
        $opts['container']='fonttogglegroup';
        $opts['item']='fonttoggleitem';
        $opts['updatecontrol']=$name;
        $opts['mode']='radio';
        $opts['maxchecks']=1;
        $PAGE->requires->js_call_amd("mod_pchat/toggleselected", 'init', array($opts));
    }

    protected final function add_conversationlength_field() {
        $options = utils::get_conversationlength_options();
        //the size attribute doesn't work because the attributes are applied on the div container holding the select
        $this->_form->addElement('select','convlength',get_string('convlength', constants::M_COMPONENT), $options,array("size"=>"5"));
        $this->_form->setDefault('convlength',constants::DEF_CONVLENGTH);
    }

    protected final function add_wordsandtips_fields() {
        global $PAGE;
        $this->_form->addElement('textarea','targetwords',get_string('targetwords', constants::M_COMPONENT),'blha blah blah',array("class"=>'mod_pchat_targetwords mod_pchat_bordered mod_pchat_readonly','disabled'=>true));

        $this->_form->addElement('text','mywords',get_string('mywords', constants::M_COMPONENT),array());
        $this->_form->setType('mywords',PARAM_TEXT);

        $this->_form->addElement('static','tips',get_string('tips', constants::M_COMPONENT),'tip tip tip',array("class"=>'mod_pchat_bordered mod_pchat_readonly'));

        $opts =Array();
        $opts['topics']=$this->topics;
        $opts['triggercontrol']='topicid';
        $opts['updatecontrol']='targetwords';
        $PAGE->requires->js_call_amd("mod_pchat/updatetargetwords", 'init', array($opts));
    }

    protected final function set_targetwords() {
        $topicidelement =& $this->_form->getElement('topicid');
        $targetwordselement =& $this->_form->getElement('targetwords');
        if ($topicidelement && $targetwordselement) {
            $topicid = $this->_form->getElementValue('topicid');
            if($topicid){
                foreach($this->topics as $topic){
                    if($topicid==$topic->id){
                        $targetwordselement->setValue($topic->targetwords);
                        break;
                    }
                }
            }
        }
    }

    protected final function add_usercombo_field($name, $label) {
        global $CFG, $PAGE;
        require_once("$CFG->libdir/outputcomponents.php");

        $checks = array();
        $this->_form->addElement('hidden', $name);
        $this->_form->setType($name,PARAM_TEXT);

        $checktemplate = '<label data-id="@@value@@" data-name="@@name@@" class="btn btn-secondary usertoggleitem"><input type="checkbox" name="@@name@@-dummycheckbox">'
                . '<span>@@username@@<span><br/><img src="@@userpic@@"></label>';


        foreach ($this->users as $user){
            $user_picture=new \user_picture($user);
            $picurl = $user_picture->get_url($PAGE);

            $onecheck = str_replace('@@name@@',$name,$checktemplate);
            $onecheck = str_replace('@@userpic@@',$picurl,$onecheck);
            $onecheck = str_replace('@@username@@',fullname($user),$onecheck);
            $onecheck = str_replace('@@value@@',$user->id,$onecheck);
            $checks[] = $onecheck;
        }
        $staticcontent = \html_writer::div(implode(' ',$checks),'btn-group btn-group-toggle usertogglegroup',array('data-toggle'=>'buttons'));
        $this->_form->addElement('static', 'combo_' . $name, $label, $staticcontent);

        $opts =Array();
        $opts['container']='usertogglegroup';
        $opts['item']='usertoggleitem';
        $opts['updatecontrol']=$name;
        $opts['mode']='checkbox';
        $opts['maxchecks']=4;
        $PAGE->requires->js_call_amd("mod_pchat/toggleselected", 'init', array($opts));
    }

    protected final function add_transcript_editor($name, $label){
        global $PAGE;

        $this->_form->addElement('hidden', $name);
        $this->_form->setType($name,PARAM_TEXT);

        $display=\html_writer::div('',constants::M_C_TRANSCRIPTDISPLAY);
        $editor=\html_writer::div('',constants::M_C_TRANSCRIPTEDITOR);

        $staticcontent = \html_writer::div($display . $editor,constants::M_C_CONVERSATION,array());
        $this->_form->addElement('static', 'control_' . $name, $label, $staticcontent);

        $opts =Array();
        $opts['displayclass']=constants::M_C_TRANSCRIPTDISPLAY;
        $opts['editorclass']=constants::M_C_TRANSCRIPTEDITOR;
        $opts['updatecontrol']=$name;

        $PAGE->requires->js_call_amd("mod_pchat/transcripteditor", 'init', array($opts));

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
            $recorder_html = $this->fetch_recorder($this->moduleinstance,'audio','fresh',$this->token, $width,$height);
            $recordingorplayerfield->setValue($recorder_html);
        }

    }

    /**
     * The html part of the recorder (js is in the fetch_activity_amd)
     * PARAM $media one of audio, video
     * PARAM $recordertype something like "upload" or "fresh" or "bmr"
     */
    public function fetch_recorder($moduleinstance, $media, $recordertype, $token,$width,$height){
        global $CFG, $PAGE;

        $recorderdiv_domid = constants::M_WIDGETID;
        //we never need more than a recorder on the page of this mod
        //but this is how to do it, and we need to update JS to use this too
        //\html_writer::random_id(constants::M_WIDGETID);


        //recorder
        //=======================================
        // $hints = new \stdClass();
        // $hints->allowearlyexit = $moduleinstance->allowearlyexit;
        // $string_hints = base64_encode (json_encode($hints));
        $can_transcribe = utils::can_transcribe($moduleinstance);
        $transcribe = $can_transcribe  ? "1" : "0";
        $recorderdiv= \html_writer::div('', constants::M_CLASS  . '_center',
                array('id'=>$recorderdiv_domid,
                        'data-id'=>'therecorder',
                        'data-parent'=>$CFG->wwwroot,
                        'data-localloading'=>'auto',
                        'data-localloader'=> constants::M_URL . '/poodllloader.html',
                        'data-media'=>$media,
                        'data-appid'=>constants::M_COMPONENT,
                        'data-type'=>$recordertype,
                        'data-width'=>$width,
                        'data-height'=>$height,
                        'data-updatecontrol'=>constants::M_WIDGETID . constants::RECORDINGURLFIELD,
                        'data-timelimit'=> 0,//$moduleinstance->timelimit,
                        'data-transcode'=>"1",
                        'data-transcribe'=>$transcribe,
                        'data-language'=>$moduleinstance->ttslanguage,
                        'data-expiredays'=>$moduleinstance->expiredays,
                        'data-region'=>$moduleinstance->region,
                        'data-fallback'=>'warning',
                        'data-token'=>$token
                    //'data-hints'=>$string_hints,
                )
        );
        $containerdiv= \html_writer::div($recorderdiv,constants::M_CLASS . '_recordercontainer'  . " " . constants::M_CLASS  . '_center',
                array('id'=>$recorderdiv_domid . '_recordercontainer'));
        //=======================================


        $recordingdiv = \html_writer::div($containerdiv ,constants::M_CLASS . '_recordingcontainer');

        //prepare output
        $ret_html = "";
        $ret_html .=$recordingdiv;

        //here we set up any info we need to pass into javascript
        //importantly we tell it the div id of the recorder
        $recopts =Array();
        $recopts['recorderid']=$recorderdiv_domid;


        //this inits the M.mod_pchat thingy, after the page has loaded.
        //we put the opts in html on the page because moodle/AMD doesn't like lots of opts in js
        $jsonstring = json_encode($recopts);
        $opts_html = \html_writer::tag('input', '', array('id' => 'amdopts_' . $recorderdiv_domid, 'type' => 'hidden', 'value' => $jsonstring));

        //the recorder div
        $ret_html .= $opts_html;

        $opts=array('cmid'=>$this->cm->id,'widgetid'=>$recorderdiv_domid);
        $PAGE->requires->js_call_amd("mod_pchat/recordercontroller", 'init', array($opts));

        //these need to be returned and echo'ed to the page
        return $ret_html;

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