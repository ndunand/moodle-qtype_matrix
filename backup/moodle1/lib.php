<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Matrix question type conversion handler
 */
class moodle1_qtype_matrix_handler extends moodle1_qtype_handler
{

    public static function create_id()
    {
        static $result = 0;
        return $result++;
    }

    /**
     * Returns the list of paths within one <QUESTION> that this qtype needs to have included
     * in the grouped question structure
     *
     * @return array of strings
     */
    public function get_question_subpaths()
    {
        $result = array(
            'MATRIX',
            'MATRIX/ROWS/ROW',
            'MATRIX/COLS/COL',
            'MATRIX/WEIGHTS/WEIGHT'
        );
        return $result;
    }

    /**
     * Gives the qtype handler a chance to write converted data into questions.xml
     *
     * @param array $data grouped question data
     * @param array $raw grouped raw QUESTION data
     */
    public function process_question(array $data, array $raw)
    {
        $matrix = $data['matrix'][0];
        $matrix['id'] = isset($matrix['id']) ? $matrix['id'] : self::create_id();
        //$this->write_xml('matrix', $matrix, array('rows/row/id', 'cols/col/id'));

        $this->xmlwriter->begin_tag('matrix');
        $this->xmlwriter->full_tag('id', $matrix['id']);
        $this->xmlwriter->full_tag('grademethod', $matrix['grademethod']);
        $this->xmlwriter->full_tag('multiple', $matrix['multiple']);
        $this->xmlwriter->full_tag('renderer', $matrix['grademethod']);

        $this->xmlwriter->begin_tag('cols');
        foreach ($matrix['cols']['col'] as $col)
        {
            $this->write_xml('col', $col);
        }
        $this->xmlwriter->end_tag('cols');

        $this->xmlwriter->begin_tag('rows');
        foreach ($matrix['rows']['row'] as $row)
        {
            $this->write_xml('row', $row);
        }
        $this->xmlwriter->end_tag('rows');

        $this->xmlwriter->begin_tag('weights');
        foreach ($matrix['weights']['weight'] as $weight)
        {
            $this->write_xml('weight', $weight);
        }
        $this->xmlwriter->end_tag('weights');

        $this->xmlwriter->end_tag('matrix');
    }

}
