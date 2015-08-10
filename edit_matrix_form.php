<?php

/**
 * The question type class for the matrix question type.
 *
 */
require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/matrix/libs/matrix_form_builder.php');
require_once($CFG->dirroot . '/question/type/matrix/libs/lang.php');

/**
 * matrix editing form definition. For information about the Moodle forms library,
 * which is based on the HTML Quickform PEAR library 
 * 
 * @see http://docs.moodle.org/en/Development:lib/formslib.php 
 */
class qtype_matrix_edit_form extends question_edit_form implements ArrayAccess
{

    //How many elements are added each time somebody click the add row/add column button.
    const DEFAULT_REPEAT_ELEMENTS = 1;
    //How many rows 
    const DEFAULT_ROWS = 4;
    //How many cols 
    const DEFAULT_COLS = 2;
    const DEFAULT_MULTIPLE = false;
    const PARAM_COLS = 'colshort';
    const PARAM_ADD_COLLUMNS = 'add_cols';
    const PARAM_ROWS = 'rowshort';
    const PARAM_ADD_ROWS = 'add_rows';
    const PARAM_GRADE_METHOD = 'grademethod';
    const PARAM_MULTIPLE = 'multiple';

    /**
     *
     * @var matrix_form_builder 
     */
    private $builder = null;

    function qtype()
    {
        return 'matrix';
    }

    function definition_inner($mform)
    {
        $this->builder = new matrix_form_builder($mform);
        $builder = $this->builder;

        $this->question->options = (isset($this->question->options)) ? $this->question->options : (object) array();

        $this->add_multiple();
        $this->add_grading();

        // mod_ND : BEGIN
        if (get_config('qtype_matrix', 'allow_dnd_ui')) {
            //todo: parameter
            $builder->add_selectyesno('use_dnd_ui', lang::use_dnd_ui());
        }
        // mod_ND : END
        //todo: paramater, default
        $mform->addElement('advcheckbox', 'shuffleanswers', lang::shuffle_answers(), null, null, [0,
            1]);
        $mform->addHelpButton('shuffleanswers', 'shuffleanswers', 'qtype_matrix');

        $mform->setDefault('shuffleanswers', 1);
    }

    /**
     * Override if you need to setup the form depending on current values.
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     */
    function definition_after_data()
    {
        $builder = $this->builder;

        $this->add_matrix();
        $builder->add_javascript($this->get_javascript());
    }

    function set_data($question)
    {
        $is_new = empty($question->id) || empty($question->options->rows);

        if (!$is_new) {

            $options = $question->options;

            $question->multiple = $options->multiple ? '1' : '0';
            $question->grademethod = $options->grademethod;
            $question->shuffleanswers = $options->shuffleanswers ? '1' : '0';
            $question->use_dnd_ui = $options->use_dnd_ui ? '1' : '0';
            $question->rowshort = [];
            $question->rowlong = [];
            $question->rowfeedback = [];
            $question->rowid = [];
            foreach ($options->rows as $row) {
                $question->rowshort[] = $row->shorttext;
                $question->rowlong[] = $row->description;
                $question->rowfeedback[] = $row->feedback;
                $question->rowid[] = $row->id;
            }

            $question->colshort = array();
            $question->collong = array();
            $question->colid = array();
            foreach ($options->cols as $col) {
                $question->colshort[] = $col->shorttext;
                $question->collong[] = $col->description;
                $question->colid[] = $col->id;
            }

            $row_index = 0;
            foreach ($options->rows as $row) {
                $col_index = 0;
                foreach ($options->cols as $col) {
                    $cell_name = qtype_matrix_grading::cell_name($row_index, $col_index, $options->multiple);
                    $weight = $options->weights[$row->id][$col->id];

                    if ($options->multiple) {
                        $value = ($weight > 0) ? 'on' : '';
                        $question->{$cell_name} = $value;
                    } else {
                        if ($weight > 0) {
                            $question->{$cell_name} = $col_index;
                            break;
                        }
                    }
                    $col_index++;
                }
                $row_index++;
            }
        }
        /* set data should be called on new questions to set up course id, etc
         * after setting up values for question
         */
        parent::set_data($question);
    }

    function validation($data, $files)
    {
        global $CFG;
        $errors = parent::validation($data, $files);
        if (!property_exists($CFG, 'qtype_matrix_show_non_kprime_gui') || $CFG->qtype_matrix_show_non_kprime_gui !== '0') {
            if ($this->col_count($data) == 0) {
                $errors['colshort[0]'] = lang::must_define_1_by_1();
            }

            if ($this->row_count($data) == 0) {
                $errors['rowshort[0]'] = lang::must_define_1_by_1();
            }
        } else {
            if ($this->col_count($data) != 2) {
                $errors['colshort[0]'] = lang::must_define_1_by_1();
            }

            if ($this->row_count($data) != 4) {
                $errors['rowshort[0]'] = lang::must_define_1_by_1();
            }
        }
        $grading = qtype_matrix::grading($data[self::PARAM_GRADE_METHOD]);
        $grading_errors = $grading->validation($data);

        $errors = array_merge($errors, $grading_errors);
        return $errors ? $errors : true;
    }

    protected function col_count($data)
    {
        return count($data['colshort']);
    }

    protected function row_count($data)
    {
        return count($data['rowshort']);
    }

    //elements
    public function add_multiple()
    {
        // multiple allowed
        global $CFG;
        $builder = $this->builder;

        if (!property_exists($CFG, 'qtype_matrix_show_non_kprime_gui') || $CFG->qtype_matrix_show_non_kprime_gui !== '0') {
            $builder->add_selectyesno(self::PARAM_MULTIPLE, lang::multiple_allowed());
            $builder->set_default(self::PARAM_MULTIPLE, self::DEFAULT_MULTIPLE);
        } else {
            $this->_form->addElement('hidden', self::PARAM_MULTIPLE, self::DEFAULT_MULTIPLE);
            $this->_form->setType(self::PARAM_MULTIPLE, PARAM_RAW);
        }
    }

    public function add_grading()
    {
        $builder = $this->builder;

        // grading method.
        $default_grading = qtype_matrix::defaut_grading();
        $default_grading_name = $default_grading->get_name();
        $gradings = qtype_matrix::gradings();

        $radioarray = array();

        foreach ($gradings as $grading) {
            $radioarray[] = & $this->_form->createElement('radio', self::PARAM_GRADE_METHOD, '', $grading->get_title(), $grading->get_name(), '');
        }

        $this->_form->addGroup($radioarray, self::PARAM_GRADE_METHOD, lang::grade_method(), array(
            '<br>'), false);
        $this->_form->setDefault(self::PARAM_GRADE_METHOD, $default_grading_name);
        $builder->add_help_button(self::PARAM_GRADE_METHOD);
    }

    function add_matrix()
    {
        global $CFG;
        $mform = $this->_form;
        $data = $mform->exportValues();
        $builder = $this->builder;

        $cols_count = $this->param_cols();
        $rows_count = $this->param_rows();

        $grademethod = $this->param_grade_method();
        $grading = qtype_matrix::grading($grademethod);

        $multiple = $this->param_multiple();

        $matrix = array();
        $html = '<table class="quedit matrix"><thead><tr>';
        $html .= '<th></th>';
        $matrix[] = $builder->create_static($html);
        for ($col = 0; $col < $cols_count; $col++) {
            $matrix[] = $builder->create_static('<th>');
            $matrix[] = $builder->create_static('<div class="input-group">');
            $matrix[] = $builder->create_text("colshort[$col]", false);

            $popup = $builder->create_htmlpopup("collong[$col]", lang::col_long());
            $matrix = array_merge($matrix, $popup);

            $matrix[] = $builder->create_hidden("colid[$col]");
            $matrix[] = $builder->create_static('</div>');
            $matrix[] = $builder->create_static('</th>');
        }

        $matrix[] = $builder->create_static('<th>');
        $matrix[] = $builder->create_static(lang::row_feedback());
        $matrix[] = $builder->create_static('</th>');

        $matrix[] = $builder->create_static('<th>');
        if (!property_exists($CFG, 'qtype_matrix_show_non_kprime_gui') || $CFG->qtype_matrix_show_non_kprime_gui !== '0') {
            $matrix[] = $builder->create_submit(self::PARAM_ADD_COLLUMNS, '  ', array(
                'class' => 'button add'));
            $builder->register_no_submit_button(self::PARAM_ADD_COLLUMNS);
        }
        $matrix[] = $builder->create_static('</th>');

        $matrix[] = $builder->create_static('</tr></thead><tbody>');

        for ($row = 0; $row < $rows_count; $row++) {
            $matrix[] = $builder->create_static('<tr>');
            $matrix[] = $builder->create_static('<td>');

            $matrix[] = $builder->create_static('<div class="input-group">');
            $matrix[] = $builder->create_text("rowshort[$row]", false);

            $question_popup = $builder->create_htmlpopup("rowlong[$row]", lang::row_long());
            $matrix = array_merge($matrix, $question_popup);
            $matrix[] = $builder->create_hidden("rowid[$row]");

            $matrix[] = $builder->create_static('</div>');
            $matrix[] = $builder->create_static('</td>');

            for ($col = 0; $col < $cols_count; $col++) {
                $matrix[] = $builder->create_static('<td>');
                $cell_content = $grading->create_cell_element($mform, $row, $col, $multiple);
                $cell_content = $cell_content ? $cell_content : $builder->create_static('');
                $matrix[] = $cell_content;
                $matrix[] = $builder->create_static('</td>');
            }

            $matrix[] = $builder->create_static('<td class="feedback">');

            $feedback_popup = $builder->create_htmlpopup("rowfeedback[$row]", lang::row_feedback());
            $matrix = array_merge($matrix, $feedback_popup);

            $matrix[] = $builder->create_static('</td>');

            $matrix[] = $builder->create_static('<td></td>');

            $matrix[] = $builder->create_static('</tr>');
        }

        $matrix[] = $builder->create_static('<tr>');
        $matrix[] = $builder->create_static('<td>');
        if (!property_exists($CFG, 'qtype_matrix_show_non_kprime_gui') || $CFG->qtype_matrix_show_non_kprime_gui !== '0') {
            $matrix[] = $builder->create_submit('add_rows', '  ', array('class' => 'button add'));
            $builder->register_no_submit_button('add_rows');
        }
        $matrix[] = $builder->create_static('</td>');
        for ($col = 0; $col < $cols_count; $col++) {
            $matrix[] = $builder->create_static('<td>');
            $matrix[] = $builder->create_static('</td>');
        }
        $matrix[] = $builder->create_static('</tr>');
        $matrix[] = $builder->create_static('</tbody></table>');

        $matrixheader = $builder->create_header('matrixheader');
        $matrix_group = $builder->create_group('matrix', null, $matrix, '', false);

        if (isset($this['tagsheader'])) {
            $builder->insert_element_before($matrixheader, 'tagsheader');
            $refresh_button = $builder->create_submit('refresh_matrix');
            $builder->register_no_submit_button('refresh_matrix');
            $builder->disabled_if('refresh_matrix', self::PARAM_GRADE_METHOD, 'eq', 'none');
            $builder->disabled_if('defaultgrade', self::PARAM_GRADE_METHOD, 'eq', 'none');
            $builder->insert_element_before($refresh_button, 'tagsheader');
            $builder->insert_element_before($matrix_group, 'tagsheader');
        } else {
            $this[] = $matrixheader;
            $refresh_button = $builder->create_submit('refresh_matrix');
            $builder->register_no_submit_button('refresh_matrix');
            $builder->disabled_if('refresh_matrix', self::PARAM_GRADE_METHOD, 'eq', 'none');
            $builder->disabled_if('defaultgrade', self::PARAM_GRADE_METHOD, 'eq', 'none');
            $this[] = $refresh_button;
            $this[] = $matrix_group;
        }

        if ($cols_count > 1 && (empty($this->question->id) || empty($this->question->options->rows))) {
            $builder->set_default('colshort[0]', lang::true_());
            $builder->set_default('colshort[1]', lang::false_());
        }
        $this->_form->setExpanded('matrixheader');
    }

    public function get_javascript()
    {
        return <<<EOT
        
        var YY = null;               
        
        window.mtrx_current = false;
        function mtrx_popup(id)
        {        
            var current_id = window.mtrx_current;
            var new_id = '#' + id;
            if(current_id == false)
            {
                node = YY.one(new_id);
                node.setStyle('display', 'block');
                window.mtrx_current = new_id;
            }
            else if(current_id == new_id)
            {
                node = YY.one(window.mtrx_current);
                node.hide();
                window.mtrx_current = false;
            }
            else
            {
                node = YY.one(current_id);
                node.hide();
                
                node = YY.one(new_id)
                node.setStyle('display', 'block');
                window.mtrx_current = new_id;
            }
        }        
        
        YUI(M.yui.loader).use('node', function(Y) {
            YY = Y;
            }); 
        
        
EOT;
    }

    /**
     * Returns the current number of columns
     * 
     * @return integer The number of columns
     */
    protected function param_cols()
    {
        $result = self::DEFAULT_COLS;
        if (isset($_POST[self::PARAM_COLS])) {
            $result = count($_POST[self::PARAM_COLS]);
        } else if (isset($this->question->options->cols) && count($this->question->options->cols) > 0) {
            $result = count($this->question->options->cols);
        }

        $add_cols = $this->param_add_collumns();
        if ($add_cols) {
            $result++;
        }

        return $result;
    }

    /**
     * True if the user asked to add a column. False otherwise.
     * 
     * @return columns to add
     */
    protected function param_add_collumns()
    {
        return optional_param(self::PARAM_ADD_COLLUMNS, '', PARAM_TEXT);
    }

    protected function param_rows()
    {
        $result = self::DEFAULT_ROWS;

        if (isset($_POST[self::PARAM_ROWS])) {
            $result = count($_POST[self::PARAM_ROWS]);
        } else if (isset($this->question->options->rows) && count($this->question->options->rows) > 0) {
            $result = count($this->question->options->rows);
        }

        $add_rows = $this->param_add_rows();
        if ($add_rows) {
            $result++;
        }
        return $result;
    }

    /**
     * True if the user asked to add a row. False otherwise.
     * 
     * @return rows to add
     */
    protected function param_add_rows()
    {
        return (optional_param(self::PARAM_ADD_ROWS, '', PARAM_TEXT)) ? true : false;
    }

    /**
     * 
     * @return The grade method parameter
     */
    protected function param_grade_method()
    {
        $data = $this->_form->exportValues();
        return isset($data[self::PARAM_GRADE_METHOD]) ? $data[self::PARAM_GRADE_METHOD] : qtype_matrix::defaut_grading()->get_name();
    }

    /**
     * 
     * @return Whether the question allows multiple answers
     */
    protected function param_multiple()
    {
        $data = $this->_form->exportValues();
        return isset($data[self::PARAM_MULTIPLE]) ? $data[self::PARAM_MULTIPLE] : self::DEFAULT_MULTIPLE;
    }

    // implement ArrayAccess

    public function offsetExists($offset)
    {
        return $this->_form->elementExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->_form->getElement($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->_form->addElement($value);
    }

    public function offsetUnset($offset)
    {
        $this->_form->removeElement($offset);
    }

}
