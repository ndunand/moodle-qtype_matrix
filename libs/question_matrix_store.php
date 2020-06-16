<?php

/**
 * 
 */
class question_matrix_store
{

    const COMPONENT = 'qtype_matrix';
    const TABLE_QUESTION_MATRIX = 'question_matrix';
    const TABLE_QUESTION_MATRIX_ROWS = 'question_matrix_rows';
    const TABLE_QUESTION_MATRIX_COLS = 'question_matrix_cols';
    const TABLE_QUESTION_MATRIX_WEIGHTS = 'question_matrix_weights';

    //question

    public function get_matrix_by_question_id($question_id)
    {
        global $DB;

        $result = $DB->get_record(self::TABLE_QUESTION_MATRIX, array('questionid' => $question_id));
        if ($result) {
            $result->multiple = (bool) $result->multiple;
        }
        return $result;
    }
    
    public function save_matrix($question)
    {
        $is_new = !isset($question->id) || empty($question->id);
        if ($is_new) {
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
    public function insert_matrix($matrix)
    {
        global $DB;

        $data = (object) array(
                'questionid' => $matrix->questionid, 
                'multiple' => $matrix->multiple,
                'grademethod' => $matrix->grademethod,
                'use_dnd_ui' => $matrix->use_dnd_ui,
                'shuffleanswers' => $matrix->shuffleanswers,
                'renderer' => 'matrix'
        );

        $new_id = $DB->insert_record(self::TABLE_QUESTION_MATRIX, $data);
        $data->id = $new_id;
        $matrix->id = $new_id;
        return $matrix;
    }

    public function update_matrix($matrix)
    {
        global $DB;

        $data = (object) array(
                'id' => $matrix->id, 
                'questionid' => $matrix->questionid, 
                'multiple' => $matrix->multiple,
                'grademethod' => $matrix->grademethod,
                'use_dnd_ui' => $matrix->use_dnd_ui,
                'shuffleanswers' => $matrix->shuffleanswers,
                'renderer' => 'matrix'
        );
        $DB->update_record(self::TABLE_QUESTION_MATRIX, $data);
        return $matrix;
    }

    function delete_question($question_id)
    {
        if (empty($question_id)) {
            return false;
        }

        global $DB, $CFG;
        $prefix = $CFG->prefix;

        /**
         * Note
         * $DB->execute does not accept multiple SQL statements
         */
        //wheights
        $sql = "DELETE FROM {$prefix}question_matrix_weights
                WHERE {$prefix}question_matrix_weights.rowid IN 
                      (
                      SELECT question_matrix_rows.id FROM {$prefix}question_matrix_rows  AS question_matrix_rows
                      INNER JOIN {$prefix}question_matrix      AS matrix ON question_matrix_rows.matrixid = matrix.id
                      WHERE matrix.questionid = $question_id
                      )";
        $DB->execute($sql);

        //rows
        $sql = "DELETE FROM {$prefix}question_matrix_rows
                WHERE {$prefix}question_matrix_rows.matrixid IN 
                      (
                      SELECT matrix.id FROM {$prefix}question_matrix AS matrix
                      WHERE matrix.questionid = $question_id
                      )";
        $DB->execute($sql);

        //cols
        $sql = "DELETE FROM {$prefix}question_matrix_cols
                WHERE {$prefix}question_matrix_cols.matrixid IN 
                      (
                      SELECT matrix.id FROM {$prefix}question_matrix AS matrix
                      WHERE matrix.questionid = $question_id
                      )";
        $DB->execute($sql);

        //matrix
        $sql = "DELETE FROM {$prefix}question_matrix WHERE questionid = $question_id";
        $DB->execute($sql);


        return true;
    }

    //row

    public function get_matrix_rows_by_matrix_id($matrix_id)
    {
        global $DB;

        $result = $DB->get_records(self::TABLE_QUESTION_MATRIX_ROWS, array('matrixid' => $matrix_id), 'id ASC');

        if (!$result) {
            return array();
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

    public function save_matrix_row($row)
    {
        $is_new = !isset($row->id) || empty($row->id);
        if ($is_new) {
            return $this->insert_matrix_row($row);
        } else {
            return $this->update_matrix_row($row);
        }
    }

    public function insert_matrix_row($row)
    {
        global $DB;

        $text = isset($row->shorttext) ? $row->shorttext : false;
        if (empty($text)) {
            return false;
        }

        $data = (object) array(
                'matrixid' => $row->matrixid,
                'shorttext' => $row->shorttext,
                'description' => $row->description['text'],
                'feedback' => $row->feedback['text']
        );
        $new_id = $DB->insert_record(self::TABLE_QUESTION_MATRIX_ROWS, $data);
        $data->id = $new_id;
        $row->id = $new_id;
        return $data;
    }

    public function update_matrix_row($row)
    {
        global $DB;

        // TODO: Add a possibility to delete if (empty($short)) 
        $data = (object) array(
                'id' => $row->id,
                'matrixid' => $row->matrixid,
                'shorttext' => $row->shorttext,
                'description' => $row->description['text'],
                'feedback' => $row->feedback['text']
        );
        $DB->update_record(self::TABLE_QUESTION_MATRIX_ROWS, $data);
        return $data;
    }

    public function delete_matrix_row($row)
    {
        global $DB;

        if (!isset($row->id) || empty($row->id)) {
            return;
        }

        return $DB->delete_records(self::TABLE_QUESTION_MATRIX_ROWS, array('id' => $row->id));
    }

    //cols

    public function get_matrix_cols_by_matrix_id($matrix_id)
    {
        global $DB;

        $result = $DB->get_records(self::TABLE_QUESTION_MATRIX_COLS, array('matrixid' => $matrix_id), 'id ASC');


        if (!$result) {
            return array();
        }

        foreach ($result as &$row) {
            $row->description = [
                    'text' => $row->description,
                    'format' => FORMAT_HTML
            ];
        }

        return $result;
    }

    public function save_matrix_col($col)
    {
        $is_new = !isset($col->id) || empty($col->id);
        if ($is_new) {
            return $this->insert_matrix_col($col);
        } else {
            return $this->update_matrix_col($col);
        }
    }

    public function insert_matrix_col($col)
    {
        global $DB;

        $text = isset($col->shorttext) ? $col->shorttext : false;
        if (empty($text)) {
            return false;
        }

        $data = (object) array(
                'matrixid' => $col->matrixid,
                'shorttext' => $col->shorttext,
                'description' => $col->description['text']
        );

        //$x = 1 / 0;
        $new_id = $DB->insert_record(self::TABLE_QUESTION_MATRIX_COLS, $data);
        $data->id = $new_id;
        $col->id = $new_id;
        return $data;
    }

    public function update_matrix_col($col)
    {
        global $DB;

        // TODO: Add a possibility to delete if (empty($short)) 
        $data = (object) array(
                'id' => $col->id,
                'matrixid' => $col->matrixid,
                'shorttext' => $col->shorttext,
                'description' => $col->description['text']
        );
        //var_dump($data);
        $DB->update_record(self::TABLE_QUESTION_MATRIX_COLS, $data);
        return $data;
    }

    public function delete_matrix_col($col)
    {
        global $DB;

        if (!isset($col->id) || empty($col->id)) {
            return;
        }

        return $DB->delete_records(self::TABLE_QUESTION_MATRIX_COLS, array('id' => $col->id));
    }

    //weights

    public function get_matrix_weights_by_question_id($question_id)
    {
        global $DB, $CFG;
        $prefix = $CFG->prefix;

        //todo: check AND?
        $sql = "SELECT weights.* 
                FROM {$prefix}question_matrix_weights AS weights
                WHERE 
                    rowid IN (SELECT question_matrix_rows.id FROM {$prefix}question_matrix_rows     AS question_matrix_rows
                              INNER JOIN {$prefix}question_matrix                   AS matrix ON question_matrix_rows.matrixid = matrix.id
                              WHERE matrix.questionid = $question_id)
                    OR
                              
                    colid IN (SELECT cols.id FROM {$prefix}question_matrix_cols     AS cols
                              INNER JOIN {$prefix}question_matrix                   AS matrix ON cols.matrixid = matrix.id
                              WHERE matrix.questionid = $question_id)
               ";
        return $DB->get_records_sql($sql);
    }

    public function delete_matrix_weights($question_id)
    {
        global $DB, $CFG;
        $prefix = $CFG->prefix;

        $sql = "DELETE FROM {$prefix}question_matrix_weights
                WHERE {$prefix}question_matrix_weights.rowid IN
                (
                 SELECT question_matrix_rows.id FROM {$prefix}question_matrix_rows  AS question_matrix_rows
                 INNER JOIN {$prefix}question_matrix AS matrix ON question_matrix_rows.matrixid = matrix.id
                 WHERE matrix.questionid = $question_id
                )";
        return $DB->execute($sql);
    }

    public function insert_matrix_weight($weight)
    {
        global $DB;

        $data = (object) array(
                'rowid' => $weight->rowid,
                'colid' => $weight->colid,
                'weight' => $weight->weight
        );
        $new_id = $DB->insert_record(self::TABLE_QUESTION_MATRIX_WEIGHTS, $data);
        $data->id = $new_id;
        return $data;
    }

}
