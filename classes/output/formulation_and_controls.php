<?php
/**
 * Author: Daniel Poggenpohl
 * Date: 02.01.2026
 */

namespace qtype_matrix\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use qtype_matrix\local\lang;
use qtype_matrix\local\setting;
use qtype_matrix_question;
use question_attempt;
use question_display_options;
use question_state;
use html_writer;

class formulation_and_controls implements renderable, templatable {

    protected question_attempt $qa;

    protected question_display_options $options;
    public function __construct(question_attempt $qa, question_display_options $options) {
        $this->qa = $qa;
        $this->options = $options;
    }

    public function export_for_template(renderer_base $output) {
        /** @var qtype_matrix_question $question */
        $question = $this->qa->get_question();
        $showfeedback = $this->options->correctness ?? false;
        $context = [];
        $context['multiple'] = $question->multiple;
        $context['isreadonly'] = $this->options->readonly;
        $context['usedndui'] = (setting::allow_dnd_ui() && $question->usedndui);
        $context['showfeedback'] = $showfeedback;
        // FIXME: I think this (expiredquestion) is no longer possible in Moodle
        // TODO: this is somehow possible since a preview is not a real attempt and therefore it can update the
        // question and it will take away the rows and this will trigger an error her so we skip these.
        $context['expiredquestion'] = (count($question->rows) == 0);
        if ($this->qa->get_state() == question_state::$invalid) {
            $context['errormessage'] = $question->get_validation_error($this->qa->get_last_qt_data());
        }
        $context['questiontext'] = $question->format_questiontext($this->qa);
        $context['answerheaders'] = [];
        // Context for the answer headers
        foreach ($question->cols as $col) {
            $context['answerheaders'][] = $this->headercontext($col);
        }
        $context['rows'] = [];

        $order = $question->get_order($this->qa);
        $response = $this->qa->get_last_qt_data();
        $nrrows = count($order);
        foreach ($order as $rowindex => $rowid) {
            $rowcontext = [];
            $row = $question->rows[$rowid];
            $rowcontext['header'] = $this->headercontext($row);
            $rowcontext['cells'] = [];
            $colindex = 0;
            foreach ($question->cols as $colid => $col) {
                $cellfullname = $question::responsekey($rowindex, $colindex);
                $responseparamname = $this->qa->get_field_prefix() . $cellfullname;
                $formfieldname = $this->qa->get_field_prefix() . $question->formfieldname($rowindex, $colindex);
                $ischecked = $question->response($response, $rowindex, $colindex);

                $cellcontext = [];
                $cellcontext['formfieldname'] = $formfieldname;
                $cellcontext['cellclass'] = $cellfullname;
                $cellcontext['ischecked'] = $ischecked;
                $cellcontext['rowindex'] = $rowindex;
                $cellcontext['colindex'] = $colindex;
                $cellcontext['responseparamname'] = $responseparamname;
                $a = [
                    'itemshorttext' => $row->shorttext,
                    'answershorttext' => $col->shorttext
                ];
                $cellcontext['arialabel'] = lang::get('cellarialabel', (object) $a);

                if ($showfeedback) {
                    $weight = $question->weight($rowid, $colid);
                    // Don't display whether a not selected cell should not be selected (useless info).
                    // Just display whether a not selected cell should have been selected.
                    if ($ischecked || question_state::graded_state_for_fraction($weight)->is_correct()) {
                        $cellcontext['feedbackimage'] = $output->feedback_image($weight);
                    }
                }
                $rowcontext['cells'][] = $cellcontext;
                $colindex++;
            }
            if ($showfeedback) {
                // feedback for the row in the final column
                $rowgrade = $question->grading()->grade_row($question, $rowindex, $response);
                $feedback = $row->feedback['text'];
                $feedback = strip_tags($feedback) ? format_text($feedback) : '';
                $rowcontext['feedback'] = $output->feedback_image($rowgrade) . $feedback;
            }
            if ($rowindex == ($nrrows - 1)) {
                $rowcontext['lastrow'] = true;
            }
            $context['rows'][] = $rowcontext;
        }

        return $context;
    }

    private function headercontext($roworcol):array {
        $headercontext = [];
        $headercontext['descriptionid'] = html_writer::random_id();
        $headercontext['shorttext'] = format_text($roworcol->shorttext);
        $description = $roworcol->description['text'];
        if (strip_tags($description)) {
            // TODO: Why do we remove an initial and final <p> tag?
            $description = preg_replace('-^<p>-', '', $description);
            $description = preg_replace('-</p>$-', '', $description);
            $headercontext['description'] = format_text($description);
        }
        return $headercontext;
    }
}
