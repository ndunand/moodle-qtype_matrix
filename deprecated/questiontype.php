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

require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__)) . '/qtype_matrix_grading.class.php';

// renderer for the whole question - needs a matching class
// see matrix_qtype::matrix_renderer_options
define('QTYPE_MATRIX_RENDERER_MATRIX', 'matrix');

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

    public static function matrix_renderer_options()
    {
        return array(
            QTYPE_MATRIX_RENDERER_MATRIX => get_string('matrix', 'qtype_matrix'),
        );
    }

    function name()
    {
        return 'matrix';
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question_options($questionid, $contextid = null)
    {
        if (empty($questionid))
        {
            return false;
        }

        global $DB;
        global $CFG;

        $prefix = $CFG->prefix;

        //wheights
        $sql = "DELETE FROM {$prefix}question_matrix_weights
                WHERE {$prefix}question_matrix_weights.rowid IN 
                      (
                      SELECT rows.id FROM {$prefix}question_matrix_rows  AS rows
                      INNER JOIN {$prefix}question_matrix      AS matrix ON rows.matrixid = matrix.id
                      WHERE matrix.questionid = $questionid
                      )";
        $DB->execute($sql);

        //rows
        $sql = "DELETE FROM {$prefix}question_matrix_rows
                WHERE {$prefix}question_matrix_rows.matrixid IN 
                      (
                      SELECT matrix.id FROM {$prefix}question_matrix AS matrix
                      WHERE matrix.questionid = $questionid
                      )";
        $DB->execute($sql);

        //cols
        $sql = "DELETE FROM {$prefix}question_matrix_cols
                WHERE {$prefix}question_matrix_cols.matrixid IN 
                      (
                      SELECT matrix.id FROM {$prefix}question_matrix AS matrix
                      WHERE matrix.questionid = $questionid
                      )";
        $DB->execute($sql);

        //matrix
        $sql = "DELETE FROM {$prefix}question_matrix WHERE questionid = $questionid";
        $DB->execute($sql);

        return true;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question($questionid, $contextid = null)
    {
        if (empty($questionid))
        {
            return false;
        }
        
        
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $this->delete_question_options($questionid);
        parent::delete_question($questionid, $contextid);

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
     * whether this question can be automatically graded
     * dependent on the grade method
     */
    function is_question_manual_graded($question, $otherquestionsinuse)
    {
        if (!$matrix = get_record('question_matrix', 'questionid', $question->id))
        {
            return false; // sensible default
        }
        $grading = self::grading($matrix->grademethod);
        return $grading->is_manual_graded();
    }

    /*
     * @return boolean to indicate success of failure.
     */

    function get_question_options($question)
    {
        parent::get_question_options($question);
        $matrix = self::retrieve_matrix($question->id);
        if ($matrix)
        {
            $question->options->rows = $matrix->rows;
            $question->options->cols = $matrix->cols;
            $question->options->weights = $matrix->weights;
            $question->options->grademethod = $matrix->grademethod;
            $question->options->multiple = $matrix->multiple;
            $question->options->renderer = $matrix->renderer;
        }
        else
        {
            $question->options->rows = array();
            $question->options->cols = array();
            $question->options->weights = array(array());
            $question->options->grademethod = self::defaut_grading()->get_name();
            $question->options->multiple = true;
        }
        return true;
    }

    static function retrieve_matrix($question_id)
    {
        if (empty($question_id))
        {
            return null;
        }

        static $results = array();
        if (isset($results[$question_id]))
        {
            return $results[$question_id];
        }

        global $DB;
        $matrix = $DB->get_record('question_matrix', array('questionid' => $question_id));

        if (empty($matrix))
        {
            return false;
        }
        else
        {
            $matrix->multiple = (bool) $matrix->multiple;
        }
        $matrix->rows = $DB->get_records('question_matrix_rows', array('matrixid' => $matrix->id), 'id ASC');
        $matrix->rows = $matrix->rows ? $matrix->rows : array();

        $matrix->cols = $DB->get_records('question_matrix_cols', array('matrixid' => $matrix->id), 'id ASC');
        $matrix->cols = $matrix->cols ? $matrix->cols : array();

        global $CFG;
        $prefix = $CFG->prefix;
        $sql = "SELECT weights.* 
                FROM {$prefix}question_matrix_weights AS weights
                WHERE 
                    rowid IN (SELECT rows.id FROM {$prefix}question_matrix_rows     AS rows 
                              INNER JOIN {$prefix}question_matrix                   AS matrix ON rows.matrixid = matrix.id
                              WHERE matrix.questionid = $question_id)
                    OR
                              
                    colid IN (SELECT cols.id FROM {$prefix}question_matrix_cols     AS cols
                              INNER JOIN {$prefix}question_matrix                   AS matrix ON cols.matrixid = matrix.id
                              WHERE matrix.questionid = $question_id)
               ";
        $matrix->rawweights = $DB->get_records_sql($sql);

        $matrix->weights = array();
       
        foreach ($matrix->rows as $r)
        {
            $matrix->fullmatrix[$r->id] = array();
            foreach ($matrix->cols as $c)
            {
                $matrix->weights[$r->id][$c->id] = 0;
            }
        }
        foreach ($matrix->rawweights as $w)
        {
            $matrix->weights[$w->rowid][$w->colid] = $w->weight;
        }
        $results[$question_id] = $matrix;
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
        //parent::save_question_options($question);

        $question_id = isset($question->id) ? $question->id : false;
        if (empty($question->makecopy))
        {
            $this->delete_question_options($question_id);
        }

        $transaction = $DB->start_delegated_transaction();

        $matrix = (object) array(
                    'questionid' => $question->id,
                    'multiple' => $question->multiple,
                    'grademethod' => $question->grademethod,
                    'renderer' => 'matrix'
        );

        $matrix_id = $DB->insert_record('question_matrix', $matrix);

        // rows
        $rowids = array(); //mapping for indexes to db ids.
        foreach ($question->rowshort as $i => $short)
        {
            if (empty($short))
            {
                break;
            }
            $row = (object) array(
                        'matrixid' => $matrix_id,
                        'shorttext' => $question->rowshort[$i],
                        'description' => $question->rowlong[$i],
                        'feedback' => $question->rowfeedback[$i]
            );
            $newid = $DB->insert_record('question_matrix_rows', $row);
            $rowids[] = $newid;
        }

        // cols
        $colids = array();
        foreach ($question->colshort as $i => $short)
        {
            if (empty($short))
            {
                break;
            }
            $col = (object) array(
                        'matrixid' => $matrix_id,
                        'shorttext' => $question->colshort[$i],
                        'description' => $question->collong[$i]
            );

            $newid = $DB->insert_record('question_matrix_cols', $col);
            $colids[] = $newid;
        }

        //wheights
        if ($question->multiple)
        {
            foreach ($question->matrix as $key => $value)
            {
                list($x, $y) = qtype_matrix_grading::cell_index($key);
                $value = (float) $value;

                $weight = (object) array(
                            'rowid' => $rowids[$x],
                            'colid' => $colids[$y],
                            'weight' => $value
                );
                $DB->insert_record('question_matrix_weights', $weight);
            }
        }
        else
        {
            foreach ($question->matrix as $key => $col)
            {
                list($x) = qtype_matrix_grading::cell_index($key);
                $y = $col;
                $value = 1;

                $weight = (object) array(
                            'rowid' => $rowids[$x],
                            'colid' => $colids[$y],
                            'weight' => $value
                );
                $DB->insert_record('question_matrix_weights', $weight);
            }
            
        }

        $transaction->allow_commit();
    }

    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt)
    {
        $state->responses = array();
        return true;
    }

    function restore_session_and_responses(&$question, &$state)
    {
        if (!is_array($state->responses) || count($state->responses) != 1)
        {
            return false;
        }
        $tmp = array_values($state->responses);
        $tmp = stripslashes($tmp[0]);
        if (!is_string($tmp))
        {
            return false;
        }
        if (!$data = @unserialize($tmp))
        {
            return false;
        }

        $state->responses = array();
        $gradeclass = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);
        foreach ($data as $r => $row)
        {
            foreach ($row as $c => $value)
            {
                $cellname = $gradeclass->cellname($r, $c);
                if ($value == 'on')
                { // checkbox
                    $state->responses[$cellname] = $value;
                }
                else if ($value == $c)
                { // radio
                    $state->responses[$cellname] = $c;
                }
            }
        }
        return true;
    }

    function save_session_and_responses(&$question, &$state)
    {
        $matrix = self::cells_to_matrix($state->responses, $question->options->rows, $question->options->cols);
        $responses = serialize($matrix);
        return set_field('question_states', 'answer', $responses, 'id', $state->id);
    }

    function get_all_responses(&$question, &$state)
    {
        $result = new stdClass();
        $result->id = $question->id;
        $result->responses = array();

        $grade = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);
        $grade->set_weights($question->options->weights);
        $grade->set_responses($state->responses);

        $matrix = self::cells_to_matrix($state->responses, $question->options->rows, $question->options->cols);
        foreach ($matrix as $row_id => $row)
        {
            $row_header = self::render_matrix_header($question->options->rows[$row_id], true);
            $items = array();
            foreach ($row as $col_id => $cell)
            {
                $key = cellname($row_id, $col_id);
                $weight = $grade->get_weight($row_id, $col_id);
                $col_header = self::render_matrix_header($question->options->cols[$col_id], true);

                if ($weight)
                {
                    $r = new stdClass;
                    $r->credit = $weight;
                    $r->answer = "$row_header : $col_header";
                    $result->responses[$key] = $r;

//                $r = new stdClass;
//                $r->credit = 0;
//                $r->answer = "$row_header : $col_header (_)";
//                $result->responses[$key . '_'] = $r;
                }
            }
        }
        return $result;
    }

    function get_actual_response($question, $state)
    {
        $grade = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);
        $grade->set_weights($question->options->weights);
        $grade->set_responses($state->responses);

        $matrix = self::cells_to_matrix($state->responses, $question->options->rows, $question->options->cols);
        $responses = array();

        foreach ($matrix as $row_id => $row)
        {
            $row_header = self::render_matrix_header($question->options->rows[$row_id], true);
            $items = array();
            foreach ($row as $col_id => $cell)
            {
                if ($cell)
                {
                    $key = cellname($row_id, $col_id);
                    $weight = $grade->get_weight($row_id, $col_id);
                    $col_header = self::render_matrix_header($question->options->cols[$col_id], true);

                    $responses[$key] = "$row_header : $col_header";
                }
            }
        }
        return $responses;
    }

    /**
     * Returns correct responses. That is all that have a weight greater than 0. 
     */
    function get_correct_responses(&$question, &$state)
    {
        $result = array();
        $weights = $question->options->weights;
        $gradeclass = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);
        foreach ($weights as $row_id => $row)
        {
            foreach ($row as $col_id => $weight)
            {
                $weight = (float) $weight;
                if ($weight > 0)
                {
                    $cell_name = cellname($row_id, $col_id);
                    $result[$cell_name] = $question->options->multiple ? $weight : $col_id;
                }
            }
        }
        return $result;
    }

    /**
     * Checks if the response given is correct and returns the id
     *
     * @return int             The ide number for the stored answer that matches the response
     *                         given by the user in a particular attempt.
     * @param object $question The question for which the correct answer is to
     *                         be retrieved. Question type specific information is
     *                         available.
     * @param object $state    The state object that corresponds to the question,
     *                         for which a correct answer is needed. Question
     *                         type specific information is included.
     */
    // ULPGC ecastro
    function check_response(&$question, &$state)
    {
        return false;
    }

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options)
    {
        global $CFG;
        $readonly = empty($options->readonly) ? '' : ' readonly="readonly" ';

        // Print formulation
        $questiontext = $this->format_text($question->questiontext, $question->questiontextformat, $cmoptions);

        //$image = get_question_image($question, $cmoptions->course);
        $image = null; //does not look supported in moodle 2.0 anymore.

        $showcorrect = !empty($options->correct_responses);
        $gradeclass = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);
        if ($gradeclass->is_manual_graded())
        {
            $showcorrect = false;
        }

        $matrix = self::render_full_matrix($question, $state->responses, $readonly, $showcorrect);

        include("$CFG->dirroot/question/type/matrix/display.html");
    }

    function grade_responses(&$question, &$state, $cmoptions)
    {
        $gradeclass = matrix_qtype::grade_class($question->options->grademethod, $question->options->multiple);

        if ($gradeclass->is_manual_graded())
        {
            $state->raw_grade = 0;
            $state->penalty = 0;

            return true;
        }
        $gradeclass->set_weights($question->options->weights);
        $subqs = $gradeclass->grade_matrix($state->responses);

        $state->raw_grade = $gradeclass->grade_question($subqs);
        //
        // Make sure we don't assign negative or too high marks.
        $state->raw_grade = min(max((float) $state->raw_grade, 0.0), 1.0) * $question->maxgrade;

        // Update the penalty.
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event == QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
        return true;
    }

    function compare_responses($question, $state, $teststate)
    {
        // compare arrays with array_diff_assoc and not array_diff as the former one compares only values.
        $result = (count(array_diff_assoc($state->responses, $teststate->responses)) == 0
                && count(array_diff_assoc($teststate->responses, $state->responses)) == 0);

        return $result;
    }

    /**
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf, $preferences, $question, $level = 6)
    {
        $status = true;
        if (!$alldata = self::load_all_data($question))
        {
            return $status;
        }
        $status = $status && fwrite($bf, start_tag('MATRIX', $level, true));
        $status = $status && fwrite($bf, full_tag('QUESTIONID', $level + 1, false, $question));
        $status = $status && fwrite($bf, full_tag('GRADEMETHOD', $level + 1, false, $alldata->grademethod));
        $status = $status && fwrite($bf, full_tag('MULTIPLE', $level + 1, false, $alldata->multiple));
        $status = $status && fwrite($bf, full_tag('RENDERER', $level + 1, false, $alldata->renderer));
        $status = $status && fwrite($bf, start_tag('ROWS', $level + 1, true));
        foreach ($alldata->rows as $row)
        {
            $status = $status && fwrite($bf, start_tag('ROW', $level + 2, true));
            $status = $status && fwrite($bf, full_tag('ID', $level + 3, false, $row->id));
            $status = $status && fwrite($bf, full_tag('MATRIXID', $level + 3, false, $row->matrixid));
            $status = $status && fwrite($bf, full_tag('SHORTTEXT', $level + 3, false, $row->shorttext));
            $status = $status && fwrite($bf, full_tag('DESCRIPTION', $level + 3, false, $row->description));
            $status = $status && fwrite($bf, full_tag('FEEDBACK', $level + 3, false, $row->feedback));
            $status = $status && fwrite($bf, end_tag('ROW', $level + 2, true));
        }
        $status = $status && fwrite($bf, end_tag('ROWS', $level + 1, true));
        $status = $status && fwrite($bf, start_tag('COLS', $level + 1, true));
        foreach ($alldata->cols as $col)
        {
            $status = $status && fwrite($bf, start_tag('COL', $level + 2, true));
            $status = $status && fwrite($bf, full_tag('ID', $level + 3, false, $col->id));
            $status = $status && fwrite($bf, full_tag('MATRIXID', $level + 3, false, $col->matrixid));
            $status = $status && fwrite($bf, full_tag('SHORTTEXT', $level + 3, false, $col->shorttext));
            $status = $status && fwrite($bf, full_tag('DESCRIPTION', $level + 3, false, $col->description));
            $status = $status && fwrite($bf, end_tag('COL', $level + 2, true));
        }
        $status = $status && fwrite($bf, end_tag('COLS', $level + 1, true));
        $status = $status && fwrite($bf, start_tag('WEIGHTS', $level + 1, true));
        foreach ($alldata->rawweights as $weight)
        {
            $status = $status && fwrite($bf, start_tag('WEIGHT', $level + 2, true));
            $status = $status && fwrite($bf, full_tag('ID', $level + 3, false, $weight->id));
            $status = $status && fwrite($bf, full_tag('ROWID', $level + 3, false, $weight->rowid));
            $status = $status && fwrite($bf, full_tag('COLID', $level + 3, false, $weight->colid));
            $status = $status && fwrite($bf, full_tag('WEIGHT', $level + 3, false, $weight->weight));
            $status = $status && fwrite($bf, end_tag('WEIGHT', $level + 2, true));
        }
        $status = $status && fwrite($bf, end_tag('WEIGHTS', $level + 1, true));

        $status = $status && fwrite($bf, end_tag('MATRIX', $level, true));
        $status = question_backup_answers($bf, $preferences, $question);

        return $status;
    }

    /**
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($old_question_id, $new_question_id, $info, $restore)
    {
        $status = begin_sql();

        $minfo = $info['#']['MATRIX'];
        $newmatrix = (object) array(
                    'questionid' => $new_question_id,
                    'grademethod' => backup_todb($minfo[0]['#']['GRADEMETHOD']['0']['#']),
                    'multiple' => backup_todb($minfo[0]['#']['MULTIPLE']['0']['#']),
                    'renderer' => backup_todb($minfo[0]['#']['RENDERER']['0']['#']),
        );

        $newmatrix->id = insert_record('question_matrix', $newmatrix);

        $rows = $minfo[0]['#']['ROWS'][0]['#']['ROW']; // why does this get eaten?!
        $rowmapping = array();
        foreach ($rows as $row)
        {
            $row = $row['#']; // more nonsense
            $newrow = (object) array(
                        'matrixid' => $newmatrix->id,
                        'shorttext' => backup_todb($row['SHORTTEXT']['0']['#']),
                        'description' => backup_todb($row['DESCRIPTION']['0']['#']),
                        'feedback' => backup_todb($row['FEEDBACK']['0']['#']),
            );
            $status = $status && $rowmapping[backup_todb($row['ID']['0']['#'])] = insert_record('question_matrix_rows', $newrow);
        }

        $cols = $minfo[0]['#']['COLS'][0]['#']['COL']; // why does this get eaten?!
        $colmapping = array();
        foreach ($cols as $col)
        {
            $col = $col['#']; // more nonsense
            $newcol = (object) array(
                        'matrixid' => $newmatrix->id,
                        'shorttext' => backup_todb($col['SHORTTEXT']['0']['#']),
                        'description' => backup_todb($col['DESCRIPTION']['0']['#']),
            );
            $status = $status && $colmapping[backup_todb($col['ID']['0']['#'])] = insert_record('question_matrix_cols', $newcol);
        }

        $weights = $minfo[0]['#']['WEIGHTS'][0]['#']['WEIGHT'];
        foreach ($weights as $weight)
        {
            $weight = $weight['#'];
            $newweight = (object) array(
                        'rowid' => $rowmapping[backup_todb($weight['ROWID'][0]['#'])],
                        'colid' => $colmapping[backup_todb($weight['COLID'][0]['#'])],
                        'weight' => backup_todb($weight['WEIGHT'][0]['#']),
            );
            $status = $status && insert_record('question_matrix_weights', $newweight);
        }
        return $status && commit_sql();
    }

    /**
     * Add styles.css to the page's header
     */
    function get_html_head_contributions(&$question, &$state)
    {
        return parent::get_html_head_contributions($question, $state);
    }

    static function count_form_rows_or_cols($data, $row = true, $returnvals = false)
    {
        $key = ($row) ? 'row' : 'col';
        $count = 0;
        $vals = array();
        $newvalcount = 0;

        $short_count = count($data[$key . 'short']);
        $long_count = count($data[$key . 'long']);
        $min = min($short_count, $long_count);
        for ($k = 0; $k < $min; $k++)
        {
            $short = aget($data, $key . 'short');  //isset($data[$key . 'short'][$k]) ? $data[$key . 'short'][$k] : '';
            $short = aget($short, $k);
            $long = aget($data, $key . 'long'); //isset($data[$key . 'long'][$k]) ? $data[$key . 'long'][$k] : '';
            $long = aget($long, $k);
            $feedback = aget($data, $key . 'feedback');
            $feedback = aget($feedback, $k);

            $shorttext = is_array($short) ? reset($short) : $short;
            $description = is_array($long) ? reset($long) : $long;
            $format = is_array($long) ? end($long) : '';

            if (!empty($short) || !empty($long))
            {
                $count++;
                if ($returnvals)
                {
                    if (array_key_exists($key . 'id', $data) && array_key_exists($k, $data[$key . 'id']) && !empty($data[$key . 'id'][$k]))
                    {
                        $thiskey = $data[$key . 'id'][$k];
                    }
                    else
                    {
                        $thiskey = $newvalcount;
                        $newvalcount++;
                    }
                    $vals[$thiskey] = compact('shorttext', 'description', 'format', 'feedback');
                }
            }
        }
        return $returnvals ? $vals : $count;
    }

    static function count_db_rows_or_cols($questionid, $table = 'rows', $returnvals = false)
    {
        global $CFG;
        $table = 'question_matrix_' . $table;
        $select = 'matrixid IN (SELECT id FROM ' . $CFG->prefix . 'question_matrix WHERE questionid = ' . $questionid . ')';
        if (!$returnvals)
        {
            return (int) count_records_select($table, $select);
        }
        return get_records_select($table, $select);
    }

    /**
     * Returns the grading object
     *
     * @staticvar array $cache
     * @param string $gradetype
     * @param bool $multiple
     * @return matrix_qtype_grading_base
     */
//    static function grade_class($gradetype, $multiple)
//    {
//        static $cache = array();
//        $classname = 'matrix_qtype_grading_' . $gradetype;
//        if (!array_key_exists($gradetype . '|' . $multiple, $cache))
//        {
//            if (!class_exists($classname))
//            {
//                $subdirlib = dirname(__FILE__) . '/grading/' . $gradetype . '/lib.php';
//                if (file_exists($subdirfile))
//                {
//                    include_once($subdirfile);
//                }
//            }
//            if (!class_exists($classname))
//            {
//                print_error("Invalid grade class $classname");
//            }
//        }
//        $cache[$gradetype . '|' . $multiple] = new $classname($multiple);
//        return $cache[$gradetype . '|' . $multiple];
//    }

//    static function formdata_to_matrix($data)
//    {
//        $matrix = array();
//
//        if (!array_key_exists('matrix', $data))
//        {
//            return $matrix;
//        }
//
//        $rows = self::count_form_rows_or_cols($data, true, true);
//        $cols = self::count_form_rows_or_cols($data, false, true);
//        $data = $data['matrix'];
//
//        return self::cells_to_matrix($data, $rows, $cols);
//    }
//
//    static function cells_to_matrix($data, $rows, $cols)
//    {
//        foreach ($rows as $i => $row)
//        {
//            $matrix[$i] = array();
//            foreach ($cols as $j => $col)
//            {
//                if (isset($data['cell' . $i . 'x' . $j]))
//                {
//                    $matrix[$i][$j] = $data['cell' . $i . 'x' . $j];
//                }
//                else if (isset($data['cell' . $i]) && $data['cell' . $i] == $j)
//                {
//                    $matrix[$i][$j] = $j;
//                }
//                else
//                {
//                    $matrix[$i][$j] = null;
//                }
//            }
//        }
//        return $matrix;
//    }
//
//    static function render_full_matrix(&$question, $responses, $readonly = false, $showcorrect = false)
//    {
//        global $CFG;
//        $alldata = self::load_all_data($question->id);
//        $table = new StdClass;
//        $table->head = array('');
//        $table->class = 'qtypematrixformmatrix';
//        $addheader = true;
//        $gradeclass = self::grade_class($alldata->grademethod, $alldata->multiple);
//        $gradeclass->set_question($question);
//        $gradeclass->set_responses($responses);
//        $gradeclass->set_weights($alldata->fullmatrix);
//
//        $grades = $showcorrect ? $gradeclass->grade_matrix($responses) : null;
//
//        foreach ($alldata->fullmatrix as $j => $row)
//        {
//            $thisrow = array(self::render_matrix_header($alldata->rows[$j]));
//            foreach ($row as $i => $col)
//            {
//                $addheader && $table->head[] = self::render_matrix_header($alldata->cols[$i]);
//                $thisrow[] = $gradeclass->render_cell($j, $i, $readonly, $showcorrect);
//            }
//            if ($showcorrect)
//            {
//                $feedback = $alldata->rows[$j]->feedback;
//                $feedback = strip_tags($feedback) ? '&nbsp;' . $feedback : '';
//                $thisrow[] = question_get_feedback_image($grades[$j]) . $feedback;
//            }
//            $addheader && $showcorrect && $table->head[] = '';
//            $addheader = false;
//            $table->data[] = $thisrow;
//        }
//        return print_table($table, true);
//    }

}

// Register this question type with the system.
//question_register_questiontype(new matrix_qtype());