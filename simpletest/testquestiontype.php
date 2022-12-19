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
 * Unit tests for the matrix question definition class.
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/matrix/questiontype.php');

/**
 * Unit tests for the matrix question definition class.
 */
class qtype_matrix_test extends UnitTestCase {

    protected $qtype;

    public function setUp(): void {
        $this->qtype = new qtype_matrix();
    }

    public function tearDown(): void {
        $this->qtype = null;
    }

    public function test_name(): void {
        $this->assertEqual($this->qtype->name(), 'matrix');
    }

    public function test_cell_name(): void {
        $id = Qtype_matrix::defaut_grading()->cell_name(0, 0, true);
        $match = preg_match('/[a-zA-Z_][a-zA-Z0-9_]*/', $id);
        $this->assertTrue($match === 1);

        $id = Qtype_matrix::defaut_grading()->cell_name(0, 0, false);
        $match = preg_match('/[a-zA-Z_][a-zA-Z0-9_]*/', $id);
        $this->assertTrue($match === 1);
    }
}
