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

use qtype_matrix\local\lang;
use qtype_matrix\local\setting;
use dml_exception;
use html_writer;
use qtype_with_combined_feedback_renderer;
use question_attempt;
use question_display_options;
use question_state;

/**
 * Generates the output for matrix questions.
 */
class renderer extends qtype_with_combined_feedback_renderer {

    /**
     * Generate the display of the formulation part of the question. This is the
     * area that contains the question text, and the controls for students to
     * input their answers. Some question types also embed bits of feedback, for
     * example ticks and crosses, in this area.
     *
     * @param question_attempt         $qa      the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     * @throws dml_exception
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options): string {
        $question = $qa->get_question();
        $showfeedback = $options->correctness ?? false;
        $context = [];
        $context['multiple'] = $question->multiple;
        $context['isreadonly'] = $options->readonly;
        $context['usedndui'] = (setting::allow_dnd_ui() && $question->usedndui);
        $context['showfeedback'] = $showfeedback;
        // TODO: this is somehow possible since a preview is not a real attempt and therefore it can update the
        // question and it will take away the rows and this will trigger an error her so we skip these.
        $context['expiredquestion'] = (count($question->rows) == 0);
        if ($qa->get_state() == question_state::$invalid) {
            $context['errormessage'] = $question->get_validation_error($qa->get_last_qt_data());
        }
        $context['questiontext'] = $question->format_questiontext($qa);
        $context['answerheaders'] = [];
        $lastcol = false;
        // Row header + answer headers + optional feedback header
        $nrcols = 1 + count($question->cols) + (int) $showfeedback;
        // The first header is an always empty one because its the header cell for the row headers
        // Therefore we start the counting at the second one (index wise)
        $colindex = 1;
        // Context for the answer headers
        foreach ($question->cols as $col) {
            if (!$showfeedback && $colindex == ($nrcols - 1)) {
                $lastcol = true;
            }
            $context['answerheaders'][] = $this->headercontext($col, $colindex, $lastcol);
            $colindex++;
        }
        // Finally the optional feedback column header
        if ($showfeedback) {
            $context['feedbackcellclass'] = 'c' . $colindex;
        }
        $context['rows'] = [];

        $order = $question->get_order($qa);
        $response = $qa->get_last_qt_data();
        $nrrows = count($order);
        $currentrow = 1;
        foreach ($order as $rowid) {
            $rowcontext = [];
            $rowcellindex = 0;
            $row = $question->rows[$rowid];
            $rowcontext['header'] = $this->headercontext($row, $rowcellindex++, false);
            $rowcontext['cells'] = [];
            $lastcol = false;
            foreach ($question->cols as $col) {
                if (!$showfeedback && $rowcellindex == ($nrcols - 1)) {
                    $lastcol = true;
                }
                $cellname = $qa->get_field_prefix() . $question->key($row, $col);
                $ischecked = $question->response($response, $row, $col);

                $cellcontext = [];
                $cellcontext['cellname'] = $cellname;
                $cellcontext['cellclass'] = 'c' . $rowcellindex++;
                $cellcontext['ischecked'] = $ischecked;
                $cellcontext['colid'] = $col->id;
                $cellcontext['lastcol'] = $lastcol;
                // Cell for item $row->SHORTTEXT and possible answer $col->shorttext
                $a = [
                    'itemshorttext' => $row->shorttext,
                    'answershorttext' => $col->shorttext
                ];
                $cellcontext['arialabel'] = lang::get('cellarialabel', (object) $a);

                $weight = $question->weight($row, $col);
                if ($showfeedback && ($ischecked || question_state::graded_state_for_fraction($weight)->is_correct())) {
                    $cellcontext['feedbackimage'] = $this->feedback_image($weight);
                }
                $rowcontext['cells'][] = $cellcontext;
            }
            if ($showfeedback) {
                // feedback for the row in the final column
                $rowgrade = $question->grading()->grade_row($question, $row, $response);
                $feedback = $row->feedback['text'];
                $feedback = strip_tags($feedback) ? format_text($feedback) : '';
                $rowcontext['feedback'] = $this->feedback_image($rowgrade) . $feedback;
                $rowcontext['feedbackcellclass'] = 'c' . $rowcellindex;
            }
            if ($currentrow == $nrrows) {
                $rowcontext['lastrow'] = true;
            }
            $currentrow++;
            $context['rows'][] = $rowcontext;
        }

        return $this->render_from_template('qtype_matrix/question', $context);
    }

    private function headercontext($roworcol, int $index, bool $lastcol):array {
        $headercontext = [];
        $headercontext['cellclass'] = 'c'.$index;
        $headercontext['descriptionid'] = html_writer::random_id();
        $headercontext['lastcol'] = $lastcol;
        $headercontext['shorttext'] = format_text($roworcol->shorttext);
        $description = $roworcol->description['text'];
        if (strip_tags($description)) {
            $description = preg_replace('-^<p>-', '', $description);
            $description = preg_replace('-</p>$-', '', $description);
            $headercontext['description'] = format_text($description);
        }
        return $headercontext;
    }
}
