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

namespace qtype_matrix;

use mod_quiz\backup\repeated_restore_test;
use question_bank;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot . '/backup/util/includes/backup_includes.php';
require_once $CFG->dirroot . '/backup/util/includes/restore_includes.php';
require_once $CFG->dirroot . '/course/externallib.php';

/**
 * Tests for the matrix question type backup and restore logic.
 *
 * @package   qtype_matrix
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
final class backup_test extends \advanced_testcase {

    /**
     * Duplicate quiz with a matrix question, and check it worked,
     * i.e. the copy of the question looks the same but is not exactly the same (db ids)
     */
    public function test_duplicate_matrix_question(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $coregenerator = $this->getDataGenerator();
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        // Create a course with a page that embeds a question.
        $course = $coregenerator->create_course();
        $quiz = $coregenerator->create_module('quiz', ['course' => $course->id]);
        $quizcontext = \context_module::instance($quiz->cmid);

        $cat = $questiongenerator->create_question_category(['contextid' => $quizcontext->id]);
        $question = $questiongenerator->create_question('matrix', 'default', ['category' => $cat->id]);

        // Store some counts.
        $numquizzes = count(get_fast_modinfo($course)->instances['quiz']);
        $nummatrixquestions = $DB->count_records('question', ['qtype' => 'matrix']);

        // Duplicate the page.
        duplicate_module($course, get_fast_modinfo($course)->get_cm($quiz->cmid));

        // Verify the copied quiz exists.
        $this->assertCount($numquizzes + 1, get_fast_modinfo($course)->instances['quiz']);

        // Verify the copied question.
        $this->assertEquals($nummatrixquestions + 1, $DB->count_records('question', ['qtype' => 'matrix']));
        $newmatrixid = $DB->get_field_sql("
                SELECT MAX(id)
                  FROM {question}
                 WHERE qtype = ?
                ", ['matrix']);
        $questiondata = question_bank::load_question_data($question->id);
        $matrixdata = question_bank::load_question_data($newmatrixid);

        $this->assertNotEquals($questiondata->id, $matrixdata->id);
        $this->assertEquals($questiondata->name, $matrixdata->name);
        $this->assertEquals($questiondata->questiontext, $matrixdata->questiontext);
        $this->assertEquals($questiondata->generalfeedback, $matrixdata->generalfeedback);
        $this->assertEquals($questiondata->defaultmark, $matrixdata->defaultmark);
        $this->assertEquals($questiondata->penalty, $matrixdata->penalty);
        $this->assertEquals($questiondata->options->grademethod, $matrixdata->options->grademethod);
        $this->assertEquals($questiondata->options->multiple, $matrixdata->options->multiple);
        $this->assertEquals($questiondata->options->usedndui, $matrixdata->options->usedndui);
        $this->assertEquals($questiondata->options->shuffleanswers, $matrixdata->options->shuffleanswers);
        $questiondatarows = array_values($questiondata->options->rows);
        $matrixdatarows = array_values($matrixdata->options->rows);
        foreach ($questiondatarows as $index => $row) {
            $this->assertNotEquals($questiondatarows[$index]->id, $matrixdatarows[$index]->id);
            $this->assertNotEquals($questiondatarows[$index]->matrixid, $matrixdatarows[$index]->matrixid);
            $this->assertEquals($questiondatarows[$index]->shorttext, $matrixdatarows[$index]->shorttext);
            $this->assertEquals($questiondatarows[$index]->description['text'], $matrixdatarows[$index]->description['text']);
            $this->assertEquals($questiondatarows[$index]->description['format'], $matrixdatarows[$index]->description['format']);
            $this->assertEquals($questiondatarows[$index]->feedback['text'], $matrixdatarows[$index]->feedback['text']);
            $this->assertEquals($questiondatarows[$index]->feedback['format'], $matrixdatarows[$index]->feedback['format']);
        }

        $questiondatacols = array_values($questiondata->options->cols);
        $matrixdatacols = array_values($matrixdata->options->cols);
        foreach ($questiondatacols as $index => $col) {
            $this->assertNotEquals($questiondatacols[$index]->id, $matrixdatacols[$index]->id);
            $this->assertNotEquals($questiondatacols[$index]->matrixid, $matrixdatacols[$index]->matrixid);
            $this->assertEquals($questiondatacols[$index]->shorttext, $matrixdatacols[$index]->shorttext);
            $this->assertEquals($questiondatacols[$index]->description['text'], $matrixdatacols[$index]->description['text']);
            $this->assertEquals($questiondatacols[$index]->description['format'], $matrixdatacols[$index]->description['format']);
        }
        $questiondataweights = array_values($questiondata->options->weights);
        $matrixdataweights = array_values($matrixdata->options->weights);
        foreach ($questiondataweights as $rowindex => $colarrays) {
            $questiondatacolvalues = array_values($colarrays);
            $matrixdatacolvalues = array_values($matrixdataweights[$rowindex]);
            foreach ($questiondatacolvalues as $colindex => $colvalue) {
                $this->assertEquals($colvalue, $matrixdatacolvalues[$colindex]);
            }
        }
    }

    /**
     * @dataProvider get_matrix_test_questions
     * @param string $testquestion - The question to check
     */
    public function test_core_repeated_restore_quiz_with_duplicated_questions($testquestion):void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/mod/quiz/tests/backup/repeated_restore_test.php');
        $coreTest = new repeated_restore_test();
        $coreTest->test_restore_quiz_with_duplicate_questions('matrix', $testquestion);
    }

    /**
     * @dataProvider get_matrix_test_questions
     * @param string $testquestion - The question to check
     */
    public function test_core_repeated_restore_quiz_with_edited_questions($testquestion):void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/mod/quiz/tests/backup/repeated_restore_test.php');
        $coreTest = new repeated_restore_test();
        $coreTest->test_restore_quiz_with_edited_questions('matrix', $testquestion);
    }

    /**
     * @return array
     */
    public static function get_matrix_test_questions(): array {
        $generators = [];
        require_once('helper.php');
        $helper = new \qtype_matrix_test_helper();
        foreach ($helper->get_test_questions() as $testquestion) {
            $generators[$testquestion] = [$testquestion];
        }
        return $generators;
    }
}
