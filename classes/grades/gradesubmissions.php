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

        $sql = "select pa.id as attemptid,
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
                    pat.uniquewords,
                    pat.longwords,
                    pat.longestturn,
                    pat.targetwords,
                    pat.totaltargetwords,
                    pat.questions,
                    pat.aiaccuracy,
                    pa.grade as rubricscore,
                    pa.feedback,
                from {" . constants::M_TABLE . "} as p
                    inner join {" . constants::M_ATTEMPTSTABLE . "} pa on p.id = pa.solo
                    inner join {course_modules} as cm on cm.course = p.course and cm.id = ?
                    inner join {user} as u on pa.userid = u.id
                    inner join {" . constants::M_STATSTABLE . "} as pat on pat.attemptid = pa.id and pat.userid = u.id
                    left outer join {" . constants::M_AITABLE . "} as par on par.attemptid = pa.id and par.courseid = p.course
                where pa.userid = ?
                    AND pa.pchat = ?
                order by pa.id DESC";

        $alldata = $DB->get_records_sql($sql, [$cmid, $userid, $moduleinstance->id]);
        if($alldata){
            return [reset($alldata)];
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
                    AND completedsteps = ?";

        return $DB->get_records_sql($sql, [$attempt, constants::STEP_SELFTRANSCRIBE]);
    }
}