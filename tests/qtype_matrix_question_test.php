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

use advanced_testcase;
use qtype_matrix\local\grading\all;
use qtype_matrix\local\grading\difference;
use qtype_matrix\local\grading\kany;
use qtype_matrix\local\grading\kprime;
use qtype_matrix\local\qtype_matrix_grading;
use qtype_matrix_question;
use question_attempt_step;
use question_classified_response;
use question_state;
use stdClass;
use qtype_matrix_test_helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot . '/question/engine/tests/helpers.php';
require_once $CFG->dirroot . '/question/engine/questionattempt.php';
require_once $CFG->dirroot . '/question/engine/questionattemptstep.php';
require_once $CFG->dirroot . '/question/type/matrix/tests/helper.php';

/**
 * Unit tests for the matrix question definition class.
 *
 */
class qtype_matrix_question_test extends advanced_testcase {

    public function test_response():void {
        $question = qtype_matrix_test_helper::make_question('nondefault');
        $row = new stdClass();
        $row->id = 1;
        $col = new stdClass();
        $col->id = 2;
        $response = [
        ];
        $this->assertFalse($question->response($response, $row, $col));
        $response = [
            'cell1_2' => 1
        ];
        $this->assertTrue($question->response($response, $row, $col));
        $response = [
            'cell0_1' => 1,
            'cell1_2' => 1
        ];
        $this->assertTrue($question->response($response, $row, $col));
        $question->multiple = false;
        $response = [
        ];
        $this->assertFalse($question->response($response, $row, $col));
        $response = [
            'cell1' => 1
        ];
        $this->assertFalse($question->response($response, $row, $col));
        $response = [
            'cell1' => 2
        ];
        $this->assertTrue($question->response($response, $row, $col));
        $response = [
            'cell0' => 2,
            'cell1' => 2
        ];
        $this->assertTrue($question->response($response, $row, $col));
    }

    public function test_key():void {
        $question = qtype_matrix_test_helper::make_question('nondefault');
        $row = new stdClass();
        $row->id = 1;
        $col = new stdClass();
        $col->id = 2;
        $randcolid = rand(3,100);
        $this->assertEquals('cell1_2', $question->key($row, $col));
        $this->assertEquals('cell1_2', $question->key($row->id, $col->id));
        $this->assertEquals('cell1_2', $question->key($row, $col, true));
        $this->assertEquals('cell1_2', $question->key($row->id, $col->id, true));
        $this->assertEquals('cell1', $question->key($row, $col, false));
        $this->assertEquals('cell1', $question->key($row->id, $col->id, false));
        $this->assertEquals('cell1', $question->key($row->id, $randcolid, false));
        $question->multiple = false;
        $this->assertEquals('cell1', $question->key($row, $col));
        $this->assertEquals('cell1', $question->key($row->id, $col->id));
        $this->assertEquals('cell1', $question->key($row->id, $randcolid));
        $this->assertEquals('cell1', $question->key($row, $col, false));
        $this->assertEquals('cell1', $question->key($row->id, $col->id, false));
        $this->assertEquals('cell1', $question->key($row->id, $randcolid));
        $this->assertEquals('cell1_2', $question->key($row, $col, true));
        $this->assertEquals('cell1_2', $question->key($row->id, $col->id, true));
    }

    public function test_answer():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $row = new stdClass();
        $row->id = 1;
        $col = new stdClass();
        $col->id = 2;
        $question->weights[$row->id][$col->id] = 1;
        $this->assertTrue($question->answer($row, $col));
        $this->assertTrue($question->answer($row->id, $col->id));
        $question->weights[$row->id][$col->id] = 0;
        $this->assertFalse($question->answer($row, $col));
        $this->assertFalse($question->answer($row->id, $col->id));
    }

    public function test_weight():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $row = new stdClass();
        $row->id = 1;
        $col = new stdClass();
        $col->id = 2;
        $question->weights[$row->id][$col->id] = 1;
        $this->assertEquals(1, $question->weight($row, $col));
        $this->assertEquals(1, $question->weight($row->id, $col->id));
        // FIXME: This throws str_replace(): Passing null to parameter #2 ($replace) is deprecated
        // $this->assertEquals(1, $question->weight('cell1x2'));
        // Strangely, this is bad data, but works here.
        $question->weights[$row->id][$col->id] = 2;
        $this->assertEquals(2, $question->weight($row, $col));
        $this->assertEquals(2, $question->weight($row->id, $col->id));
    }

    public function test_start_attempt_noshuffle():void {
        // FIXME: Don't test usedndui for now
        $qa = new question_attempt_step();
        $question = qtype_matrix_test_helper::make_question('default');
        $question->shuffleanswers = false;
        $normalrows = [
            1 => 'first',
            2 => 'second',
            3 => 'third',
            4 => 'fourth'
        ];
        $rowids = array_keys($normalrows);
        $question->rows = $normalrows;
        $question->start_attempt($qa, 1);
        $this->assertEquals($rowids, explode(',', $qa->get_qt_var($question::KEY_ROWS_ORDER)));
    }

    public function test_start_attempt_shuffle():void {
        $qa = new question_attempt_step();
        $question = qtype_matrix_test_helper::make_question('default');
        $question->shuffleanswers = true;
        $normalrows = [
            1 => 'first',
            2 => 'second',
            3 => 'third',
            4 => 'fourth'
        ];
        $rowids = array_keys($normalrows);
        $question->rows = $normalrows;
        $question->start_attempt($qa, 1);
        $shuffledids = explode(',', $qa->get_qt_var($question::KEY_ROWS_ORDER));
        foreach ($shuffledids as $shuffledid) {
            $this->assertContainsEquals($shuffledid, $rowids);
        }
        foreach ($rowids as $rowid) {
            $this->assertContainsEquals($rowid, $shuffledids);
        }
    }

    public function test_shuffle_answers():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $question->shuffleanswers = true;
        $this->assertTrue($question->shuffle_answers());
        $question->shuffleanswers = false;
        $this->assertFalse($question->shuffle_answers());
        // FIXME: $PAGE->cm influences this, should be mocked for full testing
    }

    public function test_shuffle_authorized():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->assertTrue($question->shuffle_authorized());
        // FIXME: $PAGE->cm also influences this, should be mocked for full testing
    }

    public function test_apply_attempt_state():void {
        $qa = new question_attempt_step();
        $question = qtype_matrix_test_helper::make_question('default');
        $question->shuffleanswers = true;
        $normalrows = [
            1 => 'first',
            2 => 'second',
            3 => 'third',
            4 => 'fourth'
        ];
        $question->rows = $normalrows;
        // TODO: We should probably not need to use Reflection...
        $questionClass = new \ReflectionClass($question);
        $orderProperty = $questionClass->getProperty('order');
        $this->assertNull($orderProperty->getValue($question));
        $this->assertNull($qa->get_qt_var($question::KEY_ROWS_ORDER));
        $question->apply_attempt_state($qa);
        $questionClass = new \ReflectionClass($question);
        $orderProperty = $questionClass->getProperty('order');

        $this->assertNotNull($orderProperty->getValue($question));
        $this->assertEquals(implode(',', $orderProperty->getValue($question)), $qa->get_qt_var($question::KEY_ROWS_ORDER));
        $question->apply_attempt_state($qa);
        $this->assertNotNull($orderProperty->getValue($question));
        $this->assertEquals(implode(',', $orderProperty->getValue($question)), $qa->get_qt_var($question::KEY_ROWS_ORDER));
    }

    public function test_get_order():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $qa = new question_attempt_step();
        // In a normal process, each attempt will always have the first step initialized
        $qa->set_qt_var($question::KEY_ROWS_ORDER, '1,2,3,4');
        $mockedAttempt = $this->createStub('question_attempt');
        $mockedAttempt->method('get_step')->willReturn($qa);
        $order = $question->get_order($mockedAttempt);
        $this->assertEquals([1,2,3,4], $order);
        $qa->set_qt_var($question::KEY_ROWS_ORDER, '5,6,7,8');
        $order = $question->get_order($mockedAttempt);
        $this->assertEquals([1,2,3,4], $order);
        $question = qtype_matrix_test_helper::make_question('default');
        $qa->set_qt_var($question::KEY_ROWS_ORDER, '5,6,7,8');
        $order = $question->get_order($mockedAttempt);
        $this->assertEquals([5,6,7,8], $order);
    }

// FIXME: This doesn't need to be tested as long as Matrix questions don't let the user save question hints.
//    public function test_compute_final_grade():void {
//        $question = qtype_matrix_test_helper::make_question('default');
//    }

    /**
     * @dataProvider grade_response_data_provider
     * @covers ::get_expected_data
     * @return void
     */
    public function test_grade_response(
        string $questiontype,
        string $grademethod,
        float  $correctgrade,
        float  $incorrectgrade,
        float  $onerowwronggrade,
        float  $halfcorrectgrade,
        float  $incompletepartiallycorrectgrade,
        float  $incompleteincorrectgrade
    ): void {
        $question = qtype_matrix_test_helper::make_question($questiontype);
        $question->grademethod = $grademethod;

        $correctanswer = self::make_correct_answer($question);
        $grade = $question->grade_response($correctanswer);
        $this->assertEquals($this->get_expected_grade($correctgrade), $grade);

        $incorrectanswer = self::make_incorrect_answer($question);
        $grade = $question->grade_response($incorrectanswer);
        $this->assertEquals($this->get_expected_grade($incorrectgrade), $grade);

        $onerowwronganswer = self::make_one_row_wrong_answer($question);
        $grade = $question->grade_response($onerowwronganswer);
        $this->assertEquals($this->get_expected_grade($onerowwronggrade), $grade);

        $halfcorrectanswer = self::make_half_correct_answer($question);
        $grade = $question->grade_response($halfcorrectanswer);
        $this->assertEquals($this->get_expected_grade($halfcorrectgrade), $grade);

        $incompletepartiallycorrectanswer = self::make_incomplete_partially_correct_answer($question);
        $grade = $question->grade_response($incompletepartiallycorrectanswer);
        $this->assertEquals($this->get_expected_grade($incompletepartiallycorrectgrade), $grade);

        $incompletewronganswer = self::make_incomplete_wrong_answer($question);
        $grade = $question->grade_response($incompletewronganswer);
        $this->assertEquals($this->get_expected_grade($incompleteincorrectgrade), $grade);
    }

    private function get_expected_grade(float $gradenumber):array {
        if ($gradenumber == 0) {
            $state = question_state::$gradedwrong;
        } else if ($gradenumber == 1) {
            $state = question_state::$gradedright;
        } else {
            $state = question_state::$gradedpartial;
        }
        return [$gradenumber, $state];
    }
    /**
     * Provides data for test_grade_response().
     *
     * @return array of data for function
     */
    public static function grade_response_data_provider(): array {
        // correct, one row wrong, incorrect, half correct, incomplete partially correct, incomplete wrong
        return [
            'Default question, kprime grading' => ['default', kprime::get_name(), 1, 0, 0, 0, 0, 0],
            'Default question, kany grading' => ['default', kany::get_name(), 1, 0, 0.5, 0, 0, 0],
            'Default question, all grading' => ['default', all::get_name(), 1, 0, 0.75, 0.5, 0.5, 0],
            'Default question, difference grading' => ['nondefault', difference::get_name(), 1, 0, 0.75, 0.5, 0.5, 0],
            'Nondefault question, kprime grading' => ['nondefault', kprime::get_name(), 1, 0, 0, 0, 0, 0],
            'Nondefault question, kany grading' => ['nondefault', kany::get_name(), 1, 0, 0.5, 0, 0, 0],
            'Nondefault question, all grading' => ['nondefault', all::get_name(), 1, 0, 0.75, 0.5, 0.5, 0],
            'Nondefault question, difference grading' => ['nondefault', difference::get_name(), 1, 0, 0.75, 0.5, 0.5, 0],
            'multipletwocorrect question, kprime grading' => ['multipletwocorrect', kprime::get_name(), 1, 0, 0, 0, 0, 0],
            'multipletwocorrect question, kany grading' => ['multipletwocorrect', kany::get_name(), 1, 0, 0.5, 0, 0, 0],
            'multipletwocorrect question, all grading' => ['multipletwocorrect', all::get_name(), 1, 0, 0.75, 0.25, 0.25, 0],
            'multipletwocorrect question, difference grading' => [
                'multipletwocorrect',
                difference::get_name(),
                1,
                0,
                0.75,
                0.6944444444444444,
                0.4722222222222222,
                0
            ],
        ];
    }
    /**
     * @covers ::get_expected_data
     * @return void
     */
    public function test_is_complete_response(): void {
        $question = qtype_matrix_test_helper::make_question('default');
        $answer = [];
        $this->assertFalse($question->is_complete_response($answer));
        $this->assertNotNull($question->get_validation_error($answer));

        $answer = self::make_correct_answer($question);
        $this->assertTrue($question->is_complete_response($answer));
        $this->assertNull($question->get_validation_error($answer));

        $answer = self::make_incorrect_answer($question);
        $this->assertTrue($question->is_complete_response($answer));
        $this->assertNull($question->get_validation_error($answer));

        $answer = self::make_one_row_wrong_answer($question);
        $this->assertTrue($question->is_complete_response($answer));
        $this->assertNull($question->get_validation_error($answer));

        $answer = self::make_half_correct_answer($question);
        $this->assertTrue($question->is_complete_response($answer));
        $this->assertNull($question->get_validation_error($answer));

        $answer = self::make_incomplete_partially_correct_answer($question);
        $this->assertFalse($question->is_complete_response($answer));
        $this->assertNotNull($question->get_validation_error($answer));

        $answer = self::make_incomplete_wrong_answer($question);
        $this->assertFalse($question->is_complete_response($answer));
        $this->assertNotNull($question->get_validation_error($answer));

        $question = qtype_matrix_test_helper::make_question('nondefault');

        $answer = [];
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_correct_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_incorrect_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_one_row_wrong_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_half_correct_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_incomplete_partially_correct_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_incomplete_wrong_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

    }

    public function test_get_num_selected_choices():void {
        $question = qtype_matrix_test_helper::make_question('default');

        $answer = self::make_correct_answer($question);
        $this->assertEquals(count($question->rows), $question->get_num_selected_choices($answer));
        $answer = self::make_incorrect_answer($question);
        $this->assertEquals(count($question->rows), $question->get_num_selected_choices($answer));
        $answer = self::make_one_row_wrong_answer($question);
        $this->assertEquals(count($question->rows), $question->get_num_selected_choices($answer));
        $answer = self::make_half_correct_answer($question);
        $this->assertEquals(count($question->rows), $question->get_num_selected_choices($answer));
        $answer = self::make_incomplete_partially_correct_answer($question);
        $this->assertEquals(count($question->rows) - 2, $question->get_num_selected_choices($answer));
        $answer = self::make_incomplete_wrong_answer($question);
        $this->assertEquals(count($question->rows) - 2, $question->get_num_selected_choices($answer));

        $question = qtype_matrix_test_helper::make_question('multipletwocorrect');
        $answer = self::make_correct_answer($question);
        $this->assertEquals(8, $question->get_num_selected_choices($answer));
        $answer = self::make_incorrect_answer($question);
        $this->assertEquals(4, $question->get_num_selected_choices($answer));
        $answer = self::make_one_row_wrong_answer($question);
        $this->assertEquals(7, $question->get_num_selected_choices($answer));
        $answer = self::make_half_correct_answer($question);
        $this->assertEquals(6, $question->get_num_selected_choices($answer));
        $answer = self::make_incomplete_partially_correct_answer($question);
        $this->assertEquals(4, $question->get_num_selected_choices($answer));
        $answer = self::make_incomplete_wrong_answer($question);
        $this->assertEquals(2, $question->get_num_selected_choices($answer));
    }

    public function test_is_gradable_response():void {
        $question = qtype_matrix_test_helper::make_question('default');
        foreach (qtype_matrix_grading::VALID_GRADINGS as $validgrading) {
            $question->grademethod = $validgrading;
            $answer = self::make_correct_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = self::make_incorrect_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = self::make_one_row_wrong_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = self::make_half_correct_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = self::make_incomplete_partially_correct_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = self::make_incomplete_wrong_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = [];
            $this->assertFalse($question->is_gradable_response($answer));
        }
    }

    /**
     * @covers ::get_expected_data
     * @return void
     */
    public function test_summarise_response(): void {
        $question = qtype_matrix_test_helper::make_question('default');
        $answer = self::make_correct_answer($question);
        $summary = $question->summarise_response($answer);
        foreach ($question->rows as $row) {
            $key = $question->key($row, 0);
            if (isset($answer[$key])) {
                $colid = $answer[$key];
                $this->assertStringContainsString($row->shorttext.': '.$question->cols[$colid]->shorttext, $summary);
            } else {
                $this->assertStringNotContainsString($row->shorttext.': '.$question->cols[$colid]->shorttext, $summary);
            }
        }
        $answer = self::make_incomplete_wrong_answer($question);
        $summary = $question->summarise_response($answer);
        foreach ($question->rows as $row) {
            $key = $question->key($row, 0);
            if (isset($answer[$key])) {
                $colid = $answer[$key];
                $this->assertStringContainsString($row->shorttext.': '.$question->cols[$colid]->shorttext, $summary);
            } else {
                $this->assertStringNotContainsString($row->shorttext.': '.$question->cols[$colid]->shorttext, $summary);
            }
        }

        $question = qtype_matrix_test_helper::make_question('nondefault');

        $answer = self::make_correct_answer($question);
        $summary = $question->summarise_response($answer);
        foreach ($question->rows as $row) {
            foreach ($question->cols as $col) {
                $key = $question->key($row, $col);
                if (isset($answer[$key])) {
                    $this->assertStringContainsString($row->shorttext.': '.$col->shorttext, $summary);
                } else {
                    $this->assertStringNotContainsString($row->shorttext.': '.$col->shorttext, $summary);
                }
            }
        }

    }

    /**
     * @covers ::get_expected_data
     * @return void
     */
    public function test_is_same_response(): void {
        $question = qtype_matrix_test_helper::make_question('default');

        $correct = $question->get_correct_response();
        $answer = self::make_correct_answer($question);
        $this->assertTrue($question->is_same_response($correct, $answer));

        $answer = self::make_incorrect_answer($question);
        $this->assertFalse($question->is_same_response($correct, $answer));

        $nextanswer = $answer;
        unset($nextanswer['cell3']);
        $this->assertFalse($question->is_same_response($answer, $nextanswer));

        $nextanswer = $answer;
        $nextanswer['cell3'] = $nextanswer['cell3'] - 1;
        $this->assertFalse($question->is_same_response($answer, $nextanswer));

        $question = qtype_matrix_test_helper::make_question('nondefault');

        $correct = $question->get_correct_response();
        $answer = self::make_correct_answer($question);
        $this->assertTrue($question->is_same_response($correct, $answer));

        $answer = self::make_incorrect_answer($question);
        $this->assertFalse($question->is_same_response($correct, $answer));

        $nextanswer = $answer;
        unset($nextanswer['cell3_3']);
        $this->assertFalse($question->is_same_response($answer, $nextanswer));

        $nextanswer = $answer;
        unset($nextanswer['cell3_3']);
        $nextanswer['cell3_2'] = true;
        $this->assertFalse($question->is_same_response($answer, $nextanswer));

        $nextanswer = $answer;
        $nextanswer['cell3_3'] = false;
        $this->assertFalse($question->is_same_response($answer, $nextanswer));

    }

    /**
     * @covers ::get_expected_data
     * @return void
     */
    public function test_get_correct_response():void {
        $question = qtype_matrix_test_helper::make_question('default');

        $answer = self::make_correct_answer($question);
        $this->assertEquals($answer, $question->get_correct_response());

        $answer = self::make_incorrect_answer($question);
        $this->assertNotEquals($answer, $question->get_correct_response());

        $question = qtype_matrix_test_helper::make_question('nondefault');

        $answer = self::make_correct_answer($question);
        $this->assertEquals($answer, $question->get_correct_response());

        $answer = self::make_incorrect_answer($question);
        $this->assertNotEquals($answer, $question->get_correct_response());
    }

    public function test_get_expected_data():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);
        $expected = array_fill_keys([
            'cell0',
            'cell1',
            'cell2',
            'cell3'
        ], PARAM_INT
        );
        $this->assertEquals($expected, $question->get_expected_data());

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $this->initialize_order($question);
        $expected = array_fill_keys([
            'cell0_0','cell0_1','cell0_2','cell0_3',
            'cell1_0','cell1_1','cell1_2','cell1_3',
            'cell2_0','cell2_1','cell2_2','cell2_3',
            'cell3_0','cell3_1','cell3_2','cell3_3',
            ],
            PARAM_BOOL
        );
        $this->assertEquals($expected, $question->get_expected_data());
    }

    // TODO: This test currently works if we assume the way the function is useful.
    public function test_cells():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);

        $expected_cells = ['cell0','cell1','cell2','cell3'];
        $this->assertEquals($expected_cells, array_keys($question->cells()));

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $this->initialize_order($question);
        $expected_cells = [
            'cell0_0','cell0_1','cell0_2','cell0_3',
            'cell1_0','cell1_1','cell1_2','cell1_3',
            'cell2_0','cell2_1','cell2_2','cell2_3',
            'cell3_0','cell3_1','cell3_2','cell3_3',
        ];
        $this->assertEquals($expected_cells, array_keys($question->cells()));
    }

    public function test_classify_response():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);
        $answer = self::make_correct_answer($question);
        $classifiedresponse = [
            0 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            1 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            2 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            3 => new question_classified_response(1, $question->cols[1]->shorttext, 1)
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_incorrect_answer($question);
        $classifiedresponse = [
            0 => new question_classified_response(3, $question->cols[3]->shorttext, 0),
            1 => new question_classified_response(3, $question->cols[3]->shorttext, 0),
            2 => new question_classified_response(3, $question->cols[3]->shorttext, 0),
            3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_one_row_wrong_answer($question);
        $classifiedresponse = [
            0 => new question_classified_response(3, $question->cols[3]->shorttext, 0),
            1 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            2 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            3 => new question_classified_response(1, $question->cols[1]->shorttext, 1)
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_half_correct_answer($question);
        $classifiedresponse = [
            0 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            1 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            2 => new question_classified_response(3, $question->cols[3]->shorttext, 0),
            3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_incomplete_partially_correct_answer($question);
        $classifiedresponse = [
            0 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            1 => new question_classified_response(1, $question->cols[1]->shorttext, 1),
            2 => question_classified_response::no_response(),
            3 => question_classified_response::no_response()
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_incomplete_wrong_answer($question);
        $classifiedresponse = [
            0 => new question_classified_response(3, $question->cols[3]->shorttext, 0),
            1 => new question_classified_response(3, $question->cols[3]->shorttext, 0),
            2 => question_classified_response::no_response(),
            3 => question_classified_response::no_response()
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $this->initialize_order($question);
        $answer = self::make_correct_answer($question);
        $classifiedresponse = [
            0 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            1 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            2 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            3 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)]
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_incorrect_answer($question);
        $classifiedresponse = [
            0 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)],
            1 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)],
            2 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)],
            3 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)]
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_one_row_wrong_answer($question);
        $classifiedresponse = [
            0 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)],
            1 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            2 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            3 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)]
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_half_correct_answer($question);
        $classifiedresponse = [
            0 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            1 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            2 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)],
            3 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)]
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_incomplete_partially_correct_answer($question);
        $classifiedresponse = [
            0 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            1 => [1 => new question_classified_response(1, $question->cols[1]->shorttext, 0.25)],
            2 => question_classified_response::no_response(),
            3 => question_classified_response::no_response()
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = self::make_incomplete_wrong_answer($question);
        $classifiedresponse = [
            0 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)],
            1 => [3 => new question_classified_response(3, $question->cols[3]->shorttext, 0)],
            2 => question_classified_response::no_response(),
            3 => question_classified_response::no_response()
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));
    }

    private function initialize_order(qtype_matrix_question $question):void {
        $qa = new question_attempt_step();
        $qa->set_qt_var($question::KEY_ROWS_ORDER, '0,1,2,3');
        $mockedAttempt = $this->createStub('question_attempt');
        $mockedAttempt->method('get_step')->willReturn($qa);
        $question->get_order($mockedAttempt);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_correct_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
            case 'nondefault':
                $answermatrix[0] = [0,1,0,0];
                $answermatrix[1] = [0,1,0,0];
                $answermatrix[2] = [0,1,0,0];
                $answermatrix[3] = [0,1,0,0];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [1,1,0,0];
                $answermatrix[1] = [1,1,0,0];
                $answermatrix[2] = [1,1,0,0];
                $answermatrix[3] = [1,1,0,0];
                break;
        }
        return self::build_answer_with_matrix($question, $answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_incorrect_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        $answermatrix[0] = [0,0,0,1];
        $answermatrix[1] = [0,0,0,1];
        $answermatrix[2] = [0,0,0,1];
        $answermatrix[3] = [0,0,0,1];
        return self::build_answer_with_matrix($question, $answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_one_row_wrong_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
            case 'nondefault':
                $answermatrix[0] = [0,0,0,1];
                $answermatrix[1] = [0,1,0,0];
                $answermatrix[2] = [0,1,0,0];
                $answermatrix[3] = [0,1,0,0];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [0,0,0,1];
                $answermatrix[1] = [1,1,0,0];
                $answermatrix[2] = [1,1,0,0];
                $answermatrix[3] = [1,1,0,0];
                break;
        }
        return self::build_answer_with_matrix($question, $answermatrix);
    }

    /**
     * Produces a partially correct answer, all possible variations.
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_half_correct_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
            case 'nondefault':
                $answermatrix[0] = [0,1,0,0];
                $answermatrix[1] = [0,1,0,0];
                $answermatrix[2] = [0,0,0,1];
                $answermatrix[3] = [0,0,0,1];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [1,1,0,0];
                $answermatrix[1] = [0,1,0,1];
                $answermatrix[2] = [0,1,0,0];
                $answermatrix[3] = [0,0,0,1];
                break;
        }
        return self::build_answer_with_matrix($question, $answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_incomplete_partially_correct_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
            case 'nondefault':
                $answermatrix[0] = [0,1,0,0];
                $answermatrix[1] = [0,1,0,0];
                $answermatrix[2] = [0,0,0,0];
                $answermatrix[3] = [0,0,0,0];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [1,1,0,0];
                $answermatrix[1] = [0,1,0,1];
                $answermatrix[2] = [0,0,0,0];
                $answermatrix[3] = [0,0,0,0];
                break;
        }
        return self::build_answer_with_matrix($question, $answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_incomplete_wrong_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        $answermatrix[0] = [0,0,0,1];
        $answermatrix[1] = [0,0,0,1];
        $answermatrix[2] = [0,0,0,0];
        $answermatrix[3] = [0,0,0,0];
        return self::build_answer_with_matrix($question, $answermatrix);
    }

    private static function build_answer_with_matrix(qtype_matrix_question $question, array $matrix):array {
        $answer = [];
        foreach ($matrix as $rowindex => $cols) {
            foreach ($cols as $colindex => $colvalue) {
                if ($colvalue > 0) {
                    $key = $question->key($rowindex, $colindex);
                    $answer[$key] = $question->multiple ? true : $colindex;
                }
            }
        }
        return $answer;
    }
}
