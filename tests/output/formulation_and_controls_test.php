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

namespace qtype_matrix\output;

defined('MOODLE_INTERNAL') || die();

use qtype_matrix_test_helper;
use advanced_testcase;
use question_display_options;
use testable_question_attempt;
use question_attempt_step;

global $CFG;
require_once $CFG->dirroot . '/question/type/matrix/tests/helper.php';

/**
 * Unit tests for the matrix question definition class.
 */
class formulation_and_controls_test extends advanced_testcase {

    /**
     * This is more like a test whether this function will function at all for now.
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_export_for_template():void {
        global $PAGE;
        $question = qtype_matrix_test_helper::make_question('default');
        $qa = new testable_question_attempt($question, 0);
        $stepwithresponse = new question_attempt_step();
        $qa->add_step($stepwithresponse);
        $question->start_attempt($stepwithresponse, 1);

        $options = new question_display_options();
        $options->feedback = question_display_options::VISIBLE;
        // simulate the start of an attempt
        $options->correctness = question_display_options::HIDDEN;
        $options->numpartscorrect = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::VISIBLE;
        $options->rightanswer = question_display_options::VISIBLE;
        $options->manualcomment = question_display_options::VISIBLE;
        $options->history = question_display_options::VISIBLE;

        $displayquestion = new formulation_and_controls($qa, $options);
        $renderer = $PAGE->get_renderer('qtype_matrix');
        $context = $displayquestion->export_for_template($renderer);
        $this->assertNotEquals([], $context);
        $this->assertEquals($question->usedndui, $context['usedndui']);
        foreach ($context['rows'] as $rowcontext) {
            $this->assertFalse(isset($rowcontext['feedback']));
        }
        // No response means only show the icons for correct cells.
        $this->assertFalse(isset($context['rows'][0]['cells'][0]['feedback']));
        $this->assertFalse(isset($context['rows'][0]['cells'][1]['feedback']));

        // Simulate a partial answer
        $question = qtype_matrix_test_helper::make_question('default');
        $qa = new testable_question_attempt($question, 0);
        $stepwithresponse = new question_attempt_step([
            'row0col1' => true,
            'row1col0' => true,
            'row2col2' => true
        ]);
        $qa->add_step($stepwithresponse);
        $question->start_attempt($stepwithresponse, 1);
        // Turn on feedback now.
        $options->correctness = question_display_options::VISIBLE;
        $displayquestion = new formulation_and_controls($qa, $options);
        $context = $displayquestion->export_for_template($renderer);

        foreach ($context['rows'] as $rowcontext) {
            $this->assertNotEmpty($rowcontext['feedback']);
        }
        // Not checked and not correct, so no feedback.
        $this->assertFalse($context['rows'][0]['cells'][0]['ischecked']);
        $this->assertFalse(isset($context['rows'][0]['cells'][0]['feedbackimage']));

        // Checked (and correct), so feedback.
        $this->assertTrue($context['rows'][0]['cells'][1]['ischecked']);
        $this->assertTrue(isset($context['rows'][0]['cells'][1]['feedbackimage']));

        // Checked (and incorrect), so feedback.
        $this->assertTrue($context['rows'][1]['cells'][0]['ischecked']);
        $this->assertTrue(isset($context['rows'][1]['cells'][0]['feedbackimage']));

        // Not checked but correct, so feedback.
        $this->assertFalse($context['rows'][1]['cells'][1]['ischecked']);
        $this->assertTrue(isset($context['rows'][1]['cells'][1]['feedbackimage']));

        // Neither checked nor correct, so no feedback.
        $this->assertFalse($context['rows'][2]['cells'][0]['ischecked']);
        $this->assertFalse(isset($context['rows'][2]['cells'][0]['feedbackimage']));

        // Not checked but correct, so feedback.
        $this->assertFalse($context['rows'][2]['cells'][1]['ischecked']);
        $this->assertTrue(isset($context['rows'][2]['cells'][1]['feedbackimage']));

        // Checked but incorrect, so feedback.
        $this->assertTrue($context['rows'][2]['cells'][2]['ischecked']);
        $this->assertTrue(isset($context['rows'][2]['cells'][2]['feedbackimage']));
    }

}
