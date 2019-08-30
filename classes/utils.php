<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Grade Now for pchat plugin
 *
 * @package    mod_pchat
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 namespace mod_pchat;
defined('MOODLE_INTERNAL') || die();

use \mod_pchat\constants;


/**
 * Functions used generally across this mod
 *
 * @package    mod_pchat
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils{


    //are we willing and able to transcribe submissions?
    public static function can_transcribe($instance)
    {
        //we default to true
        //but it only takes one no ....
        $ret = true;

        //The regions that can transcribe
        switch($instance->region){
            case "useast1":
            case "dublin":
            case "sydney":
            case "ottawa":
                break;
            default:
                $ret = false;
        }

        //if user disables ai, we do not transcribe
        if(!$instance->enableai){
            $ret =false;
        }

        return $ret;
    }

    public static function extract_simple_transcript($selftranscript){
        if(!$selftranscript || empty($selftranscript)){
            return '';
        }else{
            $transcriptarray=json_decode($selftranscript);
            $ret = '';
            foreach($transcriptarray as $turn){
                $ret .= $turn->part . ' ' ;
            }
            return $ret;
        }
    }

    public static function calculate_stats($usetranscript, $attempt){
        $ret= new \stdClass();
        $ret->turns=0;
        $ret->words=0;
        $ret->avturn=0;
        $ret->longestturn=0;
        $ret->targetwords=0;
        $ret->totaltargetwords=0;
        $ret->questions=0;

        if(!$usetranscript || empty($usetranscript)){
            return $ret;
        }

        $transcriptarray=json_decode($usetranscript);
        $totalturnlengths=0;
        $fulltranscript = '';

        foreach($transcriptarray as $turn){
            $part = $turn->part;
            $wordcount = str_word_count($part,0);
            if($wordcount===0){continue;}
            $fulltranscript = $turn->part . ' ' ;
            $ret->turns++;
            $ret->words+= $wordcount;
            $totalturnlengths += $wordcount;
            if($ret->longestturn < $wordcount){$ret->longestturn = $wordcount;}
            $ret->questions+= substr_count($turn->part,"?");
        }
        if($ret->turns){
            return $ret;
        }
        $ret->avturn= round($totalturnlengths  / $ret->turns);
        $topictargetwords = explode(',',$attempt->topictargetwords);
        $mywords = explode(',',$attempt->mywords);
        $targetwords = array_unique(array_merge($topictargetwords, $mywords));
        $ret->totaltargetwords = count($targetwords);


        $searchpassage = strtolower($fulltranscript);
        foreach($targetwords as $theword){
            $usecount = substr_count($searchpassage, strtolower($theword));
            if($usecount){$ret->targetwords++;}
        }

        return $ret;

    }

    //register an adhoc task to pick up transcripts
    public static function register_aws_task($activityid, $attemptid,$modulecontextid){
        $s3_task = new \mod_pchat\task\pchat_s3_adhoc();
        $s3_task->set_component('mod_pchat');

        $customdata = new \stdClass();
        $customdata->activityid = $activityid;
        $customdata->attemptid = $attemptid;
        $customdata->modulecontextid = $modulecontextid;
        $customdata->taskcreationtime = time();

        $s3_task->set_custom_data($customdata);
        // queue it
        \core\task\manager::queue_adhoc_task($s3_task);
        return true;
    }

    //we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    //this is our helper
    //we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    //this is our helper
    public static function curl_fetch($url,$postdata=false)
    {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');
        $curl = new \curl();

        $result = $curl->get($url, $postdata);
        return $result;
    }

    //This is called from the settings page and we do not want to make calls out to cloud.poodll.com on settings
    //page load, for performance and stability issues. So if the cache is empty and/or no token, we just show a
    //"refresh token" links
    public static function fetch_token_for_display($apiuser,$apisecret){
       global $CFG;

       //First check that we have an API id and secret
        //refresh token
        $refresh = \html_writer::link($CFG->wwwroot . constants::M_URL . '/refreshtoken.php',
                get_string('refreshtoken',constants::M_COMPONENT)) . '<br>';

        $message = '';
        $apiuser = trim($apiuser);
        $apisecret = trim($apisecret);
        if(empty($apiuser)){
           $message .= get_string('noapiuser',constants::M_COMPONENT) . '<br>';
       }
        if(empty($apisecret)){
            $message .= get_string('noapisecret',constants::M_COMPONENT);
        }

        if(!empty($message)){
            return $refresh . $message;
        }

        //Fetch from cache and process the results and display
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        //if we have no token object the creds were wrong ... or something
        if(!($tokenobject)){
            $message = get_string('notokenincache',constants::M_COMPONENT);
            //if we have an object but its no good, creds werer wrong ..or something
        }elseif(!property_exists($tokenobject,'token') || empty($tokenobject->token)){
            $message = get_string('credentialsinvalid',constants::M_COMPONENT);
        //if we do not have subs, then we are on a very old token or something is wrong, just get out of here.
        }elseif(!property_exists($tokenobject,'subs')){
            $message = 'No subscriptions found at all';
        }
        if(!empty($message)){
            return $refresh . $message;
        }

        //we have enough info to display a report. Lets go.
        foreach ($tokenobject->subs as $sub){
            $sub->expiredate = date('d/m/Y',$sub->expiredate);
            $message .= get_string('displaysubs',constants::M_COMPONENT, $sub) . '<br>';
        }
        //Is app authorised
        if(in_array(constants::M_COMPONENT,$tokenobject->apps)){
            $message .= get_string('appauthorised',constants::M_COMPONENT) . '<br>';
        }else{
            $message .= get_string('appnotauthorised',constants::M_COMPONENT) . '<br>';
        }

        return $refresh . $message;

    }

    //We need a Poodll token to make all this recording and transcripts happen
    public static function fetch_token($apiuser, $apisecret, $force=false)
    {

        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');
        $tokenuser = $cache->get('recentpoodlluser');
        $apiuser = trim($apiuser);
        $apisecret = trim($apisecret);
        $now = time();

        //if we got a token and its less than expiry time
        // use the cached one
        if($tokenobject && $tokenuser && $tokenuser==$apiuser && !$force){
            if($tokenobject->validuntil == 0 || $tokenobject->validuntil > $now){
               // $hoursleft= ($tokenobject->validuntil-$now) / (60*60);
                return $tokenobject->token;
            }
        }

        // Send the request & save response to $resp
        $token_url ="https://cloud.poodll.com/local/cpapi/poodlltoken.php";
        $postdata = array(
            'username' => $apiuser,
            'password' => $apisecret,
            'service'=>'cloud_poodll'
        );
        $token_response = self::curl_fetch($token_url,$postdata);
        if ($token_response) {
            $resp_object = json_decode($token_response);
            if($resp_object && property_exists($resp_object,'token')) {
                $token = $resp_object->token;
                //store the expiry timestamp and adjust it for diffs between our server times
                if($resp_object->validuntil) {
                    $validuntil = $resp_object->validuntil - ($resp_object->poodlltime - $now);
                    //we refresh one hour out, to prevent any overlap
                    $validuntil = $validuntil - (1 * HOURSECS);
                }else{
                    $validuntil = 0;
                }

                $tillrefreshhoursleft= ($validuntil-$now) / (60*60);


                //cache the token
                $tokenobject = new \stdClass();
                $tokenobject->token = $token;
                $tokenobject->validuntil = $validuntil;
                $tokenobject->subs=false;
                $tokenobject->apps=false;
                $tokenobject->sites=false;
                if(property_exists($resp_object,'subs')){
                    $tokenobject->subs = $resp_object->subs;
                }
                if(property_exists($resp_object,'apps')){
                    $tokenobject->apps = $resp_object->apps;
                }
                if(property_exists($resp_object,'sites')){
                    $tokenobject->sites = $resp_object->sites;
                }

                $cache->set('recentpoodlltoken', $tokenobject);
                $cache->set('recentpoodlluser', $apiuser);

            }else{
                $token = '';
                if($resp_object && property_exists($resp_object,'error')) {
                    //ERROR = $resp_object->error
                }
            }
        }else{
            $token='';
        }
        return $token;
    }

    public static function fetch_duration_from_transcript($fulltranscript){
        $transcript = json_decode($fulltranscript);
        $titems=$transcript->results->items;
        $twords=array();
        foreach($titems as $titem){
            if($titem->type == 'pronunciation'){
                $twords[] = $titem;
            }
        }
        $lastindex = count($twords);
        if($lastindex>0){
            return $twords[$lastindex-1]->end_time;
        }else{
            return 0;
        }
    }



  public static function get_region_options(){
      return array(
        "useast1" => get_string("useast1",constants::M_COMPONENT),
          "tokyo" => get_string("tokyo",constants::M_COMPONENT),
          "sydney" => get_string("sydney",constants::M_COMPONENT),
          "dublin" => get_string("dublin",constants::M_COMPONENT),
          "ottawa" => get_string("ottawa",constants::M_COMPONENT),
          "frankfurt" => get_string("frankfurt",constants::M_COMPONENT),
          "london" => get_string("london",constants::M_COMPONENT),
          "saopaulo" => get_string("saopaulo",constants::M_COMPONENT),
      );
  }



  public static function get_expiredays_options(){
      return array(
          "1"=>"1",
          "3"=>"3",
          "7"=>"7",
          "30"=>"30",
          "90"=>"90",
          "180"=>"180",
          "365"=>"365",
          "730"=>"730",
          "9999"=>get_string('forever',constants::M_COMPONENT)
      );
  }

    public static function fetch_options_transcribers() {
        $options =
                array(constants::TRANSCRIBER_AMAZONTRANSCRIBE => get_string("transcriber_amazontranscribe", constants::M_COMPONENT),
                        constants::TRANSCRIBER_GOOGLECLOUDSPEECH => get_string("transcriber_googlecloud", constants::M_COMPONENT));
        return $options;
    }

   public static function get_lang_options(){
       return array(
            'en-US'=>get_string('en-us',constants::M_COMPONENT),
           'en-UK'=>get_string('en-uk',constants::M_COMPONENT),
           'en-AU'=>get_string('en-au',constants::M_COMPONENT),
           'es-US'=>get_string('es-us',constants::M_COMPONENT),
           'fr-CA'=>get_string('fr-ca',constants::M_COMPONENT),
       );

   }

    public static function fetch_topic_levels(){
        return array(
                constants::M_TOPICLEVEL_COURSE=>get_string('topiclevelcourse',constants::M_COMPONENT),
                constants::M_TOPICLEVEL_CUSTOM=>get_string('topiclevelcustom',constants::M_COMPONENT)
        );

    }

    public static function get_conversationlength_options(){
        return array(
                '3'=>get_string('xminutes',constants::M_COMPONENT,3),
                '4'=>get_string('xminutes',constants::M_COMPONENT,4),
                '5'=>get_string('xminutes',constants::M_COMPONENT,5),
                '6'=>get_string('xminutes',constants::M_COMPONENT,6),
                '7'=>get_string('xminutes',constants::M_COMPONENT,7),
                '8'=>get_string('xminutes',constants::M_COMPONENT,8),
                '9'=>get_string('xminutes',constants::M_COMPONENT,9),
                '10'=>get_string('xminutes',constants::M_COMPONENT,10)
        );

    }

    public static function fetch_fonticon($fonticon, $size='fa-2x'){
        if(empty($fonticon)){return '';}
        if(strlen($fonticon)<5){return $fonticon;}
        return '<i class="fa ' . $fonticon . ' ' . $size . '"></i>';
    }
}
