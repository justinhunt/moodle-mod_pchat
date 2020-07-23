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
     * Gets assignment data for a specific student.
     *
     * @param int $courseid Course ID of chat.
     * @param int $studentid Moodle student ID
     * @param int $moduleinstance
     * @return array
     * @throws dml_exception
     */
    public function getGradeData(int $courseid, int $studentid, int $moduleinstance): array {
        global $DB;

        $sql = "select pa.id, u.lastname, u.firstname, p.name, p.transcriber, pat.words, pat.avturn, pat.longestturn, pat.targetwords, pat.totaltargetwords, pat.questions, pat.aiaccuracy
                from {" . constants::M_TABLE . "} as p
                    inner join  (select max(mpa.id) as id, mpa.userid, mpa.pchat
                            from {" . constants::M_ATTEMPTSTABLE . "} mpa
                            group by mpa.userid, mpa.pchat
                        ) as pa on p.id = pa.pchat
                    inner join {course_modules} as cm on cm.course = p.course and cm.id = ?
                    inner join {user} as u on pa.userid = u.id
                    inner join {" . constants::M_STATSTABLE . "} as pat on pat.attemptid = pa.id and pat.userid = u.id
                    left outer join {" . constants::M_AITABLE . "} as par on par.attemptid = pa.id and par.courseid = p.course
                where u.id = ?
                    AND pa.pchat = ?
                    AND p.course = ?
                order by u.lastname";

        return $DB->get_records_sql($sql, [$studentid, $moduleinstance, $courseid]);
    }

    /**
     * Gets full submission data for a student's entry.
     * 
     * @param int $userid
     * @param int $cmid
     * @return array
     * @throws dml_exception
     */
    public function getSubmissionData(int $userid, int $cmid): array {
        global $DB;

        $sql = "select pa.id,
                   u.lastname,
                   u.firstname,
                   p.name,
                   p.transcriber,
                   p.id,
                   pa.pchat,
                   pat.pchat,
                   ca.filename,
                    ca.transcript,
                    ca.jsontranscript,
                    pat.turns,
                    pat.words,
                    pat.avturn,
                    pat.longestturn,
                    pat.targetwords,
                    pat.totaltargetwords,
                    pat.questions,
                    pat.aiaccuracy,
                    ca.grade as rubricscore,
                    pa.feedback
            from {" . constants::M_TABLE . "} as p
                inner join (select max(mpa.id) as id, mpa.userid, mpa.pchat, mpa.feedback
            from {" . constants::M_ATTEMPTSTABLE . "} mpa group by  mpa.userid, mpa.pchat, mpa.feedback ) as pa
            on p.id = pa.pchat
                inner join {course_modules} as cm on cm.course = p.course
                inner join {user} as u on pa.userid = u.id
                inner join {" . constants::M_STATSTABLE . "} as pat on pat.attemptid = pa.id and pat.userid = u.id
                left outer join  {" . constants::M_AITABLE . "} as par on par.attemptid = pa.id and par.courseid = p.course
                left outer join {" . constants::M_ATTEMPTSTABLE . "} as ca on ca.pchat = pa.pchat and ca.userid = u.id
            where u.id = ?
            and cm.id = ?;";

        return $DB->get_records_sql($sql, [$userid, $cmid]);
    }

    /**
     * Returns a listing of students who should be graded based on the user clicked.
     *
     * @param int $attempt
     * @return array
     * @throws dml_exception
     */
    public function getStudentsToGrade(int $attempt): array {
        global $DB;

        $sql = "select concat(userid, ',', interlocutors) as students
                    from {pchat_attempts} pa
                    where pa.id = ?";

        return $DB->get_records_sql($sql, [$attempt]);
    }
}