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
defined('MOODLE_INTERNAL') || die();

/**
 * Represents a matrix question.
 */
class qtype_matrix_question extends question_graded_automatically_with_countback implements IteratorAggregate
{

    public $rows;
    public $cols;
    public $weights;
    public $grademethod;
    public $multiple;

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
     * @param type $key
     * 
     * @return float
     */
    public function weight($row = null, $col = null)
    {
        if (is_string($row) && is_null($col))
        {
            $key = $row;
            $key = str_replace('cell', $col, $row);
            list($row_id, $col_id) = explode('x', $key);
        }
        else
        {
            $row_id = is_object($row) ? $row->id : $row;
            $col_id = is_object($col) ? $col->id : $col;
        }
        return (float) $this->weights[$row_id][$col_id];
    }

    /**
     *
     * @param mixed $row
     * @param mixed $col
     * @return type 
     */
    public function key($row, $col)
    {
        $row_id = is_object($row) ? $row->id : $row;
        $col_id = is_object($col) ? $col->id : $col;
        $multiple = $this->multiple;

        return qtype_matrix_grading::cell_name($row_id, $col_id, $multiple);
    }

    /**
     *
     * @param type $response
     * @param type $row
     * @param type $col
     * 
     * @return boolean 
     */
    public function is_answered($response, $row, $col)
    {
        $key = $this->key($row, $col);
        $value = isset($response[$key]) ? $response[$key] : false;
        if ($value === false)
        {
            return false;
        }

        if ($this->multiple)
        {
            return !empty($value);
        }
        else
        {
            return $value == $col->id;
        }
    }

    /**
     * 
     * @param type $row
     * @param type $col
     * 
     * or
     * 
     * @param type $key
     * 
     * @return bool 
     */
    public function is_correct($row = null, $col = null)
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
     * @param question_attempt_step The first step of the {@link question_attempt}
     *      being started. Can be used to store state.
     * @param int $varant which variant of this question to start. Will be between
     *      1 and {@link get_num_variants()} inclusive.
     */
    function start_attempt(question_attempt_step $step, $variant)
    {
        ; //nothing todo
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
     * @param question_attempt_step The first step of the {@link question_attempt}
     *      being loaded.
     */
    function apply_attempt_state(question_attempt_step $step)
    {
        ; //nothing todo
    }

    /**
     * Work out a final grade for this attempt, taking into account all the
     * tries the student made.
     * @param array $responses the response for each try. Each element of this
     * array is a response array, as would be passed to {@link grade_response()}.
     * There may be between 1 and $totaltries responses.
     * @param int $totaltries The maximum number of tries allowed.
     * @return numeric the fraction that should be awarded for this
     * sequence of response.
     */
    public function compute_final_grade($responses, $totaltries)
    {
        $x = 1 / 0;
        echo 'hello';
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
        if ($this->multiple)
        {
            return true;
        }

        foreach ($this->rows as $row)
        {
            $key = $this->key($row, 0);
            if (!isset($response[$key]))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * In situations where is_gradable_response() returns false, this method
     * should generate a description of what the problem is.
     * @return string the message.
     */
    public function get_validation_error(array $response)
    {
        $is_gradable = $this->is_gradable_response($response);
        if ($is_gradable)
        {
            return '';
        }
        return qtype_matrix::get_string('oneanswerperrow');
    }

    /**
     * Produce a plain text summary of a response.
     * 
     * @param $response a response, as might be passed to {@link grade_response()}.
     * @return string a plain text summary of that response, that could be used in reports.
     */
    public function summarise_response(array $response)
    {
        $result = array();

        $row_index = 0;
        foreach ($this->rows as $row)
        {
            $col_index = 0;
            foreach ($this->cols as $col)
            {
                $key = $this->key($row, $col);
                if (isset($response[$key]))
                {
                    $result[] = "{$row->shorttext} : {$col->shorttext}";
                    
                }
                $col_index++;
            }
            $row_index++;
        }
        return implode(" / ", $result);
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
        if (count($prevresponse) != count($newresponse))
        {
            return false;
        }
        foreach ($prevresponse as $key => $previous_value)
        {
            if (!isset($newresponse[$key]))
            {
                return false;
            }
            $new_value = $newresponse[$key];
            if ($new_value != $previous_value)
            {
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
        foreach ($this->rows as $row)
        {
            foreach ($this->cols as $col)
            {
                $weight = $this->weight($row, $col);
                $key = $this->key($row, $col);
                if ($weight > 0)
                {
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
        foreach ($cells as $key => $weight)
        {
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
        foreach ($this->rows as $row)
        {
            foreach ($this->cols as $col)
            {
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