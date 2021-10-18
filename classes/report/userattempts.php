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

class userattempts extends basereport
{

    protected $report="userattempts";
    protected $fields = array('id','audiofile','topicname','partners','turns','ATL','LTL','TW','QS','ACC','timemodified','view','deletenow');
    protected $exportfields = array('id','audiofile','topicname','partners','turns','ATL','LTL','TW','QS','ACC','timemodified');
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

                if ($withlinks) {
                    $tdata = array('partners' => $users);
                    $ret = $OUTPUT->render_from_template(constants::M_COMPONENT . '/partnerlist', $tdata);
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
              //  $ret = $record->targetwords . '/' . $record->totaltargetwords ;
                $ret = $record->targetwords;
                break;

            case 'QS':
                $ret = $record->questions ;
                break;

            case 'ACC':
                if($record->aiaccuracy<0) {
                    $ret = '';
                }else{
                    $ret = $record->aiaccuracy;
                }

                break;

            case 'audiofile':
                if ($withlinks && !empty($record->filename)) {


                    $ret = \html_writer::tag('audio','',
                            array('controls'=>'1','src'=>$record->filename));
                    //hidden player works but less useful right now
                    /*
                    $ret = \html_writer::div('<i class="fa fa-play-circle fa-2x"></i>',
                            constants::M_HIDDEN_PLAYER_BUTTON, array('data-audiosource' => $record->filename));
                    */

                } else {
                    $ret = get_string('submitted', constants::M_COMPONENT);
                }
                break;

            case 'timemodified':
                $ret = date("Y-m-d H:i:s", $record->timemodified);
                break;

            case 'view':
                if ($withlinks && has_capability('mod/pchat:manageattempts', $this->context)) {
                    $url = new \moodle_url(constants::M_URL . '/reports.php',
                            array('format'=>'html','report' => 'singleattempt', 'id' => $this->cm->id, 'attemptid' => $record->id));
                    $btn = new \single_button($url, get_string('view'), 'post');
                    $ret = $OUTPUT->render($btn);
                }else {
                    $ret = '';
                }
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
        $user = $this->fetch_cache('user', $record->userid);
        $usersname = fullname($user);
        return get_string('userattemptsheading',constants::M_COMPONENT,$usersname );

    }

    public function process_raw_data($formdata){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();
        $this->headingdata->userid=$formdata->userid;

        $emptydata = array();
        $sql = 'SELECT at.id, at.userid, at.topicname, at.interlocutors,at.filename, st.turns, st.avturn, st.longestturn, st.targetwords, st.totaltargetwords,st.questions,st.aiaccuracy, at.timemodified ';
        $sql .= '  FROM {' . constants::M_ATTEMPTSTABLE . '} at INNER JOIN {' . constants::M_STATSTABLE .  '} st ON at.id = st.attemptid ';
        $sql .= '  INNER JOIN {' . constants::M_TABLE .  '} p ON p.id = at.pchat ';
        $sql .= ' WHERE at.userid = :userid AND p.course = :courseid';
        $sql .= ' ORDER BY at.timemodified DESC';
        $alldata = $DB->get_records_sql($sql,array('userid'=>$formdata->userid, 'courseid'=>$this->cm->course));

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