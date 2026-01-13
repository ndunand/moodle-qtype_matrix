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

use qtype_matrix\local\grading\difference;
use qtype_matrix\local\lang;
use qtype_matrix\local\matrix_form_builder;
use qtype_matrix\local\setting;

defined('MOODLE_INTERNAL') || die;

global $CFG;

/**
 * The question type class for the matrix question type.
 *
 */
require_once $CFG->dirroot . '/question/type/edit_question_form.php';
require_once $CFG->dirroot . '/question/type/matrix/question.php';
require_once $CFG->dirroot . '/question/type/matrix/questiontype.php';

/**
 * matrix editing form definition. For information about the Moodle forms library,
 * which is based on the HTML Quickform PEAR library
 *
 * @see http://docs.moodle.org/en/Development:lib/formslib.php
 */
class qtype_matrix_edit_form extends question_edit_form {

    const PARAM_COLS = 'cols_shorttext';
    const DEFAULT_COLS = 2;
    const PARAM_ADD_COLUMNS = 'add_cols';
    const PARAM_ROWS = 'rows_shorttext';
    const DEFAULT_ROWS = 4;
    const PARAM_ADD_ROWS = 'add_rows';
    const PARAM_GRADE_METHOD = 'grademethod';
    const PARAM_MULTIPLE = 'multiple';
    const PARAM_USE_DND_UI = 'usedndui';
    const PARAM_SHUFFLE_ANSERS = 'shuffleanswers';

    /**
     *
     * @var matrix_form_builder
     */
    private $builder = null;

    public function qtype(): string {
        return 'matrix';
    }

    /**
     * @param $mform MoodleQuickForm should be MoodleQuickForm but cant type it due the parent function not implementing it.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public function definition_inner($mform): void {
        $this->builder = new matrix_form_builder($mform);
        $builder = $this->builder;

        $this->question->options = $this->question->options ?? (object) [];

        $this->add_multiple();
        $this->add_grading();

        if (setting::allow_dnd_ui()) {
            $builder->add_selectyesno(self::PARAM_USE_DND_UI, lang::use_dnd_ui());
            $builder->set_default(self::PARAM_USE_DND_UI, qtype_matrix::DEFAULT_USEDNDUI);
        }

        $mform->addElement('advcheckbox', self::PARAM_SHUFFLE_ANSERS, lang::shuffle_answers(), null, null, [0,
            1]);
        $builder->add_help_button(self::PARAM_SHUFFLE_ANSERS);
        $builder->set_default(self::PARAM_SHUFFLE_ANSERS, qtype_matrix::DEFAULT_SHUFFLEANSWERS);
    }

    /**
     * @return void
     * @throws coding_exception
     */
    public function add_multiple(): void {
        // Multiple allowed.
        $builder = $this->builder;

        if (setting::show_kprime_gui()) {
            $builder->add_selectyesno(self::PARAM_MULTIPLE, lang::multiple_allowed());
            $builder->set_default(self::PARAM_MULTIPLE, qtype_matrix::DEFAULT_MULTIPLE);
            $builder->register_hook_multiple();
        } else {
            $this->_form->addElement('hidden', self::PARAM_MULTIPLE, qtype_matrix::DEFAULT_MULTIPLE);
            $this->_form->setType(self::PARAM_MULTIPLE, PARAM_BOOL);
        }
    }

    /**
     * @return void
     * @throws coding_exception
     */
    public function add_grading(): void {
        $builder = $this->builder;

        // Grading method.
        $defaultgrading = qtype_matrix::default_grading();
        $defaultgradingname = $defaultgrading->get_name();
        $gradings = qtype_matrix::gradings();

        $radioarray = [];

        foreach ($gradings as $grading) {
            $radioarray[] = &$this->_form->createElement('radio',
                self::PARAM_GRADE_METHOD,
                '',
                $grading->get_title(),
                $grading->get_name(),
                '');
        }

        $this->_form->addGroup($radioarray, self::PARAM_GRADE_METHOD, lang::grade_method(), [
            '<br>'], false);
        $this->_form->setDefault(self::PARAM_GRADE_METHOD, $defaultgradingname);
        $builder->add_help_button(self::PARAM_GRADE_METHOD);
    }

    /**
     * Override if you need to setup the form depending on current values.
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     *
     * @return void
     * @throws coding_exception
     */
    public function definition_after_data(): void {
        $builder = $this->builder;

        $this->add_matrix();
        $builder->add_javascript($this->get_javascript());
    }

    /**
     * @return void
     * @throws coding_exception
     */
    public function add_matrix(): void {
        $builder = $this->builder;

        $colscount = $this->nr_dims_to_display('col');
        $rowscount = $this->nr_dims_to_display('row');

        $multiple = $this->param_multiple();

        $matrix = [];
        $html = '<table class="quedit matrix"><thead><tr>';
        $html .= '<th></th>';
        $matrix[] = $builder->create_static($html);
        for ($colindex = 0; $colindex < $colscount; $colindex++) {
            $matrix[] = $builder->create_static('<th>');
            $matrix[] = $builder->create_static('<div class="input-group">');
            $matrix[] = $builder->create_text("cols_shorttext[$colindex]", false);

            $popup = $builder->create_htmlpopup("cols_description[$colindex]", lang::col_description());
            $matrix = array_merge($matrix, $popup);

            $matrix[] = $builder->create_static('</div>');
            $matrix[] = $builder->create_static('</th>');
        }

        $matrix[] = $builder->create_static('<th>');
        $matrix[] = $builder->create_static(lang::row_feedback());
        $matrix[] = $builder->create_static('</th>');

        $matrix[] = $builder->create_static('<th>');
        if (setting::show_kprime_gui()) {
            $matrix[] = $builder->create_submit(self::PARAM_ADD_COLUMNS, '+', [
                'class' => 'button-add']);
            $builder->register_no_submit_button(self::PARAM_ADD_COLUMNS);
        }
        $matrix[] = $builder->create_static('</th>');

        $matrix[] = $builder->create_static('</tr></thead><tbody>');

        for ($rowindex = 0; $rowindex < $rowscount; $rowindex++) {
            $matrix[] = $builder->create_static('<tr>');
            $matrix[] = $builder->create_static('<td>');

            $matrix[] = $builder->create_static('<div class="input-group">');

            $matrix[] = $builder->create_text("rows_shorttext[$rowindex]", false);
            $questionpopup = $builder->create_htmlpopup("rows_description[$rowindex]", lang::row_long());
            $matrix = array_merge($matrix, $questionpopup);

            $matrix[] = $builder->create_static('</div>');
            $matrix[] = $builder->create_static('</td>');

            for ($colindex = 0; $colindex < $colscount; $colindex++) {
                $matrix[] = $builder->create_static('<td>');
                $fieldname = qtype_matrix_question::formfield_name($rowindex, $colindex, $multiple);
                if ($multiple) {
                    $cellcontent = $this->_form->createElement('checkbox', $fieldname, 'label');
                } else {
                    $cellcontent = $this->_form->createElement('radio', $fieldname, '', '', $colindex);
                }

                $cellcontent = $cellcontent ? : $builder->create_static('');
                $matrix[] = $cellcontent;
                $matrix[] = $builder->create_static('</td>');
            }

            $matrix[] = $builder->create_static('<td class="feedback">');

            $feedbackpopup = $builder->create_htmlpopup("rows_feedback[$rowindex]", lang::row_feedback());
            $matrix = array_merge($matrix, $feedbackpopup);

            $matrix[] = $builder->create_static('</td>');

            $matrix[] = $builder->create_static('<td></td>');

            $matrix[] = $builder->create_static('</tr>');
        }

        $matrix[] = $builder->create_static('<tr>');
        $matrix[] = $builder->create_static('<td>');
        if (setting::show_kprime_gui()) {
            $matrix[] = $builder->create_submit('add_rows', '+', ['class' => 'button-add']);
            $builder->register_no_submit_button('add_rows');
        }
        $matrix[] = $builder->create_static('</td>');
        for ($colindex = 0; $colindex < $colscount; $colindex++) {
            $matrix[] = $builder->create_static('<td>');
            $matrix[] = $builder->create_static('</td>');
        }
        $matrix[] = $builder->create_static('</tr>');
        $matrix[] = $builder->create_static('</tbody></table>');

        $matrixheader = $builder->create_header('matrixheader');
        $matrixgroup = $builder->create_group('matrix', null, $matrix, '', false);

        $refreshbutton = $builder->create_submit('refresh_matrix');
        $builder->register_no_submit_button('refresh_matrix');
        if (isset($this->_form->_elementIndex['tagsheader'])) {
            $builder->insert_element_before($matrixheader, 'tagsheader');
            $builder->insert_element_before($refreshbutton, 'tagsheader');
            $builder->insert_element_before($matrixgroup, 'tagsheader');
        } else {
            $this->_form->addElement($matrixheader);
            $this->_form->addElement($refreshbutton);
            $this->_form->addElement($matrixgroup);
        }

        if ($colscount > 1 && (empty($this->question->id) || empty($this->question->options->rows))) {
            $builder->set_default('cols_shorttext[0]', lang::true_());
            $builder->set_default('cols_shorttext[1]', lang::false_());
        }
        $this->_form->setExpanded('matrixheader');
    }

    protected function nr_dims_to_display(string $type):int {
        switch ($type) {
            case 'row':
                $currentparamname = self::PARAM_ROWS;
                $newdimparamname = self::PARAM_ADD_ROWS;
                $fallbackvalue = self::DEFAULT_ROWS;
                $lastversiondims = $this->question->options->rows ?? [];
                break;
            case 'col':
                $currentparamname = self::PARAM_COLS;
                $newdimparamname = self::PARAM_ADD_COLUMNS;
                $fallbackvalue = self::DEFAULT_COLS;
                $lastversiondims = $this->question->options->cols ?? [];
                break;
        }
        $nrmatrixdims = $fallbackvalue;
        $nrcurrentdims = count(optional_param_array($currentparamname, [], PARAM_TEXT));
        $nrlastversiondims = count($lastversiondims ?? []);
        if ($nrcurrentdims) {
            $nrmatrixdims = $nrcurrentdims;
        } else if ($nrlastversiondims) {
            $nrmatrixdims = $nrlastversiondims;
        }

        $nrmatrixdims += (int) optional_param($newdimparamname, false, PARAM_BOOL);

        return $nrmatrixdims;
    }

    /**
     * Cant type this function -> to many types used. why is the name returned here?
     *
     * @return array|string|string[] The grade method parameter
     */
    protected function param_grade_method() {
        $data = $this->_form->exportValues();
        return $data[self::PARAM_GRADE_METHOD] ?? qtype_matrix::default_grading()->get_name();
    }

    /**
     * Cant type this function -> to many types used.
     *
     * @return mixed Whether the question allows multiple answers
     */
    protected function param_multiple() {
        $data = $this->_form->exportValues();
        if ($this->param_grade_method() == difference::get_name()) {
            $data[self::PARAM_MULTIPLE] = false;
        }
        return $data[self::PARAM_MULTIPLE] ?? qtype_matrix::DEFAULT_MULTIPLE;
    }

    public function get_javascript(): string {
        return "var YY = null;
        window.mtrx_current = false;
        function mtrx_popup(id) {
            var current_id = window.mtrx_current;
            var new_id = '#' + id;
            if(current_id == false) {
                console.log(current_id);
                node = YY.one(new_id);
                node.setStyle('display', 'block');
                window.mtrx_current = new_id;
            } else if(current_id == new_id) {
                console.log(current_id);
                node = YY.one(window.mtrx_current);
                node.setStyle('display', 'none');
                window.mtrx_current = false;
            } else {
                console.log(current_id);
                node = YY.one(current_id);
                node.setStyle('display', 'none');
                node = YY.one(new_id);
                node.setStyle('display', 'block');
                window.mtrx_current = new_id;
            }
        }
        YUI(M.yui.loader).use('node', function(Y) {
            YY = Y;
        });";
    }

    /**
     *
     * @param $question object
     * @return void
     */
    public function set_data($question): void {
        $isnew = empty($question->id);
        if (!$isnew) {
            $options = $question->options;
            $question->rows_shorttext = [];
            $question->rows_description = [];
            $question->rows_feedback = [];

            foreach ($options->rows as $row) {
                $question->rows_shorttext[] = $row->shorttext;
                $question->rows_description[] = $row->description;
                $question->rows_feedback[] = $row->feedback;
            }

            $question->cols_shorttext = [];
            $question->cols_description = [];
            foreach ($options->cols as $col) {
                $question->cols_shorttext[] = $col->shorttext;
                $question->cols_description[] = $col->description;
            }

            foreach (array_keys($options->rows) as $rowindex => $rowid) {
                foreach (array_keys($options->cols) as $colindex => $colid) {
                    if ($options->weights[$rowid][$colid] > 0) {
                        $fieldname = qtype_matrix_question::formfield_name($rowindex, $colindex, $options->multiple);
                        $question->{$fieldname} = $options->multiple ? true : $colindex;
                        if (!$options->multiple) {
                            break;
                        }
                    }
                }
            }
        }
        /* set data should be called on new questions to set up course id, etc
         * after setting up values for question
         */
        parent::set_data($question);
    }

    /**
     *
     * @param $fromform
     * @param $files
     * @return mixed
     * @throws coding_exception
     */
    public function validation($fromform, $files): array {
        $errors = parent::validation($fromform, $files);
        if (setting::show_kprime_gui()) {
            if ($this->col_count($fromform) < 2) {
                $errors['refresh_matrix'] = lang::must_define_1_by_1();
            }
            if ($this->row_count($fromform) == 0) {
                $errors['refresh_matrix'] = lang::must_define_1_by_1();
            }
        } else {
            if ($this->col_count($fromform) != 2) {
                $errors['refresh_matrix'] = lang::must_define_1_by_1();
            }
            if ($this->row_count($fromform) != 4) {
                $errors['refresh_matrix'] = lang::must_define_1_by_1();
            }
        }
        $grading = qtype_matrix::grading($fromform[self::PARAM_GRADE_METHOD]);
        $gradingerrors = $grading->validation($fromform);
        return array_merge($errors, $gradingerrors);
    }

    protected function col_count(array $data): int {
        return count(array_filter($data['cols_shorttext']));
    }

    protected function row_count(array $data): int {
        return count(array_filter($data['rows_shorttext']));
    }
}
