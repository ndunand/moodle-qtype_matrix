<?php
/**
 * Author: Daniel Poggenpohl
 * Date: 06.01.2026
 */

namespace qtype_matrix\db;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot . '/question/type/matrix/question.php';

use core\exception\moodle_exception;
use qtype_matrix_question;

class stepdata_migration_utils {

    const ERRORCODE_BADDATA = 'attemptdatamigration_baddata';

    public static function extract_row_id(string $stepdataname):int {
        $ismultiple = str_contains($stepdataname, '_');
        if (!str_contains($stepdataname, 'cell')) {
            return 0;
        }
        $nocellname = str_replace('cell', '', $stepdataname);
        if ($nocellname === '') {
            return 0;
        }
        $rowid = $ismultiple ? preg_replace('/_.*$/', '', $nocellname) : $nocellname;
        if (!preg_match('/^\d{1,}$/', $rowid)) {
            return 0;
        }
        return $rowid;
    }

    public static function extract_col_id(string $stepdataname, string $stepdatavalue):int {
        $ismultiple = str_contains($stepdataname, '_');
        if (!str_contains($stepdataname, 'cell')) {
            return 0;
        }
        $nocellname = str_replace('cell', '', $stepdataname);
        $colid = $ismultiple ? preg_replace('/^.*_/', '', $nocellname) : $stepdatavalue;
        if ($nocellname === '' || $colid === '') {
            return 0;
        }
        if (!preg_match('/^\d{1,}$/', $colid)) {
            return 0;
        }
        return $colid;
    }

    public static function to_new_name(
        string $oldstepdataname,
        string $oldstepdatavalue,
        array $attemptorder,
        array $colids
    ):string {
        $oldrowid = stepdata_migration_utils::extract_row_id($oldstepdataname);
        $oldcolid = stepdata_migration_utils::extract_col_id($oldstepdataname, $oldstepdatavalue);
        if (!$attemptorder || !$oldrowid || !$oldcolid || !$colids) {
            throw new moodle_exception(self::ERRORCODE_BADDATA, 'qtype_matrix');
        }
        $newrowindex = array_search($oldrowid, $attemptorder);
        $newcolindex = array_search($oldcolid, $colids);
        if ($newcolindex === false || $newrowindex === false) {
            throw new moodle_exception(self::ERRORCODE_BADDATA, 'qtype_matrix');
        }
        return qtype_matrix_question::responsekey($newrowindex, $newcolindex);
    }

    public static function stepdata_sql(string $qinsql, string $stepdatanameregex): string {
         return "SELECT qasd.id as stepdataid, q.id as questionid, qa.id as attemptid, qas.id as stepid, qasd.name, qasd.value
                FROM {question} q
                JOIN {question_attempts} qa ON qa.questionid = q.id
                JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
                JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id
                WHERE q.id ".$qinsql. "
                AND qasd.name ~ '".$stepdatanameregex."' ORDER BY qasd.name DESC, q.id ASC, qa.id ASC, qas.id ASC, qasd.id ASC 
            ";
    }

    public static function extract_matrixinfos(array $questionids):array {
        global $DB;

        $matrixinfos = [];

        [$qinsql, $qidparams] = $DB->get_in_or_equal(array_keys($questionids));

        // Leave out matrix questions with broken data (missing col records)
        $colssql = '
                SELECT qmc.id as colid, qm.id as matrixid, q.id as questionid
                FROM {question} q
                LEFT JOIN {qtype_matrix} qm ON qm.questionid = q.id
                LEFT JOIN {qtype_matrix_cols} qmc ON qmc.matrixid = qm.id
                WHERE q.id ' . $qinsql . ' AND qmc.id IS NOT NULL ORDER BY q.id, qm.id, qmc.id ASC
            ';
        $matrixcols = $DB->get_records_sql($colssql, $qidparams);

        // First collect metadata for question attempt step data (matrix data, column data, attempt row order).
        foreach ($matrixcols as $matrixcol) {
            if (!$matrixcol->matrixid || !$matrixcol->colid) {
                // broken question (no matrix or no col records)
                continue;
            }
            if (!isset($matrixinfos[$matrixcol->questionid])) {
                $matrixinfos[$matrixcol->questionid] = [];
            }
            $matrix = &$matrixinfos[$matrixcol->questionid];
            if (!isset($matrix['matrixid'])) {
                $matrix['matrixid'] = $matrixcol->matrixid;
            }
            if (!isset($matrix['cols'])) {
                $matrix['cols'] = [];
            }
            $matrix['cols'][] = $matrixcol->colid;
            if (!isset($matrix['attemptroworder'])) {
                $matrix['attemptroworder'] = [];
            }
        }
        unset($matrixcols);

        // Collect info about attempt row order.
        $orderstepdatasql = stepdata_migration_utils::stepdata_sql($qinsql, '^_order$');
        $orderdatarecords = $DB->get_records_sql($orderstepdatasql, $qidparams);
        foreach ($orderdatarecords as $orderdata) {
            if (!isset($matrixinfos[$orderdata->questionid])) {
                continue;
            }
            $matrix = &$matrixinfos[$orderdata->questionid];
            $matrix['attemptroworder'][$orderdata->attemptid] = explode(',', $orderdata->value);
        }
        unset($orderdatarecords);
        return $matrixinfos;
    }

    public static function migrate_stepdata(array $matrixinfos, array $questionids):void {
        global $DB;
        [$qinsql, $qidparams] = $DB->get_in_or_equal(array_keys($questionids));
        $cellstepdatasql = self::stepdata_sql($qinsql, '^cell');
        $celldatarecords = $DB->get_records_sql($cellstepdatasql, $qidparams);
        $nrcelldata = count($celldatarecords);
        $celldatacount = 0;

        $celldatabatchsize = 10000;
        $celldatabatchcount = 0;
        $celldataids = [];
        $sqlnamecase = '';
        $sqlvaluecase = '';

        foreach ($celldatarecords as $celldata) {
            $celldatacount++;
            if (!isset($matrixinfos[$celldata->questionid])) {
                continue;
            }
            $matrix = &$matrixinfos[$celldata->questionid];
            try {
                $newname = stepdata_migration_utils::to_new_name(
                    $celldata->name,
                    $celldata->value,
                    $matrix['attemptroworder'][$celldata->attemptid],
                    $matrix['cols']
                );
                $when = ' WHEN id = '.$celldata->stepdataid;
                $sqlnamecase .= $when;
                $sqlvaluecase .= $when;
                $sqlnamecase .= " THEN '".$newname."'";
                $sqlvaluecase .= " THEN '1'";
                $celldataids[] = $celldata->stepdataid;
                $celldatabatchcount++;
            } catch (moodle_exception $e) {
                if ($e->errorcode == stepdata_migration_utils::ERRORCODE_BADDATA) {
                    // Step data and matrix data probably doesn't match anymore
                    continue;
                }
            }
            if ($celldatabatchcount == $celldatabatchsize || $celldatacount == $nrcelldata) {
                if ($celldataids) {
                    // Build a big UPDATE query for each stepdata batch to speed up migration.
                    [$insql, $celldataparams] = $DB->get_in_or_equal($celldataids);
                    $updatesql = 'UPDATE {question_attempt_step_data}';
                    $sqlnamecase = ' SET name = CASE' . $sqlnamecase . ' END';
                    $sqlvaluecase = ', value = CASE' . $sqlvaluecase . ' END';
                    $updatesql .= $sqlnamecase . $sqlvaluecase;
                    $updatesql .= ' WHERE id '.$insql;
                    $DB->execute($updatesql, $celldataparams);
                }
                // Reset loop vars.
                $sqlnamecase = '';
                $sqlvaluecase = '';
                $celldatabatchcount = 0;
                $celldataids = [];
            }
        }
    }
}
