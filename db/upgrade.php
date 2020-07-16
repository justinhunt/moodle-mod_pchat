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

/**
 * This file keeps track of upgrades to the pchat module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_pchat
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_pchat\constants;

/**
 * Execute pchat upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_pchat_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2019092400){
        $table = new xmldb_table('pchat_ai_result');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('moduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('transcript', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('passage', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('jsontranscript', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('wpm', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('accuracy', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sessionscore', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sessiontime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sessionerrors', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('sessionmatches', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('sessionendword', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('errorcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table pchat ai result.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for pchat ai resiult.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, 2019092400, 'pchat');
    }

    if ($oldversion < 2019092701) {
        $table = new xmldb_table('pchat');
        $fields = array();
        $fields[] = new xmldb_field('userconvlength', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $fields[] = new xmldb_field('revq1', XMLDB_TYPE_TEXT, null, null, null, null);
        $fields[] = new xmldb_field('revq2', XMLDB_TYPE_TEXT, null, null, null, null);
        $fields[] = new xmldb_field('revq3', XMLDB_TYPE_TEXT, null, null, null, null);
        $fields[] = new xmldb_field('tips', XMLDB_TYPE_TEXT, null, null, null, null);
        $fields[] = new xmldb_field('tipsformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field introformat
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2019092701, 'pchat');
    }

    if ($oldversion < 2019092702) {
        $table = new xmldb_table('pchat');
        $fields = array();
        $fields[] = new xmldb_field('convlength', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field introformat
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2019092702, 'pchat');
    }
    if ($oldversion < 2019092704) {
        $table = new xmldb_table('pchat_attempts');
        $field = new xmldb_field('reviewquestions', XMLDB_TYPE_TEXT, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
                $dbman->rename_field($table,$field,'revq1');
        }
        $field = new xmldb_field('reviewlonganswers', XMLDB_TYPE_TEXT, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table,$field,'revq2');
        }
        $field = new xmldb_field('reviewimprove', XMLDB_TYPE_TEXT, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table,$field,'revq3');
        }


        upgrade_mod_savepoint(true, 2019092704, 'pchat');
    }

    if ($oldversion < 2019100500) {
        $table = new xmldb_table('pchat_attemptstats');
        $field =  new xmldb_field('aiaccuracy', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019100500, 'pchat');
    }

    if ($oldversion < 2019120900) {
        $table = new xmldb_table('pchat');
        $field =  new xmldb_field('postattemptedit', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019120900, 'pchat');
    }


    if ($oldversion < 2020061615) {
        // Define field feedback to be added to pchat_attempts.
        $table = new xmldb_table('pchat_attempts');
        $field = new xmldb_field('feedback', XMLDB_TYPE_TEXT, null, null, null, null, null, 'completedsteps');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table pchat_rubric_scores to be created.
        $table = new xmldb_table('pchat_rubric_scores');

        // Adding fields to table pchat_rubric_scores.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('criteria', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('remark', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('levelid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table pchat_rubric_scores.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for pchat_rubric_scores.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pchat savepoint reached.
        upgrade_mod_savepoint(true, 2020061615, 'pchat');
    }

    if ($oldversion < 2020071501) {
        $table = new xmldb_table('pchat_attempts');
        $field =  new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020071501, 'pchat');
    }

    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
