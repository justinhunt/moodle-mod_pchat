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
     * @param int $coursemoduleid
     * @param int $moduleinstance Module instance ID for given chat.
     * @return array
     * @throws dml_exception
     */
    public function getGrades($courseid, $coursemoduleid, $moduleinstance, $groupid){
        global $DB;

        if($groupid>0){
            list($groupswhere, $groupparams) = $DB->get_in_or_equal($groupid);
            $sql = "select pa.id as attemptid,
                    u.lastname,
                    u.firstname,
                    p.name,
                    p.transcriber,
                    pat.words as tw, 
                    pat.turns as tt,
                    pat.avturn as atl,
                    pat.longestturn as ltl,
                    pat.targetwords as tv,
                    pat.totaltargetwords as ttv,
                    pat.questions as qa,
                    par.accuracy,
                    pa.pchat,
                    pat.aiaccuracy as aia,
                    pa.grade,
                    pa.userid
                from {pchat} as p
                    inner join {pchat_attempts} pa on p.id = pa.pchat
                    inner join {course_modules} as cm on cm.course = p.course and cm.id = ?
                    inner join {groups_members} gm ON pa.userid=gm.userid
                    inner join {user} as u on pa.userid = u.id
                    inner join {pchat_attemptstats} as pat on pat.attemptid = pa.id and pat.userid = u.id
                    left outer join {pchat_ai_result} as par on par.attemptid = pa.id and par.courseid = p.course
                where p.course = ?
                    AND pa.pchat = ?
                    AND gm.groupid $groupswhere 
                order by pa.id DESC";

            $alldata = $DB->get_records_sql($sql, array_merge([$coursemoduleid, $courseid, $moduleinstance] , $groupparams));

            //not groups
        }else {
            $sql = "select pa.id as attemptid,
                    u.lastname,
                    u.firstname,
                    p.name,
                    p.transcriber,
                    pat.words as tw, 
                    pat.turns as tt,
                    pat.avturn as atl,
                    pat.longestturn as ltl,
                    pat.targetwords as tv,
                    pat.totaltargetwords as ttv,
                    pat.questions as qa,
                    par.accuracy,
                    pa.pchat,
                    pat.aiaccuracy as aia,
                    pa.grade,
                    pa.userid
                from {pchat} as p
                    inner join {pchat_attempts} pa on p.id = pa.pchat
                    inner join {course_modules} as cm on cm.course = p.course and cm.id = ?
                    inner join {user} as u on pa.userid = u.id
                    inner join {pchat_attemptstats} as pat on pat.attemptid = pa.id and pat.userid = u.id
                    left outer join {pchat_ai_result} as par on par.attemptid = pa.id and par.courseid = p.course
                where p.course = ?
                    AND pa.pchat = ?
                order by u.lastname, pa.id DESC";
            $alldata = $DB->get_records_sql($sql, [$coursemoduleid, $courseid, $moduleinstance]);
        }

        //loop through data getting most recent attempt
        $results=array();
        if ($alldata) {
            $user_attempt_totals = array();
            foreach ($alldata as $thedata) {

                //we ony take the most recent attempt
                if (array_key_exists($thedata->userid, $user_attempt_totals)) {
                    $user_attempt_totals[$thedata->userid] = $user_attempt_totals[$thedata->userid] + 1;
                    continue;
                }
                $user_attempt_totals[$thedata->userid] = 1;

                $results[] = $thedata;
            }
            foreach ($results as $thedata) {
                $thedata->totalattempts = $user_attempt_totals[$thedata->userid];
            }
        }
        return $results;
    }//end of function
}//end of class