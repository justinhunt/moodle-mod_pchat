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
                    pat.turns,
                    pat.words,
                    pat.avturn,
                    pat.longestturn,
                    pat.targetwords,
                    pat.totaltargetwords,
                    pat.questions,
                    pat.aiaccuracy,
                    pa.grade as rubricscore,
                    pa.feedback 
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
                }
            }
            return [$onedata];
        }else{
            return [];
        }
    }

    /**
     * Returns a listing of students who should be graded based on the user clicked.
     *
     * @param int $attempt
     * @return array
     * @throws dml_exception
     */
    public function getStudentsToGrade($attempt,$groupid) {
        global $DB;

        $sql = "select concat(userid, ',', interlocutors) as students
                    from {pchat_attempts} pa
                    where pa.id = ?
                    AND completedsteps >= ?";

        return $DB->get_records_sql($sql, [$attempt, constants::STEP_SELFTRANSCRIBE]);
    }
}