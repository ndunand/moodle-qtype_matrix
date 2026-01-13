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

namespace local\grading;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use qtype_matrix\local\grading\difference;
use qtype_matrix_test_helper;
use question_attempt_step;
use testable_question_attempt;

global $CFG;
require_once $CFG->dirroot . '/question/type/matrix/tests/helper.php';

/**
 * Unit tests for the all grading class.
 */
class difference_test extends advanced_testcase {

    /**
     * @dataProvider grade_question_data_provider
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_grade_question(
        string $questiontype,
        float  $correctgrade,
        float  $incorrectgrade,
        float  $onerowwronggrade,
        float  $completevariationsgrade,
        float  $incompletepartiallycorrectgrade,
        float  $incompleteincorrectgrade
    ):void {
        $question = qtype_matrix_test_helper::make_question($questiontype);
        $question->shuffleanswers = false;
        $qa = new testable_question_attempt($question, 0);
        $step = new question_attempt_step();
        $qa->add_step($step);
        $question->start_attempt($step, 1);

        $difference = new difference();

        $response = qtype_matrix_test_helper::make_correct_answer($question);
        $this->assertEquals($correctgrade, $difference->grade_question($question, [4,5,6,7], $response));

        $response = qtype_matrix_test_helper::make_incorrect_answer($question);
        $this->assertEquals($incorrectgrade, $difference->grade_question($question, [4,5,6,7], $response));

        $response = qtype_matrix_test_helper::make_first_row_wrong_answer($question);
        $this->assertEquals($onerowwronggrade, $difference->grade_question($question, [4,5,6,7], $response));

        $response = qtype_matrix_test_helper::make_complete_with_variations_answer($question);
        $this->assertEquals($completevariationsgrade, $difference->grade_question($question, [4,5,6,7], $response));

        $response = qtype_matrix_test_helper::make_incomplete_partially_correct_answer($question);
        $this->assertEquals($incompletepartiallycorrectgrade, $difference->grade_question($question, [4,5,6,7], $response));

        $response = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
        $this->assertEquals($incompleteincorrectgrade, $difference->grade_question($question, [4,5,6,7], $response));
    }

    public function grade_question_data_provider():array {
        // correct, incorrect, one row wrong, complete with variations, incomplete partially correct, incomplete wrong
        return [
            'Default question' => ['default', 1, 0, 0.75, 0.5, 0.5, 0],
            'Nondefault question' => ['nondefault', 1, 0, 0.75, 0.5, 0.5, 0],
            'multipletwocorrect question' => ['multipletwocorrect',
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
     * @dataProvider grade_row_data_provider
     * @param string $questiontype
     * @param array $allrowgrades
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_grade_row(
        string $questiontype,
        array $allrowgrades
    ):void {
        $question = qtype_matrix_test_helper::make_question($questiontype);
        $question->shuffleanswers = false;
        $qa = new \testable_question_attempt($question, 0);
        $step = new question_attempt_step();
        $qa->add_step($step);
        $question->start_attempt($step, 1);

        $difference = new difference();

        $order = [4,5,6,7];
        foreach ($order as $rowindex => $rowid) {
            $rowgrades = $allrowgrades[$rowindex];
            $correctrowgrade = $rowgrades[0];
            $incorrectrowgrade = $rowgrades[1];
            $onerowwrongrowgrade = $rowgrades[2];
            $completerowvariationsgrade = $rowgrades[3];
            $incompletepartiallyrowcorrectgrade = $rowgrades[4];
            $incompleteincorrectrowgrade = $rowgrades[5];
            $wrongmessage = 'Wrong for rowindex '.$rowindex;
            $response = qtype_matrix_test_helper::make_correct_answer($question);
            $this->assertEquals($correctrowgrade, $difference->grade_row($question, $rowindex, $response), $wrongmessage);

            $response = qtype_matrix_test_helper::make_incorrect_answer($question);
            $this->assertEquals($incorrectrowgrade, $difference->grade_row($question, $rowindex, $response), $wrongmessage);

            $response = qtype_matrix_test_helper::make_first_row_wrong_answer($question);
            $this->assertEquals($onerowwrongrowgrade, $difference->grade_row($question, $rowindex, $response), $wrongmessage);

            $response = qtype_matrix_test_helper::make_complete_with_variations_answer($question);
            $this->assertEquals($completerowvariationsgrade, $difference->grade_row($question, $rowindex, $response), $wrongmessage);

            $response = qtype_matrix_test_helper::make_incomplete_partially_correct_answer($question);
            $this->assertEquals($incompletepartiallyrowcorrectgrade, $difference->grade_row($question, $rowindex, $response), $wrongmessage);

            $response = qtype_matrix_test_helper::make_incomplete_wrong_answer($question);
            $this->assertEquals($incompleteincorrectrowgrade, $difference->grade_row($question, $rowindex, $response), $wrongmessage);
        }
    }

    public function grade_row_data_provider() {
        // correct, incorrect, one row wrong, complete with row variations, incomplete partially correct, incomplete wrong
        return [
            'Default question' => ['default', [
                0 => [1, 0, 0, 1, 1, 0],
                1 => [1, 0, 1, 1, 1, 0],
                2 => [1, 0, 1, 0, 0, 0],
                3 => [1, 0, 1, 0, 0, 0],
            ]],
            'Nondefault question' => ['nondefault', [
                0 => [1, 0, 0, 1, 1, 0],
                1 => [1, 0, 1, 1, 1, 0],
                2 => [1, 0, 1, 0, 0, 0],
                3 => [1, 0, 1, 0, 0, 0],
            ]],
            // TODO: This is correct for the way the difference grading currently works
            //       But I am NOT sure whether the difference grading behaves like it should
            'multipletwocorrect question' => ['multipletwocorrect', [
                0 => [1, 0, 0, 1, 1, 0],
                1 => [1, 0, 1, 0.8888888888888888, 0.8888888888888888, 0],
                2 => [1, 0, 1, 0.8888888888888888, 0, 0],
                3 => [1, 0, 1, 0, 0, 0],
            ]],
        ];
    }
}
