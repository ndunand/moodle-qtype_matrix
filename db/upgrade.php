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

// This file keeps track of upgrades to
// the match qtype plugin
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

use qtype_matrix\db\stepdata_migration_utils;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot  . '/question/type/matrix/questiontype.php';

/**
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_qtype_matrix_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2014040800) {
        // Define table matrix to be created.
        $table = new xmldb_table('question_matrix');
        // Adding fields to table matrix.
        $newfield = $table->add_field(
            'shuffleanswers',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            XMLDB_NOTNULL,
            null,
            (int) qtype_matrix::DEFAULT_SHUFFLEANSWERS
        );
        $dbman->add_field($table, $newfield);
        upgrade_plugin_savepoint(true, 2014040800, 'qtype', 'matrix');
    }

    if ($oldversion < 2015070100) {
        // Define table matrix to be created.
        $table = new xmldb_table('question_matrix');
        // Adding fields to table matrix.
        $newfield = $table->add_field(
            'use_dnd_ui',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            XMLDB_NOTNULL,
            null,
            (int) qtype_matrix::DEFAULT_USEDNDUI
        );
        $dbman->add_field($table, $newfield);
        upgrade_plugin_savepoint(true, 2015070100, 'qtype', 'matrix');
    }

    if ($oldversion < 2023010303) {
        // Rename tables and columns to match the coding guidelines.
        $table = new xmldb_table('question_matrix');
        $dbman->rename_table($table, 'qtype_matrix');

        $table = new xmldb_table('question_matrix_cols');
        $dbman->rename_table($table, 'qtype_matrix_cols');

        $table = new xmldb_table('question_matrix_rows');
        $dbman->rename_table($table, 'qtype_matrix_rows');

        $table = new xmldb_table('question_matrix_weights');
        $dbman->rename_table($table, 'qtype_matrix_weights');

        $table = new xmldb_table('qtype_matrix');
        // Rename the field use_dnd_ui to usedndui because direct working with this variable will be hard in php,
        // when the coding standard don't allow '_' in variable names.
        $newfield = $table->add_field(
            'use_dnd_ui',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            XMLDB_NOTNULL,
            null,
            (int) qtype_matrix::DEFAULT_USEDNDUI
        );
        $dbman->rename_field($table, $newfield, 'usedndui');

        upgrade_plugin_savepoint(true, 2023010303, 'qtype', 'matrix');
    }
    if ($oldversion < 2025093001) {
        // Drop the unused renderer option field
        $table = new xmldb_table('qtype_matrix');
        $rendererfield = new xmldb_field('renderer');
        if ($dbman->field_exists($table, $rendererfield)) {
            $dbman->drop_field($table, $rendererfield);
        }
        upgrade_plugin_savepoint(true, 2025093001, 'qtype', 'matrix');

    }
    if ($oldversion < 2025093002) {
        // Replace the non-unique index
        $table = new xmldb_table('qtype_matrix');
        $oldforeignindex = new xmldb_index('quesmatr_que_ix', XMLDB_INDEX_NOTUNIQUE, ['questionid']);
        if ($dbman->index_exists($table, $oldforeignindex)) {
            $dbman->drop_index($table, $oldforeignindex);
            $newuniqueindex = new xmldb_index('qtypmatr_que_uix', XMLDB_INDEX_UNIQUE, ['questionid']);
            $dbman->add_index($table, $newuniqueindex);
        }
        upgrade_plugin_savepoint(true, 2025093002, 'qtype', 'matrix');

    }

    $stepdatamigrationversion = 2025093004;
    if ($oldversion < $stepdatamigrationversion) {
        // This can be long running depending on how many step data there is.
        // To avoid timeout problems this is forced to run via CLI.
        if (!CLI_SCRIPT) {
            throw new upgrade_exception('qtype_matrix', $stepdatamigrationversion,
            'The upgrade to '.$stepdatamigrationversion.' MUST be run via CLI to avoid webserver timeouts'
                .' caused by the potentially long running update process of attempt step data.'
            );
        }
        // Attempt step data contained keys and values using database IDs for rows and cols.
        // When you create a new question version, you create new database IDs for them.
        // When you then regrade a question, the attempt data can't be touched and is therefore useless.
        // We therefore migrate to row/column index based responses.

        core_php_time_limit::raise();
        // Ensure we have a base memory limit with which to work.
        raise_memory_limit(MEMORY_HUGE);
        $now = time();
        $transaction = $DB->start_delegated_transaction();
        // Guessed batch size for processing questions when updating question attempt step data.
        $questionbatchsize = 1000;
        // Show a progress bar.
        $total = $DB->count_records('question', ['qtype' => 'matrix']);
        $pbar = new progress_bar('upgrade_qtype_matrix_stepdata_to_row', 500, true);
        $offset = 0;
        while ($offset < $total) {
            $pbar->update($offset, $total, "Updating attempt data for qtype_matrix questions - $offset/$total questions.");
            $questionids = $DB->get_records(
                'question', ['qtype' => 'matrix'], 'id ASC', 'id', $offset, $questionbatchsize
            );
            $offset += $questionbatchsize;
            $matrixinfos = stepdata_migration_utils::extract_matrixinfos($questionids);
            // Now migrate the cell stepdata for the question batch (also done in batches).
            stepdata_migration_utils::migrate_stepdata($matrixinfos, $questionids);
        }
        $pbar->update($offset, $total, 'Done. Seconds: '.(time() - $now));
        $transaction->allow_commit();
        upgrade_plugin_savepoint(true, $stepdatamigrationversion, 'qtype', 'matrix');
    }
    return true;
}
