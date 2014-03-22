<?php

defined('MOODLE_INTERNAL') || die();

/**
 * restore plugin class that provides the necessary information
 * needed to restore one match qtype plugin
 */
class restore_qtype_matrix_plugin extends restore_qtype_plugin {

    /**
     * Returns the paths to be handled by the plugin at question level
     */
    protected function define_question_plugin_structure() {
        $result = array();

        $elename = 'matrix';
        $elepath = $this->get_pathfor('/matrix'); // we used get_recommended_name() so this works
        $result[] = new restore_path_element($elename, $elepath);

        $elename = 'col';
        $elepath = $this->get_pathfor('/matrix/cols/col'); // we used get_recommended_name() so this works
        $result[] = new restore_path_element($elename, $elepath);

        $elename = 'row';
        $elepath = $this->get_pathfor('/matrix/rows/row'); // we used get_recommended_name() so this works
        $result[] = new restore_path_element($elename, $elepath);

        $elename = 'weight';
        $elepath = $this->get_pathfor('/matrix/weights/weight'); // we used get_recommended_name() so this works
        $result[] = new restore_path_element($elename, $elepath);

        return $result;
    }

    /**
     * Detect if the question is created or mapped
     * 
     * @return bool
     */
    protected function is_question_created() {
        $oldquestionid = $this->get_old_parentid('question');
        //$newquestionid = $this->get_new_parentid('question');
        return $this->get_mappingid('question_created', $oldquestionid) ? true : false;
    }

    /**
     * Process the qtype/matrix
     */
    public function process_matrix($data) {
        if (!$this->is_question_created()) {
            return;
        }

        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

       //todo: check import of version moodle1 data
        
        $data->questionid = $this->get_new_parentid('question');
        $newitemid = $DB->insert_record('question_matrix', $data);
        $this->set_mapping('matrix', $oldid, $newitemid);
    }

    /**
     * Process the qtype/cols/col
     */
    public function process_col($data) {
        if (!$this->is_question_created()) {
            return;
        }

        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->matrixid = $this->get_new_parentid('matrix');
        $newitemid = $DB->insert_record('question_matrix_cols', $data);
        $this->set_mapping('col', $oldid, $newitemid);
    }

    /**
     * Process the qtype/rows/row element
     */
    public function process_row($data) {
        if (!$this->is_question_created()) {
            return;
        }

        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->matrixid = $this->get_new_parentid('matrix');
        $newitemid = $DB->insert_record('question_matrix_rows', $data);
        $this->set_mapping('row', $oldid, $newitemid);
    }

    /**
     * Process the qtype/weights/weight element
     */
    public function process_weight($data) {
        if (!$this->is_question_created()) {
            return;
        }

        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $key = $data->colid . 'x' . $data->rowid ;
        $data->colid = $this->get_mappingid('col', $data->colid);
        $data->rowid = $this->get_mappingid('row', $data->rowid);
        $newitemid = $DB->insert_record('question_matrix_weights', $data);
        $this->set_mapping('weight' . $key, $oldid, $newitemid);
    }

    /**
     * Map back
     */
    public function recode_state_answer($state) {
        $result = array();
        $answer = unserialize($state->answer);
        foreach ($answer as $row_id => $row) {
            $new_rowid = $this->get_mappingid('row', $row_id);
            $new_row = array();
            foreach ($row as $col_id => $cell) {
                $new_colid = $this->get_mappingid('col', $col_id);
                $new_row[$new_colid] = $cell;
            }
            $result[$new_rowid] = $new_row;
        }

        return serialize($result);
    }

    /**
     * Return the contents of this qtype to be processed by the links decoder
     */
    static public function define_decode_contents() {
        $result = array();

        $fields = array('shorttext', 'description');
        $result[] = new restore_decode_content('question_matrix_cols', $fields, 'question_matrix_cols');
        $fields = array('shorttext', 'description', 'feedback');
        $result[] = new restore_decode_content('question_matrix_rows', $fields, 'question_matrix_rows');
        $fields = array('rowid', 'colid', 'weight');
        $result[] = new restore_decode_content('question_matrix_weights', $fields, 'question_matrix_weights');

        return $result;
    }       

}
