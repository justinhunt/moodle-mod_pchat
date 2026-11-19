<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_pchat;

use mod_pchat\report\classprogress;

/**
 * Access checks on grades and the class progress report.
 *
 * @package    mod_pchat
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_pchat_external
 * @covers     \mod_pchat\report\classprogress
 */
final class grading_access_test extends \advanced_testcase {
    /** @var \stdClass an activity's course module */
    protected $cm;

    /** @var \stdClass an activity's course module, in another course */
    protected $othercm;

    /** @var \stdClass a student in the first course, with a completed attempt */
    protected $student;

    /** @var \stdClass an editing teacher in the first course */
    protected $teacher;

    /** @var \stdClass a student in the other course, with a completed attempt */
    protected $otherstudent;

    /**
     * Load the library files.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/mod/pchat/lib.php');
        require_once($CFG->dirroot . '/course/lib.php');
    }

    /**
     * Two courses with an activity and a student attempt each, and a teacher in the first.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $othercourse = $gen->create_course();
        $this->cm = $this->create_activity($course);
        $this->othercm = $this->create_activity($othercourse);
        $this->student = $gen->create_and_enrol($course, 'student');
        $this->teacher = $gen->create_and_enrol($course, 'editingteacher');
        $this->otherstudent = $gen->create_and_enrol($othercourse, 'student');
        $this->create_attempt($this->cm, $this->student);
        $this->create_attempt($this->othercm, $this->otherstudent);
    }

    /**
     * Create an activity directly, without the speech services that add_instance may use.
     *
     * @param \stdClass $course the course
     * @return \stdClass the course module
     */
    protected function create_activity(\stdClass $course): \stdClass {
        global $DB;
        $instanceid = $DB->insert_record(constants::M_TABLE, (object) [
            'course' => $course->id,
            'name' => 'Chat',
            'multiattempts' => 1,
            'userconvlength' => 60,
            'grade' => 100,
            'timecreated' => time(),
        ]);
        $cmid = add_course_module((object) [
            'course' => $course->id,
            'module' => $DB->get_field('modules', 'id', ['name' => constants::M_MODNAME]),
            'instance' => $instanceid,
            'section' => 0,
        ]);
        course_add_cm_to_section($course, $cmid, 0);
        return get_coursemodule_from_id(constants::M_MODNAME, $cmid, 0, false, MUST_EXIST);
    }

    /**
     * Give a user a completed attempt, with stats, on an activity.
     *
     * @param \stdClass $cm the course module
     * @param \stdClass $user the user
     */
    protected function create_attempt(\stdClass $cm, \stdClass $user): void {
        global $DB;
        $attemptid = $DB->insert_record(constants::M_ATTEMPTSTABLE, (object) [
            'pchat' => $cm->instance,
            'courseid' => $cm->course,
            'userid' => $user->id,
            'completedsteps' => constants::STEP_SELFTRANSCRIBE,
            'feedback' => 'secret feedback',
            'grade' => 50,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record(constants::M_STATSTABLE, (object) [
            'pchat' => $cm->instance,
            'userid' => $user->id,
            'attemptid' => $attemptid,
            'turns' => 1, 'words' => 1, 'avturn' => 1, 'longestturn' => 1, 'targetwords' => 1,
            'totaltargetwords' => 1, 'questions' => 1, 'aiaccuracy' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Run the class progress report for the first course.
     *
     * @param string $selectedactivities the selectedactivities URL parameter
     * @return int the number of report rows
     */
    protected function class_progress_rows(string $selectedactivities): int {
        $report = new classprogress($this->cm);
        $report->process_raw_data((object) [
            'klassname' => 'Class',
            'groupid' => 0,
            'selectedactivities' => $selectedactivities,
        ]);
        return $report->fetch_all_rows_count();
    }

    /**
     * SQL in the selected activities parameter does not reach the query.
     */
    public function test_class_progress_ignores_injected_sql(): void {
        $this->assertEquals(0, $this->class_progress_rows('0) OR 1=1 OR p.id IN (0'));
    }

    /**
     * The class progress report still lists all, or the selected, activities of the course.
     */
    public function test_class_progress_selection_still_works(): void {
        $this->assertEquals(1, $this->class_progress_rows('*'));
        $this->assertEquals(1, $this->class_progress_rows((string) $this->cm->instance));
        $this->assertEquals(0, $this->class_progress_rows((string) $this->othercm->instance));
    }

    /**
     * A student cannot fetch another student's submission and grade.
     */
    public function test_get_grade_submission_denied_for_student(): void {
        $this->setUser($this->getDataGenerator()->create_and_enrol(get_course($this->cm->course), 'student'));
        $this->expectException(\required_capability_exception::class);
        \mod_pchat_external::get_grade_submission($this->student->id, $this->cm->id);
    }

    /**
     * A teacher can still fetch a submission in their own course.
     */
    public function test_get_grade_submission_allowed_for_teacher(): void {
        $this->setUser($this->teacher);
        $result = \mod_pchat_external::get_grade_submission($this->student->id, $this->cm->id);
        $this->assertNotEmpty($result['response']);
    }

    /**
     * A teacher cannot grade an activity in another course through their own course's context.
     */
    public function test_submit_grade_other_course_refused(): void {
        global $DB;
        $this->setUser($this->teacher);
        $contextid = \context_module::instance($this->cm->id)->id;
        // A complete, valid submission, so that only the permission check can stop the write.
        $formdata = json_encode(http_build_query(
            ['grade' => 99, 'feedback' => 'changed', 'sesskey' => sesskey(), '_qf__simple_grade_form' => 1],
            '',
            '&'
        ));
        $_POST['sesskey'] = sesskey();
        try {
            \mod_pchat_external::submit_simple_grade_form($contextid, $formdata, $this->otherstudent->id, $this->othercm->id);
            $this->fail('A grade was written to another course');
        } catch (\moodle_exception $e) {
            $this->assertEquals(50, $DB->get_field(
                constants::M_ATTEMPTSTABLE,
                'grade',
                ['userid' => $this->otherstudent->id]
            ));
        }
    }

    /**
     * A student cannot load the grade form fragments, which show another student's grade.
     *
     * @dataProvider fragment_provider
     * @param string $function the fragment callback
     */
    public function test_grade_fragments_denied_for_student(string $function): void {
        $this->setUser($this->getDataGenerator()->create_and_enrol(get_course($this->cm->course), 'student'));
        $this->expectException(\required_capability_exception::class);
        $function([
            'context' => \context_module::instance($this->cm->id),
            'cmid' => $this->cm->id,
            'studentid' => $this->student->id,
        ]);
    }

    /**
     * Data for test_grade_fragments_denied_for_student.
     *
     * @return array
     */
    public static function fragment_provider(): array {
        return [
            'rubric' => ['mod_pchat_output_fragment_rubric_grade_form'],
            'simple' => ['mod_pchat_output_fragment_simple_grade_form'],
        ];
    }
}
