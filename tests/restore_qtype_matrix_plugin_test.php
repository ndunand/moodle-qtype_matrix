<?php
/**
 * Author: Daniel Poggenpohl
 * Date: 09.01.2026
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot . '/backup/util/plan/tests/fixtures/plan_fixtures.php';
require_once $CFG->dirroot . '/question/type/matrix/backup/moodle2/restore_qtype_matrix_plugin.class.php';

use qtype_matrix\db\question_matrix_store;

class restore_qtype_matrix_plugin_test extends advanced_testcase {

    /**
     * @dataProvider process_matrix_data_provider
     * @return void
     * @throws dml_exception
     * @throws restore_step_exception
     */
    public function test_process_matrix(
        string $grademethod,
        string $multiple,
        string $shuffleanswers,
        string $usedndui,
        bool $questioncreated
    ):void {
        global $DB;
        $this->resetAfterTest();
        $backupquestionid = 123;
        $createdquestionid = 234;

        $mockedrestore = $this
            ->getMockBuilder(restore_qtype_matrix_plugin::class)
            ->setConstructorArgs([
                'qtype',
                'matrix',
                new mock_restore_structure_step(
                    'name',
                    'filename',
                    null
                )])->onlyMethods([
                'is_question_created',
                'get_new_parentid',
                'set_mapping'
            ])
            ->getMock();
        $mockedrestore->method('is_question_created')->willReturn($questioncreated);
        $mockedrestore->method('get_new_parentid')->willReturn($createdquestionid);

        $backupdata = [
            'id' => $backupquestionid,
            'grademethod' => $grademethod,
            'multiple' => $multiple,
            'shuffleanswers' => $shuffleanswers,
            'usedndui' => $usedndui
        ];
        $this->assertEquals(0, $DB->count_records('qtype_matrix'));
        $mockedrestore->process_matrix($backupdata);
        $nrmatrixrecords = $questioncreated ? 1 : 0;
        $this->assertEquals($nrmatrixrecords, $DB->count_records('qtype_matrix'));
        if ($questioncreated) {
            $createdmatrix = $DB->get_record('qtype_matrix', ['questionid' => $createdquestionid]);
            $this->assertNotNull($createdmatrix);
            $this->assertEquals($grademethod, $createdmatrix->grademethod);
            $this->assertEquals($multiple, $createdmatrix->multiple);
            $this->assertEquals($shuffleanswers, $createdmatrix->shuffleanswers);
            $this->assertEquals($usedndui, $createdmatrix->usedndui);
        }
    }

    public function process_matrix_data_provider() {
        return [
            'Good data, question was created' => ['kprime', '0', '0', '0', true],
            'Good data, question could be mapped' => ['kprime', '0', '0', '0', false],
            // FIXME: This should probably be prevented and massaged to a default value
            'Bad grademethod, question was created' => ['foobar', '0', '0', '0', true],
            // FIXME: Unknown if this will create problems, but saving options this should probably be massaged to bool
            'Bad multiple, question was created' => ['kprime', '2', '0', '0', true],
            // FIXME: Unknown if this will create problems, but saving options this should probably be massaged to bool
            'Bad shuffleanswers, question was created' => ['kprime', '0', '2', '0', true],
            // FIXME: Unknown if this will create problems, but saving options this should probably be massaged to bool
            'Bad usedndui, question was created' => ['kprime', '0', '0', '2', true],
        ];
    }

    /**
     * @dataProvider process_col_data_provider
     * @param bool $questioncreated
     * @return void
     * @throws dml_exception
     */
    public function test_process_col(
        string $backupshorttext,
        string $backupdescription,
        bool $questioncreated,
        bool $exceptionexpected
    ): void {
        global $DB;
        $this->resetAfterTest();
        $backupcolid = 456;
        $backupmatrixid = 123;
        $createdormappedmatrixid = 234;
        $mockedrestore = $this
            ->getMockBuilder(restore_qtype_matrix_plugin::class)
            ->setConstructorArgs([
                'qtype',
                'matrix',
                new mock_restore_structure_step(
                    'name',
                    'filename',
                    null
                )])->onlyMethods([
                'is_question_created',
                'get_old_parentid',
                'get_new_parentid',
                'set_mapping'
            ])
            ->getMock();
        $mockedrestore->method('is_question_created')->willReturn($questioncreated);
        $mockedrestore->method('get_old_parentid')->willReturn($backupmatrixid);
        $mockedrestore->method('get_new_parentid')->willReturn($createdormappedmatrixid);
        $backupdata = [
            'id' => $backupcolid,
            'matrixid' => $backupmatrixid,
            'shorttext' => $backupshorttext,
            'description' => $backupdescription
        ];
        $this->assertEquals(0, $DB->count_records('qtype_matrix_cols'));
        if ($exceptionexpected) {
            $this->expectExceptionMessageMatches('/Failed to find an answer matching/');
        }
        $mockedrestore->process_col($backupdata);
        $expectedcolrecords = $questioncreated ? 1 : 0;
        $this->assertEquals($expectedcolrecords, $DB->count_records('qtype_matrix_cols'));
        if ($questioncreated) {
            $createdcol = $DB->get_record('qtype_matrix_cols', ['matrixid' => $createdormappedmatrixid]);
            $this->assertNotNull($createdcol);
            $this->assertEquals($createdormappedmatrixid, $createdcol->matrixid);
            $this->assertEquals($backupshorttext, $createdcol->shorttext);
            $this->assertEquals($backupdescription, $createdcol->description);
        }
        // FIXME: Missing that the question was mapped and the matching column can be found
    }

    public function process_col_data_provider() {
        return [
            'Good data, question created' => ['BShorttext', 'BDescription', true, false],
            // FIXME: This should be tested once we prevent rows/columns having the same shorttext
            // 'Good data, question mapped, matching column' => ['BShorttext', 'BDescription', false, false],
            // FIXME: This test is probably useless, should never happen if questiondata hash generation is good
            'Good data, question mapped, no matching column' => ['BShorttext', 'BDescription', false, true]
        ];
    }

    public function test_process_weight():void {
        global $DB;
        $this->resetAfterTest();
        $mockedrestore = $this
            ->getMockBuilder(restore_qtype_matrix_plugin::class)
            ->setConstructorArgs([
                'qtype',
                'matrix',
                new mock_restore_structure_step(
                    'name',
                    'filename',
                    null
                )])->onlyMethods([
                'get_mappingid',
                'set_mapping'
            ])
            ->getMock();
        $backupweightid = 345;
        $backuprowid = 123;
        $backupcolid = 234;
        $mappedrowid = 456;
        $mappedcolid = 567;
        $get_mappingid_map = [
            ['row', $backuprowid, false, $mappedrowid],
            ['col', $backupcolid, false, $mappedcolid],
        ];
        $mockedrestore->method('get_mappingid')->willReturnMap($get_mappingid_map);
        $backupdata = [
            'id' => $backupweightid,
            'rowid' => $backuprowid,
            'colid' => $backupcolid,
            'weight' => 1
        ];
        $this->assertEquals(0, $DB->count_records('qtype_matrix_weights'));
        $mockedrestore->process_weight($backupdata);
        $this->assertEquals(1, $DB->count_records('qtype_matrix_weights'));
        $createdweightrecord = $DB->get_record('qtype_matrix_weights', ['colid' => $mappedcolid]);
        $this->assertNotNull($createdweightrecord);
        $this->assertNotEquals($backuprowid, $createdweightrecord->rowid);
        $this->assertEquals($mappedrowid, $createdweightrecord->rowid);
        $this->assertNotEquals($backupcolid, $createdweightrecord->colid);
        $this->assertEquals($mappedcolid, $createdweightrecord->colid);
        $this->assertEquals(1, $createdweightrecord->weight);
    }

    public function test_recode_legacy_state_answer():void {
        $mockedrestore = $this
            ->getMockBuilder(restore_qtype_matrix_plugin::class)
            ->setConstructorArgs([
                'qtype',
                'matrix',
                new mock_restore_structure_step(
                    'name',
                    'filename',
                    null
                )])->onlyMethods([
                'get_mappingid',
            ])
            ->getMock();
        $state = new stdClass();

        $state->answer = [];
        $row = [234 => 1, 235 => 0, 236 => 0, 237 => 0];
        $state->answer[123] = $row;
        $row = [234 => 0, 235 => 1, 236 => 0, 237 => 0];
        $state->answer[124] = $row;
        $row = [234 => 0, 235 => 0, 236 => 1, 237 => 0];
        $state->answer[125] = $row;
        $row = [234 => 0, 235 => 0, 236 => 0, 237 => 1];
        $state->answer[126] = $row;
        $state->answer = serialize($state->answer);
        $get_mappingid_map = [
            ['row', 123, false, 345],
            ['row', 124, false, 346],
            ['row', 125, false, 347],
            ['row', 126, false, 348],
            ['col', 234, false, 456],
            ['col', 235, false, 457],
            ['col', 236, false, 458],
            ['col', 237, false, 459],
        ];
        $mockedrestore->method('get_mappingid')->willReturnMap($get_mappingid_map);
        $result = unserialize($mockedrestore->recode_legacy_state_answer($state));
        $expectedresult = [];
        $row = [456 => 1, 457 => 0, 458 => 0, 459 => 0];
        $expectedresult[345] = $row;
        $row = [456 => 0, 457 => 1, 458 => 0, 459 => 0];
        $expectedresult[346] = $row;
        $row = [456 => 0, 457 => 0, 458 => 1, 459 => 0];
        $expectedresult[347] = $row;
        $row = [456 => 0, 457 => 0, 458 => 0, 459 => 1];
        $expectedresult[348] = $row;

        $this->assertEquals($expectedresult, $result);
    }

    public function test_recode_response():void {
        $fakematrixid = 111;
        $fakequestionid = 667;
        $fakeattemptid = 890;
        $fakecolumn = new stdClass();
        $mockedstore = $this
            ->getMockBuilder(question_matrix_store::class)
            ->onlyMethods([
                'get_matrix_by_question_id',
                'get_matrix_cols_by_matrix_id',
            ])
            ->getMock();
        $mockedrestore = $this
            ->getMockBuilder(restore_qtype_matrix_plugin::class)
            ->setConstructorArgs([
                'qtype',
                'matrix',
                new mock_restore_structure_step(
                    'name',
                    'filename',
                    null
                )])->onlyMethods([
                'get_new_parentid',
                'get_matrix_store',
                'get_mappingid'
            ])
            ->getMock();
        $mockmatrix = new stdClass();
        $mockmatrix->id = $fakematrixid;
        $mockmatrix->multiple = true;
        $mockedstore->method('get_matrix_by_question_id')->willReturn($mockmatrix);
        $mockcols = [
            456 => $fakecolumn,
            457 => $fakecolumn,
            458 => $fakecolumn,
            459 => $fakecolumn,
        ];
        $mockedstore->method('get_matrix_cols_by_matrix_id')->willReturn($mockcols);
        $mockedrestore->method('get_matrix_store')->willReturn($mockedstore);
        $get_mappingid_map = [
            ['row', 123, 0, 345],
            ['row', 123, false, 345],
            ['row', 124, 0, 346],
            ['row', 124, false, 346],
            ['row', 125, 0, 347],
            ['row', 125, false, 347],
            ['row', 126, 0, 348],
            ['row', 126, false, 348],
            ['col', 234, 0, 456],
            ['col', 234, false, 456],
            ['col', 235, 0, 457],
            ['col', 235, false, 457],
            ['col', 236, 0, 458],
            ['col', 236, false, 458],
            ['col', 237, 0, 459],
            ['col', 237, false, 459],
        ];
        $mockedrestore->method('get_new_parentid')->willReturn($fakeattemptid);
        $mockedrestore->method('get_mappingid')->willReturnMap($get_mappingid_map);

        $multipleresponses = [
            [
                '_order' => '123,124,125,126'
            ],
            [
                'cell123_234' => 1,
                'cell123_235' => 1,
                'unchanged' => 'foobar'
            ],
            [
                '-finish' => 1
            ]
        ];
        $expectedrecoded = [
            [
                '_order' => '345,346,347,348'
            ],
            [
                'row0col0' => 1,
                'row0col1' => 1,
                'unchanged' => 'foobar'
            ],
            [
                '-finish' => 1
            ]
        ];
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($expectedrecoded[$i], $mockedrestore->recode_response(
                $fakequestionid, $i, $multipleresponses[$i]
            ));
        }
        $mockmatrix->multiple = false;
        $singleresponses = [
            [
                '_order' => '123,124,125,126'
            ],
            [
                'cell123' => '234',
                'cell124' => '235',
                'unchanged' => 'foobar'
            ],
            [
                '-finish' => 1
            ]
        ];
        $expectedrecoded = [
            [
                '_order' => '345,346,347,348'
            ],
            [
                'row0col0' => 1,
                'row1col1' => 1,
                'unchanged' => 'foobar'
            ],
            [
                '-finish' => 1
            ]
        ];
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($expectedrecoded[$i], $mockedrestore->recode_response(
                $fakequestionid, $i, $singleresponses[$i]
            ));
        }
        $badresponses = [
            [
                '_order' => '111,124,125,126',
            ],
            [
                'cell123' => '234',
                'cell124' => '235',
                'unchanged' => 'foobar'
            ],
            [
                '-finish' => 1
            ]
        ];
        $mockedrestore->recode_response(
            $fakequestionid, 0, $badresponses[0]
        );
        $this->expectExceptionMessage('error_qtype_matrix_attempt_step_data_not_migratable');
        $mockedrestore->recode_response(
            $fakequestionid, 0, $badresponses[1]
        );
    }

    public function test_convert_backup_to_questiondata():void {
        $backupdata = [];
        $backupdata['qtype'] = 'matrix';
        // also every XML shit
        $backupdata['plugin_qtype_matrix_question']['matrix'][0] = [];
        $matrix = &$backupdata['plugin_qtype_matrix_question']['matrix'][0];
        $matrix['id'] = '111';
        $matrix['grademethod'] = 'kprime';
        $matrix['multiple'] = '1';
        $matrix['shuffleanswers'] = '1';
        $matrix['usedndui'] = '0';
        $matrix['rows']['row'][0] = [
            'id' => '123',
            'description' => 'backuprowdesc',
            'feedback' => 'backuprowfeedback'
        ];
        $matrix['rows']['row'][1] = [
            'id' => '124',
            'description' => 'backuprowdesc124',
            'feedback' => 'backuprowfeedback124'
        ];
        $matrix['cols']['col'][0] = [
            'id' => '234',
            'description' => 'backupcoldesc',
        ];
        $matrix['weights']['weight'][0] = [
            'rowid' => '123',
            'colid' => '234',
            'weight' => '1'
        ];
        $expected = new stdClass();
        $expected->qtype = 'matrix';
        $expected->options = new stdClass();
        $options = $expected->options;
        $options->id = 111;
        $options->grademethod = 'kprime';
        $options->multiple = true;
        $options->shuffleanswers = true;
        $options->usedndui = false;
        $options->rows = [];
        $rows = &$options->rows;
        $rows[123] = (object) [
            'id' => 123,
            'matrixid' => 111,
            'description' => [
                'text' => 'backuprowdesc',
                'format' => FORMAT_HTML
            ],
            'feedback' => [
                'text' => 'backuprowfeedback',
                'format' => FORMAT_HTML
            ]
        ];
        $rows[124] = (object) [
            'id' => 124,
            'matrixid' => 111,
            'description' => [
                'text' => 'backuprowdesc124',
                'format' => FORMAT_HTML
            ],
            'feedback' => [
                'text' => 'backuprowfeedback124',
                'format' => FORMAT_HTML
            ]
        ];
        $options->cols = [];
        $cols = &$options->cols;
        $cols[234] = (object) [
            'id' => 234,
            'matrixid' => 111,
            'description' => [
                'text' => 'backupcoldesc',
                'format' => FORMAT_HTML
            ]
        ];
        $options->weights = [];
        $weights = &$options->weights;
        $weights[123][234] = 1;
        $weights[124][234] = 0;
        $this->assertEquals($expected, restore_qtype_matrix_plugin::convert_backup_to_questiondata($backupdata));
    }
}
