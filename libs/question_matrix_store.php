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

    public function get_question($id)
    {
        global $DB;

        $result = $DB->get_record(self::TABLE_QUESTION_MATRIX, array('questionid' => $id));
        if ($result) {
            $result->multiple = (bool) $result->multiple;
        }
        return $result;
    }

    public function save_question($question)
    {
        $is_new = !isset($question->id) || empty($question->id);
        if ($is_new) {
            return $this->insert_question($question);
        } else {
            return $this->update_question($question);
        }
    }

    /**
     * We may want to insert an existing question to make a copy
     * 
     * @param object $question
     * @return object
     */
    public function insert_question($question)
    {
        global $DB;

        $matrix = (object) array(
                'questionid' => $question->id, //todo: check if we should not set
                'multiple' => $question->multiple,
                'grademethod' => $question->grademethod,
                'use_dnd_ui' => $question->use_dnd_ui,
                'shuffleanswers' => $question->shuffleanswers,
                'renderer' => 'matrix'
        );

        $new_id = $DB->insert_record(self::TABLE_QUESTION_MATRIX, $matrix);
        $matrix->id = $new_id;
        $question->id = $new_id;
        return $matrix;
    }

    public function update_question($question)
    {
        global $DB;

        $matrix = $this->get_question($question->id);
        //$matrix->questionid = $question->id;
        $matrix->multiple = $question->multiple;
        $matrix->grademethod = $question->grademethod;
        $matrix->shuffleanswers = $question->shuffleanswers;
        $matrix->use_dnd_ui = $question->use_dnd_ui;
        $matrix->renderer = 'matrix';
        $DB->update_record(self::TABLE_QUESTION_MATRIX, $matrix);
        return $matrix;
    }

    function delete_question($question_id)
    {
        if (empty($question_id)) {
            return false;
        }

        global $DB, $CFG;
        $prefix = $CFG->prefix;

        $sql = <<<EOT
        DELETE FROM {$prefix}question_matrix_weights
                WHERE {$prefix}question_matrix_weights.rowid IN 
                      (
                        SELECT rows.id FROM {$prefix}question_matrix_rows  AS rows
                        INNER JOIN {$prefix}question_matrix AS matrix ON rows.matrixid = matrix.id
                        WHERE matrix.questionid = $question_id
                      );
                          
        DELETE FROM {$prefix}question_matrix_rows
                WHERE {$prefix}question_matrix_rows.matrixid IN 
                      (
                        SELECT matrix.id FROM {$prefix}question_matrix AS matrix
                        WHERE matrix.questionid = $question_id
                      );
                          
        DELETE FROM {$prefix}question_matrix_cols
                WHERE {$prefix}question_matrix_cols.matrixid IN 
                      (
                        SELECT matrix.id FROM {$prefix}question_matrix AS matrix
                        WHERE matrix.questionid = $question_id
                      );
                          
        DELETE FROM {$prefix}question_matrix WHERE questionid = $question_id;
            
        DELETE FROM {$prefix}question_attempt_step_data 
               USING {$prefix}question_attempt_steps, {$prefix}question_attempts 
               WHERE {$prefix}question_attempt_steps.id = {$prefix}question_attempt_step_data.attemptstepid 
                   AND {$prefix}question_attempts.id = {$prefix}question_attempt_steps.questionattemptid 
                   AND {$prefix}question_attempts.questionid = $question_id;
                          
EOT;

        $DB->execute($sql);
        return true;
    }

    //row

    public function get_question_rows($matrix_id)
    {
        global $DB;

        $result = $DB->get_records(self::TABLE_QUESTION_MATRIX_ROWS, array('matrixid' => $matrix_id), 'id ASC');
        return $result ? $result : array();
    }

    public function save_row($row)
    {
        $is_new = !isset($row->id) || empty($row->id);
        if ($is_new) {
            return $this->insert_row($row);
        } else {
            return $this->update_row($row);
        }
    }

    public function insert_row($row)
    {
        global $DB;

        $text = isset($row->shorttext) ? $row->shorttext : false;
        if (empty($text)) {
            return false;
        }

        $data = (object) array(
                'matrixid' => $row->matrixid,
                'shorttext' => $row->shorttext,
                'description' => $row->description,
                'feedback' => $row->feedback
        );
        $new_id = $DB->insert_record(self::TABLE_QUESTION_MATRIX_ROWS, $data);
        $data->id = $new_id;
        $row->id = $new_id;
        return $data;
    }

    public function update_row($row)
    {
        global $DB;

        // TODO: Add a possibility to delete if (empty($short)) 
        $data = (object) array(
                'id' => $row->id,
                'matrixid' => $row->matrixid,
                'shorttext' => $row->shorttext,
                'description' => $row->description,
                'feedback' => $row->feedback
        );
        $DB->update_record(self::TABLE_QUESTION_MATRIX_ROWS, $data);
        return $data;
    }

    public function delete_row($row)
    {
        global $DB;

        if (!isset($row->id) || empty($row->id)) {
            return;
        }

        return $DB->delete_records(self::TABLE_QUESTION_MATRIX_ROWS, array('id' => $row->id));
    }

    //cols

    public function get_question_cols($matrix_id)
    {
        global $DB;

        $result = $DB->get_records(self::TABLE_QUESTION_MATRIX_COLS, array('matrixid' => $matrix_id), 'id ASC');
        return $result ? $result : array();
    }

    public function save_col($col)
    {
        $is_new = !isset($col->id) || empty($col->id);
        if ($is_new) {
            return $this->insert_col($col);
        } else {
            return $this->update_col($col);
        }
    }

    public function insert_col($col)
    {
        global $DB;

        $text = isset($col->shorttext) ? $col->shorttext : false;
        if (empty($text)) {
            return false;
        }

        $data = (object) array(
                'matrixid' => $col->matrixid,
                'shorttext' => $col->shorttext,
                'description' => $col->description
        );

        //$x = 1 / 0;
        $new_id = $DB->insert_record(self::TABLE_QUESTION_MATRIX_COLS, $data);
        $data->id = $new_id;
        $col->id = $new_id;
        return $data;
    }

    public function update_col($col)
    {
        global $DB;

        // TODO: Add a possibility to delete if (empty($short)) 
        $data = (object) array(
                'id' => $col->id,
                'matrixid' => $col->matrixid,
                'shorttext' => $col->shorttext,
                'description' => $col->description
        );
        //var_dump($data);
        $DB->update_record(self::TABLE_QUESTION_MATRIX_COLS, $data);
        return $data;
    }

    public function delete_col($col)
    {
        global $DB;

        if (!isset($col->id) || empty($col->id)) {
            return;
        }

        return $DB->delete_records(self::TABLE_QUESTION_MATRIX_COLS, array('id' => $col->id));
    }

    //weights

    public function get_question_weights($question_id)
    {
        global $DB, $CFG;
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
        return $DB->get_records_sql($sql);
    }

    public function delete_question_weights($question_id)
    {
        global $DB, $CFG;
        $prefix = $CFG->prefix;

        $sql = "DELETE FROM {$prefix}question_matrix_weights
                WHERE {$prefix}question_matrix_weights.rowid IN
                (
                 SELECT rows.id FROM {$prefix}question_matrix_rows  AS rows
                 INNER JOIN {$prefix}question_matrix AS matrix ON rows.matrixid = matrix.id
                 WHERE matrix.questionid = $question_id
                )";
        return $DB->execute($sql);
    }

    public function insert_weight($weight)
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
