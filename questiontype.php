<?php

/**
 * The question type class for the matrix question type.
 *
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once (dirname(__FILE__)) . '/qtype_matrix_grading.class.php';
require_once($CFG->dirroot . '/question/type/matrix/libs/question_matrix_store.php');

/**
 * The matrix question class
 *
 * Pretty simple concept - a matrix with a number of different grading methods and options.
 */
class qtype_matrix extends question_type
{

    public static function get_string($identifier, $component = 'qtype_matrix', $a = null)
    {
        return get_string($identifier, $component, $a);
    }

    public static function gradings()
    {
        return qtype_matrix_grading::gradings();
    }

    public static function grading($type)
    {
        return qtype_matrix_grading::create($type);
    }

    public static function defaut_grading()
    {
        return qtype_matrix_grading::default_grading();
    }

    function name()
    {
        return 'matrix';
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $question_id The question being deleted
     * @param integer $context_id The context id
     * @return boolean to indicate success of failure.
     */
    function delete_question_options($question_id, $context_id = null)
    {
        if (empty($question_id)) {
            return false;
        }

        $store = new question_matrix_store();
        $store->delete_question($question_id);

        return true;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $question_id The question being deleted
     * @param integer $context_id
     * @return boolean to indicate success of failure.
     */
    function delete_question($question_id, $context_id = null)
    {
        if (empty($question_id)) {
            return false;
        }

        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $this->delete_question_options($question_id);
        parent::delete_question($question_id, $context_id);

        $transaction->allow_commit();

        return true;
    }

    /**
     * @return boolean true if this question type sometimes requires manual grading.
     */
    function is_manual_graded()
    {
        return true;
    }

    /**
     * 
     * @param object $question
     * @return boolean
     */
    function get_question_options($question)
    {
        parent::get_question_options($question);
        $matrix = self::retrieve_matrix($question->id);       
        if ($matrix) {
            $question->options->rows = $matrix->rows;
            $question->options->cols = $matrix->cols;
            $question->options->weights = $matrix->weights;
            $question->options->grademethod = $matrix->grademethod;
            $question->options->shuffleanswers = isset($matrix->shuffleanswers) ? $matrix->shuffleanswers : true; // allow for old versions which don't have this field
            $question->options->use_dnd_ui = $matrix->use_dnd_ui;
            $question->options->multiple = $matrix->multiple;
            $question->options->renderer = $matrix->renderer;
        } else {
            $question->options->rows = array();
            $question->options->cols = array();
            $question->options->weights = array(array());
            $question->options->grademethod = self::defaut_grading()->get_name();
            $question->options->shuffleanswers = true;
            $question->options->use_dnd_ui = false;
            $question->options->multiple = true;
        }
        return true;
    }

    static function retrieve_matrix($question_id)
    {
        $store = new question_matrix_store();

        if (empty($question_id)) {
            return null;
        }

        $matrix = $store->get_matrix_by_question_id($question_id);
        if (empty($matrix)) {
            return null;
        }
        $matrix_id = $matrix->id;

        $matrix->rows = $store->get_matrix_rows_by_matrix_id($matrix_id);
        $matrix->cols = $store->get_matrix_cols_by_matrix_id($matrix_id);

        $raw_weights = $store->get_matrix_weights_by_question_id($question_id);

        //initialize weights      
        $matrix->weights = array();
        foreach ($matrix->rows as $row) {
            $matrix->weights[$row->id] = array();
            foreach ($matrix->cols as $col) {
                $matrix->weights[$row->id][$col->id] = 0;
            }
        }
        //set non zero weights
        foreach ($raw_weights as $weight) {
            $matrix->weights[$weight->rowid][$weight->colid] = (float) $weight->weight;
        }
        return $matrix;
    }

    /**
     * Initialise the common question_definition fields.
     *
     * @param question_definition $question the question_definition we are creating.
     * @param object $questiondata the question data loaded from the database.
     */
    protected function initialise_question_instance(question_definition $question, $questiondata)
    {
        parent::initialise_question_instance($question, $questiondata);
        $question->rows = $questiondata->options->rows;
        $question->cols = $questiondata->options->cols;
        $question->weights = $questiondata->options->weights;
        $question->grademethod = $questiondata->options->grademethod;
        $question->shuffleanswers = $questiondata->options->shuffleanswers;
        $question->multiple = $questiondata->options->multiple;
    }

    /**
     * Saves question-type specific options.
     * This is called by {@link save_question()} to save the question-type specific data.
     *
     * @param object $question This holds the information from the editing form, it is not a standard question object.
     * @return object $result->error or $result->noticeyesno or $result->notice
     */
    function save_question_options($question)
    {
        global $DB;
        $store = new question_matrix_store();

        $transaction = $DB->start_delegated_transaction();

        $question_id = $question->id;
        $make_copy = (property_exists($question, 'makecopy') && $question->makecopy == '1');

        /**
         * $question_id != matrix->id
         */
        $matrix = (object) $store->get_matrix_by_question_id($question_id);

        $is_new = !isset($matrix->id) || empty($matrix->id);
        
        $matrix->questionid = $question_id;
        $matrix->multiple = $question->multiple;
        $matrix->grademethod = $question->grademethod;
        $matrix->shuffleanswers = $question->shuffleanswers;
        $matrix->use_dnd_ui = isset($question->use_dnd_ui) ? ($question->use_dnd_ui) : (0);

        if ($is_new || $make_copy) {
            $store->insert_matrix($matrix);
        } else {
            $store->update_matrix($matrix);
        }

        $matrix_id = $matrix->id;

        // rows
        // mapping for indexes to db ids.
        $rowids = array();
        foreach ($question->rows_shorttext as $i => $short) {
            $row_id = $question->rowid[$i];
            $is_new = !$row_id;
            $row = (object) array(
                    'id' => $row_id,
                    'matrixid' => $matrix_id,
                    'shorttext' => $question->rows_shorttext[$i],
                    'description' => $question->rows_description[$i],
                    'feedback' => $question->rows_feedback[$i]
            );
            $delete = empty($row->shorttext);

            if ($delete && $is_new) {
                //noop
            } else if ($delete) {
                $store->delete_matrix_row($row);
            } else if ($is_new || $make_copy) {
                $store->insert_matrix_row($row);
                $rowids[] = $row->id;
            } else {
                $store->update_matrix_row($row);
                $rowids[] = $row->id;
            }
        }

        // cols
        // mapping for indexes to db ids.
        $colids = array();
        foreach ($question->cols_shorttext as $i => $short) {
            $col_id = $question->colid[$i];
            $is_new = !$col_id;
            $col = (object) array(
                    'id' => $col_id,
                    'matrixid' => $matrix_id,
                    'shorttext' => $question->cols_shorttext[$i],
                    'description' => $question->cols_description[$i]
            );
            $delete = empty($col->shorttext);
            if ($delete && $is_new) {
                //noop
            } else if ($delete) {
                $store->delete_matrix_col($col);
            } else if (!$col_id || $make_copy) {
                $store->insert_matrix_col($col);
                $colids[] = $col->id;
            } else {
                $store->update_matrix_col($col);
                $colids[] = $question->colid[$i];
            }
        }

        /**
         * Wheights
         * 
         * First we delete all weights. (There is no danger of deleting the original weights when making a copy, because we are anyway deleting only weights associated with our newly created question ID).
         * Then we recreate them. (Because updating is too much of a pain)
         * 
         */
        $store->delete_matrix_weights($question_id);

        /**
         * When we switch from multiple answers to single answers (or the other
         * way around) we loose answers. 
         * 
         * To avoid loosing information when we switch, we test if the weight matrix is empty. 
         * If the weight matrix is empty we try to read from the other 
         * representation directly from POST data.
         * 
         * We read from the POST because post data are not read into the question
         * object because there is no corresponding field.
         * 
         * This is bit hacky but it is safe. The to_weight_matrix returns only 
         * 0 or 1.
         */
        $weights = array();
        if ($question->multiple) {
            $weights = $this->to_weigth_matrix($question, true);
            if ($this->is_matrix_empty($weights)) {
                $weights = $this->to_weigth_matrix($_POST, false);
            }
        } else {
            $weights = $this->to_weigth_matrix($question, false);
            if ($this->is_matrix_empty($weights)) {
                $weights = $this->to_weigth_matrix($_POST, true);
            }
        }

        foreach ($rowids as $row_index => $row_id) {
            foreach ($colids as $col_index => $col_id) {
                $value = $weights[$row_index][$col_index];
                if ($value) {
                    $weight = (object) array(
                            'rowid' => $row_id,
                            'colid' => $col_id,
                            'weight' => 1
                    );
                    $store->insert_matrix_weight($weight);
                }
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Transform the weight from the edit-form's representation to a standard matrix 
     * representation
     * 
     * Input data is either
     * 
     *      $question->{cell0_1] = 1
     * 
     * or
     * 
     *      $question->{cell0] = 3
     * 
     * Output
     * 
     *      [ 1 0 1 0 ]
     *      [ 0 0 0 1 ]
     *      [ 1 1 1 0 ]
     *      [ 0 1 0 1 ]
     * 
     * 
     * @param object $data              Question's data, either from the question object or from the post
     * @param boolean $from_multiple    Whether we extract from multiple representation or not
     * @result array                    The weights
     */
    public function to_weigth_matrix($data, $from_multiple)
    {
        $data = (object) $data;
        $result = array();
        $row_count = 20;
        $col_count = 20;

        //init
        for ($row = 0; $row < $row_count; $row++) {
            for ($col = 0; $col < $col_count; $col++) {
                $result[$row][$col] = 0;
            }
        }

        if ($from_multiple) {
            for ($row = 0; $row < $row_count; $row++) {
                for ($col = 0; $col < $col_count; $col++) {
                    $key = qtype_matrix_grading::cell_name($row, $col, $from_multiple);
                    $value = isset($data->{$key}) ? $data->{$key} : 0;
                    $result[$row][$col] = $value ? 1 : 0;
                }
            }
        } else {
            for ($row = 0; $row < $row_count; $row++) {
                $key = qtype_matrix_grading::cell_name($row, 0, $from_multiple);
                if (isset($data->{$key})) {
                    $col = $data->{$key};
                    $result[$row][$col] = 1;
                }
            }
        }
        return $result;
    }

    /**
     * True if the matrix is empty (contains only zeroes). False otherwise.
     * 
     * @param array $matrix Array of arrays
     * @return boolean True if the matrix contains only zeros. False otherwise
     */
    public function is_matrix_empty($matrix)
    {
        foreach ($matrix as $row) {
            foreach ($row as $value) {
                if ($value && $value > 0) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * This method should be overriden if you want to include a special heading or some other
     * html on a question editing page besides the question editing form.
     *
     * @param question_edit_form $mform a child of question_edit_form
     * @param object $question
     * @param string $wizardnow is '' for first page.
     */
    public function display_question_editing_page($mform, $question, $wizardnow)
    {
        global $OUTPUT;
        $heading = $this->get_heading(empty($question->id));

        if (get_string_manager()->string_exists('pluginname_help', $this->plugin_name())) {
            echo $OUTPUT->heading_with_help($heading, 'pluginname', $this->plugin_name());
        } else {
            echo $OUTPUT->heading_with_help($heading, $this->name(), $this->plugin_name());
        }
        $mform->display();
    }

    // mod_ND : BEGIN
    public function extra_question_fields()
    {
        return array('question_matrix', 'use_dnd_ui');
    }

    // mod_ND : END
}
