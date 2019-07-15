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
const M_QTABLE='pchat_attempts';
const M_MODNAME='pchat';
const M_URL='/mod/pchat';
const M_CLASS='mod_pchat';
const M_CLASS_ITEMTABLE='mod_pchat_attempttable';


const M_RECORDERID='therecorder';
const M_WIDGETID='therecorder_opts_9999';

//Constants for RS Questions
const NONE=0;
const TYPE_PICTUREPROMPT= 1;
const TYPE_AUDIOPROMPT = 2 ;
const TYPE_TEXTPROMPT_LONG = 4;
const TYPE_TEXTPROMPT_SHORT = 5;
const TYPE_TEXTPROMPT_AUDIO = 6;
const TYPE_INSTRUCTIONS = 7;

const TYPE_AUDIORECORDING= 1;



const T_AUDIORECORDING= "audiorecording";
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
CONST C_ACEEDITOR= 'vs_aceeditor';
CONST C_QUILLEDITOR= 'vs_quilleditor';
CONST C_CURRENTFORMAT= 'vs_currentformat';
CONST C_LANGSELECT = 'vs_langselect';
CONST C_VOICESELECT = 'vs_voiceselect';
CONST C_PLAYBUTTON = 'vs_playbutton';
CONST C_FILENAMETEXT = 'vs_filenametext';
CONST C_DOWNLOADMP3BUTTON = 'vs_downloadmp3button';
CONST C_DOWNLOADSSMLBUTTON = 'vs_downloadssmlbutton';

const RECORDINGURLFIELD='customtext1';
const RECORDERORPLAYERFIELD='recorderorplayer';

const TRANSCRIBER_NONE = 0;
const TRANSCRIBER_AMAZONTRANSCRIBE = 1;
const TRANSCRIBER_GOOGLECLOUDSPEECH = 2;
const TRANSCRIBER_GOOGLECHROME = 3;

}