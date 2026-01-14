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
 * The question type class for the matrix question type.
 *
 */

use qtype_matrix\local\setting;
use qtype_matrix\local\lang;
use qtype_matrix\local\qtype_matrix_grading;

/**
 * Represents a matrix question.
 */
// FIXME: This makes no sense. question_graded_automatically_with_countback needs a question to be able to define hints.
//        Matrix doesn't do that. The interactive behaviour allows nrofhints + 1 tries. So Matrix always allows only one try.
//        So there's always just one final grade, not one dependending on the tries.
class qtype_matrix_question extends question_graded_automatically_with_countback {

    const KEY_ROWS_ORDER = '_order';

    public $rows;
    public $cols;
    public $weights;
    public $grademethod;
    public $multiple;
    public $shuffleanswers;
    public $usedndui;

    /**
     * Contains the keys of the rows array
     * Used to maintain order when shuffling answers
     *
     * @var array
     */
    protected $order = null;

    /**
     * The user's response of for the cell at $rowindex, $colindex. That is if the cell is checked or not.
     * If the user didn't make an answer at all (no response) the method returns false.
     *
     * @param array $response object containing the raw answer data
     * @param int $rowindex matrix row index
     * @param int $colindex matrix col index
     *
     * @return bool True if the cell at $rowindex, $colindex was checked by the user. False otherwise.
     */
    public function response(array $response, int $rowindex, int $colindex):bool {
        $key = $this::responsekey($rowindex, $colindex);
        return $response[$key] ?? false;
    }

    /**
     * Returns the response key for a given row and col index.
     * Should be a valid php and html identifier.
     *
     * @param int  $rowindex Relative row index number
     * @param int  $colindex Relative col index number
     *
     * @return string
     */
    public static function responsekey(int $rowindex, int $colindex): string {
        return 'row'.$rowindex.'col'.$colindex;
    }

    /**
     *
     * @param int $rowindex The row index to generate the key for
     * @param int $colindex The col index to generate the key for
     * @return string
     */
    public function formfieldname(int $rowindex, int $colindex = -1): string {
        return self::formfield_name($rowindex, $colindex, $this->multiple);
    }

    /**
     * Returns the new style form field name.
     * Should be a valid php and html identifier.
     *
     * @param int  $rowindex Relative row index number
     * @param int  $colindex Relative col index number
     * @param bool $multiple one answer per row or several
     *
     * @return string
     */
    public static function formfield_name(int $rowindex, int $colindex, bool $multiple): string {
        $cellname = 'r'.$rowindex;
        if ($multiple) {
            $cellname .= 'c'.$colindex;
        }
        return $cellname;
    }

    /**
     * Returns the expected answer for the cell at $rowid, $colid.
     *
     * @param int $rowid
     * @param int $colid
     *
     * @return boolean  True if cell($rowid, $colid) is correct, false otherwise.
     */
    public function answer(int $rowindex, int $colindex): bool {
        $rowid = $this->order[$rowindex];
        $colid = array_keys($this->cols)[$colindex];
        return $this->weight($rowid, $colid) > 0;
    }

    /**
     *
     * @param int $rowid
     * @param int $colid
     * @return float
     */
    public function weight(int $rowid, int $colid):float {
        return (float) $this->weights[$rowid][$colid] ?? 0;
    }

    public function autopass_row(int $rowindex): bool {
        if (!setting::allow_autopass() || !$this->order) {
            return false;
        }
        $rowid = $this->order[$rowindex];
        return $this->rows[$rowid]->autopass;
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
     *
     * @param question_attempt_step $step
     *          The first step of the {@link question_attempt} being started.
     *          Can be used to store state.
     * @param int                   $variant
     *          Which variant of this question to start. Will be between
     *          1 and {@link get_num_variants()} inclusive.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public function start_attempt(question_attempt_step $step, $variant): void {
        $this->order = array_keys($this->rows);
        if ($this->shuffle_answers()) {
            shuffle($this->order);
        }
        $this->write_order_data($step);
    }

    /**
     * @return bool True if rows should be shuffled. False otherwise.
     * @throws dml_exception
     */
    public function shuffle_answers(): bool {
        if (!$this->shuffle_authorized()) {
            return false;
        }
        return $this->shuffleanswers;
    }

    /**
     * Question shuffle can be disabled at the Quiz level. If false then the
     * question parts are not shuffled. If true then the question's shuffle parameter
     * decide wheter the question's parts are actually shuffled.
     *
     * If the question is executed outside of a Quiz (for example in preview)
     * returns true.
     *
     * @return boolean          True if shuffling is authorized. False otherwise.
     * @throws dml_exception
     * @global object $DB   Database object
     * @global object $PAGE Page object
     */
    private function shuffle_authorized(): bool {
        global $DB, $PAGE;
        $cm = $PAGE->cm;
        if (!is_object($cm)) {
            return true;
        }
        // There is no API for activities to detect whether they use questions or may shuffle them
        // So we just allow shuffling for any other activity than quiz
        if ($cm->modname != 'quiz') {
            return true;
        }

        return $DB->get_record('quiz', ['id' => $cm->instance])->shuffleanswers ?? $this->shuffleanswers;
    }

    /**
     * Write persistent data to a step for further retrieval
     *
     * @param question_attempt_step $step
     * @return void
     * @throws coding_exception
     */
    protected function write_order_data(question_attempt_step $step): void {
        $step->set_qt_var(self::KEY_ROWS_ORDER, implode(',', $this->order));
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
     *
     * @param question_attempt_step $step The first step of the {@link question_attempt}
     *                                    being loaded.
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     */
    public function apply_attempt_state(question_attempt_step $step): void {
        $this->load_data($step);
    }

    /**
     * Load persistent data from a step.
     *
     * @param question_attempt_step $step Storage
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     */
    protected function load_data(question_attempt_step $step): void {
        $order = $step->get_qt_var(self::KEY_ROWS_ORDER);
        if ($order !== null) {
            $this->order = explode(',', $order);
        } else {
            // The order doesn't exist in the database.
            // This can happen because the question is old and doesn't have the shuffling possibility yet.
            $this->order = array_keys($this->rows);
            if ($this->shuffle_answers()) {
                shuffle($this->order);
            }
            $this->write_order_data($step);
        }
    }

    /**
     * @param question_attempt $qa
     * @return array
     * @throws coding_exception
     */
    public function get_order(question_attempt $qa): array {
        $this->init_order($qa);
        return $this->order;
    }

    /**
     * @param question_attempt $qa
     * @return void
     * @throws coding_exception
     */
    protected function init_order(question_attempt $qa): void {
        if ($this->order) {
            return;
        }
        $this->order = explode(',', $qa->get_step(0)->get_qt_var(self::KEY_ROWS_ORDER));
    }

    /**
     * Verify if an attempt at this question can be re-graded using the other question version.
     *
     * To put it another way, will {@see update_attempt_state_data_for_new_version()} be able to work?
     *
     * It is expected that this relationship is symmetrical, so if you can regrade from V1 to V3, then
     * you can change back from V3 to V1 (qtype_matrix is not symmetrical)
     *
     * @param question_definition $otherversion Some older different version of the question to use in the regrade.
     * @return string|null null if the regrade can proceed, else a reason why not.
     */
    public function validate_can_regrade_with_other_version(question_definition $otherversion): ?string {
        $basemessage = parent::validate_can_regrade_with_other_version($otherversion);
        if ($basemessage) {
            return $basemessage;
        }
        /** @var qtype_matrix_question $oldmatrixversion */
        $oldmatrixversion = $otherversion;

        // FIXME: Because of question versioning, a mechanism is missing
        //        where if a new version has the same nr of rows
        //        (by e.g. deleting the first and then creating a new row)
        //        it will be seen as regradable but shouldn't
        //        (because the response is now mismatched)
        if (count($this->rows) != count($oldmatrixversion->rows)) {
            return get_string('regrade_different_nr_rows', 'qtype_matrix');
        }
        if (count($this->cols) != count($oldmatrixversion->cols)) {
            return get_string('regrade_different_nr_cols', 'qtype_matrix');
        }

        // FIXME: This would currently prevent a workflow where we change from single to multiple
        //        and mark all columns as correct to remove all points for all participants
        //        to then give everyone the same bonus point.
        // TODO: This probably must be activated because going from multiple to single and a user that checked all, his answer is now always correct
//        if ($oldmatrixversion->multiple != $this->multiple) {
//            $thisrows = array_keys($this->rows);
//            $otherrows = array_keys($otherversion->rows);
//            foreach ($thisrows as $rowindex => $rowid) {
//                $otherrowid = $otherrows[$rowindex];
//                if ($this->count_correct_answers($rowid) > 1 || $otherversion->count_correct_answers($otherrowid) > 1) {
//                    return get_string('regrade_switching_multiple_too_many_correct', 'qtype_matrix');
//                }
//            }
//        }

        return null;
    }

    /**
     * Counts the nr of answers defined as correct for a matrix row.
     * @param $rowid - the database ID of the row to count for
     * @return int - the nr of answers defined as correct
     */
    public function count_correct_answers($rowid): int {
        $nrcorrectanswers = 0;
        foreach ($this->cols as $colid => $col) {
            if ($this->weight($rowid, $colid) > 0) {
                $nrcorrectanswers++;
            }
        }
        return $nrcorrectanswers;
    }

    /**
     * During regrading a quiz attempt that perhaps always uses the latest version,
     * it may happen that a quiz question received a new version.
     * Moodle checks if the new question version is sufficiently similar to the old version
     * that regrades are possible (which the questiontype decides).
     * If it is allowed, the attempt data must be adapted to the new question version,
     * which this function does.
     *
     * @param question_attempt_step $oldstep the first step of a {@see question_attempt} at $oldquestion.
     * @param qtype_matrix_question $oldquestion the previous version of the question, which $oldstate comes from.
     * @return array the submit data which can be passed to {@see apply_attempt_state} to start
     *     an attempt at this version of this question, corresponding to the attempt at the old question.
     * @throws coding_exception if this can't be done.
     */
    public function update_attempt_state_data_for_new_version(
        question_attempt_step $oldstep, question_definition $oldquestion) {
        $newattemptdata = parent::update_attempt_state_data_for_new_version($oldstep, $oldquestion);
        // Map the possibly shuffled old rows to the new question version's rows
        $oldroworder = explode(',', $newattemptdata[self::KEY_ROWS_ORDER]);
        $oldnewrowmapping = array_combine(array_keys($oldquestion->rows), array_keys($this->rows));
        $newroworder = [];
        foreach ($oldroworder as $oldrowid) {
            $newroworder[] = $oldnewrowmapping[$oldrowid];
        }
        $newattemptdata[self::KEY_ROWS_ORDER] = implode(',', $newroworder);
        return $newattemptdata;
    }

    /**
     * Work out a final grade for this attempt, taking into account all the
     * tries the student made.
     *
     * @param array $responses  the response for each try. Each element of this
     *                          array is a response array, as would be passed to {@link grade_response()}.
     *                          There may be between 1 and $totaltries responses.
     *
     * @param int   $totaltries The maximum number of tries allowed.
     *
     * @return numeric the fraction that should be awarded for this
     * sequence of response.
     */
    public function compute_final_grade($responses, $totaltries): float {
        $gradevalue = 0;
        foreach ($responses as $response) {
            $x = $this->grade_response($response);
            // FIXME: This doesn't make sense taking into account what's documented for the countback behaviour
            //        Example: You attempt the question save three different responses, each having enough items correct to get a grade of e.g. 0.5 for each response
            //        So now the final grade is 1.5 out of ... um 1.0 ? Depends on what the maximum grade one can get for a question
            //        Also, no question hints means this behaviour function is useless anyway.
            $gradevalue += $x[0];
        }
        return $gradevalue;
    }

    /**
     * Checks if any row in this question version automatically receives a passing grade.
     * @return bool
     */
    public function has_autopass_rows(): bool {
        foreach ($this->rows as $row) {
            if ($row->autopass) {
                return true;
            }
        }
        return false;
    }

    /**
     * Grade a response to the question, returning a fraction between
     * get_min_fraction() and 1.0, and the corresponding {@link question_state}
     * right, partial or wrong.
     *
     * @param array $response responses, as returned by
     *                        {@link question_attempt_step::get_qt_data()}.
     * @return array (number, integer) the fraction, and the state.
     */
    public function grade_response(array $response): array {
        $grade = $this->grading()->grade_question($this, $this->order, $response);
        $state = question_state::graded_state_for_fraction($grade);
        return [$grade, $state];
    }

    /**
     *
     * @return qtype_matrix_grading
     */
    public function grading(): qtype_matrix_grading {
        return qtype_matrix::grading($this->grademethod);
    }

    /**
     * Used by many of the behaviours, to work out whether the student's
     * response to the question is complete. That is, whether the question attempt
     * should move to the COMPLETE or INCOMPLETE state.
     *
     * @param array $response responses, as returned by
     *                        {@link question_attempt_step::get_qt_data()}.
     * @return bool whether this response is a complete answer to this question.
     */
    public function is_complete_response(array $response): bool {
        if ($this->multiple) {
            return true;
        }
        $nransweredrows = count($response);

        if ($nransweredrows == 0) {
            return false;
        }
        return ($nransweredrows == count($this->rows));
    }

    /**
     * In situations where is_gradable_response() returns false, this method
     * should generate a description of what the problem is.
     *
     * @param array $response
     * @return string the message.
     * @throws coding_exception
     */
    public function get_validation_error(array $response): ?string {
        if (!$this->is_complete_response($response)) {
            return lang::one_answer_per_row();
        }
        return null;
    }

    /**
     * Use by many of the behaviours to determine whether the student
     * has provided enough of an answer for the question to be graded automatically,
     * or whether it must be considered aborted.
     *
     * @param array $response responses, as returned by
     *                        {@see question_attempt_step::get_qt_data()}.
     * @return bool whether this response can be graded.
     */
    public function is_gradable_response(array $response): bool {
        return (count($response) > 0);
    }

    /**
     * Produce a plain text summary of a response.
     *
     * @param array $response A response, as might be passed to {@link grade_response()}.
     * @return string a plain text summary of that response, that could be used in reports.
     */
    public function summarise_response(array $response): string {
        $result = [];
        $colids = array_keys($this->cols);
        foreach ($this->order as $rowindex => $rowid) {
            $row = $this->rows[$rowid];
            foreach ($colids as $colindex => $colid) {
                if ($this->response($response, $rowindex, $colindex)) {
                    $result[] = $row->shorttext.': '.$this->cols[$colid]->shorttext;
                }
            }
        }
        return implode('; ', $result);
    }

    /**
     * Use by many of the behaviours to determine whether the student's
     * response has changed. This is normally used to determine that a new set
     * of responses can safely be discarded.
     *
     * @param array $prevresponse the responses previously recorded for this question,
     *                            as returned by {@link question_attempt_step::get_qt_data()}
     * @param array $newresponse  the new responses, in the same format.
     * @return bool whether the two sets of responses are the same - that is
     *                            whether the new set of responses can safely be discarded.
     */
    public function is_same_response(array $prevresponse, array $newresponse): bool {
        if (count($prevresponse) != count($newresponse)) {
            return false;
        }
        foreach ($prevresponse as $key => $previousvalue) {
            if (!isset($newresponse[$key])) {
                return false;
            }
            $newvalue = $newresponse[$key];
            if ($newvalue != $previousvalue) {
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
    public function get_correct_response(): array {
        $response = [];
        $colids = array_keys($this->cols);
        foreach ($this->order as $rowindex => $rowid) {
            foreach ($colids as $colindex => $colid) {
                if ($this->weight($rowid, $colid) > 0) {
                    $key = $this::responsekey($rowindex, $colindex);
                    $response[$key] = true;
                    if (!$this->multiple) {
                        break;
                    }
                }
            }
        }
        return $response;
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
    public function get_expected_data(): array {
        $responsesignature = [];
        foreach ($this->order as $rowindex => $rowid) {
            foreach (array_keys($this->cols) as $colindex => $colid) {
                $key = $this::responsekey($rowindex, $colindex);
                $responsesignature[$key] = PARAM_BOOL;
            }
        }
        return $responsesignature;
    }

    /**
     * Categorise the student's response according to the categories defined by get_possible_responses.
     * @param array $response a response, as might be passed to  grade_response().
     * @return array subpartid => question_classified_response objects.
     *      returns an empty array if no analysis is possible.
     */
    public function classify_response(array $response) {
        // See which column numbers have been selected.
        $selectedcolumns = $this->get_selected_columns($response);

        $classifiedresponses = [];
        $nrrows = count($this->rows);
        foreach ($this->rows as $rowid => $row) {
            $rowresponses = [];
            if (!$selectedcolumns[$rowid]) {
                $classifiedresponses[$rowid] = question_classified_response::no_response();
                continue;
            }
            foreach ($selectedcolumns[$rowid] as $colid) {
                $partialcredit = 0;
                if ($this->weights[$rowid][$colid] > 0) {
                    $partialcredit = $this->grademethod == 'all' ? (1 / $nrrows) : 1;
                }
                $column = $this->cols[$colid];
                $classifiedresponse = new question_classified_response($colid, $column->shorttext, $partialcredit);
                if ($this->multiple) {
                    $rowresponses[$colid] = $classifiedresponse;
                } else {
                    $classifiedresponses[$rowid] = $classifiedresponse;
                    break;
                }
            }
            if ($this->multiple) {
                $classifiedresponses[$rowid] = $rowresponses;
            }
        }

        return $classifiedresponses;
    }

    protected function get_selected_columns(array $response): array {
        $selectedcolumns = [];
        foreach ($this->order as $rowindex => $rowid) {
            $selectedcolumns[$rowid] = [];
            $indicedcolids = array_keys($this->cols);
            foreach ($indicedcolids as $colindex => $colid) {
                if ($this->response($response, $rowindex, $colindex)) {
                    $selectedcolumns[$rowid][] = $colid;
                    if (!$this->multiple) {
                        break;
                    }
                }
            }
        }
        return $selectedcolumns;
    }
}
