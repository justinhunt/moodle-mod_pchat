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

class myprogress extends basereport
{

    protected $report="myprogress";
    protected $fields = array('pchatname','stats_words','stats_turns','stats_avturn','stats_longestturn','stats_questions','stats_aiaccuracy');
    protected $exportfields = array('pchatname','stats_words','stats_turns','stats_avturn','stats_longestturn','stats_questions','stats_aiaccuracy');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();

    public function fetch_chart_data($allfields=[]){

        if(empty($allfields)){
            $allfields=$this->fields;
        }


            //if we have data, yay
            if ($this->rawdata) {

                //init our data set
                $chartdata = new \stdClass();
                $chartdata->labels =[];
                $chartdata->series =[];

                //get some working data
                $rawdata = new \stdClass();
                foreach($allfields as $field){
                    $rawdata->{$field}=[];
                }


                //loop through each attempt
                foreach ($this->rawdata as $data) {
                    foreach ($allfields as $field) {
                        switch ($field) {
                            case 'pchatname':
                                $chartdata->labels[] = $data->pchatname;
                                break;
                            default:
                                $rawdata->{$field}[] = round($data->{$field},1);
                        }

                    }
                }
                //add rawdata to chartdata
                //get some working data
                foreach($allfields as $field){
                    switch ($field) {
                        case 'pchatname':
                            break;
                        default:
                            $chartdata->series[] = new \core\chart_series(get_string($field, constants::M_COMPONENT),$rawdata->{$field});
                    }
                }
                return $chartdata;

            }else{
                return false;
            }
    }


    public function fetch_formatted_field($field,$record,$withlinks)
    {
        global $DB, $CFG, $OUTPUT;
        switch ($field) {
            case 'pchatname':
                $ret = $record->pchatname;
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
        return get_string('myprogressheading',constants::M_COMPONENT,$usersname );

    }

    public function process_raw_data($formdata){
        global $DB, $USER;

        //heading data
        $this->headingdata = new \stdClass();
        if(!empty($formdata) && isset($formdata->userid) && $formdata->userid > 0){
            $this->headingdata->userid = $formdata->userid;
        }else {
            $this->headingdata->userid = $USER->id;
        }

        $emptydata = array();
        $sql = 'SELECT p.id, p.name pchatname, MAX(st.words) stats_words, MAX(st.turns) stats_turns, MAX(st.avturn) stats_avturn, MAX(st.longestturn) stats_longestturn,   MAX(st.questions)stats_questions ,MAX(st.aiaccuracy) stats_aiaccuracy';
        $sql .= '  FROM {' . constants::M_ATTEMPTSTABLE . '} at INNER JOIN {' . constants::M_STATSTABLE .  '} st ON at.id = st.attemptid ';
        $sql .= '  INNER JOIN {' . constants::M_TABLE .  '} p ON p.id = at.pchat ';
        $sql .= ' WHERE p.course = :courseid AND at.userid= :userid';
        $sql .= ' GROUP BY p.id, p.name, at.timecreated';
        $sql .= ' ORDER BY at.timecreated, p.name, p.id';
        $alldata = $DB->get_records_sql($sql,array('courseid'=>$this->cm->course, 'userid'=>$this->headingdata->userid));

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