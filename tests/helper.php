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
 * Test helpers for the truefalse question type.
 */

/**
 * Test helper class for the matrix question type.
 */
class qtype_matrix_test_helper extends question_test_helper {

    public function get_test_questions(): array {
        return ['kprime', 'all', 'any', 'none', 'weighted', 'multiple', 'single'];
    }

    public function get_matrix_question_form_data_kprime() {
        $question = self::make_matrix_question_kprime();
        $form = new stdClass();
        $form->name = $question->name;
        $form->questiontext = [];
        $form->questiontext['format'] = '1';
        $form->questiontext['text'] = $question->questiontext;

        $form->generalfeedback = [];
        $form->generalfeedback['format'] = '1';
        $form->generalfeedback['text'] = $question->generalfeedback;

        $form->defaultmark = $question->defaultmark;
        $form->penalty = $question->penalty;

        $form->multiple = $question->multiple;
        $form->grademethod = $question->grademethod;
        $form->usedndui = $question->usedndui;
        $form->shuffleanswers = $question->shuffleanswers;

        foreach ($question->rows as $index => $row) {
            $id = 'rows_shorttext['.$index.']';
            $form->{$id} = [];
            // TODO: Right format for shorttext?
            $form->{$id}['format'] = '1';
            $form->{$id}['text'] = $row->shorttext;
            $id = 'rows_description['.$index.']';
            $form->{$id} = [];
            $form->{$id}['format'] = '1';
            $form->{$id}['text'] = $row->description;
            $id = 'rows_feedback['.$index.']';
            $form->{$id} = [];
            $form->{$id}['format'] = '1';
            $form->{$id}['text'] = $row->feedback;
        }
        foreach ($question->cols as $index => $col) {
            $id = 'cols_shorttext['.$index.']';
            $form->{$id} = [];
            // TODO: Right format for shorttext?
            $form->{$id}['format'] = '1';
            $form->{$id}['text'] = $col->shorttext;
            $id = 'cols_description['.$index.']';
            $form->{$id} = [];
            $form->{$id}['format'] = '1';
            $form->{$id}['text'] = $col->description;
        }
        foreach ($question->rows as $ri => $row) {
            foreach ($question->cols as $ci => $col) {
                $key = $question->key($ri, $ci, $question->multiple);
                $form->{$key} = $question->weights[$ri][$ci];
            }
        }

        $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $form;
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
        // TODO: Is this the default?
        $question->usedndui = true;
        // TODO: Is this the default?
        $question->shuffleanswers = true;
        $matrix = $this->generate_matrix_question_matrix();
        // TODO: Arrays are copied, objects are references, this could break when the objects are changed
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
