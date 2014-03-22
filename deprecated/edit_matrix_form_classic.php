<?php

/**
 * The question type class for the matrix question type.
 *
 * @copyright   2012 University of Geneva
 * @author      laurent.opprecht@unige.ch
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package     qtype
 * @subpackage  matrix
 */
require_once($CFG->dirroot . '/question/type/edit_question_form.php');
//require_once(dirname(__FILE__) . '/form/matrix_form_renderer.class.php');

/**
 * matrix editing form definition.
 *
 * See http://docs.moodle.org/en/Development:lib/formslib.php for information
 * about the Moodle forms library, which is based on the HTML Quickform PEAR library.
 */
class qtype_matrix_edit_form_classic extends question_edit_form implements ArrayAccess
{

    const DEFAULT_REPEAT_ELEMENTS = 1; //i.e. how many elements are added each time somebody click the add row/add column button.
    const DEFAULT_ROWS = 4; //i.e. how many rows 
    const DEFAULT_COLS = 2; //i.e. how many cols 

    function qtype()
    {
        return 'matrix';
    }

    function definition_inner($mform)
    {
        global $COURSE;
        $this->question->options = isset($this->question->options) ? $this->question->options : (object) array();

        // multiple allowed
        $this->add_selectyesno('multiple', qtype_matrix::get_string('multipleallowed'));
        $this->set_default('multiple', true);

        // grading method.
        $options = array();
        $default_grading = qtype_matrix::defaut_grading();
        $options[$default_grading->get_name()] = $default_grading->get_title();
        $gradings = qtype_matrix::gradings();
        foreach ($gradings as $grading)
        {
            $options[$grading->get_name()] = $grading->get_title();
        }
        $this->add_select('grademethod', '', $options);
        $this->add_help_button('grademethod');


        // renderer to use
//        $renderers = qtype_matrix::matrix_renderer_options();
//        if (count($renderers) > 1)
//        {
//            $this->add_select('renderer', '', $renderers);
//        }
//        else
//        {
//            $this->add_hidden('renderer', array_pop(array_keys($renderers)));
//        }

        // rows
        $rows_prototype = array();
        $rows_prototype[] = $this->create_static('<hr/>');
        $rows_prototype[] = $this->create_text('rowshort');
        $rows_prototype[] = $this->create_htmleditor('rowlong');
        $rows_prototype[] = $this->create_htmleditor('rowfeedback');
        $rows_prototype[] = $this->create_hidden('rowid');

        if (isset($this->question->options->rows) && count($this->question->options->rows) > 0)
        {
            $repeatno = count($this->question->options->rows);
        }
        else
        {
            $repeatno = self::DEFAULT_ROWS;
        }

        $this->add_header('rowsheader');
        $this->add_static(qtype_matrix::get_string('rowsheader_desc'));

        $this->repeat_elements($rows_prototype, $repeatno, array('betweenrowshr' => array('template' => '{element}')), 'option_repeat_rows', 'option_add_rows', self::DEFAULT_REPEAT_ELEMENTS, get_string('addmorerows', 'qtype_matrix', '{no}'));

        // cols
        $cols_prototype = array();
        $cols_prototype[] = $this->create_static('<hr/>');
        $cols_prototype[] = $this->create_text('colshort');
        $cols_prototype[] = $this->create_htmleditor('collong');
        $cols_prototype[] = $this->create_hidden('colid');

        if (isset($this->question->options->cols) && count($this->question->options->cols) > 0)
        {
            $repeatno = count($this->question->options->cols);
        }
        else
        {
            $repeatno = self::DEFAULT_COLS;
            $this->set_default('colshort[0]', qtype_matrix::get_string('true'));
            $this->set_default('colshort[1]', qtype_matrix::get_string('false'));
        }

        $this->add_header('colsheader');
        $this->add_static(qtype_matrix::get_string('colsheader_desc'));
        $this->repeat_elements($cols_prototype, $repeatno, array('betweencolshr' => array('template' => '{element}')), 'option_repeat_cols', 'option_add_cols', self::DEFAULT_REPEAT_ELEMENTS, get_string('addmorecols', 'qtype_matrix', '{no}'));

        // weights
        $this->add_submit('addweights', qtype_matrix::get_string('selectcorrectanswers'));
        $this->register_no_submit_button('addweights');
        $this->disabled_if('addweights', 'grademethod', 'eq', 'none');
        $this->disabled_if('defaultgrade', 'grademethod', 'eq', 'none');
    }

    function definition_after_data()
    {
        $weightsadded = optional_param('weightsadded', false, PARAM_CLEAN);
        if ($weightsadded)
        {
            $this->add_grading_matrix();
            return;
        }

        $submitted = optional_param('addweights', false, PARAM_CLEAN);
        if ($submitted && $this->validate_defined_fields(true))
        {
            $this->add_grading_matrix();
            return;
        }

        if (isset($this->question->id))
        {
            $this->add_grading_matrix();
        }
    }

    function add_grading_matrix()
    {
        static $added = false;
        if ($added)
        {
            return;
        }
        $added = true;

        $mform = $this->_form;
        $data = $mform->exportValues();

        $multiple = $data['multiple'];
        $grademethod = isset($data['grademethod']) ? $data['grademethod'] : qtype_matrix::defaut_grading()->get_name();

        if ($grademethod == qtype_matrix_grading_none::TYPE)
        {
            return;
        }

        $gradeclass = qtype_matrix::grading($grademethod);

        $matrix = array();
        $html = '<table class="qtypematrixformmatrix"><thead><tr>';
        $html .= '<th></th>';
        foreach ($data['colshort'] as $col_short)
        {
            if (empty($col_short))
            {
                break;
            }
            $html .= "<th>$col_short</th>";
        }

        $html .= '</tr></thead><tbody>';
        $matrix[] = $this->create_static($html);

        foreach ($data['rowshort'] as $row_index => $row_short)
        {
            if (empty($row_short))
            {
                break;
            }
            $matrix[] = $this->create_static('<tr>');
            $matrix[] = $this->create_static("<td>$row_short</td>");
            foreach ($data['colshort'] as $col_index => $col_short)
            {
                if (empty($col_short))
                {
                    break;
                }

                $matrix[] = $this->create_static('<td>');
                $cell_content = $gradeclass->create_cell_element($mform, $row_index, $col_index, $multiple);
                $cell_content = $cell_content ? $cell_content : $this->create_static('');
                $matrix[] = $cell_content;
                $matrix[] = $this->create_static('</td>');
            }
            $matrix[] = $this->create_static('</tr>');
        }
        $matrix[] = $this->create_static('</tbody></table>');

        $matrixel = $this->create_group('matrix', null, $matrix, '', true);
        $matrixheader = $this->create_header('matrixheader', qtype_matrix::get_string('matrixheader'));
        if (isset($this['tagsheader']))
        {
            $this->insert_element_before($matrixheader, 'tagsheader');
            $this->insert_element_before($matrixel, 'tagsheader');
        }
        else
        {
            $this[] = $matrixheader;
            $this[] = $matrixel;
        }

        $this->add_hidden('weightsadded', 1);
        $this['addweights']->setValue(qtype_matrix::get_string('updatematrix'));
        $this->disabled_if('matrix', 'grademethod', 'eq', 'none');
    }

    function set_data($question)
    {
        $is_new = empty($question->id) || empty($question->options->rows);

        if (!$is_new)
        {

            $options = $question->options;

            $question->multiple = $options->multiple ? '1' : '0';
            $question->grademethod = $options->grademethod;
            //$question->renderer = $options->renderer;

            $question->rowshort = array();
            $question->rowlong = array();
            $question->rowfeedback = array();
            $question->rowid = array();
            foreach ($options->rows as $row)
            {
                $question->rowshort[] = $row->shorttext;
                $question->rowlong[] = $row->description;
                $question->rowfeedback[] = $row->feedback;
                $question->rowid[] = $row->id;
            }

            $question->colshort = array();
            $question->collong = array();
            $question->colid = array();
            foreach ($options->cols as $col)
            {
                $question->colshort[] = $col->shorttext;
                $question->collong[] = $col->description;
                $question->colid[] = $col->id;
            }

            $question->matrix = array();
            $row_index = 0;
            foreach ($options->rows as $row)
            {
                $col_index = 0;
                foreach ($options->cols as $col)
                {
                    $cell_name = qtype_matrix_grading::cell_name($row_index, $col_index, $options->multiple);
                    $weight = $options->weights[$row->id][$col->id];
                    $question->matrix[$cell_name] = $weight;
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
        $errors = parent::validation($data, $files);

        if ($this->col_count($data) == 0)
        {
            $errors['colshort[0]'] = qtype_matrix::get_string('mustdefine1by1');
        }

        if ($this->row_count($data) == 0)
        {
            $errors['rowshort[0]'] = qtype_matrix::get_string('mustdefine1by1');
        }

        $grading = qtype_matrix::grading($data['grademethod']);
        $grading_errors = $grading->validation($data);

        $errors = array_merge($errors, $grading_errors);
        return $errors ? $errors : true;
    }

    protected function col_count($data)
    {
        foreach ($data['colshort'] as $index => $value)
        {
            if (empty($value))
            {
                return $index++;
            }
        }
        return count($data['colshort']);
    }

    protected function row_count($data)
    {
        foreach ($data['rowshort'] as $index => $value)
        {
            if (empty($value))
            {
                return $index++;
            }
        }
        return count($data['rowshort']);
    }

    //utility functions

    protected function create_static($html)
    {
        static $count = 0;
        $name = '__a' . $count++;
        return $this->_form->createElement('static', $name, null, $html);
    }

    protected function create_text($name, $label = '')
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->createElement('text', $name, $label);
    }

    protected function create_htmleditor($name, $label = '')
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->createElement('htmleditor', $name, $label);
    }

    protected function create_hidden($name, $value = null)
    {
        return $this->_form->createElement('hidden', $name, $value);
    }

    protected function create_group($name = null, $label = null, $elements = null, $separator = '', $appendName = true)
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->createElement('group', $name, $label, $elements, $separator, $appendName);
    }

    protected function create_header($name, $label = '')
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->createElement('header', $name, $label);
    }

    protected function add_static($html)
    {
        return $this->_form->addElement('static', null, null, $html);
    }

    protected function add_text($name, $label = '')
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->addElement('text', $name, $label);
    }

    protected function add_htmleditor($name, $label = '')
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->addElement('htmleditor', $name, $label);
    }

    protected function add_hidden($name, $value = null)
    {
        return $this->_form->addElement('hidden', $name, $value);
    }

    protected function add_group($name = null, $label = null, $elements = null, $separator = '', $appendName = true)
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->addElement('group', $name, $label, $elements, $separator, $appendName);
    }

    protected function add_header($name, $label = '')
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->addElement('header', $name, $label);
    }

    protected function add_selectyesno($name, $label = '')
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->addElement('selectyesno', $name, $label);
    }

    protected function add_select($name, $label = '', $options = null)
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->addElement('select', $name, $label, $options);
    }

    protected function add_submit($name, $label = '')
    {
        if ($label === '')
        {
            $label = qtype_matrix::get_string($name);
        }
        return $this->_form->addElement('submit', $name, $label);
    }

    protected function add_help_button($elementname, $identifier = null, $component = 'qtype_matrix', $linktext = '', $suppresscheck = false)
    {
        if (is_null($identifier))
        {
            $identifier = $elementname;
        }
        return $this->_form->addHelpButton($elementname, $identifier, $component, $linktext, $suppresscheck);
    }

    protected function add_element($element)
    {
        return $this->_form->addElement($element);
    }

    protected function set_default($name, $value)
    {
        return $this->_form->setDefault($name, $value);
    }

    protected function element_exists($name)
    {
        return $this->_form->elementExists($name);
    }

    protected function insert_element_before($element, $before_name)
    {
        return $this->_form->insertElementBefore($element, $before_name);
    }

    protected function disabled_if($elementName, $dependentOn, $condition = 'notchecked', $value = '1')
    {
        return $this->_form->disabledIf($elementName, $dependentOn, $condition, $value);
    }

    protected function register_no_submit_button($name)
    {
        return $this->_form->registerNoSubmitButton($name);
    }

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