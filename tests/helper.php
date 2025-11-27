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

require_once($CFG->dirroot.'/question/engine/tests/helpers.php');

/**
 * Test helper class for the matrix question type.
 */
class qtype_matrix_test_helper extends question_test_helper {

    public function get_test_questions():array {
        return ['kprime', 'all', 'any', 'none', 'weighted', 'multiple', 'single', 'dnd', 'nodnd', 'shuffle', 'noshuffle'];
    }

    public function get_matrix_question_form_data_kprime():stdClass {
        $question = self::make_matrix_question_kprime();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_all():stdClass {
        $question = self::make_matrix_question_all();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_any():stdClass {
        $question = self::make_matrix_question_any();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_none():stdClass {
        $question = self::make_matrix_question_none();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_weighted():stdClass {
        $question = self::make_matrix_question_weighted();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_multiple():stdClass {
        $question = self::make_matrix_question_multiple();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_single():stdClass {
        $question = self::make_matrix_question_single();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_dnd():stdClass {
        $question = self::make_matrix_question_dnd();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_nodnd():stdClass {
        $question = self::make_matrix_question_nodnd();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_shuffle():stdClass {
        $question = self::make_matrix_question_shuffle();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    public function get_matrix_question_form_data_noshuffle():stdClass {
        $question = self::make_matrix_question_noshuffle();
        $form = self::transform_generated_question_to_form_data($question);

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
    }

    private function transform_generated_question_to_form_data($question):stdClass {
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

        $form->rowid = [];
        $form->colid = [];

        foreach ($question->rows as $index => $row) {
            $form->rows_shorttext = $form->rows_shorttext ?? [];
            $form->rows_shorttext[$index] = $row->shorttext;
            $form->rows_description = $form->rows_description ?? [];
            $form->rows_description[$index]['format'] = FORMAT_HTML;
            $form->rows_description[$index]['text'] = $row->description;
            $form->rows_feedback = $form->rows_feedback ?? [];
            $form->rows_feedback[$index]['format'] = FORMAT_HTML;
            $form->rows_feedback[$index]['text'] = $row->feedback;
            $form->rowid[$index] = '';
        }
        foreach ($question->cols as $index => $col) {
            $form->cols_shorttext = $form->cols_shorttext ?? [];
            $form->cols_shorttext[$index] = $col->shorttext;
            $form->cols_description = $form->cols_description ?? [];
            $form->cols_description[$index]['format'] = FORMAT_HTML;
            $form->cols_description[$index]['text'] = $col->description;
            $form->colid[$index] = '';
        }
        foreach ($question->rows as $ri => $row) {
            foreach ($question->cols as $ci => $col) {
                $key = $question->key($ri, $ci, $question->multiple);
                $form->{$key} = $question->weights[$ri][$ci];
            }
        }
        return $form;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_noshuffle(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->shuffleanswers = false;
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_shuffle(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->shuffleanswers = true;
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_nodnd(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->usedndui = false;
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_dnd(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->usedndui = true;
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_multiple(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->multiple = true;
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_single(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->multiple = false;
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_kprime(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->grademethod = 'kprime';
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_all(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->grademethod = 'all';
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_any(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->grademethod = 'any';
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_none(): qtype_matrix_question {
        $result = $this->make_matrix_question();
        $result->grademethod = 'none';
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    public function make_matrix_question_weighted() {
        $question = $this->init_matrix_question();

        for ($r = 0; $r < 4; $r++) {
            for ($c = 0; $c < 4; $c++) {
                $question->weights[$r][$c] = ($c < 2) ? 0.5 : 0;
            }
        }

        $question->grademethod = 'weighted';

        return $question;
    }

    /**
     *
     * @return qtype_matrix_question
     * @throws coding_exception
     */
    protected function make_matrix_question() {
        $question = $this->init_matrix_question();

        for ($r = 0; $r < 4; $r++) {
            for ($c = 0; $c < 4; $c++) {
                $question->weights[$r][$c] = ($c == 0) ? 1 : 0;
            }
        }

        return $question;
    }

    /**
     * @return \qtype_matrix_question
     * @throws \coding_exception
     */
    public function init_matrix_question(): qtype_matrix_question {
        question_bank::load_question_definition_classes('matrix');
        $question = new qtype_matrix_question();
        test_question_maker::initialise_a_question($question);
        $question->name = 'Matrix question';
        $question->questiontext = 'K prime graded question.';
        $question->generalfeedback = 'First column is true.';
        $question->defaultmark = 1;
        $question->penalty = 1;
        $question->qtype = question_bank::get_qtype('matrix');

        $question->grademethod = 'kprime';
        $question->multiple = true;
        $question->shuffleanswers = true;
        $question->usedndui = true;
        $matrix = $this->generate_matrix_question_matrix();
        $question->rows = $matrix->rows;
        $question->cols = $matrix->cols;
        $question->weights = [];
        return $question;
    }

    private function generate_matrix_question_matrix() {
        $matrix = (object) [];
        $matrix->rows = [];
        for ($r = 0; $r < 4; $r++) {
            $matrix->rows[$r] = $this->generate_matrix_row_or_column($r, true);
        }
        $matrix->cols = [];
        for ($c = 0; $c < 4; $c++) {
            $matrix->cols[$c] = $this->generate_matrix_row_or_column($c, true);
        }
        return $matrix;
    }

    private function generate_matrix_row_or_column(int $id, bool $row) {
        $roworcolumn = (object) [];
        $roworcolumn->id = $id;
        $roworcolumn->shorttext = ($row ? "Row " : "Column ").$id;
        $roworcolumn->description = "Description $id";
        if ($row) {
            $roworcolumn->feedback = "Feedback $id";
        }
        return $roworcolumn;
    }
}
