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

namespace qtype_matrix;

use qtype_matrix\local\qtype_matrix_grading;
use qtype_matrix_test_helper;
use qtype_matrix;
use advanced_testcase;
use context_course;
use qformat_xml;
use question_bank;
use test_question_maker;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot . '/question/type/matrix/tests/helper.php';
require_once $CFG->dirroot . '/question/format/xml/format.php';

/**
 * Unit tests for the matrix question definition class.
 */
class qtype_matrix_test extends advanced_testcase {

    protected $qtype;

    /**
     * @var \core_question_generator question generator.
     */
    protected $qgenerator;

    public function setUp(): void {
        parent::setUp();
        $this->qtype = new qtype_matrix();
        $coregenerator = $this->getDataGenerator();
        $this->qgenerator = $coregenerator->get_plugin_generator('core_question');
    }

    public function tearDown(): void {
        $this->qtype = null;
        parent::tearDown();
    }

    public function test_gradings():void {
        $gradings = $this->qtype::gradings();
        foreach ($gradings as $grading) {
            $this->assertEquals(is_subclass_of($grading, qtype_matrix_grading::class), true);
        }
    }

    public function test_grading():void {
        foreach (qtype_matrix_grading::VALID_GRADINGS as $validgrading) {
            $this->assertNotNull($this->qtype::grading($validgrading));
        }
        $invalidgrading = 'invalidgrading';
        $this->expectException('TypeError');
        $this->assertNotContains($invalidgrading, qtype_matrix_grading::VALID_GRADINGS, 'A previously invalid grading now exists');
        $this->qtype::grading($invalidgrading);
    }

    public function test_delete_question():void {
        global $DB;
        $this->resetAfterTest();
        $coregenerator = $this->getDataGenerator();
        $course = $coregenerator->create_course();
        $coursecontext = context_course::instance($course->id);
        $qcat = $this->qgenerator->create_question_category(['contextid' => $coursecontext->id]);
        $question = $this->qgenerator->create_question('matrix', 'default', ['category' => $qcat->id]);
        $this->assertTrue($DB->record_exists('question', ['id' => $question->id]));
        $matrix = $this->qtype::retrieve_matrix($question->id);
        $this->assertTrue($DB->record_exists('qtype_matrix', ['id' => $matrix->id]));
        $this->assertTrue($DB->record_exists('qtype_matrix_rows', ['matrixid' => $matrix->id]));
        $this->assertTrue($DB->record_exists('qtype_matrix_cols', ['matrixid' => $matrix->id]));
        foreach ($matrix->weights as $rowid => $colweights) {
            foreach ($colweights as $colid => $weight) {
                if ($weight) {
                    $this->assertTrue($DB->record_exists('qtype_matrix_weights', ['rowid' => $rowid, 'colid' => $colid]));
                }
            }
        }
        // This calls delete_question()
        question_delete_question($question->id);
        $this->assertFalse($DB->record_exists('question', ['id' => $question->id]));
        $this->assertFalse($DB->record_exists('qtype_matrix', ['id' => $matrix->id]));
        $this->assertFalse($DB->record_exists('qtype_matrix_rows', ['matrixid' => $matrix->id]));
        $this->assertFalse($DB->record_exists('qtype_matrix_cols', ['matrixid' => $matrix->id]));
        foreach ($matrix->weights as $rowid => $colweights) {
            foreach ($colweights as $colid => $weight) {
                if ($weight) {
                    $this->assertFalse($DB->record_exists('qtype_matrix_weights', ['rowid' => $rowid, 'colid' => $colid]));
                }
            }
        }
    }

    public function test_get_question_options(): void {
        global $DB;
        $this->resetAfterTest();
        $coregenerator = $this->getDataGenerator();
        $course = $coregenerator->create_course();
        $coursecontext = context_course::instance($course->id);
        $qcat = $this->qgenerator->create_question_category(['contextid' => $coursecontext->id]);
        $question = $this->qgenerator->create_question('matrix', 'default', ['category' => $qcat->id]);
        $matrixoptions = $DB->get_record('qtype_matrix', ['questionid' => $question->id]);
        // this uses get_question_options()
        $questiondata = question_bank::load_question_data($question->id);
        $this->assertEquals($matrixoptions->grademethod, $questiondata->options->grademethod);
        $this->assertEquals($matrixoptions->multiple, $questiondata->options->multiple);
        $this->assertEquals($matrixoptions->shuffleanswers, $questiondata->options->shuffleanswers);
        $this->assertEquals($matrixoptions->usedndui, $questiondata->options->usedndui);
        $matrixrows = $DB->get_records('qtype_matrix_rows', ['matrixid' => $matrixoptions->id]);
        $this->assertIsArray($questiondata->options->rows);
        foreach ($matrixrows as $matrixrow) {
            $questiondatarow = $questiondata->options->rows[$matrixrow->id];
            $this->assertEquals($matrixrow->id, $questiondatarow->id);
            $this->assertEquals($matrixrow->matrixid, $questiondatarow->matrixid);
            $this->assertEquals($matrixrow->shorttext, $questiondatarow->shorttext);
            $this->assertEquals($matrixrow->description, $questiondatarow->description['text']);
            $this->assertEquals(FORMAT_HTML, $questiondatarow->description['format']);
            $this->assertEquals($matrixrow->feedback, $questiondatarow->feedback['text']);
            $this->assertEquals(FORMAT_HTML, $questiondatarow->feedback['format']);
        }
        $matrixcols = $DB->get_records('qtype_matrix_cols', ['matrixid' => $matrixoptions->id]);
        $this->assertIsArray($questiondata->options->cols);
        foreach ($matrixcols as $matrixcol) {
            $questiondatacol = $questiondata->options->cols[$matrixcol->id];
            $this->assertEquals($matrixcol->id, $questiondatacol->id);
            $this->assertEquals($matrixcol->matrixid, $questiondatacol->matrixid);
            $this->assertEquals($matrixcol->shorttext, $questiondatacol->shorttext);
            $this->assertEquals($matrixcol->description, $questiondatacol->description['text']);
            $this->assertEquals(FORMAT_HTML, $questiondatacol->description['format']);
        }
        $this->assertIsArray($questiondata->options->weights);
        foreach ($matrixrows as $matrixrow) {
            $matrixweights = $DB->get_records('qtype_matrix_weights', ['rowid' => $matrixrow->id]);
            foreach ($matrixweights as $matrixweight) {
                $questiondatarowweights = $questiondata->options->weights[$matrixweight->rowid];
                $this->assertContainsEquals($matrixweight->colid, array_keys($questiondatarowweights));
                $this->assertEquals($matrixweight->weight, $questiondatarowweights[$matrixweight->colid]);
            }
        }
    }

    public function test_retrieve_matrix():void {
        global $DB;
        $this->resetAfterTest();
        $this->assertNull($this->qtype::retrieve_matrix(0));
        $this->assertNull($this->qtype::retrieve_matrix(-1));
        $coregenerator = $this->getDataGenerator();
        $course = $coregenerator->create_course();
        $coursecontext = context_course::instance($course->id);
        $qcat = $this->qgenerator->create_question_category(['contextid' => $coursecontext->id]);
        $otherquestion = $this->qgenerator->create_question('essay', null, ['category' => $qcat->id]);
        $this->assertNull($this->qtype::retrieve_matrix($otherquestion->id));
        $question = $this->qgenerator->create_question('matrix', 'default', ['category' => $qcat->id]);
        $this->assertTrue($DB->record_exists('question', ['id' => $question->id]));
        $matrixoptions = $DB->get_record('qtype_matrix', ['questionid' => $question->id]);
        $matrix = $this->qtype::retrieve_matrix($question->id);
        $this->assertEquals($matrixoptions->id, $matrix->id);
        $this->assertEquals($matrixoptions->questionid, $matrix->questionid);
        $this->assertEquals($matrixoptions->grademethod, $matrix->grademethod);
        $this->assertEquals($matrixoptions->multiple, $matrix->multiple);
        $this->assertEquals($matrixoptions->shuffleanswers, $matrix->shuffleanswers);
        $this->assertEquals($matrixoptions->usedndui, $matrix->usedndui);
        $matrixrows = $DB->get_records('qtype_matrix_rows', ['matrixid' => $matrixoptions->id]);
        $this->assertIsArray($matrix->rows);
        foreach ($matrixrows as $matrixrow) {
            $retrievedmatrixrow = $matrix->rows[$matrixrow->id];
            $this->assertEquals($matrixrow->id, $retrievedmatrixrow->id);
            $this->assertEquals($matrixrow->matrixid, $retrievedmatrixrow->matrixid);
            $this->assertEquals($matrixrow->shorttext, $retrievedmatrixrow->shorttext);
            $this->assertEquals($matrixrow->description, $retrievedmatrixrow->description['text']);
            $this->assertEquals(FORMAT_HTML, $retrievedmatrixrow->description['format']);
            $this->assertEquals($matrixrow->feedback, $retrievedmatrixrow->feedback['text']);
            $this->assertEquals(FORMAT_HTML, $retrievedmatrixrow->feedback['format']);
        }
        $matrixcols = $DB->get_records('qtype_matrix_cols', ['matrixid' => $matrixoptions->id]);
        $this->assertIsArray($matrix->cols);
        foreach ($matrixcols as $matrixcol) {
            $retrievedmatrixcol = $matrix->cols[$matrixcol->id];
            $this->assertEquals($matrixcol->id, $retrievedmatrixcol->id);
            $this->assertEquals($matrixcol->matrixid, $retrievedmatrixcol->matrixid);
            $this->assertEquals($matrixcol->shorttext, $retrievedmatrixcol->shorttext);
            $this->assertEquals($matrixcol->description, $retrievedmatrixcol->description['text']);
            $this->assertEquals(FORMAT_HTML, $retrievedmatrixcol->description['format']);
        }
        $this->assertIsArray($matrix->weights);
        foreach ($matrixrows as $matrixrow) {
            $matrixweights = $DB->get_records('qtype_matrix_weights', ['rowid' => $matrixrow->id]);
            foreach ($matrixweights as $matrixweight) {
                $retrievedmatrixweights = $matrix->weights[$matrixweight->rowid];
                $this->assertContainsEquals($matrixweight->colid, array_keys($retrievedmatrixweights));
                $this->assertEquals($matrixweight->weight, $retrievedmatrixweights[$matrixweight->colid]);
            }
        }
    }

    /**
     * @dataProvider save_question_options_data_provider
     * @param bool $type What test question type will be used
     * @param array $expectedmultiple What the question value for multiple should be
     * @param array $nrweightrecords Expected nr of weight records
     * @return void
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public function test_save_question_options($type, $expectedmultiple, $nrweightrecords):void {
        global $DB;
        $this->resetAfterTest();
        $typequestion = qtype_matrix_test_helper::make_question($type);
        $fromform = test_question_maker::get_question_form_data('matrix', $type);
        $this->assertEquals($expectedmultiple, $fromform->multiple);
        $fromform->id = 1;
        $this->assertFalse($DB->record_exists('qtype_matrix', ['questionid' => $fromform->id]));
        $this->assertEquals(0, $DB->count_records('qtype_matrix_rows'));
        $this->assertEquals(0, $DB->count_records('qtype_matrix_cols'));
        $this->assertEquals(0, $DB->count_records('qtype_matrix_weights'));
        $errors = $this->qtype->save_question_options($fromform);
        $this->assertIsObject($errors);
        $this->assertFalse((bool) get_object_vars($errors));
        $this->assertEquals(1, $DB->count_records('qtype_matrix'));
        $matrixoptions = $DB->get_record('qtype_matrix', ['questionid' => $fromform->id]);
        $this->assertEquals($fromform->grademethod, $matrixoptions->grademethod);
        $this->assertEquals($fromform->id, $matrixoptions->questionid);
        $this->assertEquals($fromform->multiple, $matrixoptions->multiple);
        $this->assertEquals($fromform->shuffleanswers, $matrixoptions->shuffleanswers);
        $this->assertEquals($fromform->usedndui, $matrixoptions->usedndui);

        $this->assertEquals(4, $DB->count_records('qtype_matrix_rows'));
        $matrixrows = $DB->get_records('qtype_matrix_rows', ['matrixid' => $matrixoptions->id], 'id ASC');
        $this->assertEquals(4, count($matrixrows));
        $rowindex = 0;
        foreach ($matrixrows as $matrixrow) {
            $this->assertEquals($fromform->rows_shorttext[$rowindex], $matrixrow->shorttext);
            $this->assertEquals($fromform->rows_description[$rowindex]['text'], $matrixrow->description);
            $this->assertEquals($fromform->rows_feedback[$rowindex]['text'], $matrixrow->feedback);
            $rowindex++;
        }

        $this->assertEquals(4, $DB->count_records('qtype_matrix_cols'));
        $matrixcols = $DB->get_records('qtype_matrix_cols', ['matrixid' => $matrixoptions->id], 'id ASC');
        $this->assertEquals(4, count($matrixcols));
        $colindex = 0;
        foreach ($matrixcols as $matrixcol) {
            $this->assertEquals($fromform->cols_shorttext[$colindex], $matrixcol->shorttext);
            $this->assertEquals($fromform->cols_description[$colindex]['text'], $matrixcol->description);
            $colindex++;
        }

        $indicedrowids = array_keys($matrixrows);
        $indicedcolids = array_keys($matrixcols);

        $weightrecords = $DB->get_records('qtype_matrix_weights');
        $this->assertEquals($nrweightrecords, count($weightrecords));
        $foundrowids = [];
        $foundcombos = [];
        foreach ($weightrecords as $weightrecord) {
            if (!$expectedmultiple){
                // A single record for each row.
                $this->assertNotContainsEquals($weightrecord->rowid, $foundrowids);
            }
            $this->assertNotContainsEquals([$weightrecord->rowid, $weightrecord->colid], $foundcombos);
            $this->assertContainsEquals($weightrecord->rowid, $indicedrowids);
            $this->assertContainsEquals($weightrecord->colid, $indicedcolids);
            $this->assertEquals(1, $weightrecord->weight);
            $foundrowids[] = $weightrecord->rowid;
            $foundcombos[] = [$weightrecord->rowid, $weightrecord->colid];
        }
        $indicedtestquestionrows = array_keys($typequestion->rows);
        $indicedtestquestioncols = array_keys($typequestion->cols);
        foreach ($typequestion->weights as $rowid => $row) {
            $savedrowid = $indicedrowids[array_search($rowid, $indicedtestquestionrows)];
            foreach ($row as $colid => $colvalue) {
                $savedcolid = $indicedcolids[array_search($colid, $indicedtestquestioncols)];
                if ($colvalue > 0) {
                    $this->assertContainsEquals([$savedrowid, $savedcolid], $foundcombos);
                } else {
                    $this->assertNotContainsEquals([$savedrowid, $savedcolid], $foundcombos);
                }
            }
        }
    }

    /**
     * Test data and expected results for test_save_question_options()
     *
     * @return array Array of data for testing saving question options
     */
    public function save_question_options_data_provider():array {
        return [
            'Default question' => ['default', false, 4],
            'Nondefault question' => ['nondefault', true, 4],
            'Multiple, two correct question' => ['multipletwocorrect', true, 8],
        ];
    }

    /**
     * @dataProvider to_weight_matrix_data_provider
     * @param bool $multiple Whether it should be a multi-value matrix
     * @param array $fromform form data for values for the function
     * @param array $expectedmatrixvalues Filled matrix cells we expect
     * @return void
     */
    public function test_to_weight_matrix(bool $multiple, array $fromform, array $expectedmatrixvalues): void {
        $emptymatrix = array_fill(
            0,
            20,
            array_fill(0, 20, 0)
        );
        $expectedmatrix = array_replace_recursive($emptymatrix, $expectedmatrixvalues);
        $weightmatrix = $this->qtype->to_weight_matrix((object) $fromform, $multiple);
        $this->assertEquals($expectedmatrix, $weightmatrix);
    }

    public function test_is_matrix_empty(): void {
        $emptymatrix = array_fill(
            0,
            20,
            array_fill(0, 20, 0)
        );
        $this->assertTrue($this->qtype->is_matrix_empty($emptymatrix));
        $nonemptymatrix = $emptymatrix;
        $nonemptymatrix[0][0] = 1;
        $this->assertFalse($this->qtype->is_matrix_empty($nonemptymatrix));
        $nonemptymatrix = $emptymatrix;
        $nonemptymatrix[19][0] = 1;
        $this->assertFalse($this->qtype->is_matrix_empty($nonemptymatrix));
        $fakenonemptymatrix = $emptymatrix;
        $fakenonemptymatrix[0][0] = -1;
        $this->assertTrue($this->qtype->is_matrix_empty($fakenonemptymatrix));
    }

    /**
     * Create test formdata and expected results for weight matrices
     *
     * @return array Array of data for weight matrix testing.
     */
    public static function to_weight_matrix_data_provider(): array {
        return [
            'multiple, first col correct' => [
                true,
                [
                    'r0c0' => 1
                ],
                [
                    0 => [
                        0 => 1
                    ]
                ]
            ],
            'multiple, two cols correct' => [
                true,
                [
                    'r0c0' => 1,
                    'r0c1' => 1
                ],
                [
                    0 => [
                        0 => 1,
                        1 => 1
                    ]
                ]
            ],
            'single, first col correct' => [
                false,
                [
                    'r0' => 0
                ],
                [
                    0 => [
                        0 => 1
                    ]
                ]
            ],
            // TODO: See to_weight_matrix FIXME comments, it is unclear what is the intended behaviour
//            'single, out of range' => [
//                false,
//                [
//                    'r0' => 21,
//                    'r21' => 0
//                ],
//                [
//                ],
//            ],
            'multiple, out of range' => [
                false,
                [
                    'r0c21' => 1,
                    'r21c0' => 1,
                ],
                [
                ],
            ],
        ];
    }
    /**
     * @covers ::get_expected_data
     * @return void
     */

    public function test_import_from_xml_default_value_question():void {
        $xmlcontent = implode('', file(__DIR__ . '/fixtures/default_question_to_import.xml'));
        $xml = xmlize($xmlcontent, 0, 'UTF-8', true);
        $qformat = new qformat_xml();
        $fromform = new \stdClass();
        $fromform = $this->qtype->import_from_xml($xml['question'], $fromform, $qformat);
        $this->assertEquals('matrix', $fromform->qtype);
        $this->assertEquals('kprime', $fromform->grademethod);
        $this->assertEquals(false, $fromform->multiple);
        $this->assertEquals(false, $fromform->usedndui);
        $this->assertEquals(true, $fromform->shuffleanswers);
        for ($rowindex = 0; $rowindex < 4; $rowindex++) {
            $this->assertEquals('row'.$rowindex.'shorttext', $fromform->rows_shorttext[$rowindex]);
            $this->assertEquals('<p>row'.$rowindex.'description</p>', $fromform->rows_description[$rowindex]['text']);
            $this->assertEquals(FORMAT_HTML, $fromform->rows_description[$rowindex]['format']);
            $this->assertEquals('<p>row'.$rowindex.'feedback</p>', $fromform->rows_feedback[$rowindex]['text']);
            $this->assertEquals(FORMAT_HTML, $fromform->rows_feedback[$rowindex]['format']);
        }
        for ($colindex = 0; $colindex < 3; $colindex++) {
            $this->assertEquals('col'.$colindex.'shorttext', $fromform->cols_shorttext[$colindex]);
            $this->assertEquals('<p>col'.$colindex.'description</p>', $fromform->cols_description[$colindex]['text']);
            $this->assertEquals(FORMAT_HTML, $fromform->cols_description[$colindex]['format']);
        }
        $this->assertEquals(0, $fromform->r0);
        $this->assertFalse(isset($fromform->r0c0));
        $this->assertFalse(isset($fromform->r0c1));
        $this->assertFalse(isset($fromform->r0c2));
        $this->assertEquals(1, $fromform->r1);
        $this->assertFalse(isset($fromform->r1c0));
        $this->assertFalse(isset($fromform->r1c1));
        $this->assertFalse(isset($fromform->r1c2));
        $this->assertEquals(2, $fromform->r2);
        $this->assertFalse(isset($fromform->r2c0));
        $this->assertFalse(isset($fromform->r2c1));
        $this->assertFalse(isset($fromform->r2c2));
        $this->assertEquals(0, $fromform->r3);
        $this->assertFalse(isset($fromform->r3c0));
        $this->assertFalse(isset($fromform->r3c1));
        $this->assertFalse(isset($fromform->r3c2));
    }

    public function test_import_from_xml_nondefault_value_question():void {
        $xmlcontent = implode('', file(__DIR__ . '/fixtures/nondefault_question_to_import.xml'));
        $xml = xmlize($xmlcontent, 0, 'UTF-8', true);
        $qformat = new qformat_xml();
        $fromform = new \stdClass();
        $fromform = $this->qtype->import_from_xml($xml['question'], $fromform, $qformat);
        $this->assertEquals('matrix', $fromform->qtype);
        $this->assertEquals('all', $fromform->grademethod);
        $this->assertEquals(true, $fromform->multiple);
        $this->assertEquals(true, $fromform->usedndui);
        $this->assertEquals(false, $fromform->shuffleanswers);
        for ($rowindex = 0; $rowindex < 4; $rowindex++) {
            $this->assertEquals('row'.$rowindex.'shorttext', $fromform->rows_shorttext[$rowindex]);
            if (in_array($rowindex, [0, 2])) {
                $this->assertEquals('<p>row'.$rowindex.'description</p>', $fromform->rows_description[$rowindex]['text']);
            } else {
                $this->assertEquals('', $fromform->rows_description[$rowindex]['text']);
            }
            $this->assertEquals(FORMAT_HTML, $fromform->rows_description[$rowindex]['format']);
            if (in_array($rowindex, [0, 1])) {
                $this->assertEquals('<p>row'.$rowindex.'feedback</p>', $fromform->rows_feedback[$rowindex]['text']);
            } else {
                $this->assertEquals('', $fromform->rows_feedback[$rowindex]['text']);
            }
            $this->assertEquals(FORMAT_HTML, $fromform->rows_feedback[$rowindex]['format']);
        }
        for ($colindex = 0; $colindex < 3; $colindex++) {
            $this->assertEquals('col'.$colindex.'shorttext', $fromform->cols_shorttext[$colindex]);
            if ($colindex == 0) {
                $this->assertEquals('<p>col'.$colindex.'description</p>', $fromform->cols_description[$colindex]['text']);
            } else {
                $this->assertEquals('', $fromform->cols_description[$colindex]['text']);
            }
            $this->assertEquals(FORMAT_HTML, $fromform->cols_description[$colindex]['format']);
        }
        $this->assertEquals(1, $fromform->r0c0);
        $this->assertEquals(1, $fromform->r0c1);
        $this->assertEquals(1, $fromform->r0c2);
        $this->assertFalse(isset($fromform->r0));
        $this->assertEquals(1, $fromform->r1c0);
        $this->assertFalse(isset($fromform->r1c1));
        $this->assertEquals(1, $fromform->r1c2);
        $this->assertFalse(isset($fromform->r1));
        $this->assertFalse(isset($fromform->r2c0));
        $this->assertFalse(isset($fromform->r2c1));
        $this->assertEquals(1, $fromform->r2c2);
        $this->assertFalse(isset($fromform->r2));
        $this->assertEquals(1, $fromform->r3c0);
        $this->assertFalse(isset($fromform->r3c1));
        $this->assertFalse(isset($fromform->r3c2));
        $this->assertFalse(isset($fromform->r3));
    }

    public function test_import_from_xml_missing_options_question():void {
        $xmlcontent = implode('', file(__DIR__ . '/fixtures/missing_options_question_to_import.xml'));
        $xml = xmlize($xmlcontent, 0, 'UTF-8', true);
        $qformat = new qformat_xml();
        $fromform = new \stdClass();
        $fromform = $this->qtype->import_from_xml($xml['question'], $fromform, $qformat);
        $this->assertEquals(qtype_matrix_grading::default_grading()->get_name(), $fromform->grademethod);
        $this->assertEquals(qtype_matrix::DEFAULT_MULTIPLE, $fromform->multiple);
        $this->assertEquals(qtype_matrix::DEFAULT_USEDNDUI, $fromform->usedndui);
        $this->assertEquals(qtype_matrix::DEFAULT_SHUFFLEANSWERS, $fromform->shuffleanswers);
    }

    // FIXME: There should probably a question with invalid values for everything
    //        and that should either use fallbacks or throw an invalid import exception or something

    public function test_export_to_xml(): void {
        $questiondata = test_question_maker::get_question_data('matrix', 'default');
        $exporter = new qformat_xml();
        $xml = $this->qtype->export_to_xml($questiondata, $exporter);
        $expectedxml = implode('', file(__DIR__ . '/fixtures/exported_question_content_default.xml'));
        $this->assertEquals($expectedxml, $xml);

        $questiondata = test_question_maker::get_question_data('matrix', 'nondefault');
        $exporter = new qformat_xml();
        $xml = $this->qtype->export_to_xml($questiondata, $exporter);
        $expectedxml = implode('', file(__DIR__ . '/fixtures/exported_question_content_nondefault.xml'));
        $this->assertEquals($expectedxml, $xml);

        // FIXME: This is better testable when we don't export the IDs in comments anymore
    }

    public function test_name(): void {
        $this->assertEquals('matrix', $this->qtype->name());
    }
}
