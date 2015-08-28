<?php

/**
 * The question type class for the matrix question type.
 *
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/matrix/libs/lang.php');

/**
 * Represents a matrix question.
 */
class qtype_matrix_question extends question_graded_automatically_with_countback implements IteratorAggregate
{

    const KEY_ROWS_ORDER = '_order';

    public $rows;
    public $cols;
    public $weights;
    public $grademethod;
    public $multiple;
    public $shuffleanswers;
    public $use_dnd_ui;

    /**
     * Contains the keys of the rows array
     * Used to maintain order when shuffling answers
     * 
     * @var array 
     */
    protected $order = null;

    /**
     *
     * @return qtype_matrix_grading
     */
    public function grading()
    {
        return qtype_matrix::grading($this->grademethod);
    }

    /**
     *
     * @param mixed $row
     * @param mixed $col 
     * 
     * or
     * 
     * @param object $key
     * 
     * @return float
     */
    public function weight($row = null, $col = null)
    {
        if (is_string($row) && is_null($col)) {
            //$key = $row;
            $key = str_replace('cell', $col, $row);
            list($row_id, $col_id) = explode('x', $key);
        } else {
            $row_id = is_object($row) ? $row->id : $row;
            $col_id = is_object($col) ? $col->id : $col;
        }
        return (float) $this->weights[$row_id][$col_id];
    }

    /**
     *
     * @param object $row
     * @param object $col
     * @param boolean|null $multiple
     * @return string
     */
    public function key($row, $col, $multiple = null)
    {
        $row_id = is_object($row) ? $row->id : $row;
        $col_id = is_object($col) ? $col->id : $col;
        $multiple = (is_null($multiple)) ? $this->multiple : $multiple;

        return qtype_matrix_grading::cell_name($row_id, $col_id, $multiple);
    }

    /**
     * The user's response of cell at $row, $col. That is if the cell is checked or not.
     * If the user didn't make an answer at all (no response) the method returns false.
     * 
     * @param array $response  object containing the raw answer data
     * @param any $row          matrix row, either an id or an object
     * @param any $col          matrix col, either an id or an object
     * 
     * @return boolean True if the cell($row, $col) was checked by the user. False otherwise.
     */
    public function response($response, $row, $col)
    {
        /**
         * A student may response with a question with the multiple answer turned on.
         * Later the teacher may turn that flag off. The result is that the question
         * and response formats won't match.
         * 
         * To fix that problem we don't use the question->multiple flag but instead we
         * use the use the user's response to detect the correct value.
         * 
         * Note
         * A part of the problems come from the fact that we use two representation formats
         * depending on the multiple flags. The cause is the html matrix representation
         * that requires two differents views (checkboxes or radio). This representation
         * then leaks to memory.
         * 
         * A better strategy would be to use only one normalized representation in memory. 
         * The same way we have only one representation in the DB. For that we 
         * would need to transform the html form data after the post. 
         * Not sure we can dot it.
         */
        $response_multiple = $this->multiple;
        foreach ($response as $key => $value) {
            $response_multiple = (strpos($key, '_') !== false);
            break;
        }

        $key = $this->key($row, $col, $response_multiple);
        $value = isset($response[$key]) ? $response[$key] : false;
        if ($value === false) {
            return false;
        }

        if ($response_multiple) {
            return !empty($value);
        } else {
            return $value == $col->id;
        }
    }

    /**
     * Returns the expected answer for the cell at $row, $col.
     * 
     * @param integer|object $row
     * @param integer|object $col
     * 
     * @return boolean  True if cell($row, $col) is correct, false otherwise.
     */
    public function answer($row = null, $col = null)
    {
        return $this->weight($row, $col) > 0;
    }

    /**
     * Start a new attempt at this question, storing any information that will
     * be needed later in the step.
     *
     * This is where the question can do any initialisation required on a
     * per-attempt basis. For example, this is where the multiple choice
     * question type randomly shuffles the choices (if that option is set).
     *
     * Any information about how the question has been set up for this attempt
     * should be stored in the $step, by calling $step->set_qt_var(...).
     *
     * @param question_attempt_step $step 
     *          The first step of the {@link question_attempt} being started. 
     *          Can be used to store state.
     * @param int $variant 
     *          Which variant of this question to start. Will be between 
     *          1 and {@link get_num_variants()} inclusive.
     */
    function start_attempt(question_attempt_step $step, $variant)
    {
        global $PAGE;
        // mod_ND : BEGIN
        if ($this->use_dnd_ui && !$PAGE->requires->is_head_done()) {
            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('ui-css');
            $PAGE->requires->js('/question/type/matrix/js/dnd.js');
        }
        // mod_ND : END
        $this->order = array_keys($this->rows);
        if ($this->shuffle_answers()) {
            shuffle($this->order);
        }
        $this->write_data($step);
    }

    /**
     * Question shuffle can be disabled at the Quiz level. If false then the
     * question parts are not shuffled. If true then the question's shuffle parameter
     * decide wheter the question's parts are actually shuffled.
     * 
     * If the question is executed outside of a Quiz (for example in preview)
     * returns true. 
     * 
     * @global object $DB       Database object
     * @global object $PAGE     Page object
     * @return boolean          True if shuffling is authorized. False otherwise.
     */
    function shuffle_authorized()
    {
        global $DB, $PAGE;

        $cm = $PAGE->cm;
        if (!is_object($cm)) {
            return true;
        }
        $quiz = $DB->get_record('quiz', array('id' => $cm->instance));
        return $quiz->shuffleanswers;
    }

    /**
     * 
     * @return boolean True if rows should be shuffled. False otherwise.
     */
    function shuffle_answers()
    {
        if (!$this->shuffle_authorized()) {
            return false;
        }
        return $this->shuffleanswers;
    }

    /**
     * Write persistent data to a step for further retrieval
     * 
     * @param question_attempt_step $step Storage
     */
    protected function write_data(question_attempt_step $step)
    {
        $step->set_qt_var(self::KEY_ROWS_ORDER, implode(',', $this->order));
    }

    /**
     * Load persistent data from a step.
     * 
     * @param question_attempt_step $step Storage
     */
    protected function load_data(question_attempt_step $step)
    {
        $order = $step->get_qt_var(self::KEY_ROWS_ORDER);
        if ($order !== null) {
            $this->order = explode(',', $order);
        } else {
            /**
             * The order doesn't exist in the database. 
             * This can happen because the question is old and doesn't have the shuffling possibility yet.
             */
            $this->order = array_keys($this->rows);
            if ($this->shuffle_answers()) {
                shuffle($this->order);
            }
            $this->write_order($step);
        }

        /**
         * Rows can be deleted between attempts. We need therefore to remove
         * those that were stored in the step but are not present anymore.
         */
        $rows_removed = array();
        foreach ($this->order as $row_key) {
            if (!isset($this->rows[$row_key])) {
                $rows_removed[] = $row_key;
            }
        }
        $this->order = array_diff($this->order, $rows_removed);


        /**
         * Rows can be added between attempts. We need therefore to add those
         * rows that were not stored in the step.
         */
        $rows_added = array();
        $rows_keys = array_keys($this->rows);
        foreach ($rows_keys as $row_key) {
            if (!in_array($row_key, $this->order)) {
                $rows_added[] = $row_key;
            }
        }
        if ($this->shuffle_answers()) {
            shuffle($rows_added);
        }
        foreach ($rows_added as $row_key) {
            $this->order[] = $row_key;
        }
    }

    /**
     * When an in-progress {@link question_attempt} is re-loaded from the
     * database, this method is called so that the question can re-initialise
     * its internal state as needed by this attempt.
     *
     * For example, the multiple choice question type needs to set the order
     * of the choices to the order that was set up when start_attempt was called
     * originally. All the information required to do this should be in the
     * $step object, which is the first step of the question_attempt being loaded.
     *
     * @param question_attempt_step $step The first step of the {@link question_attempt}
     *      being loaded.
     */
    function apply_attempt_state(question_attempt_step $step)
    {
        // mod_ND : BEGIN
        if ($this->use_dnd_ui) {
            global $PAGE;
            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('ui-css');
            $PAGE->requires->js('/question/type/matrix/js/dnd.js');
        }
        // mod_ND : END
        $this->load_data($step);
    }

    public function get_order(question_attempt $qa)
    {
        $this->init_order($qa);
        return $this->order;
    }

    protected function init_order(question_attempt $qa)
    {
        if ($this->order) {
            return;
        }

        $this->order = explode(',', $qa->get_step(0)->get_qt_var(self::KEY_ROWS_ORDER));
    }

    /**
     * Work out a final grade for this attempt, taking into account all the
     * tries the student made.
     * 
     * @param array $responses the response for each try. Each element of this
     * array is a response array, as would be passed to {@link grade_response()}.
     * There may be between 1 and $totaltries responses.
     * 
     * @param int $totaltries The maximum number of tries allowed.
     * 
     * @return numeric the fraction that should be awarded for this
     * sequence of response.
     */
    public function compute_final_grade($responses, $totaltries)
    {
        $x = 1 / 0;
    }

    /**
     * Used by many of the behaviours, to work out whether the student's
     * response to the question is complete. That is, whether the question attempt
     * should move to the COMPLETE or INCOMPLETE state.
     *
     * @param array $response responses, as returned by
     *      {@link question_attempt_step::get_qt_data()}.
     * @return bool whether this response is a complete answer to this question.
     */
    public function is_complete_response(array $response)
    {
        if ($this->multiple) {
            return true;
        }

        foreach ($this->rows as $row) {
            $key = $this->key($row, 0);
            if (!isset($response[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * In situations where is_gradable_response() returns false, this method
     * should generate a description of what the problem is.
     * @param array $response
     * @return string the message.
     */
    public function get_validation_error(array $response)
    {
        $is_gradable = $this->is_gradable_response($response);
        if ($is_gradable) {
            return '';
        }
        return lang::one_answer_per_row();
    }

    /**
     * Produce a plain text summary of a response.
     * 
     * @param array response A response, as might be passed to {@link grade_response()}.
     * @return string a plain text summary of that response, that could be used in reports.
     */
    public function summarise_response(array $response)
    {
        $result = array();

        $row_index = 0;
        foreach ($this->order as $rowid) {
            $row = $this->rows[$rowid];
            $col_index = 0;
            foreach ($this->cols as $col) {
                $key = $this->key($row, $col);
                $value = isset($response[$key]) ? $response[$key] : false;
                if ($value == $col->id) {
                    $result[] = "{$row->shorttext}: {$col->shorttext}";
                }
                $col_index++;
            }
            $row_index++;
        }
        return implode("; ", $result);
    }

    /**
     * Use by many of the behaviours to determine whether the student's
     * response has changed. This is normally used to determine that a new set
     * of responses can safely be discarded.
     *
     * @param array $prevresponse the responses previously recorded for this question,
     *      as returned by {@link question_attempt_step::get_qt_data()}
     * @param array $newresponse the new responses, in the same format.
     * @return bool whether the two sets of responses are the same - that is
     *      whether the new set of responses can safely be discarded.
     */
    public function is_same_response(array $prevresponse, array $newresponse)
    {
        if (count($prevresponse) != count($newresponse)) {
            return false;
        }
        foreach ($prevresponse as $key => $previous_value) {
            if (!isset($newresponse[$key])) {
                return false;
            }
            $new_value = $newresponse[$key];
            if ($new_value != $previous_value) {
                return false;
            }
        }

        return true;
    }

    /**
     * What data would need to be submitted to get this question correct.
     * If there is more than one correct answer, this method should just
     * return one possibility.
     *
     * @return array parameter name => value.
     */
    public function get_correct_response()
    {
        $result = array();
        foreach ($this->order as $rowid) {
            $row = $this->rows[$rowid];
            foreach ($this->cols as $col) {
                $weight = $this->weight($row, $col);
                $key = $this->key($row, $col);
                if ($weight > 0) {
                    $result[$key] = $this->multiple ? 'on' : $col->id;
                }
            }
        }

        return $result;
    }

    /**
     * Grade a response to the question, returning a fraction between
     * get_min_fraction() and 1.0, and the corresponding {@link question_state}
     * right, partial or wrong.
     * @param array $response responses, as returned by
     *      {@link question_attempt_step::get_qt_data()}.
     * @return array (number, integer) the fraction, and the state.
     */
    public function grade_response(array $response)
    {
        $grade = $this->grading()->grade_question($this, $response);
        $state = question_state::graded_state_for_fraction($grade);
        return array($grade, $state);
    }

    /**
     * What data may be included in the form submission when a student submits
     * this question in its current state?
     *
     * This information is used in calls to optional_param. The parameter name
     * has {@link question_attempt::get_field_prefix()} automatically prepended.
     *
     * @return array|string variable name => PARAM_... constant, or, as a special case
     *      that should only be used in unavoidable, the constant question_attempt::USE_RAW_DATA
     *      meaning take all the raw submitted data belonging to this question.
     */
    public function get_expected_data()
    {
        $result = array();
        $cells = $this->cells();
        foreach ($cells as $key => $weight) {
            $result[$key] = $this->multiple ? PARAM_BOOL : PARAM_INT;
        }
        return $result;
    }

    /**
     * Returns an array where keys are the weights' cell names and the values
     * are the weights
     * 
     * @return array
     */
    public function cells()
    {
        $result = array();
        foreach ($this->order as $rowid) {
            $row = $this->rows[$rowid];
            foreach ($this->cols as $col) {
                $result[self::key($row, $col)] = $this->weight($row, $col);
            }
        }
        return $result;
    }

    /**
     * Returns an array where keys are the weights' cell names and the values
     * are the weights
     */
    public function getIterator()
    {
        return new ArrayIterator($this->cells());
    }

}
