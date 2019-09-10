<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/16
 * Time: 19:31
 */

namespace mod_pchat;

defined('MOODLE_INTERNAL') || die();

class constants
{
//component name, db tables, things that define app
const M_COMPONENT='mod_pchat';
const M_TABLE='pchat';
const M_ATTEMPTSTABLE='pchat_attempts';
const M_STATSTABLE='pchat_attemptstats';
const M_TOPIC_TABLE='pchat_topics';
const M_SELECTEDTOPIC_TABLE='pchat_selectedtopics';
const M_MODNAME='pchat';
const M_URL='/mod/pchat';
const M_CLASS='mod_pchat';
const M_CLASS_ITEMTABLE='mod_pchat_attempttable';
const M_CLASS_TOPICSCONTAINER ='topicscontainer';
const M_CLASS_TOPICSCHECKBOX = 'topicscheckbox';


const M_RECORDERID='therecorder';
const M_WIDGETID='therecorder_opts_9999';

//grading options
const M_GRADEHIGHEST= 0;
const M_GRADELOWEST= 1;
const M_GRADELATEST= 2; // we only use this one currently
const M_GRADEAVERAGE= 3;
const M_GRADENONE= 4;

//Constants for Attempt Steps
const STEP_NONE=0;
const STEP_USERSELECTIONS= 1;
const STEP_AUDIORECORDING= 2;
const STEP_SELFTRANSCRIBE= 3;
const STEP_SELFREVIEW= 4;

const T_AUDIORECORDING= "audiorecording";
const T_USERSELECTIONS= "userselections";
const T_SELFTRANSCRIBE= "selftranscribe";
const T_SELFREVIEW= "selfreview";

const TEXTDESCR = 'itemtext';
const TEXTDESCR_FILEAREA = 'itemarea';

const M_FILEAREA_SUBMISSIONS='submission';

const AUDIOPROMPT_FILEAREA = 'audioitem';
const AUDIOANSWER_FILEAREA = 'audioanswer';
const PICTUREPROMPT_FILEAREA = 'pictureitem';
const TEXTPROMPT_FILEAREA = 'textitem';
const TEXTANSWER_FILEAREA ='answerarea';
const PASSAGEPICTURE_FILEAREA = 'passagepicture';

//CSS DEFS
CONST C_AUDIOPLAYER = 'vs_audioplayer';
CONST C_CURRENTFORMAT= 'vs_currentformat';
CONST C_LANGSELECT = 'vs_langselect';
CONST C_VOICESELECT = 'vs_voiceselect';
CONST C_PLAYBUTTON = 'vs_playbutton';
CONST C_FILENAMETEXT = 'vs_filenametext';
CONST C_TARGETWORDSDISPLAY = 'mod_pchat_targetwordsdisplay';

const RECORDINGURLFIELD='filename';
const RECORDERORPLAYERFIELD='recorderorplayer';

const TRANSCRIBER_NONE = 0;
const TRANSCRIBER_AMAZONTRANSCRIBE = 1;
const TRANSCRIBER_GOOGLECLOUDSPEECH = 2;
const TRANSCRIBER_GOOGLECHROME = 3;

const M_TOPICLEVEL_CUSTOM =1;
const M_TOPICLEVEL_COURSE =0;

const DEF_CONVLENGTH=7;
const M_C_TRANSCRIPTDISPLAY='mod_pchat_transcriptdisplay';
const M_C_TRANSCRIPTEDITOR='mod_pchat_transcripteditor';
const M_C_CONVERSATION='mod_pchat_conversation';

}