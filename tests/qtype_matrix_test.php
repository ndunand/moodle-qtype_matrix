<?php

/**
 * Unit tests for the matrix question definition class.
 */

global $CFG;
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/question/type/matrix/questiontype.php');

/**
 * Unit tests for the matrix question definition class.
 */
class qtype_matrix_test extends advanced_testcase
{

    protected $qtype;

    public function setUp(): void
    {
        $this->qtype = new qtype_matrix();
    }

    public function tearDown(): void
    {
        $this->qtype = null;
    }

    public function test_name()
    {
        $this->assertEquals($this->qtype->name(), 'matrix');
    }

    public function test_cell_name()
    {
        $id = Qtype_matrix::defaut_grading()->cell_name(0, 0, true);
        $match = preg_match('/[a-zA-Z_][a-zA-Z0-9_]*/', $id);
        $this->assertSame(1, $match);

        $id = Qtype_matrix::defaut_grading()->cell_name(0, 0, false);
        $match = preg_match('/[a-zA-Z_][a-zA-Z0-9_]*/', $id);
        $this->assertSame(1, $match);
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
