<?php

/**
 * Unit tests for the matrix question definition class.
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/matrix/questiontype.php');

/**
 * Unit tests for the matrix question definition class.
 */
class qtype_matrix_test extends UnitTestCase
{

    protected $qtype;

    public function setUp()
    {
        $this->qtype = new qtype_matrix();
    }

    public function tearDown()
    {
        $this->qtype = null;
    }

    public function test_name()
    {
        $this->assertEqual($this->qtype->name(), 'matrix');
    }

    public function test_cell_name()
    {
        $id = Qtype_matrix::defaut_grading()->cell_name(0, 0, true);
        $match = preg_match('/[a-zA-Z_][a-zA-Z0-9_]*/', $id);
        $this->assertTrue($match === 1);

        $id = Qtype_matrix::defaut_grading()->cell_name(0, 0, false);
        $match = preg_match('/[a-zA-Z_][a-zA-Z0-9_]*/', $id);
        $this->assertTrue($match === 1);
    }


//    public function test_can_analyse_responses()
//    {
//        $this->assertTrue($this->qtype->can_analyse_responses());
//    }
//
//    public function test_get_random_guess_score()
//    {
//        $this->assertEqual(0.5, $this->qtype->get_random_guess_score(null));
//    }
}
