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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot . '/question/engine/bank.php';
require_once $CFG->dirroot . '/question/type/matrix/question.php';
require_once $CFG->dirroot . '/question/type/matrix/questiontype.php';

use qtype_matrix\db\question_matrix_store;
use qtype_matrix\db\stepdata_migration_utils;
use core\exception\moodle_exception;

/**
 * restore plugin class that provides the necessary information
 * needed to restore one match qtype plugin
 *
 */
class restore_qtype_matrix_plugin extends restore_qtype_plugin {

    private $attemptorders = [];

    /**
     * Return the contents of this qtype to be processed by the links decoder
     */
    public static function define_decode_contents(): array {
        $result = [];

        $fields = ['shorttext', 'description'];
        $result[] = new restore_decode_content('qtype_matrix_cols', $fields, 'qtype_matrix_cols');
        $fields = ['shorttext', 'description', 'feedback'];
        $result[] = new restore_decode_content('qtype_matrix_rows', $fields, 'qtype_matrix_rows');

        return $result;
    }

    /**
     * Process the qtype/matrix
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    public function process_matrix($data): void {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;

        if ($this->is_question_created()) {
            $qtypeobj = question_bank::get_qtype($this->pluginname);
            $data->{$qtypeobj->questionid_column_name()} = $this->get_new_parentid('question');
            $extrafields = $qtypeobj->extra_question_fields();
            $extrafieldstable = array_shift($extrafields);
            $newitemid = $DB->insert_record($extrafieldstable, $data);
            $this->set_mapping('matrix', $oldid, $newitemid);
        }
        else {
            $this->set_mapping('matrix', $oldid, null);
        }
    }

    /**
     * Detect if the question is created or mapped
     *
     * @return bool
     */
    protected function is_question_created(): bool {
        return (bool) $this->get_mappingid('question_created', $this->get_old_parentid('question'));
    }

    /**
     * Process the qtype/rows/row element.
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    public function process_row($data): void {
        $this->process_dim($data, true);
    }

    /**
     * Process the qtype/cols/col element.
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    public function process_col($data): void {
        $this->process_dim($data, false);
    }

    private function process_dim($backupdata, bool $isrow): void {
        global $DB;
        $backupdata = (object) $backupdata;
        $oldid = $backupdata->id;

        $oldmatrixid = $this->get_old_parentid('matrix');
        $newmatrixid = $this->get_new_parentid('matrix');
        if (!$newmatrixid) {
            return;
        }

        $dim = $isrow ? 'row' : 'col';
        $dimtable = $isrow ? 'qtype_matrix_rows' : 'qtype_matrix_cols';
        $newitemid = 0;
        if ($this->is_question_created()) {
            $backupdata->matrixid = $newmatrixid;
            $newitemid = $DB->insert_record($dimtable, $backupdata);
        } else {
            // FIXME: It isn't ensured that 2 rows/cols don't have the same shorttext right now.
            $originalrecords = $DB->get_records($dimtable, ['matrixid' => $newmatrixid]);
            foreach ($originalrecords as $record) {
                if ($backupdata->shorttext == $record->shorttext) {
                    $newitemid = $record->id;
                }
            }
        }
        if (!$newitemid) {
            $info = new stdClass();
            $info->filequestionid = $oldmatrixid;
            $info->dbquestionid = $newmatrixid;
            $info->answer = $backupdata->shorttext;
            throw new restore_step_exception('error_question_answers_missing_in_db', $info);
        } else {
            $this->set_mapping($dim, $oldid, $newitemid);
        }
    }

    /**
     * Process the qtype/weights/weight element
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    public function process_weight($data): void {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;

        $key = $data->colid . 'x' . $data->rowid;
        $data->colid = $this->get_mappingid('col', $data->colid);
        $data->rowid = $this->get_mappingid('row', $data->rowid);
        // This prevents bad data to arrive in the database. Currently the only useful weight values are 1 or 0.
        $data->weight = (int) (bool) $data->weight;
        $newitemid = $DB->insert_record('qtype_matrix_weights', $data);
        $this->set_mapping('weight' . $key, $oldid, $newitemid);
    }

    /**
     * Map back
     *
     * @param $state
     * @return string
     */
    public function recode_legacy_state_answer($state): string {
        $result = [];
        $answer = unserialize($state->answer, ['allowed_classes' => false]);
        foreach ($answer as $rowid => $row) {
            $newrowid = $this->get_mappingid('row', $rowid);
            $newrow = [];
            foreach ($row as $colid => $cell) {
                $newcolid = $this->get_mappingid('col', $colid);
                $newrow[$newcolid] = $cell;
            }
            $result[$newrowid] = $newrow;
        }

        return serialize($result);
    }

    /**
     * Return a matrix store for database access.
     * Exists mainly because unit tests work better with it.
     * @return question_matrix_store
     */
    protected function get_matrix_store():question_matrix_store {
        static $store = null;
        if (!$store) {
            $store = new question_matrix_store();
        }
        return $store;
    }

    public function recode_response($questionid, $sequencenumber, array $response): array {
        $recodedresponse = [];
        $newattemptid = $this->get_new_parentid('question_attempt');

        if ($sequencenumber == 0) {
            if (isset($response[qtype_matrix_question::KEY_ROWS_ORDER])) {
                $recodedresponse[qtype_matrix_question::KEY_ROWS_ORDER] =
                    $this->recode_choice_order($response[qtype_matrix_question::KEY_ROWS_ORDER]);
                $this->attemptorders[$newattemptid] = explode(',', $recodedresponse[qtype_matrix_question::KEY_ROWS_ORDER]);
            } else {
                throw new restore_step_exception('error_qtype_matrix_attempt_step_data_not_migratable');
            }
        } else {
            $neworder = $this->attemptorders[$newattemptid] ?? [];
            if (!$neworder) {
                throw new restore_step_exception('error_qtype_matrix_attempt_step_data_not_migratable');
            }
            $store = $this->get_matrix_store();
            $restoredmatrix = $store->get_matrix_by_question_id($questionid);
            $restoredcols = $store->get_matrix_cols_by_matrix_id($restoredmatrix->id);
            $restoredcolids = array_keys($restoredcols);
            foreach ($response as $key => $value) {
                if (str_contains($key, 'cell')) {
                    $oldrowid = stepdata_migration_utils::extract_row_id($key);
                    $newrowid = $this->get_mappingid('row', $oldrowid, 0);
                    $newrowindex = array_search($newrowid, $neworder);
                    $oldcolid = stepdata_migration_utils::extract_col_id($key, $value);
                    $newcolid = $this->get_mappingid('col', $oldcolid, 0);
                    $newcolindex = array_search($newcolid, $restoredcolids);
                    // Either we can map a backup ID to new rows/cols of a new question or to an already existing question.
                    // If we couldn't, then the row/col IDs from the attempt point to those of another earlier question version
                    // This version may or may not be in the backup and thus may or may not have been restored yet.
                    if ($newrowindex === false || $newcolindex === false) {
                        throw new restore_step_exception('error_qtype_matrix_attempt_step_data_not_migratable');
                    }
                    $newname = qtype_matrix_question::responsekey($newrowindex, $newcolindex);
                    $recodedresponse[$newname] = true;
                } else {
                    $recodedresponse[$key] = $value;
                }
            }
        }

        return $recodedresponse;
    }

    /**
     * Recode the choice order as stored in the response.
     *
     * @param string $order the original order.
     * @return string the recoded order.
     */
    protected function recode_choice_order(string $order): string {
        $neworder = [];
        foreach (explode(',', $order) as $id) {
            if ($newid = $this->get_mappingid('row', (int) $id)) {
                $neworder[] = $newid;
            }
        }
        return implode(',', $neworder);
    }

    /**
     * Returns the paths to be handled by the plugin at question level
     */
    protected function define_question_plugin_structure(): array {
        $result = [];

        $elename = 'matrix';
        $elepath = $this->get_pathfor('/matrix'); // We used get_recommended_name() so this works.
        $result[] = new restore_path_element($elename, $elepath);

        $elename = 'col';
        $elepath = $this->get_pathfor('/matrix/cols/col'); // We used get_recommended_name() so this works.
        $result[] = new restore_path_element($elename, $elepath);

        $elename = 'row';
        $elepath = $this->get_pathfor('/matrix/rows/row'); // We used get_recommended_name() so this works.
        $result[] = new restore_path_element($elename, $elepath);

        $elename = 'weight';
        $elepath = $this->get_pathfor('/matrix/weights/weight'); // We used get_recommended_name() so this works.
        $result[] = new restore_path_element($elename, $elepath);

        return $result;
    }

    /**
     * Converts the backup data structure to the question data structure.
     * This is needed for question identity hash generation to work correctly.
     *
     * @param array $backupdata Data from the backup
     * @return stdClass The converted question data
     */
    public static function convert_backup_to_questiondata(array $backupdata): stdClass {
        $questiondata = parent::convert_backup_to_questiondata($backupdata);
        $questiondata = qtype_matrix::clean_data($questiondata, true);
        // Add the matrix-specific options.
        if (isset($backupdata['plugin_qtype_matrix_question']['matrix'][0])) {
            $matrix = &$backupdata['plugin_qtype_matrix_question']['matrix'][0];

            // Process rows to correct format
            $rowids = [];
            if (isset($matrix['rows']['row'])) {
                $rows = [];
                foreach ($matrix['rows']['row'] as $row) {
                    $description = $row['description'] ?? '';

                    $row['matrixid'] = $matrix['id'];
                    $row['description'] = [
                        'text' => $description,
                        'format' => FORMAT_HTML
                    ];

                    $feedback = $row['feedback'] ?? '';
                    $row['feedback'] = [
                        'text' => $feedback,
                        'format' => FORMAT_HTML
                    ];

                    $rows[$row['id']] = (object) $row;
                    $rowids[] = $row['id'];
                }
                $questiondata->options->rows = $rows;
            }

            // Process cols to correct format
            $columnids = [];
            if (isset($matrix['cols']['col'])) {
                $columns = [];
                foreach ($matrix['cols']['col'] as $column) {
                    $column['matrixid'] = $matrix['id'];

                    $description = $column['description'] ?? '';
                    $column['description'] = [
                        'text' => $description,
                        'format' => FORMAT_HTML
                    ];

                    $columns[$column['id']] = (object) $column;
                    $columnids[] = $column['id'];
                }
                $questiondata->options->cols = $columns;
            }

            /**
             * Return the weights in the format of rowid -> colid -> value
             */
            $weights = [];

            // prepare weights with 0 values as they are not present when their value is empty
            foreach ($rowids as $rowid) {
                foreach ($columnids as $columnid) {
                    $weights[$rowid][$columnid] = 0;
                }
            }

            if (isset($matrix['weights']['weight'])) {
                foreach ($matrix['weights']['weight'] as $weight) {
                    $weight = (object) $weight;
                    $weights[$weight->rowid][$weight->colid] = $weight->weight;
                }
            }
            $questiondata->options->weights = $weights;
        }

        return $questiondata;
    }

    #[\Override]
    public function define_excluded_identity_hash_fields(): array {
        return [
            '/options/cols/id',
            '/options/cols/matrixid',
            '/options/rows/id',
            '/options/rows/matrixid',
            '/options/weights/id',
            '/options/weights/rowid',
            '/options/weights/colid',
        ];
    }

}
