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
class grades {
    /**
     * Gets listing of grades for students.
     *
     * @param int $courseid Course ID of chat.
     * @param int $moduleinstanceid Module instance ID for given chat.
     * @return array
     * @throws dml_exception
     */
    public function getGrades(int $courseid, int $coursemoduleid, int $moduleinstance) : array {
        global $DB;

        $sql = "select pa.id, u.lastname, u.firstname, p.name, p.transcriber, turns, avturn, par.accuracy
                from {pchat} as p
                    inner join  (select max(mpa.id) as id, mpa.userid, mpa.pchat
                            from {pchat_attempts} mpa
                            group by id, mpa.userid, mpa.pchat
                        ) as pa on p.id = pa.pchat
                    inner join {course_modules} as cm on cm.course = p.course and cm.id = ?
                    inner join {user} as u on pa.userid = u.id
                    inner join {pchat_attemptstats} as pat on pat.attemptid = pa.id and pat.userid = u.id
                    left outer join {pchat_ai_result} as par on par.attemptid = pa.id and par.courseid = p.course
                where p.course = ?
                    AND pa.pchat = ?
                order by u.lastname";

        return $DB->get_records_sql($sql, [$coursemoduleid, $courseid, $moduleinstance]);
    }
}