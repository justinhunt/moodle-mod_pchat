<?php

namespace mod_pchat\grades;

use dml_exception;

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
     * @param int $moduleinstanceid Module instance ID for given chat.
     * @return array
     * @throws dml_exception
     */
    public function getGradeData(int $courseid, int $studentid, int $moduleinstance): array {
        global $DB;

        $sql = "select pa.id, u.lastname, u.firstname, p.name, p.transcriber, pat.words, pat.avturn, pat.longestturn, pat.targetwords, pat.totaltargetwords, pat.questions, pat.aiaccuracy
                from {pchat} as p
                    inner join  (select max(mpa.id) as id, mpa.userid, mpa.pchat
                            from {pchat_attempts} mpa
                            group by mpa.userid, mpa.pchat
                        ) as pa on p.id = pa.pchat
                    inner join {course_modules} as cm on cm.course = p.course and cm.id = ?
                    inner join {user} as u on pa.userid = u.id
                    inner join {pchat_attemptstats} as pat on pat.attemptid = pa.id and pat.userid = u.id
                    left outer join {pchat_ai_result} as par on par.attemptid = pa.id and par.courseid = p.course
                where u.id = ?
                    AND pa.pchat = ?
                    AND p.course = ?
                order by u.lastname";

        return $DB->get_records_sql($sql, [$studentid, $moduleinstance, $courseid]);
    }

    public function getSubmissionData(int $userid, int $moduleid, int $cmid): array {
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
                    (select round(sum(grl.score), 2) 
                    from mdl_grading_definitions AS gd
                    JOIN mdl_gradingform_rubric_criteria AS grc ON (grc.definitionid = gd.id)
                    JOIN mdl_gradingform_rubric_levels AS grl ON (grl.criterionid = grc.id)
                    where grl.criterionid = prs.criteria
                    and grl.id = prs.levelid) as rubricscore,
                    prs.remark,
                    pa.feedback
            from mdl_pchat as p
                inner join (select max(mpa.id) as id, mpa.userid, mpa.pchat, mpa.feedback
            from mdl_pchat_attempts mpa group by  mpa.userid, mpa.pchat ) as pa
            on p.id = pa.pchat
                inner join mdl_course_modules as cm on cm.course = p.course and cm.id = 5
                inner join mdl_user as u on pa.userid = u.id
                inner join mdl_pchat_attemptstats as pat on pat.attemptid = pa.id and pat.userid = u.id
                left outer join mdl_pchat_rubric_scores as prs on prs.userid = pat.userid and prs.attemptid = pa.id
                left outer join mdl_pchat_ai_result as par on par.attemptid = pa.id and par.courseid = p.course
                left outer join mdl_pchat_attempts as ca on ca.pchat = pa.pchat and ca.userid = u.id
            where u.id = ?
            and cm.id = ?;";

        return $DB->get_records_sql($sql, [$userid, $cmid]);
    }

    public function getStudentsToGrade(int $attempt): array {
        global $DB;

        $sql = "select concat(userid, ',', interlocutors) as students
                    from {pchat_attempts} pa
                    where pa.id = ?";

        return $DB->get_records_sql($sql, [$attempt]);
    }
}