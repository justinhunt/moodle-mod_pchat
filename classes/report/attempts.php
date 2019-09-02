<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_pchat\report;

use \mod_pchat\constants;
use \mod_pchat\utils;

class attempts extends basereport
{

    protected $report="attempts";
    protected $fields = array('id','username','topicname','partners','turns','ATL','LTL','TW','timemodified','deletenow');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();


    public function fetch_formatted_field($field,$record,$withlinks)
    {
        global $DB, $CFG, $OUTPUT;
        switch ($field) {
            case 'id':
                $ret = $record->id;
                break;

            case 'username':
                $user = $this->fetch_cache('user', $record->userid);
                $ret = fullname($user);
                break;

            case 'topicname':
                $ret = $record->topicname;
                break;

            case 'partners':
                //we need to work out usernames and stuff.
                //just return blank if we have none, right from the start
                if(empty($record->interlocutors)){
                    $ret='';
                    break;
                }
                $partners = explode(',',$record->interlocutors);
                $users = array();
                foreach ($partners as $partner){
                    $users[] = fullname($this->fetch_cache('user', $partner));
                }
                //this is bad. We use the targetwords tags for users. It just seemed like a good idea
                if ($withlinks) {
                    $tdata = array('targetwords' => $users);
                    $ret =$targetwordcontent = $OUTPUT->render_from_template(constants::M_COMPONENT . '/targetwords', $tdata);
                }else{
                    $ret =implode(',' , $users);
                }

                break;

            case 'turns':
                $ret = $record->turns;
                break;

            case 'ATL':
                $ret = $record->avturn;
                break;

            case 'LTL':
                $ret = $record->longestturn;
                break;

            case 'TW':
                $ret = $record->targetwords . '/' . $record->totaltargetwords ;
                break;

            case 'timemodified':
                $ret = date("Y-m-d H:i:s", $record->timemodified);
                break;

            case 'deletenow':
                if ($withlinks && has_capability('mod/pchat:manageattempts', $this->context)) {
                    $url = new \moodle_url(constants::M_URL . '/attempt/manageattempts.php',
                        array('action' => 'delete', 'id' => $this->cm->id, 'attemptid' => $record->id, 'source' => $this->report));
                    $btn = new \single_button($url, get_string('delete'), 'post');
                    $btn->add_confirm_action(get_string('deleteattemptconfirm', constants::M_COMPONENT));
                    $ret = $OUTPUT->render($btn);
                }else {
                    $ret = '';
                }
                break;

            default:
                if (property_exists($record, $field)) {
                    $ret = $record->{$field};
                } else {
                    $ret = '';
                }
        }
        return $ret;
    }

    public function fetch_formatted_heading(){
        $record = $this->headingdata;
        $ret='';
        if(!$record){return $ret;}
        return get_string('attemptsheading',constants::M_COMPONENT);

    }

    public function process_raw_data($formdata){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();

        $emptydata = array();
        $sql = 'SELECT at.id, at.userid, at.topicname, at.interlocutors, st.turns, st.avturn, st.longestturn, st.targetwords, st.totaltargetwords, at.timemodified ';
        $sql .= '  FROM {' . constants::M_ATTEMPTSTABLE . '} at INNER JOIN {' . constants::M_STATSTABLE .  '} st ON at.id = st.attemptid ';
        $sql .= ' WHERE at.pchat = :pchatid';
        $alldata = $DB->get_records_sql($sql,array('pchatid'=>$formdata->pchatid));

        if($alldata){
            foreach($alldata as $thedata){
                //do any processing here
            }
            $this->rawdata= $alldata;
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }

}