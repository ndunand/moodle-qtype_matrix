<?php

/**
 * Per row grading. The total grade is the average of grading received 
 * for reach one of the rows.
 * 
 * Weighted grading. Each cell receives a weighting, and the positive values 
 * for each row must add up to 100%. Penalises user for wrong answers
 *
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class qtype_matrix_grading_weighted extends qtype_matrix_grading
{

    const TYPE = 'weighted';

    public static function get_name()
    {
        return self::TYPE;
    }

    public static function get_title()
    {
        return qtype_matrix::get_string(self::TYPE);
    }

    /**
     * Factory 
     * 
     * @return qtype_matrix_grading_weighted
     */
    public static function create()
    {
        static $result = false;
        if ($result)
        {
            return $result;
        }
        return $result = new self();
    }

    /**
     * Grade a specific row
     * 
     * @param qtype_matrix_question     $question
     * @param object                    $row
     * @param array                     $answers
     * @return float 
     */
    public function grade_row($question, $row, $answers)
    {
        $row_score = 0;
        $row_malus = 0;
        foreach ($question->cols as $col)
        {
            $is_correct = $question->is_correct($row, $col);
            $is_answered = $question->is_answered($answers, $row, $col);
            if ($is_answered)
            {
                $weight = $question->weight($row, $col);
                $row_score += $weight > 0 ? $weight : 0;
                $row_malus += $weight < 0 ? abs($weight) : 0;
            }
        }
        $row_malus = min(1, $row_malus);
        return $row_score - $row_score * $row_malus;
    }

    /**
     * Create the form element used to define the weight of the cell
     * 
     * @param MoodleQuickForm   $form
     * @param int               $row
     * @param int               $col
     * 
     * @return object
     */
    public function create_cell_element($form, $row, $col, $multiple)
    {
        $options = question_bank::fraction_options();
        $cell_name = $this->cell_name($row, $col, $multiple);
        return $form->createElement('select', $cell_name, 'label', $options);
    }

    public function validation($data)
    {
        $multiple = $data['multiple'];
        if (empty($multiple))
        {
            return array('multiple' => qtype_matrix::get_string('weightednomultiple'));
        }

        // each row must have a total weight of 100%
        $rows_count = $this->row_count($data);
        $cols_count = $this->col_count($data);
        for ($row = 0; $row < $rows_count; $row++)
        {
            $row_grade = 0;
            for ($col = 0; $col < $cols_count; $col++)
            {
                $cell_name = $this->cell_name($row, $col, $multiple);
                $row_grade += (float) $data[$cell_name];
            }
            if (abs(abs($row_grade) - 1)>0.0001)
            {
                return array("matrix" => qtype_matrix::get_string('mustaddupto100'));
            }
        }
        return array();
    }

}