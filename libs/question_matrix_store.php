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
 *
 */
class question_matrix_store {

    const COMPONENT = 'qtype_matrix';
    const TABLE_QUESTION_MATRIX = 'question_matrix';
    const TABLE_QUESTION_MATRIX_ROWS = 'question_matrix_rows';
    const TABLE_QUESTION_MATRIX_COLS = 'question_matrix_cols';
    const TABLE_QUESTION_MATRIX_WEIGHTS = 'question_matrix_weights';

    // Question.

    public function get_matrix_by_question_id($questionid) {
        global $DB;
        $result = $DB->get_record(self::TABLE_QUESTION_MATRIX, ['questionid' => $questionid]);
        if ($result) {
            $result->multiple = (bool) $result->multiple;
        }
        return $result;
    }

    public function save_matrix($question) {
        $isnew = empty($question->id);
        if ($isnew) {
            return $this->insert_matrix($question);
        } else {
            return $this->update_matrix($question);
        }
    }

    /**
     * We may want to insert an existing question to make a copy
     *
     * @param object $matrix
     * @return object
     */
    public function insert_matrix($matrix) {
        global $DB;
        $data = (object) [
            'questionid' => $matrix->questionid,
            'multiple' => $matrix->multiple,
            'grademethod' => $matrix->grademethod,
            'use_dnd_ui' => $matrix->usedndui,
            'shuffleanswers' => $matrix->shuffleanswers,
            'renderer' => 'matrix'
        ];

        $newid = $DB->insert_record(self::TABLE_QUESTION_MATRIX, $data);
        $data->id = $newid;
        $matrix->id = $newid;
        return $matrix;
    }

    public function update_matrix($matrix) {
        global $DB;
        $data = (object) [
            'id' => $matrix->id,
            'questionid' => $matrix->questionid,
            'multiple' => $matrix->multiple,
            'grademethod' => $matrix->grademethod,
            'use_dnd_ui' => $matrix->usedndui,
            'shuffleanswers' => $matrix->shuffleanswers,
            'renderer' => 'matrix'
        ];
        $DB->update_record(self::TABLE_QUESTION_MATRIX, $data);
        return $matrix;
    }

    public function delete_question($questionid) {
        if (empty($questionid)) {
            return false;
        }

        global $DB;

        // Note: $DB->execute does not accept multiple SQL statements
        // Weights.
        $sql = "DELETE FROM {question_matrix_weights} qmw
                WHERE qmw.rowid IN
                      (
                      SELECT qmr.id FROM {question_matrix_rows} qmr
                      INNER JOIN {question_matrix} qm ON qmr.matrixid = qm.id
                      WHERE qm.questionid = $questionid
                      )"; // Todo: remove unsafe sql operation.
        $DB->execute($sql);

        // Rows.
        $sql = "DELETE FROM {question_matrix_rows} qmr
                WHERE qmr.matrixid IN
                      (
                      SELECT qm.id FROM {question_matrix} qm
                      WHERE qm.questionid = $questionid
                      )"; // Todo: remove unsafe sql operation.
        $DB->execute($sql);

        // Cols.
        $sql = "DELETE FROM {question_matrix_cols} qmc
                WHERE qmc.matrixid IN
                      (
                      SELECT qm.id FROM {question_matrix} qm
                      WHERE qm.questionid = $questionid
                      )"; // Todo: remove unsafe sql operation.
        $DB->execute($sql);

        // Matrix.
        $sql = "DELETE FROM {question_matrix} WHERE questionid = $questionid";
        // Todo: remove unsafe sql operation.
        $DB->execute($sql);
        return true;
    }

    // Row.

    public function get_matrix_rows_by_matrix_id($matrixid) {
        global $DB;
        $result = $DB->get_records(self::TABLE_QUESTION_MATRIX_ROWS, ['matrixid' => $matrixid], 'id ASC');
        if (!$result) {
            return [];
        }
        foreach ($result as &$row) {
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

    public function save_matrix_row($row) {
        $isnew = !isset($row->id) || empty($row->id);
        if ($isnew) {
            return $this->insert_matrix_row($row);
        } else {
            return $this->update_matrix_row($row);
        }
    }

    public function insert_matrix_row($row) {
        global $DB;

        $text = isset($row->shorttext) ? $row->shorttext : false;
        if (empty($text)) {
            return false;
        }

        $data = (object) [
            'matrixid' => $row->matrixid,
            'shorttext' => $row->shorttext,
            'description' => $row->description['text'],
            'feedback' => $row->feedback['text']
        ];
        $newid = $DB->insert_record(self::TABLE_QUESTION_MATRIX_ROWS, $data);
        $data->id = $newid;
        $row->id = $newid;
        return $data;
    }

    public function update_matrix_row($row) {
        global $DB;
        // TODO: Add a possibility to delete if (empty($short)).
        $data = (object) [
            'id' => $row->id,
            'matrixid' => $row->matrixid,
            'shorttext' => $row->shorttext,
            'description' => $row->description['text'],
            'feedback' => $row->feedback['text']
        ];
        $DB->update_record(self::TABLE_QUESTION_MATRIX_ROWS, $data);
        return $data;
    }

    public function delete_matrix_row($row) {
        global $DB;

        if (!isset($row->id) || empty($row->id)) {
            return;
        }

        return $DB->delete_records(self::TABLE_QUESTION_MATRIX_ROWS, ['id' => $row->id]);
    }

    // Cols.

    public function get_matrix_cols_by_matrix_id($matrixid) {
        global $DB;

        $result = $DB->get_records(self::TABLE_QUESTION_MATRIX_COLS, ['matrixid' => $matrixid], 'id ASC');
        if (!$result) {
            return [];
        }

        foreach ($result as &$row) {
            $row->description = [
                'text' => $row->description,
                'format' => FORMAT_HTML
            ];
        }
        return $result;
    }

    public function save_matrix_col($col) {
        $isnew = !isset($col->id) || empty($col->id);
        if ($isnew) {
            return $this->insert_matrix_col($col);
        } else {
            return $this->update_matrix_col($col);
        }
    }

    public function insert_matrix_col($col) {
        global $DB;

        $text = isset($col->shorttext) ? $col->shorttext : false;
        if (empty($text)) {
            return false;
        }

        $data = (object) [
            'matrixid' => $col->matrixid,
            'shorttext' => $col->shorttext,
            'description' => $col->description['text']
        ];

        $newid = $DB->insert_record(self::TABLE_QUESTION_MATRIX_COLS, $data);
        $data->id = $newid;
        $col->id = $newid;
        return $data;
    }

    public function update_matrix_col($col) {
        global $DB;

        // TODO: Add a possibility to delete if (empty($short)).
        $data = (object) [
            'id' => $col->id,
            'matrixid' => $col->matrixid,
            'shorttext' => $col->shorttext,
            'description' => $col->description['text']
        ];

        $DB->update_record(self::TABLE_QUESTION_MATRIX_COLS, $data);
        return $data;
    }

    public function delete_matrix_col($col) {
        global $DB;

        if (!isset($col->id) || empty($col->id)) {
            return;
        }

        return $DB->delete_records(self::TABLE_QUESTION_MATRIX_COLS, ['id' => $col->id]);
    }

    // Weights.

    public function get_matrix_weights_by_question_id($questionid) {
        global $DB;
        // Todo: check AND?
        $sql = "SELECT qmw.*
                FROM {question_matrix_weights} qmw
                WHERE
                    rowid IN (SELECT qmr.id FROM {question_matrix_rows} qmr
                              INNER JOIN {question_matrix} qm ON qmr.matrixid = qm.id
                              WHERE qm.questionid = $questionid)
                    OR
                    colid IN (SELECT qmc.id FROM {question_matrix_cols} qmc
                              INNER JOIN {question_matrix} qm ON qmc.matrixid = qm.id
                              WHERE qm.questionid = $questionid)
               "; // Todo: remove unsafe sql operation.
        return $DB->get_records_sql($sql);
    }

    public function delete_matrix_weights($questionid) {
        global $DB;
        $sql = "DELETE FROM {question_matrix_weights} qmw
                WHERE qmw.rowid IN
                (
                 SELECT qmr.id FROM {question_matrix_rows} AS qmr
                 INNER JOIN {question_matrix} qm ON qmr.matrixid = qm.id
                 WHERE qm.questionid = $questionid
                )"; // Todo: remove unsafe sql operation.
        return $DB->execute($sql);
    }

    public function insert_matrix_weight($weight) {
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
