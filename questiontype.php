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
use qtype_matrix\local\qtype_matrix_grading;
use qtype_matrix\db\question_matrix_store;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->libdir . '/questionlib.php';
require_once $CFG->dirroot . '/question/type/matrix/question.php';

/**
 * The matrix question class
 *
 * Pretty simple concept - a matrix with a number of different grading methods and options.
 */
class qtype_matrix extends question_type {

    public const DEFAULT_USEDNDUI = false;

    public const DEFAULT_MULTIPLE = false;

    public const DEFAULT_SHUFFLEANSWERS = true;

    public const DEFAULT_ROW_AUTOPASS = false;

    public static function clean_data($questiondata, bool $useoptions = false) {
        $datasource = $questiondata;
        if ($useoptions) {
            $questiondata->options ??= new stdClass();
            $datasource = $questiondata->options;
        }

        if (
            !isset($datasource->grademethod)
            || !is_string($datasource->grademethod)
            || !in_array($datasource->grademethod, qtype_matrix_grading::VALID_GRADINGS)
        ) {
            $datasource->grademethod = qtype_matrix_grading::default_grading()->get_name();
        }
        $datasource->multiple = (bool)($datasource->multiple ?? self::DEFAULT_MULTIPLE);
        $datasource->shuffleanswers = (bool)($datasource->shuffleanswers ?? self::DEFAULT_SHUFFLEANSWERS);
        $datasource->usedndui = (bool)($datasource->usedndui ?? self::DEFAULT_USEDNDUI);
        $datasource->rows ??= [];
        $datasource->cols ??= [];
        $datasource->weights ??= [[]];
        return $questiondata;
    }

    /**
     * @return qtype_matrix_grading[]
     */
    public static function gradings(): array {
        return qtype_matrix_grading::gradings();
    }

    public static function grading(string $type): qtype_matrix_grading {
        return qtype_matrix_grading::create($type);
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param int $questionid The question being deleted
     * @return boolean to indicate success of failure.
     * @throws dml_exception
     */
    private function delete_question_options(int $questionid): bool {
        if (empty($questionid)) {
            return false;
        }
        $store = new question_matrix_store();
        $store->delete_question($questionid);
        return true;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @param integer $contextid
     * @throws dml_exception
     */
    public function delete_question($questionid, $contextid = null) {
        if (empty($questionid)) {
            return;
        }
        $this->delete_question_options($questionid);
        parent::delete_question($questionid, $contextid);
    }

    /**
     * @return boolean true if this question type sometimes requires manual grading.
     */
    public function is_manual_graded(): bool {
        return false;
    }

    /**
     *
     * @param object $questiondata
     * @return boolean
     * @throws dml_exception
     */
    public function get_question_options($questiondata): bool {
        parent::get_question_options($questiondata);
        $questiondata = self::clean_data($questiondata, true);

        $matrix = self::retrieve_matrix($questiondata->id);
        $questiondata->options->rows = $matrix->rows ?? [];
        $questiondata->options->cols = $matrix->cols ?? [];
        $questiondata->options->weights = $matrix->weights ?? [[]];
        return true;
    }

    /**
     * cant type this function -> to many returning options!
     *
     * @param int $questionid
     * @return mixed|stdClass|null
     * @throws dml_exception
     */
    public static function retrieve_matrix(int $questionid) {
        if (empty($questionid)) {
            return null;
        }

        $store = new question_matrix_store();

        $matrix = $store->get_matrix_by_question_id($questionid);
        if (empty($matrix)) {
            return null;
        }
        $matrixid = $matrix->id;
        $matrix->rows = $store->get_matrix_rows_by_matrix_id($matrixid);
        $matrix->cols = $store->get_matrix_cols_by_matrix_id($matrixid);
        $rawweights = $store->get_matrix_weights_by_question_id($questionid);

        // Initialize weights.
        $matrix->weights = [];
        foreach ($matrix->rows as $row) {
            $matrix->weights[$row->id] = [];
            foreach ($matrix->cols as $col) {
                $matrix->weights[$row->id][$col->id] = 0;
            }
        }
        // Set non zero weights.
        foreach ($rawweights as $weight) {
            $matrix->weights[$weight->rowid][$weight->colid] = (float) $weight->weight;
        }
        return $matrix;
    }

    public static function default_grading(): qtype_matrix_grading {
        return qtype_matrix_grading::default_grading();
    }

    /**
     * @param $fromform - the form data containing the dimension's data
     * @param int $matrixid - the matrix id to store dimension data for
     * @param bool $isrow - whether we want to store rows or cols
     * @return array - the database ids of existing dimension records for the matrix
     */
    private function save_dimension($fromform, int $matrixid, bool $isrow):array {
        $store = new question_matrix_store();
        $dim = $isrow ? 'row' : 'col';
        $dimids = [];

        foreach ($fromform->{$dim.'s_shorttext'} as $i => $short) {
            if (!$short) {
                continue;
            }
            $dimrecord = (object) [
                'matrixid' => $matrixid,
                'shorttext' => $short,
                'description' => $fromform->{$dim.'s_description'}[$i]['text'],
            ];
            if ($isrow) {
                $dimrecord->feedback = $fromform->{'rows_feedback'}[$i]['text'];
                $dimrecord->autopass = $fromform->{'rows_autopass'}[$i];
            }
            $newdimid = $store->{'insert_matrix_'.$dim}($dimrecord);
            if ($newdimid) {
                $dimids[] = $newdimid;
            }
        }
        return $dimids;
    }
    /**
     * Saves question-type specific options.
     * This is called by {@link save_question()} to save the question-type specific data.
     * This is always called after a new question has been created and saved
     * @param object $fromform This holds the information from the editing form, it is not a standard question object.
     * @return object $result->error or $result->noticeyesno or $result->notice
     * @throws dml_exception
     * @throws dml_transaction_exception
     */
    public function save_question_options($fromform): object {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        parent::save_question_options($fromform);
        $store = new question_matrix_store();

        $questionid = $fromform->id;


        $matrix = (object) $store->get_matrix_by_question_id($questionid);
        $rowids = $this->save_dimension($fromform, $matrix->id,true);
        $colids = $this->save_dimension($fromform, $matrix->id,false);

        // FIXME: We should just not validate the form with true if we changed the option (or dynamically change the form with AJAX)
        // When we switch from multiple answers to single answers (or the other
        // way around) we loose answers.
        //
        // To avoid loosing information when we switch, we test if the weight matrix is empty.
        // If the weight matrix is empty we try to read from the other
        // representation directly from POST data.
        //
        // We read from the POST because post data are not read into the question
        // object because there is no corresponding field.
        //
        // This is bit hacky but it is safe. The to_weight_matrix returns only
        // 0 or 1.
        $multiple = $fromform->multiple;
        $weights = $this->to_weight_matrix($fromform, $multiple);
        if ($this->is_matrix_empty($weights)) {
            $weights = $this->to_weight_matrix((object) $_POST, !$multiple); // Todo: remove unsafe $_POST.
        }

        foreach ($rowids as $rowindex => $rowid) {
            foreach ($colids as $colindex => $colid) {
                $value = $weights[$rowindex][$colindex] ?? false;
                if ($value) {
                    $weight = (object) [
                        'rowid' => $rowid,
                        'colid' => $colid,
                        'weight' => 1
                    ];
                    $store->insert_matrix_weight($weight);
                }
            }
        }

        $transaction->allow_commit();
        return (object) [];
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
     * @param object  $fromform         Question's data, either from the question object or from the post
     * @param boolean $frommultiple Whether we extract from multiple representation or not
     * @result array                    The weights
     */
    public function to_weight_matrix(object $fromform, bool $frommultiple): array {
        // FIXME: Tests show that you even if it looks like there can only be a 20x20 matrix max, in single mode you could create out-of-bounds references.
        //        Even that is inconsistent currently, as you can reference too large colindices but not have too many rows
        // TODO: Should there a be a global limit for rows/cols? I don't think it's enforced consistently yet.
        // TODO: Do we even need a matrix with 0 value padding in code? Or just a sparse matrix?
        $matrix = array_fill(
            0,
            20,
            array_fill(0, 20, 0)
        );

        foreach ($matrix as $rowindex => $row) {
            foreach ($row as $colindex => $initialvalue) {
                // Reminder: The cell name only uses rowindex if we're not allowing multiple correct answers
                $formfieldname = qtype_matrix_question::formfield_name($rowindex, $colindex, $frommultiple);
                if (isset($fromform->{$formfieldname})) {
                    if (!$frommultiple) {
                        // Only one column can be correct, so ensure that we don't continue after we find it
                        $correctcolindex = $fromform->{$formfieldname};
                        $matrix[$rowindex][$correctcolindex] = 1;
                        break;
                    } else if ($fromform->{$formfieldname}) {
                        $matrix[$rowindex][$colindex] = 1;
                    }
                }
            }
        }
        return $matrix;
    }

    /**
     * True if the matrix is empty (contains only zeroes). False otherwise.
     *
     * @param array $matrix Array of arrays
     * @return boolean True if the matrix contains only zeros. False otherwise
     */
    public function is_matrix_empty(array $matrix): bool {
        foreach ($matrix as $row) {
            foreach ($row as $value) {
                if ($value && $value > 0) {
                    return false;
                }
            }
        }
        return true;
    }

    public function name(): string {
        return 'matrix';
    }

    public function extra_question_fields(): array {
        return ['qtype_matrix', 'grademethod', 'multiple', 'shuffleanswers', 'usedndui'];
    }

    /**
     * Cant type this function to many  types returned!
     * import a matrix question from Moodle XML format
     *
     * @param             $data
     * @param             $fromform
     * @param qformat_xml $format
     * @param null        $extra
     * @return bool|object
     */
    public function import_from_xml($data, $fromform, qformat_xml $format, $extra = null) {
        // TODO: Can't yet use parent::import_from_xml() because of MDL-87330
        // $fromform = parent::import_from_xml($data, $fromform, $format, $extra);
        if (!isset($data['@']['type']) || $data['@']['type'] != 'matrix') {
            return false;
        }

        // Initial.
        $fromform = $format->import_headers($data);
        $fromform->qtype = 'matrix';

        // Grademethod.
        $fromform->grademethod = $format->getpath(
            $data,
            ['#', 'grademethod', 0, '#'],
            self::default_grading()->get_name()
        );
        if (!in_array($fromform->grademethod, qtype_matrix_grading::VALID_GRADINGS)) {
            $fromform->grademethod = qtype_matrix_grading::default_grading()->get_name();
        }

        // Multiple.
        $fromform->multiple = (bool) $format->trans_single($format->getpath(
            $data,
            ['#', 'multiple', 0, '#'],
            self::DEFAULT_MULTIPLE)
        );

        // Shuffleanswers.
        $fromform->shuffleanswers = (bool) $format->trans_single($format->getpath(
            $data,
            ['#', 'shuffleanswers', 0, '#'],
            self::DEFAULT_SHUFFLEANSWERS)
        );

        // Use_dnd_ui.
        // FIXME: Do we still need this or can we just drop it?
        $olddnduivalue = (bool) $format->trans_single($format->getpath(
            $data,
            ['#', 'use_dnd_ui', 0, '#'],
            self::DEFAULT_USEDNDUI)
        );
        $fromform->usedndui = (bool) $format->trans_single($format->getpath(
            $data,
            ['#', 'usedndui', 0, '#'],
            $olddnduivalue)
        );

        // Rows.
        $fromform->rows_shorttext = [];
        $fromform->rows_description = [];
        $fromform->rows_feedback = [];
        $index = 0;
        $rowsxml = $data['#']['row'];

        foreach ($rowsxml as $rowxml) {
            // FIXME: Should an empty text for shorttext be OK?
            $fromform->rows_shorttext[$index] = $format->getpath($rowxml, ['#', 'shorttext', 0, '#'], '');
            $fromform->rows_description[$index] = [
                'text' => $format->getpath($rowxml, ['#', 'description', 0, '#', 'text', 0, '#'], ''),
                'format' => $format->trans_format(
                    $format->getpath(
                        $rowxml, ['#', 'description', 0, '@', 'format'], $format->get_format(FORMAT_HTML)
                    )
                )
            ];
            $fromform->rows_feedback[$index] = [
                'text' => $format->getpath($rowxml, ['#', 'feedback', 0, '#', 'text', 0, '#'], ''),
                'format' => $format->trans_format(
                    $format->getpath(
                        $rowxml, ['#', 'feedback', 0, '@', 'format'], $format->get_format(FORMAT_HTML)
                    )
                )
            ];
            // Note: row autopass values are not imported because a question XML is imported as a new v1 question.
            $index++;
        }

        // Cols.
        $fromform->cols_shorttext = [];
        $fromform->cols_description = [];
        $index = 0;
        $colsxml = $data['#']['col'];

        foreach ($colsxml as $colxml) {
            // FIXME: Should an empty text for shorttext be OK?
            $fromform->cols_shorttext[$index] = $format->getpath($colxml, ['#', 'shorttext', 0, '#'], '');
            $fromform->cols_description[$index] = [
                'text' => $format->getpath($colxml, ['#', 'description', 0, '#', 'text', 0, '#'], ''),
                'format' => $format->trans_format(
                    $format->getpath(
                        $colxml, ['#', 'description', 0, '@', 'format'], $format->get_format(FORMAT_HTML)
                    )
                )
            ];
            $index++;
        }

        // Weights.
        $fromform->weights = [];
        $weightsofrowsxml = $data['#']['weights-of-row'];

        $rowindex = 0;
        foreach ($weightsofrowsxml as $weightsofrowxml) {
            $colindex = 0;
            foreach ($weightsofrowxml['#']['weight-of-col'] as $weightofcolxml) {
                $weight = floatval($weightofcolxml['#']);
                if ($weight) {
                    $formfieldname = qtype_matrix_question::formfield_name($rowindex, $colindex, $fromform->multiple);
                    $fromform->{$formfieldname} = $fromform->multiple ? $weight : $colindex;
                    if (!$fromform->multiple) {
                        break;
                    }
                }
                $colindex++;
            }
            $rowindex++;
        }

        return $fromform;
    }

    /**
     * export a matrix question to Moodle XML format
     * 2020-06-05
     *
     * @param             $questiondata
     * @param qformat_xml $format
     * @param null        $extra
     * @return string
     */
    public function export_to_xml($questiondata, qformat_xml $format, $extra = null): string {
        // This is necessary so we don't have "false" values translated to empty tags
        foreach ($questiondata->options as $key => $value) {
            if ($value === false) {
                $questiondata->options->{$key} = 0;
            }
        }
        $output = parent::export_to_xml($questiondata, $format, $extra);
        // Rows.
        $rowindex = 0;
        foreach ($questiondata->options->rows as $row) {
            $output .= "<!--row: " . $rowindex . "-->\n";
            $output .= "    <row>\n";
            $output .= "        <shorttext>" . $row->shorttext . "</shorttext>\n";
            $output .= "        <description {$format->format($row->description['format'])}>\n";
            $output .= $format->writetext($row->description['text'], 6);
            $output .= "        </description>\n";
            $output .= "        <feedback {$format->format($row->feedback['format'])}>\n";
            $output .= $format->writetext($row->feedback['text'], 6);
            $output .= "        </feedback>\n";
            $output .= "    </row>\n";
            // Note: row autopass values are not exported because a question XML is imported as a new v1 question.
            $rowindex++;
        }

        // Cols.
        $colindex = 0;
        foreach ($questiondata->options->cols as $col) {
            $output .= "<!--col: " . $colindex . "-->\n";
            $output .= "    <col>\n";
            $output .= "        <shorttext>" . $col->shorttext . "</shorttext>\n";
            $output .= "        <description {$format->format($col->description['format'])}>\n";
            $output .= $format->writetext($col->description['text'], 6);
            $output .= "        </description>\n";
            $output .= "    </col>\n";
            $colindex++;
        }

        // Weights.
        foreach ($questiondata->options->weights as $rowid => $weightsofrow) {
            $rowindex = array_search($rowid, array_keys($questiondata->options->rows));
            $output .= "<!--weights of row: " . $rowindex . "-->\n";
            $output .= "    <weights-of-row>\n";
            foreach ($weightsofrow as $colid => $weightofcol) {
                $colindex = array_search($colid, array_keys($questiondata->options->cols));
                $output .= "<!--weight of col: " . $colindex . "-->\n";
                $output .= "        <weight-of-col>" . $weightofcol . "</weight-of-col>\n";
            }
            $output .= "    </weights-of-row>\n";
        }

        return $output;
    }

    /**
     * Initialise the common question_definition fields.
     *
     * @param question_definition $question     the question_definition we are creating.
     * @param object              $questiondata the question data loaded from the database.
     */
    protected function initialise_question_instance(question_definition $question, $questiondata): void {
        parent::initialise_question_instance($question, $questiondata);
        $question->rows = $questiondata->options->rows;
        $question->cols = $questiondata->options->cols;
        $question->weights = $questiondata->options->weights;
    }

    /**
     * (non-PHPdoc).
     * @see question_type::get_possible_responses()
     * @param object $questiondata
     * @return array
     */
    public function get_possible_responses($questiondata) {
        /** @var qtype_matrix_question $question */
        $question = $this->make_question($questiondata);
        $weights = $question->weights;
        $parts = [];

        foreach ($question->rows as $rowid => $row) {

            $choices = [];
            foreach ($question->cols as $columnid => $column) {
                $correctreponse = '';
                $partialcredit = 0;
                /*
                 * Calculate the partial credit
                 * For Analysis of responses needs and due to non-linear math the fraction is set to 1
                 * for Kprime, Kprime1/0 and Difference scoring method. This way we are able to determine correct responses
                 */
                if ($weights[$rowid][$columnid] > 0) {
                    $partialcredit = $question->grademethod == 'all' ? (1 / count($question->rows)) : 1;
                    $correctreponse = ' (' . get_string('correctresponse', 'qtype_matrix') . ')';
                }

                $choices[$columnid] = new question_possible_response(
                    question_utils::to_plain_text($row->shorttext, $row->description['format']) .
                    ': ' . question_utils::to_plain_text($column->shorttext . $correctreponse, $column->description['format']), $partialcredit);
            }

            $choices[null] = question_possible_response::no_response();
            $parts[$rowid] = $choices;
        }

        return $parts;
    }
}
