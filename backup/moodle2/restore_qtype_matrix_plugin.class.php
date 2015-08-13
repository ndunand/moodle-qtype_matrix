<?php

defined('MOODLE_INTERNAL') || die();

/**
 * restore plugin class that provides the necessary information
 * needed to restore one match qtype plugin
 * 
 * Note
 * Failing to see why we are using $GLOBALS here. Adding a property to the class
 * should do the work.
 */
class restore_qtype_matrix_plugin extends restore_qtype_plugin
{

    /**
     * Returns the paths to be handled by the plugin at question level
     */
    protected function define_question_plugin_structure()
    {
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
    protected function is_question_created()
    {
        $oldquestionid = $this->get_old_parentid('question');
        //$newquestionid = $this->get_new_parentid('question');
        return $this->get_mappingid('question_created', $oldquestionid) ? true : false;
    }

    /**
     * Process the qtype/matrix
     *
     * @param $data
     */
    public function process_matrix($data)
    {
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
     *
     * @param $data
     */
    public function process_col($data)
    {
        global $DB;

        $data = (object) $data;
        $GLOBALS['matrixTempCols'][$data->id] = $data->id;
        if (!$this->is_question_created()) {
            return;
        }


        $oldid = $data->id;

        $data->matrixid = $this->get_new_parentid('matrix');
        $newitemid = $DB->insert_record('question_matrix_cols', $data);
        $this->set_mapping('col', $oldid, $newitemid);
        $GLOBALS['matrixTempCols'][$oldid] = $newitemid;
    }

    /**
     * Process the qtype/rows/row element
     *
     * @param $data
     */
    public function process_row($data)
    {
        global $DB;

        $data = (object) $data;
        $GLOBALS['matrixTempRows'][$data->id] = $data->id;
        if (!$this->is_question_created()) {
            return;
        }


        $oldid = $data->id;

        $data->matrixid = $this->get_new_parentid('matrix');
        $newitemid = $DB->insert_record('question_matrix_rows', $data);
        $this->set_mapping('row', $oldid, $newitemid);
        $GLOBALS['matrixTempRows'][$oldid] = $newitemid;
    }

    /**
     * Process the qtype/weights/weight element
     *
     * @param $data
     */
    public function process_weight($data)
    {
        if (!$this->is_question_created()) {
            return;
        }

        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $key = $data->colid . 'x' . $data->rowid;
        $data->colid = $this->get_mappingid('col', $data->colid);
        $data->rowid = $this->get_mappingid('row', $data->rowid);
        $newitemid = $DB->insert_record('question_matrix_weights', $data);
        $this->set_mapping('weight' . $key, $oldid, $newitemid);
    }

    /**
     * Map back
     *
     * @param $state
     * @return string
     */
    public function recode_state_answer($state)
    {
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

    public function recode_response($questionid, $sequencenumber, array $response)
    {
        $recodedResponse = array();
        foreach ($response as $responseKey => $responseVal) {
            if ($responseKey == '_order') {
                $recodedResponse['_order'] = $this->recode_choice_order($responseVal);
            } else if (substr($responseKey, 0, 4) == 'cell') {
                $responseKeyNoCell = substr($responseKey, 4);
                $responseKeyIDs = explode('_', $responseKeyNoCell);
                //$this->get_mappingid('row', $responseKeyIDs[0]);
                $newRowID = $GLOBALS['matrixTempRows'][$responseKeyIDs[0]];
                //$this->get_mappingid('col', $responseVal);
                $newColID = isset($GLOBALS['matrixTempCols'][$responseVal]) ? $GLOBALS['matrixTempCols'][$responseVal] : false;
                if (count($responseKeyIDs) == 1) {
                    $recodedResponse['cell' . $newRowID] = $newColID;
                } else if (count($responseKeyIDs) == 2) {
                    $recodedResponse['cell' . $newRowID . '_' . $newColID] = $newColID;
                } else {
                    $recodedResponse[$responseKey] = $responseVal;
                }
            } else {
                $recodedResponse[$responseKey] = $responseVal;
            }
        }
        return $recodedResponse;
    }

    /**
     * Recode the choice order as stored in the response.
     * @param string $order the original order.
     * @return string the recoded order.
     */
    protected function recode_choice_order($order)
    {
        $neworder = array();
        foreach (explode(',', $order) as $id) {
            if ($newid = $GLOBALS['matrixTempRows'][$id]) {//$this->get_mappingid('row', $id)) {
                $neworder[] = $newid;
            }
        }
        return implode(',', $neworder);
    }

    /**
     * Return the contents of this qtype to be processed by the links decoder
     */
    static public function define_decode_contents()
    {
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
