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

class classprogress extends basereport
{

    protected $report="classprogress";
    protected $fields = array('pchatname','avturns','avatl','avltl','avw','avtw','avq','avacc');
    protected $exportfields = array('pchatname','avturns','avatl','avltl','avw','avtw','avq','avacc');
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
                                $rawdata->{$field}[] = $data->{$field};
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

            case 'avturns':
                $ret = round($record->avturns,1);
                break;
            case 'avatl':
                $ret = round($record->avatl,1);
                break;
            case 'avltl':
                $ret = round($record->avltl,1);
                break;
            case 'avw':
                $ret = round($record->avw,1);
                break;
            case 'avtw':
                $ret = round($record->avtw,1);
                break;
            case 'avq':
                $ret = round($record->avq,1);
                break;
            case 'avacc':
                $ret = round($record->avacc,1);
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
        if(!$record || $record->klassname==null){return $ret;}
        return get_string('classprogressheading',constants::M_COMPONENT,$record->klassname );

    }

    public function process_raw_data($formdata){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();
        $this->headingdata->klassname=$formdata->klassname;

        $emptydata = array();

        $selectedactivities = $formdata->selectedactivities;
        if(!empty($selectedactivities) && $selectedactivities !='*'){
            $selectedactivities_where = ' AND p.id IN ('  . $selectedactivities . ') ';
        }else{
            $selectedactivities_where = '';
        }

        //if we need to show just one group
        if($formdata->groupid > 0){

            list($groupswhere, $allparams) = $DB->get_in_or_equal($formdata->groupid);

            $sql = 'SELECT p.id, p.name pchatname, AVG(st.turns) avturns, AVG(st.avturn) avatl, AVG(st.longestturn) avltl, AVG(st.words) avw, AVG(st.targetwords)avtw, AVG(st.questions)avq ,AVG(st.aiaccuracy) avacc';
            $sql .= '  FROM {' . constants::M_ATTEMPTSTABLE . '} at INNER JOIN {' . constants::M_STATSTABLE .  '} st ON at.id = st.attemptid ';
            $sql .= '  INNER JOIN {' . constants::M_TABLE .  '} p ON p.id = at.pchat ';
            $sql .= ' INNER JOIN {groups_members} gm ON at.userid=gm.userid';
            $sql .= ' WHERE gm.groupid ' . $groupswhere;
            $sql .= $selectedactivities_where;
            $sql .= ' AND p.course = ?';
            $sql .= ' GROUP BY p.id, p.name';
            $sql .= ' ORDER BY p.name, p.id';
            $allparams[]=$this->cm->course;
            $alldata = $DB->get_records_sql($sql,$allparams);

            //if no groups, or can see all groups then the SQL is simple
        }else{

            $sql = 'SELECT p.id, p.name pchatname, AVG(st.turns) avturns, AVG(st.avturn) avatl, AVG(st.longestturn) avltl, AVG(st.words) avw, AVG(st.targetwords)avtw, AVG(st.questions)avq ,AVG(st.aiaccuracy) avacc';
            $sql .= '  FROM {' . constants::M_ATTEMPTSTABLE . '} at INNER JOIN {' . constants::M_STATSTABLE .  '} st ON at.id = st.attemptid ';
            $sql .= '  INNER JOIN {' . constants::M_TABLE .  '} p ON p.id = at.pchat ';
            $sql .= ' WHERE p.course = :courseid';
            $sql .= $selectedactivities_where;
            $sql .= ' GROUP BY p.id, p.name';
            $sql .= ' ORDER BY p.name, p.id';
            $alldata = $DB->get_records_sql($sql,array('courseid'=>$this->cm->course));

        }

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