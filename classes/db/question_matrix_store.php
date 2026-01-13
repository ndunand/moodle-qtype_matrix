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

namespace qtype_matrix\db;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot . '/question/type/matrix/questiontype.php';

use dml_exception;
use qtype_matrix;
use stdClass;

class question_matrix_store {

    const COMPONENT = 'qtype_matrix';
    const TABLE_QUESTION_MATRIX = 'qtype_matrix';
    const TABLE_QUESTION_MATRIX_ROWS = 'qtype_matrix_rows';
    const TABLE_QUESTION_MATRIX_COLS = 'qtype_matrix_cols';
    const TABLE_QUESTION_MATRIX_WEIGHTS = 'qtype_matrix_weights';

    // Question.

    /**
     * This function is not strong typed false|object should not be ok.
     *
     * @param int $questionid
     * @return false|mixed|stdClass
     * @throws dml_exception
     */
    public function get_matrix_by_question_id(int $questionid) {
        global $DB;
        $result = $DB->get_record(self::TABLE_QUESTION_MATRIX, ['questionid' => $questionid]);
        if ($result) {
            $result = qtype_matrix::clean_data($result);
        }
        return $result;
    }

    /**
     * @param int $questionid
     * @return bool
     * @throws dml_exception
     */
    public function delete_question(int $questionid): bool {
        global $DB;
        if (empty($questionid)) {
            return false;
        }

        // Weights.
        $DB->delete_records_select('qtype_matrix_weights',
            'rowid IN (
                      SELECT qmr.id FROM {qtype_matrix_rows} qmr
                      INNER JOIN {qtype_matrix} qm ON qmr.matrixid = qm.id
                      WHERE qm.questionid = :qid
                      )',
            ['qid' => $questionid]);

        // Rows.
        $DB->delete_records_select('qtype_matrix_rows',
            'matrixid IN (
                      SELECT qm.id FROM {qtype_matrix} qm
                      WHERE qm.questionid = :qid)',
            ['qid' => $questionid]);

        // Cols.
        $DB->delete_records_select('qtype_matrix_cols',
            'matrixid IN (
                      SELECT qm.id FROM {qtype_matrix} qm
                      WHERE qm.questionid = :qid)',
            ['qid' => $questionid]);

        // The qtype_matrix record is automatically deleted by Moodle core
        return true;
    }


    public function rows_exist(int $matrixid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE_QUESTION_MATRIX_ROWS, ['matrixid' => $matrixid]);
    }

    public function cols_exist(int $matrixid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE_QUESTION_MATRIX_COLS, ['matrixid' => $matrixid]);
    }

    /**
     * @param int $matrixid
     * @return array
     * @throws dml_exception
     */
    public function get_matrix_rows_by_matrix_id(int $matrixid): array {
        global $DB;
        $result = $DB->get_records(self::TABLE_QUESTION_MATRIX_ROWS, ['matrixid' => $matrixid], 'id ASC');
        if (!$result) {
            return [];
        }
        foreach ($result as $row) {
            $row->description = [
                'text' => $row->description,
                'format' => FORMAT_HTML
            ];
            $row->feedback = [
                'text' => $row->feedback,
                'format' => FORMAT_HTML
            ];
        }

        return $result;
    }

    /**
     * cant type this function result false|object should not be ok.
     *
     * @param object $row
     * @return int the new rowid
     * @throws dml_exception
     */
    public function insert_matrix_row(object $row):int {
        global $DB;

        $text = $row->shorttext ?? false;
        if (empty($text)) {
            return 0;
        }

        $newid = $DB->insert_record(self::TABLE_QUESTION_MATRIX_ROWS, $row);
        if ($newid === false) {
            $newid = 0;
        }
        return $newid;
    }

    // Cols.

    /**
     * @param int $matrixid
     * @return array
     * @throws dml_exception
     */
    public function get_matrix_cols_by_matrix_id(int $matrixid): array {
        global $DB;

        $result = $DB->get_records(self::TABLE_QUESTION_MATRIX_COLS, ['matrixid' => $matrixid], 'id ASC');
        if (!$result) {
            return [];
        }

        foreach ($result as $row) {
            $row->description = [
                'text' => $row->description,
                'format' => FORMAT_HTML
            ];
        }
        return $result;
    }

    /**
     * Cant type this function result can be false|object
     *
     * @param object $col
     * @return int the new colid
     * @throws dml_exception
     */
    public function insert_matrix_col(object $col):int {
        global $DB;

        $text = $col->shorttext ?? false;
        if (empty($text)) {
            return 0;
        }

        $newid = $DB->insert_record(self::TABLE_QUESTION_MATRIX_COLS, $col);
        return $newid;
    }

    // Weights.

    /**
     * @param int $questionid
     * @return array
     * @throws dml_exception
     */
    public function get_matrix_weights_by_question_id(int $questionid): array {
        global $DB;
        $params = [
            'rowquestionid' => $questionid,
            'colquestionid' => $questionid
        ];
        $sql = 'SELECT qmw.*
                FROM {qtype_matrix_weights} qmw
                WHERE
                    rowid IN (SELECT qmr.id FROM {qtype_matrix_rows} qmr
                              INNER JOIN {qtype_matrix} qm ON qmr.matrixid = qm.id
                              WHERE qm.questionid = :rowquestionid)
                    AND
                    colid IN (SELECT qmc.id FROM {qtype_matrix_cols} qmc
                              INNER JOIN {qtype_matrix} qm ON qmc.matrixid = qm.id
                              WHERE qm.questionid = :colquestionid)
               ';
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * @param object $weight
     * @return object
     * @throws dml_exception
     */
    public function insert_matrix_weight(object $weight): object {
        global $DB;

        $data = (object) [
            'rowid' => $weight->rowid,
            'colid' => $weight->colid,
            'weight' => $weight->weight
        ];
        $newid = $DB->insert_record(self::TABLE_QUESTION_MATRIX_WEIGHTS, $data);
        $data->id = $newid;
        return $data;
    }

}
