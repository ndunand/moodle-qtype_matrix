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
 * Test helpers for the matrix question type.
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

use qtype_matrix\local\qtype_matrix_grading;
use qtype_matrix\local\grading\all;
use core_question\local\bank\question_version_status;

require_once $CFG->dirroot . '/question/engine/tests/helpers.php';
require_once $CFG->dirroot . '/question/type/matrix/question.php';
require_once $CFG->dirroot . '/question/type/matrix/questiontype.php';

/**
 * Test helper class for the matrix question type.
 */
class qtype_matrix_test_helper extends question_test_helper {

    public static function build_answer_with_matrix(array $matrix): array {
        $answer = [];
        foreach ($matrix as $rowindex => $cols) {
            foreach ($cols as $colindex => $colvalue) {
                if ($colvalue > 0) {
                    $key = qtype_matrix_question::responsekey($rowindex, $colindex);
                    $answer[$key] = true;
                }
            }
        }
        return $answer;
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    public static function make_correct_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
            case 'nondefault':
                $answermatrix[0] = [0, 1, 0, 0];
                $answermatrix[1] = [0, 1, 0, 0];
                $answermatrix[2] = [0, 1, 0, 0];
                $answermatrix[3] = [0, 1, 0, 0];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [1, 1, 0, 0];
                $answermatrix[1] = [1, 1, 0, 0];
                $answermatrix[2] = [1, 1, 0, 0];
                $answermatrix[3] = [1, 1, 0, 0];
                break;
        }
        return self::build_answer_with_matrix($answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    public static function make_incorrect_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        $answermatrix[0] = [0, 0, 0, 1];
        $answermatrix[1] = [0, 0, 0, 1];
        $answermatrix[2] = [0, 0, 0, 1];
        $answermatrix[3] = [0, 0, 0, 1];
        return self::build_answer_with_matrix($answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    public static function make_first_row_wrong_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
            case 'nondefault':
                $answermatrix[0] = [0, 0, 0, 1];
                $answermatrix[1] = [0, 1, 0, 0];
                $answermatrix[2] = [0, 1, 0, 0];
                $answermatrix[3] = [0, 1, 0, 0];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [0, 0, 0, 1];
                $answermatrix[1] = [1, 1, 0, 0];
                $answermatrix[2] = [1, 1, 0, 0];
                $answermatrix[3] = [1, 1, 0, 0];
                break;
        }
        return self::build_answer_with_matrix($answermatrix);
    }

    /**
     * Produces a complete answer with all possible variations for row selections.
     * @param qtype_matrix_question $question
     * @return array
     */
    public static function make_complete_with_variations_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
            case 'nondefault':
                $answermatrix[0] = [0, 1, 0, 0];
                $answermatrix[1] = [0, 1, 0, 0];
                $answermatrix[2] = [0, 0, 0, 1];
                $answermatrix[3] = [0, 0, 0, 1];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [1, 1, 0, 0];
                $answermatrix[1] = [0, 1, 0, 1];
                $answermatrix[2] = [0, 1, 0, 0];
                $answermatrix[3] = [0, 0, 0, 1];
                break;
        }
        return self::build_answer_with_matrix($answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    public static function make_incomplete_partially_correct_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
            case 'nondefault':
                $answermatrix[0] = [0, 1, 0, 0];
                $answermatrix[1] = [0, 1, 0, 0];
                $answermatrix[2] = [0, 0, 0, 0];
                $answermatrix[3] = [0, 0, 0, 0];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [1, 1, 0, 0];
                $answermatrix[1] = [0, 1, 0, 1];
                $answermatrix[2] = [0, 0, 0, 0];
                $answermatrix[3] = [0, 0, 0, 0];
                break;
        }
        return self::build_answer_with_matrix($answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    public static function make_incomplete_wrong_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        $answermatrix[0] = [0, 0, 0, 1];
        $answermatrix[1] = [0, 0, 0, 1];
        $answermatrix[2] = [0, 0, 0, 0];
        $answermatrix[3] = [0, 0, 0, 0];
        return self::build_answer_with_matrix($answermatrix);
    }

    /**
     * @param qtype_matrix_question $question
     * @return array
     */
    public static function make_kany_answer(qtype_matrix_question $question): array {
        $answermatrix = [];
        switch ($question->questiontext) {
            case 'default':
                $answermatrix[0] = [0, 1, 0, 0];
                $answermatrix[1] = [1, 0, 0, 0];
                $answermatrix[2] = [0, 0, 1, 0];
                $answermatrix[3] = [0, 0, 0, 0];
                break;
            case 'nondefault':
                $answermatrix[0] = [0, 1, 0, 0];
                $answermatrix[1] = [1, 1, 0, 0];
                $answermatrix[2] = [0, 1, 1, 0];
                $answermatrix[3] = [0, 0, 1, 0];
                break;
            case 'multipletwocorrect':
                $answermatrix[0] = [1, 1, 0, 0];
                $answermatrix[1] = [0, 1, 0, 1];
                $answermatrix[2] = [1, 0, 1, 0];
                $answermatrix[3] = [0, 1, 0, 0];
                break;
        }
        return self::build_answer_with_matrix($answermatrix);
    }

    public function get_test_questions():array {
        return ['default', 'nondefault', 'multipletwocorrect'];
    }

    public function get_matrix_question_data_default():stdClass {
        $question = self::make_matrix_question_default();
        $questiondata = self::transform_generated_question_to_question_data($question);

        return $questiondata;
    }

    public function get_matrix_question_data_nondefault():stdClass {
        $question = self::make_matrix_question_nondefault();
        $questiondata = self::transform_generated_question_to_question_data($question);

        return $questiondata;
    }

    public function get_matrix_question_form_data_default():stdClass {
        $question = self::make_matrix_question_default();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_nondefault():stdClass {
        $question = self::make_matrix_question_nondefault();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_multipletwocorrect():stdClass {
        $question = self::make_matrix_question_multipletwocorrect();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    private function transform_generated_question_to_question_data(qtype_matrix_question $question):stdClass {
        $questiondata = new stdClass();
        test_question_maker::initialise_question_data($questiondata);
        $questiondata->name = $question->questiontext;
        $questiondata->generalfeedback = $question->generalfeedback;
        $questiondata->defaultmark = $question->defaultmark;
        $questiondata->penalty = $question->penalty;

        $questiondata->options = new stdClass();

        // This here is necessary because parent::export_to_xml() expects something
        // which is normally created when using parent::get_question_options().
        $questiondata->options->answers = [];

        $questiondata->options->multiple = $question->multiple;
        $questiondata->options->grademethod = $question->grademethod;
        $questiondata->options->usedndui = $question->usedndui;
        $questiondata->options->shuffleanswers = $question->shuffleanswers;

        $questiondata->options->rows = [];
        foreach ($question->rows as $row) {
            $optionrow = new stdClass();
            $optionrow->id = $row->id;
            $optionrow->shorttext = $row->shorttext;
            $optionrow->description = [];
            $optionrow->description['text'] = $row->description['text'];
            $optionrow->description['format'] = $row->description['format'];
            $optionrow->feedback['text'] = $row->feedback['text'];
            $optionrow->feedback['format'] = $row->feedback['format'];;
            $questiondata->options->rows[$row->id] = $optionrow;
        }
        $questiondata->options->cols = [];
        foreach ($question->cols as $col) {
            $optioncol = new stdClass();
            $optioncol->id = $col->id;
            $optioncol->shorttext = $col->shorttext;
            $optioncol->description = [];
            $optioncol->description['text'] = $col->description['text'];
            $optioncol->description['format'] = $col->description['format'];
            $questiondata->options->cols[$col->id] = $optioncol;
        }
        $questiondata->options->weights = $question->weights;
        return $questiondata;
    }

    private function transform_generated_question_to_form_data(qtype_matrix_question $question):stdClass {
        $form = new stdClass();
        $form->name = $question->name;
        $form->questiontext = [];
        $form->questiontext['format'] = FORMAT_HTML;
        $form->questiontext['text'] = $question->questiontext;

        $form->generalfeedback = [];
        $form->generalfeedback['format'] = FORMAT_HTML;
        $form->generalfeedback['text'] = $question->generalfeedback;

        $form->defaultmark = $question->defaultmark;
        $form->penalty = $question->penalty;

        $form->multiple = $question->multiple;
        $form->grademethod = $question->grademethod;
        $form->usedndui = $question->usedndui;
        $form->shuffleanswers = $question->shuffleanswers;

        $rowindex = 0;
        foreach ($question->rows as $row) {
            $form->rows_shorttext = $form->rows_shorttext ?? [];
            $form->rows_shorttext[$rowindex] = $row->shorttext;
            $form->rows_description = $form->rows_description ?? [];
            $form->rows_description[$rowindex]['text'] = $row->description['text'];
            $form->rows_description[$rowindex]['format'] = $row->description['format'];
            $form->rows_feedback = $form->rows_feedback ?? [];
            $form->rows_feedback[$rowindex]['text'] = $row->feedback['text'];
            $form->rows_feedback[$rowindex]['format'] = $row->feedback['format'];
            $rowindex++;
        }
        $colindex = 0;
        foreach ($question->cols as $col) {
            $form->cols_shorttext = $form->cols_shorttext ?? [];
            $form->cols_shorttext[$colindex] = $col->shorttext;
            $form->cols_description = $form->cols_description ?? [];
            $form->cols_description[$colindex]['text'] = $col->description['text'];
            $form->cols_description[$colindex]['format'] = $col->description['format'];
            $colindex++;
        }
        foreach (array_keys($question->rows) as $rowindex => $rowid) {
            foreach (array_keys($question->cols) as $colindex => $colid) {
                $formfieldname = $question->formfieldname($rowindex, $colindex);
                if ($question->weights[$rowid][$colid]) {
                    $form->{$formfieldname} = $question->multiple ? $question->weights[$rowid][$colid] : $colindex;
                    if (!$question->multiple) {
                        break;
                    }
                }
            }
        }
        return $form;
    }

    /**
     * Single correct. Produced weight matrix:
     * <pre>
     * 0 1 0 0
     * 0 1 0 0
     * 0 1 0 0
     * 0 1 0 0
     * </pre>
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_default():qtype_matrix_question {
        $question = $this->init_default_matrix_question();

        $question->questiontext = 'default';
        $question->weights[4][9] = 1;
        $question->weights[5][9] = 1;
        $question->weights[6][9] = 1;
        $question->weights[7][9] = 1;

        return $question;
    }

    /**
     * Multiple may be correct, but only one is. Produced weight matrix:
     * <pre>
     * 0 1 0 0
     * 0 1 0 0
     * 0 1 0 0
     * 0 1 0 0
     * </pre>
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_nondefault():qtype_matrix_question {
        $question = $this->make_matrix_question_default();
        $question->questiontext = 'nondefault';

        $question->grademethod = all::get_name();
        $question->multiple = !qtype_matrix::DEFAULT_MULTIPLE;
        $question->shuffleanswers = !qtype_matrix::DEFAULT_SHUFFLEANSWERS;
        $question->usedndui = !qtype_matrix::DEFAULT_USEDNDUI;
        return $question;
    }

    /**
     * Multiple may be correct, and two are. Produced weight matrix:
     * <pre>
     * 1 1 0 0
     * 1 1 0 0
     * 1 1 0 0
     * 1 1 0 0
     * </pre>
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_multipletwocorrect():qtype_matrix_question {
        $question = $this->make_matrix_question_default();
        $question->questiontext = 'multipletwocorrect';

        $question->multiple = true;
        $question->weights[4][8] = 1;
        $question->weights[4][9] = 1;
        $question->weights[5][8] = 1;
        $question->weights[5][9] = 1;
        $question->weights[6][8] = 1;
        $question->weights[6][9] = 1;
        $question->weights[7][8] = 1;
        $question->weights[7][9] = 1;
        return $question;
    }
    /**
     * @return \qtype_matrix_question
     * @throws \coding_exception
     */
    public function init_default_matrix_question():qtype_matrix_question {
        question_bank::load_question_definition_classes('matrix');
        $question = new qtype_matrix_question();
        test_question_maker::initialise_a_question($question);
        $question->name = 'Matrix question';
        $question->questiontext = 'K prime graded question.';
        $question->generalfeedback = 'First column is true.';
        $question->defaultmark = 1;
        $question->penalty = 1;
        $question->qtype = question_bank::get_qtype('matrix');

        $question->grademethod = qtype_matrix_grading::default_grading()->get_name();
        $question->multiple = qtype_matrix::DEFAULT_MULTIPLE;
        $question->shuffleanswers = qtype_matrix::DEFAULT_SHUFFLEANSWERS;
        $question->usedndui = qtype_matrix::DEFAULT_USEDNDUI;
        $matrix = $this->generate_matrix_question_matrix();
        $question->rows = $matrix->rows;
        $question->cols = $matrix->cols;
        $question->weights = array_fill(
            4,
            4,
            array_fill(8, 4, 0)
        );

        return $question;
    }

    public function generate_matrix_question_matrix():stdClass {
        $matrix = (object) [];
        $matrix->rows = [];
        for ($r = 4; $r < 8; $r++) {
            $matrix->rows[$r] = $this->generate_matrix_row_or_column($r, true);
        }
        $matrix->cols = [];
        for ($c = 8; $c < 12; $c++) {
            $matrix->cols[$c] = $this->generate_matrix_row_or_column($c, false);
        }
        return $matrix;
    }

    public function generate_matrix_row_or_column(int $id, bool $row):stdClass {
        $roworcolumn = (object) [];
        $roworcolumn->id = $id;
        $roworcolumn->shorttext = ($row ? "Row " : "Column ").$id;
        $roworcolumn->description = [
            'text' => 'Description '.$id,
            'format' => FORMAT_HTML
        ];
        if ($row) {
            $roworcolumn->feedback = [
                'text' => 'Feedback '.$id,
                'format' => FORMAT_HTML
            ];
        }
        return $roworcolumn;
    }

    /**
     * @param string $type
     * @return qtype_matrix_question the requested question object.
     */
    public static function make_question(string $type):qtype_matrix_question {
        /** @var qtype_matrix_question $question */
        $question = test_question_maker::make_question('matrix', $type);
        return $question;
    }

}
