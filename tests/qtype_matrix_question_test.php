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
use qtype_matrix_test_helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot . '/question/engine/tests/helpers.php';
require_once $CFG->dirroot . '/question/engine/questionattempt.php';
require_once $CFG->dirroot . '/question/engine/questionattemptstep.php';
require_once $CFG->dirroot . '/question/type/matrix/question.php';
require_once $CFG->dirroot . '/question/type/matrix/tests/helper.php';

/**
 * @covers \qtype_matrix_question
 * Unit tests for the matrix question definition class.
 *
 */
class qtype_matrix_question_test extends advanced_testcase {

    public function test_response():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $question->multiple = false;
        $response = [
        ];
        $this->assertFalse($question->response($response, 1, 2));
        $response = [
            'row1col1' => true
        ];
        $this->assertFalse($question->response($response, 1, 2));
        $response = [
            'row1col2' => true
        ];
        $this->assertTrue($question->response($response, 1, 2));
        $response = [
            'row0col2' => true,
            'row1col2' => true
        ];
        $this->assertTrue($question->response($response, 1, 2));

        $question->multiple = true;
        $response = [
        ];
        $this->assertFalse($question->response($response, 1, 2));
        $response = [
            'row1col2' => true
        ];
        $this->assertTrue($question->response($response, 1, 2));
        $response = [
            'row0col2' => true
        ];
        $this->assertFalse($question->response($response, 1, 2));
        $response = [
            'row0col1' => true,
            'row1col2' => true
        ];
        $this->assertTrue($question->response($response, 1, 2));
    }

    /**
     * @return void
     */
    public function test_formfield_name():void {
        $this->assertEquals('r0c0',
            qtype_matrix_question::formfield_name(0, 0, true));
        $this->assertEquals('r1c2',
            qtype_matrix_question::formfield_name(1, 2, true));

        $this->assertEquals('r1',
            qtype_matrix_question::formfield_name(1, 2, false));
        $this->assertEquals('r355',
            qtype_matrix_question::formfield_name(355, 123, false));
    }

    /**
     * @return void
     */
    public function test_responsekey():void {
        $this->assertEquals('row0col0',
            qtype_matrix_question::responsekey(0, 0));
        $this->assertEquals('row1col2',
            qtype_matrix_question::responsekey(1, 2));
        $this->assertEquals('row355col123',
            qtype_matrix_question::responsekey(355, 123));
    }

    public function test_answer():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);
        $question->weights[5][10] = 1;
        $this->assertTrue($question->answer(1, 2));
        $question->weights[5][10] = 0;
        $this->assertFalse($question->answer(1, 2));
    }

    public function test_weight():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $question->weights[5][10] = 1;
        $this->assertEquals(1, $question->weight(5, 10));
        // Strangely, this is bad data, but works here.
        $question->weights[5][10] = 2;
        $this->assertEquals(2, $question->weight(5, 10));
    }

    public function test_start_attempt_noshuffle():void {
        $qa = new question_attempt_step();
        $question = qtype_matrix_test_helper::make_question('default');
        $question->shuffleanswers = false;
        $normalrows = [
            4 => 'first',
            5 => 'second',
            6 => 'third',
            7 => 'fourth'
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
            4 => 'first',
            5 => 'second',
            6 => 'third',
            7 => 'fourth'
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

    /**
     * @return void
     * @throws \dml_exception
     */
    public function test_shuffle_answers():void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $question = qtype_matrix_test_helper::make_question('default');

        // $PAGE->cm = NULL
        $this->assertNull($PAGE->cm);
        $question->shuffleanswers = true;
        $this->assertTrue($question->shuffle_answers());
        $question->shuffleanswers = false;
        $this->assertFalse($question->shuffle_answers());

        // $PAGE->cm = 'foobar'
        $mockedpage = $this->createStub('moodle_page');
        $mockedpage->method('__get')->willReturn('foobar');
        $PAGE = $mockedpage;
        $question->shuffleanswers = true;
        $this->assertTrue($question->shuffle_answers());
        $question->shuffleanswers = false;
        $this->assertFalse($question->shuffle_answers());

        // $PAGE->cm = cm_info mock, NOT quiz
        $mockedcm = $this->createStub('cm_info');
        $cmgetreturns = [
            ['modname', 'foobar'],
            ['instance', 123]
        ];
        $mockedcm->method('__get')->willReturnMap($cmgetreturns);
        $mockedpage = $this->createStub('moodle_page');
        $mockedpage->method('__get')->willReturn($mockedcm);
        $PAGE = $mockedpage;
        $question->shuffleanswers = true;
        $this->assertTrue($question->shuffle_answers());
        $question->shuffleanswers = false;
        $this->assertFalse($question->shuffle_answers());

        // Prepare a quiz record with shuffleanswers = true.
        $this->assertEquals(0, $DB->count_records('quiz'));
        $quizid = $DB->insert_record('quiz', [
            'intro' => 'foobar',
            'shuffleanswers' => true
        ]);
        $this->assertNotEmpty($quizid);
        $this->assertEquals(1, $DB->count_records('quiz'));

        // cm_info mock of the quiz.
        $mockedcm = $this->createStub('cm_info');
        $cmgetreturns = [
            ['modname', 'quiz'],
            ['instance', $quizid]
        ];
        $mockedcm->method('__get')->willReturnMap($cmgetreturns);
        $mockedpage = $this->createStub('moodle_page');
        $mockedpage->method('__get')->willReturn($mockedcm);
        $PAGE = $mockedpage;
        $question->shuffleanswers = true;
        $this->assertTrue($question->shuffle_answers());
        $question->shuffleanswers = false;
        $this->assertFalse($question->shuffle_answers());

        // Change quiz to forbid shuffling
        $this->assertTrue($DB->update_record('quiz', [
            'id' => $quizid,
            'shuffleanswers' => false
        ]));
        $question->shuffleanswers = true;
        $this->assertFalse($question->shuffle_answers());
        $question->shuffleanswers = false;
        $this->assertFalse($question->shuffle_answers());
    }

    public function test_apply_attempt_state():void {
        $qa = new question_attempt_step();
        $question = qtype_matrix_test_helper::make_question('default');
        $question->shuffleanswers = true;
        $normalrows = [
            4 => 'first',
            5 => 'second',
            6 => 'third',
            7 => 'fourth'
        ];
        $question->rows = $normalrows;
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
        $qa->set_qt_var($question::KEY_ROWS_ORDER, '4,5,6,7');
        $mockedAttempt = $this->createStub('question_attempt');
        $mockedAttempt->method('get_step')->willReturn($qa);
        $order = $question->get_order($mockedAttempt);
        $this->assertEquals([4,5,6,7], $order);
        $qa->set_qt_var($question::KEY_ROWS_ORDER, '7,6,5,4');
        $order = $question->get_order($mockedAttempt);
        $this->assertEquals([4,5,6,7], $order);
        $question = qtype_matrix_test_helper::make_question('default');
        $qa->set_qt_var($question::KEY_ROWS_ORDER, '7,6,5,4');
        $order = $question->get_order($mockedAttempt);
        $this->assertEquals([7,6,5,4], $order);
    }

    /**
     * @dataProvider validate_can_regrade_data_provider
     * @return void
     */
    public function test_validate_can_regrade_with_other_version(
        qtype_matrix_question $somequestion,
        qtype_matrix_question $otherquestion,
        bool $canregrade
    ):void {
        if ($canregrade) {
            $this->assertNull($somequestion->validate_can_regrade_with_other_version($otherquestion));
        } else {
            $this->assertNotNull($somequestion->validate_can_regrade_with_other_version($otherquestion));
        }
        $this->assertEquals(
            $somequestion->validate_can_regrade_with_other_version($otherquestion),
            $otherquestion->validate_can_regrade_with_other_version($somequestion)
        );
    }

    public function validate_can_regrade_data_provider() {
        $helper = new qtype_matrix_test_helper();
        $question = qtype_matrix_test_helper::make_question('default');
        $equivalentquestion = qtype_matrix_test_helper::make_question('default');
        $morerowsquestion = qtype_matrix_test_helper::make_question('default');
        $morerowsquestion->rows[12] = $helper->generate_matrix_row_or_column(12, true);
        $lessrowsquestion = qtype_matrix_test_helper::make_question('default');
        unset($lessrowsquestion->rows[4]);
        $morecolsquestion = qtype_matrix_test_helper::make_question('default');
        $morecolsquestion->rows[12] = $helper->generate_matrix_row_or_column(12, false);
        $lesscolsquestion = qtype_matrix_test_helper::make_question('default');
        unset($lesscolsquestion->rows[4]);
        $multiplesamenranswersquestion = qtype_matrix_test_helper::make_question('nondefault');
        // $differentnrofanswersquestion = qtype_matrix_test_helper::make_question('multipletwocorrect');
        return [
            'Same question' => [$question, $question, true],
            'Equivalent question' => [$question, $equivalentquestion, true],
            'More rows question' => [$question, $morerowsquestion, false],
            'Less rows question' => [$question, $lessrowsquestion, false],
            'More columns question' => [$question, $morecolsquestion, false],
            'Less columns question' => [$question, $lesscolsquestion, false],
            'Multiple, same nr of answers per row' => [$question, $multiplesamenranswersquestion, true],
            // FIXME: See function, currently used in a workflow for the same amount of point for all participants
            // 'Multiple, different nr of right answers per row' => [$question, $differentnrofanswersquestion, false],
        ];
    }

    public function test_update_attempt_state_data_for_new_version(): void {
        $helper = new qtype_matrix_test_helper();
        $oldquestion = qtype_matrix_test_helper::make_question('default');
        $newquestion = qtype_matrix_test_helper::make_question('default');
        $newquestion->rows[12] = $helper->generate_matrix_row_or_column(12, true);
        $newquestion->rows[13] = $helper->generate_matrix_row_or_column(13, true);
        $newquestion->rows[14] = $helper->generate_matrix_row_or_column(14, true);
        $newquestion->rows[15] = $helper->generate_matrix_row_or_column(15, true);
        unset($newquestion->rows[4]);
        unset($newquestion->rows[5]);
        unset($newquestion->rows[6]);
        unset($newquestion->rows[7]);
        $firststep = new question_attempt_step([
            '_order' => '4,5,6,7'
        ]);

        $this->assertEquals(['_order' => '12,13,14,15'], $newquestion->update_attempt_state_data_for_new_version($firststep, $oldquestion));

        $firststep = new question_attempt_step([
            '_order' => '7,5,4,6'
        ]);

        $this->assertEquals(['_order' => '15,13,12,14'], $newquestion->update_attempt_state_data_for_new_version($firststep, $oldquestion));
    }

// FIXME: This doesn't need to be tested as long as Matrix questions don't let the user save question hints.
//    public function test_compute_final_grade():void {
//        $question = qtype_matrix_test_helper::make_question('default');
//    }

    /**
     * Tests only the state part, the grades are tested in their respective grading classes.
     * @dataProvider grade_response_data_provider
     * @param string $questiontype
     * @param string $grademethod
     * @param question_state $expectedstateforcorrect
     * @param question_state $expectedstateforincorrect
     * @param question_state $expectedstateforonerowwrong
     * @param question_state $expectedstateforcompletewithrowvariations
     * @param question_state $expectedstateforincompletepartiallycorrect
     * @param question_state $expectedstateforincompleteincorrect
     * @return void
     */
    public function test_grade_response(
        string $questiontype,
        string $grademethod,
        question_state $expectedstateforcorrect,
        question_state $expectedstateforincorrect,
        question_state $expectedstateforonerowwrong,
        question_state $expectedstateforcompletewithrowvariations,
        question_state $expectedstateforincompletepartiallycorrect,
        question_state $expectedstateforincompleteincorrect,
    ): void {
        $question = qtype_matrix_test_helper::make_question($questiontype);
        $this->initialize_order($question);
        $question->grademethod = $grademethod;

        $correctanswer = qtype_matrix_test_helper::make_correct_answer($question);
        $state = $question->grade_response($correctanswer)[1];
        $this->assertEquals($expectedstateforcorrect, $state);

        $incorrectanswer = qtype_matrix_test_helper::make_incorrect_answer($question);
        $state = $question->grade_response($incorrectanswer)[1];
        $this->assertEquals($expectedstateforincorrect, $state);

        $onerowwronganswer = qtype_matrix_test_helper::make_first_row_wrong_answer($question);
        $state = $question->grade_response($onerowwronganswer)[1];
        $this->assertEquals($expectedstateforonerowwrong, $state);

        $completevariationsanswer = qtype_matrix_test_helper::make_complete_with_variations_answer($question);
        $state = $question->grade_response($completevariationsanswer)[1];
        $this->assertEquals($expectedstateforcompletewithrowvariations, $state);

        $incompletepartiallycorrectanswer = qtype_matrix_test_helper::make_incomplete_partially_correct_answer($question);
        $state = $question->grade_response($incompletepartiallycorrectanswer)[1];
        $this->assertEquals($expectedstateforincompletepartiallycorrect, $state);

        $incompletewronganswer = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
        $state = $question->grade_response($incompletewronganswer)[1];
        $this->assertEquals($expectedstateforincompleteincorrect, $state);
    }

    /**
     * Provides data for test_grade_response().
     *
     * @return array of data for function
     */
    public static function grade_response_data_provider(): array {
        // correct, incorrect, one row wrong, complete with row variations, incomplete partially correct, incomplete wrong
        $r = question_state::$gradedright;
        $w = question_state::$gradedwrong;
        $p = question_state::$gradedpartial;
        return [
            'Default question, kprime grading' => ['default', kprime::get_name(), $r, $w, $w, $w, $w, $w],
            'Default question, kany grading' => ['default', kany::get_name(), $r, $w, $p, $w, $w, $w],
            'Default question, all grading' => ['default', all::get_name(), $r, $w, $p, $p, $p, $w],
            'Default question, difference grading' => ['default', difference::get_name(), $r, $w, $p, $p, $p, $w],
            'Nondefault question, kprime grading' => ['nondefault', kprime::get_name(), $r, $w, $w, $w, $w, $w],
            'Nondefault question, kany grading' => ['nondefault', kany::get_name(), $r, $w, $p, $w, $w, $w],
            'Nondefault question, all grading' => ['nondefault', all::get_name(), $r, $w, $p, $p, $p, $w],
            'Nondefault question, difference grading' => ['nondefault', difference::get_name(), $r, $w, $p, $p, $p, $w],
            'multipletwocorrect question, kprime grading' => ['multipletwocorrect', kprime::get_name(), $r, $w, $w, $w, $w, $w],
            'multipletwocorrect question, kany grading' => ['multipletwocorrect', kany::get_name(), $r, $w, $p, $w, $w, $w],
            'multipletwocorrect question, all grading' => ['multipletwocorrect', all::get_name(), $r, $w, $p, $p, $p, $w],
            'multipletwocorrect question, difference grading' => ['multipletwocorrect', difference::get_name(), $r, $w, $p, $p, $p, $w]
        ];
    }
    /**
     * @covers ::get_expected_data
     * @return void
     */
    public function test_is_complete_response(): void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);
        $answer = [];
        $this->assertFalse($question->is_complete_response($answer));
        $this->assertNotNull($question->get_validation_error($answer));

        $answer = qtype_matrix_test_helper::make_correct_answer($question);
        $this->assertTrue($question->is_complete_response($answer));
        $this->assertNull($question->get_validation_error($answer));

        $answer = qtype_matrix_test_helper::make_incorrect_answer($question);
        $this->assertTrue($question->is_complete_response($answer));
        $this->assertNull($question->get_validation_error($answer));

        $answer = qtype_matrix_test_helper::make_first_row_wrong_answer($question);
        $this->assertTrue($question->is_complete_response($answer));
        $this->assertNull($question->get_validation_error($answer));

        $answer = qtype_matrix_test_helper::make_complete_with_variations_answer($question);
        $this->assertTrue($question->is_complete_response($answer));
        $this->assertNull($question->get_validation_error($answer));

        $answer = qtype_matrix_test_helper::make_incomplete_partially_correct_answer($question);
        $this->assertFalse($question->is_complete_response($answer));
        $this->assertNotNull($question->get_validation_error($answer));

        $answer = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
        $this->assertFalse($question->is_complete_response($answer));
        $this->assertNotNull($question->get_validation_error($answer));

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $this->initialize_order($question);

        $answer = [];
        $this->assertTrue($question->is_complete_response($answer));

        $answer = qtype_matrix_test_helper::make_correct_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = qtype_matrix_test_helper::make_incorrect_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = qtype_matrix_test_helper::make_first_row_wrong_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = qtype_matrix_test_helper::make_complete_with_variations_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = qtype_matrix_test_helper::make_incomplete_partially_correct_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
        $this->assertTrue($question->is_complete_response($answer));

    }

    public function test_is_gradable_response():void {
        $question = qtype_matrix_test_helper::make_question('default');
        foreach (qtype_matrix_grading::VALID_GRADINGS as $validgrading) {
            $question->grademethod = $validgrading;
            $answer = qtype_matrix_test_helper::make_correct_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = qtype_matrix_test_helper::make_incorrect_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = qtype_matrix_test_helper::make_first_row_wrong_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = qtype_matrix_test_helper::make_complete_with_variations_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = qtype_matrix_test_helper::make_incomplete_partially_correct_answer($question);
            $this->assertTrue($question->is_gradable_response($answer));
            $answer = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
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
        $order = $this->initialize_order($question);

        $response = qtype_matrix_test_helper::make_correct_answer($question);
        $summary = $question->summarise_response($response);
        $this->check_summary($question, $order, $response, $summary);

        $response = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
        $summary = $question->summarise_response($response);
        $this->check_summary($question, $order, $response, $summary);

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $order = $this->initialize_order($question);

        $response = qtype_matrix_test_helper::make_correct_answer($question);
        $summary = $question->summarise_response($response);
        $this->check_summary($question, $order, $response, $summary);
    }

    private function check_summary(qtype_matrix_question $question, array $order, array $response, string $summary):void {
        $colids = array_keys($question->cols);
        foreach ($order as $rowindex => $rowid) {
            $row = $question->rows[$rowid];
            $shouldcolids = [];
            foreach ($colids as $colindex => $colid) {
                $key = $question::responsekey($rowindex, $colindex);
                if (isset($response[$key])) {
                    $shouldcolids[] = $colid;
                }
            }
            $shouldcolids = array_unique($shouldcolids);
            foreach ($shouldcolids as $shouldcolid) {
                $this->assertStringContainsString(
                    $row->shorttext . ': ' . $question->cols[$shouldcolid]->shorttext, $summary
                );
            }
            $notcolids = array_diff($colids, $shouldcolids);
            foreach ($notcolids as $notcolid) {
                $this->assertStringNotContainsString($row->shorttext.': '.$question->cols[$notcolid]->shorttext, $summary);
            }
        }
    }
    /**
     * @covers ::get_expected_data
     * @return void
     */
    public function test_is_same_response(): void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);

        $correct = $question->get_correct_response();
        $response = qtype_matrix_test_helper::make_correct_answer($question);
        $this->assertTrue($question->is_same_response($correct, $response));

        $response = qtype_matrix_test_helper::make_incorrect_answer($question);
        $this->assertFalse($question->is_same_response($correct, $response));

        $nextresponse = $response;
        unset($nextresponse['row3col3']);
        $this->assertFalse($question->is_same_response($response, $nextresponse));

        $nextresponse = $response;
        unset($nextresponse['row3col3']);
        $nextresponse['row3col2'] = true;
        $this->assertFalse($question->is_same_response($response, $nextresponse));

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $this->initialize_order($question);

        $correct = $question->get_correct_response();
        $response = qtype_matrix_test_helper::make_correct_answer($question);
        $this->assertTrue($question->is_same_response($correct, $response));

        $response = qtype_matrix_test_helper::make_incorrect_answer($question);
        $this->assertFalse($question->is_same_response($correct, $response));

        $nextresponse = $response;
        unset($nextresponse['row3col3']);
        $this->assertFalse($question->is_same_response($response, $nextresponse));

        $nextresponse = $response;
        unset($nextresponse['row3col3']);
        $nextresponse['row3col2'] = true;
        $this->assertFalse($question->is_same_response($response, $nextresponse));

        $nextresponse = $response;
        $nextresponse['row3col3'] = false;
        $this->assertFalse($question->is_same_response($response, $nextresponse));

    }

    /**
     * @covers ::get_expected_data
     * @return void
     */
    public function test_get_correct_response():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);

        $answer = qtype_matrix_test_helper::make_correct_answer($question);
        $this->assertEquals($answer, $question->get_correct_response());

        $answer = qtype_matrix_test_helper::make_incorrect_answer($question);
        $this->assertNotEquals($answer, $question->get_correct_response());

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $this->initialize_order($question);

        $answer = qtype_matrix_test_helper::make_correct_answer($question);
        $this->assertEquals($answer, $question->get_correct_response());

        $answer = qtype_matrix_test_helper::make_incorrect_answer($question);
        $this->assertNotEquals($answer, $question->get_correct_response());
    }

    public function test_get_expected_data():void {
        $expected = array_fill_keys([
            'row0col0','row0col1','row0col2','row0col3',
            'row1col0','row1col1','row1col2','row1col3',
            'row2col0','row2col1','row2col2','row2col3',
            'row3col0','row3col1','row3col2','row3col3',
        ],
            PARAM_BOOL
        );

        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);
        $this->assertEquals($expected, $question->get_expected_data());

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $this->initialize_order($question);
        $this->assertEquals($expected, $question->get_expected_data());
    }

    public function test_classify_response():void {
        $question = qtype_matrix_test_helper::make_question('default');
        $this->initialize_order($question);
        $answer = qtype_matrix_test_helper::make_correct_answer($question);
        $classifiedresponse = [
            4 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            5 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            6 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            7 => new question_classified_response(9, $question->cols[9]->shorttext, 1)
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_incorrect_answer($question);
        $classifiedresponse = [
            4 => new question_classified_response(11, $question->cols[11]->shorttext, 0),
            5 => new question_classified_response(11, $question->cols[11]->shorttext, 0),
            6 => new question_classified_response(11, $question->cols[11]->shorttext, 0),
            7 => new question_classified_response(11, $question->cols[11]->shorttext, 0)
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_first_row_wrong_answer($question);
        $classifiedresponse = [
            4 => new question_classified_response(11, $question->cols[11]->shorttext, 0),
            5 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            6 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            7 => new question_classified_response(9, $question->cols[9]->shorttext, 1)
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_complete_with_variations_answer($question);
        $classifiedresponse = [
            4 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            5 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            6 => new question_classified_response(11, $question->cols[11]->shorttext, 0),
            7 => new question_classified_response(11, $question->cols[11]->shorttext, 0)
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_incomplete_partially_correct_answer($question);
        $classifiedresponse = [
            4 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            5 => new question_classified_response(9, $question->cols[9]->shorttext, 1),
            6 => question_classified_response::no_response(),
            7 => question_classified_response::no_response()
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
        $classifiedresponse = [
            4 => new question_classified_response(11, $question->cols[11]->shorttext, 0),
            5 => new question_classified_response(11, $question->cols[11]->shorttext, 0),
            6 => question_classified_response::no_response(),
            7 => question_classified_response::no_response()
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $question = qtype_matrix_test_helper::make_question('nondefault');
        $this->initialize_order($question);
        $answer = qtype_matrix_test_helper::make_correct_answer($question);
        $classifiedresponse = [
            4 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            5 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            6 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            7 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)]
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_incorrect_answer($question);
        $classifiedresponse = [
            4 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)],
            5 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)],
            6 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)],
            7 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)]
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_first_row_wrong_answer($question);
        $classifiedresponse = [
            4 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)],
            5 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            6 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            7 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)]
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_complete_with_variations_answer($question);
        $classifiedresponse = [
            4 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            5 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            6 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)],
            7 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)]
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_incomplete_partially_correct_answer($question);
        $classifiedresponse = [
            4 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            5 => [9 => new question_classified_response(9, $question->cols[9]->shorttext, 0.25)],
            6 => question_classified_response::no_response(),
            7 => question_classified_response::no_response()
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));

        $answer = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
        $classifiedresponse = [
            4 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)],
            5 => [11 => new question_classified_response(11, $question->cols[11]->shorttext, 0)],
            6 => question_classified_response::no_response(),
            7 => question_classified_response::no_response()
        ];
        $this->assertEquals($classifiedresponse, $question->classify_response($answer));
    }

    private function initialize_order(qtype_matrix_question $question):array {
        $qa = new question_attempt_step();
        $rowids = array_keys($question->rows);
        $qa->set_qt_var($question::KEY_ROWS_ORDER, implode(',', $rowids));
        $mockedAttempt = $this->createStub('question_attempt');
        $mockedAttempt->method('get_step')->willReturn($qa);
        return $question->get_order($mockedAttempt);
    }

}
