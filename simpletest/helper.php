<?php

/**
 * Test helpers for the truefalse question type.
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Test helper class for the matrix question type.
 *
 */
class qtype_matrix_test_helper extends question_test_helper
{

    public function get_test_questions()
    {
        return array('kprime', 'all', 'any', 'none', 'weighted', 'multiple', 'single');
    }

    /**
     *
     * @return qtype_matrix_question 
     */
    public function make_matrix_question_multiple()
    {
        $result = $this->make_matrix_question();
        $result->multiple = true;
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question 
     */
    public function make_matrix_question_single()
    {
        $result = $this->make_matrix_question();
        $result->multiple = false;
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question 
     */
    public function make_matrix_question_kprime()
    {
        $result = $this->make_matrix_question();
        $result->grademethod = 'kprime';
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question 
     */
    public function make_matrix_question_all()
    {
        $result = $this->make_matrix_question();
        $result->grademethod = 'all';
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question 
     */
    public function make_matrix_question_any()
    {
        $result = $this->make_matrix_question();
        $result->grademethod = 'any';
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question 
     */
    public function make_matrix_question_none()
    {
        $result = $this->make_matrix_question();
        $result->grademethod = 'none';
        return $result;
    }

    /**
     *
     * @return qtype_matrix_question 
     */
    public function make_matrix_question_weighted()
    {

        question_bank::load_question_definition_classes('matrix');
        $result = new qtype_matrix_question();
        test_question_maker::initialise_a_question($result);
        $result->name = 'Matrix question';
        $result->questiontext = 'K prime graded question.';
        $result->generalfeedback = 'First column is true.';
        $result->penalty = 1;
        $result->qtype = question_bank::get_qtype('matrix');

        $result->rows = array();
        $result->cols = array();
        $result->weights = array();

        for ($r = 0; $r < 4; $r++)
        {
            $row = (object) array();
            $row->id = $r;
            $row->shorttext = "Row $r";
            $row->description = "Description $r";
            $row->feedback = "Feedback $r";
            $result->rows[$r] = $row;
            for ($c = 0; $c < 4; $c++)
            {
                $col = (object) array();
                $col->id = $c;
                $col->shortext = "Column $c";
                $col->description = "Description $c";
                $result->cols[$c] = $col;

                $result->weights[$r][$c] = ($c < 2) ? 0.5 : 0;
            }
        }

        $result->grademethod = 'weighted';
        $result->multiple = true;

        return $result;
    }

    /**
     *
     * @return qtype_matrix_question 
     */
    protected function make_matrix_question()
    {
        question_bank::load_question_definition_classes('matrix');
        $result = new qtype_matrix_question();
        test_question_maker::initialise_a_question($result);
        $result->name = 'Matrix question';
        $result->questiontext = 'K prime graded question.';
        $result->generalfeedback = 'First column is true.';
        $result->penalty = 1;
        $result->qtype = question_bank::get_qtype('matrix');

        $result->rows = array();
        $result->cols = array();
        $result->weights = array();

        for ($r = 0; $r < 4; $r++)
        {
            $row = (object) array();
            $row->id = $r;
            $row->shorttext = "Row $r";
            $row->description = "Description $r";
            $row->feedback = "Feedback $r";
            $result->rows[$r] = $row;
            for ($c = 0; $c < 4; $c++)
            {
                $col = (object) array();
                $col->id = $c;
                $col->shortext = "Column $c";
                $col->description = "Description $c";
                $result->cols[$c] = $col;

                $result->weights[$r][$c] = ($c == 0) ? 1 : 0;
            }
        }

        $result->grademethod = 'kprime';
        $result->multiple = true;

        return $result;
    }

}
