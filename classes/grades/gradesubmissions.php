<?php

namespace mod_pchat\grades;

use dml_exception;
use mod_pchat\constants;

/**
 * Class grades
 *
 * Defines a listing of student grades for this course and module.
 *
 * @package mod_pchat\grades
 */
class gradesubmissions {


    /**
     * Gets full submission data for a student's entry.
     * 
     * @param int $userid
     * @param int $cmid
     * @return array
     * @throws dml_exception
     */
    public function getSubmissionData( $userid, $cmid){
        global $DB;

        $cm = get_coursemodule_from_id(constants::M_MODNAME, $cmid, 0, false, MUST_EXIST);
        $moduleinstance = $DB->get_record(constants::M_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);

        $sql = "SELECT pa.id as attemptid,
                    u.lastname,
                    u.firstname,
                    p.name,
                    p.transcriber,
                    p.id,
                    pa.filename,
                    pa.selftranscript,
                    pa.transcript,
                    pa.jsontranscript,
                    pat.turns as tt,
                    pat.words as tw,
                    pat.avturn as atl,
                    pat.longestturn as ltl,
                    pat.targetwords as tv,
                    pat.totaltargetwords as ttv,
                    pat.questions as qa,
                    pat.aiaccuracy as aia,
                    pa.grade as rubricscore,
                    pa.feedback,
                    pa.revq1,
                    pa.revq2,
                    pa.revq3
                FROM {" . constants::M_TABLE . "} p INNER JOIN {" . constants::M_ATTEMPTSTABLE . "} pa ON p.id = pa.pchat 
                INNER JOIN {course_modules} cm ON cm.course = p.course AND cm.id = ? 
                INNER JOIN {user} u ON pa.userid = u.id  
                INNER JOIN {" . constants::M_STATSTABLE . "} pat ON pat.attemptid = pa.id AND pat.userid = u.id 
                LEFT OUTER JOIN {" . constants::M_AITABLE . "}  par ON par.attemptid = pa.id AND par.courseid = p.course 
                WHERE pa.userid = ? 
                    AND pa.pchat = ? 
                ORDER BY pa.id DESC";

        $alldata = $DB->get_records_sql($sql, [$cmid, $userid, $moduleinstance->id]);
        if($alldata){
            $onedata = reset($alldata);
            //display grades gives us x/50 type display or for "scale" grades "has demonstrated competence"
            $displaygrades = make_grades_menu($moduleinstance->grade);
            if($onedata->rubricscore===null){
                $onedata->rubricscore ='';
            }else{
                if(array_key_exists($onedata->rubricscore,$displaygrades)){
                    $onedata->rubricscore =$displaygrades[$onedata->rubricscore];
                //In the case of decimals, they wont appear in the display grades list, so we mess around removing zeros and building our own equivalent
                }elseif (count($displaygrades)>1 &&
                    (is_numeric($onedata->rubricscore ) && is_float($onedata->rubricscore ))){
                        $onedata->rubricscore = rtrim(((string)$onedata->rubricscore),'0') . '/' . $moduleinstance->grade;
                }
            }
            return [$onedata];
        }else{
            return [];
        }
    }

    /**
     * Returns a listing of students who should be graded
     *
     * @param int $attempt
     * @return array
     * @throws dml_exception
     */
    public function getStudentsToGrade($moduleinstance,$groupid) {
        global $DB;

        //fetch all finished attempts
        if($groupid>0) {
            list($groupswhere, $groupparams) = $DB->get_in_or_equal($groupid);
            $sql = "SELECT pa.id, pa.userid as userid, concat(pa.userid, ',', pa.interlocutors) as students
                    FROM {pchat_attempts} pa                    
                     INNER JOIN {groups_members} gm ON pa.userid=gm.userid
                     WHERE pa.pchat = ? AND pa.completedsteps >= " . constants::STEP_SELFTRANSCRIBE .
                " AND gm.groupid $groupswhere 
                      ORDER BY pa.id DESC";
            $results = $DB->get_records_sql($sql, array_merge([$moduleinstance->id],$groupparams));
        }else{
            $sql = "SELECT pa.id, pa.userid as userid, concat(pa.userid, ',', pa.interlocutors) as students
                    FROM {pchat_attempts} pa
                    WHERE pa.pchat = ? AND pa.completedsteps >= " . constants::STEP_SELFTRANSCRIBE .
                " ORDER BY pa.id DESC";
            $results = $DB->get_records_sql($sql, [$moduleinstance->id]);
        }

        return $results;

    }//end of function


    /**
     * Returns a pages of students who should be graded.
     *
     * @param int $attempt
     * @return array
     * @throws dml_exception
     */
    public function getPageOfStudents($students, $studentid=0,$perpage=1) {
        $currentpagemembers=[];
        $pages=[];
        $studentpage=-1;
        //build array of 3 student pages
        foreach($students as $student){
            if(count($currentpagemembers)>=$perpage){
                $pages[]=$currentpagemembers;
                $currentpagemembers=[];
            }
            $currentpagemembers[]=$student->userid;
            if($studentid>0 && $student->userid ==$studentid ){
                $studentpage=count($pages);
            }
        }
        if(count($currentpagemembers)>0){
            $pages[]=$currentpagemembers;
        }
        //return page details
        $ret = [$pages,$studentpage];
        return $ret;
    }
}