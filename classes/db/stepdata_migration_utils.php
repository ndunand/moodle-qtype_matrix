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

    public static function stepdata_sql(string $qinsql, string $stepdatanamelike): string {
         return "SELECT qasd.id as stepdataid, q.id as questionid, qa.id as attemptid, qas.id as stepid, qasd.name, qasd.value
                FROM {question} q
                JOIN {question_attempts} qa ON qa.questionid = q.id
                JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
                JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id
                WHERE qasd.name LIKE '" . $stepdatanamelike . "' 
                AND q.id " . $qinsql . "
                ORDER BY qasd.id
            ";
    }

    public static function extract_matrixinfos(array $questionids):array {
        global $DB;

        $matrixinfos = [];

        [$qinsql, $qidparams] = $DB->get_in_or_equal(array_keys($questionids));
        // Leave out matrix questions with broken data (missing col records)
        $colssql = "
                SELECT qmc.id as colid, qm.id as matrixid, q.id as questionid
                FROM {question} q
                LEFT JOIN {qtype_matrix} qm ON qm.questionid = q.id
                LEFT JOIN {qtype_matrix_cols} qmc ON qmc.matrixid = qm.id
                WHERE q.id " . $qinsql . "
                AND qmc.id IS NOT NULL and qm.id IS NOT NULL
            ";
        $matrixcols = $DB->get_recordset_sql($colssql, $qidparams);

        // First collect metadata for question attempt step data (matrix data, column data, attempt row order).
        foreach ($matrixcols as $matrixcol) {
            if (!$matrixcol->matrixid || !$matrixcol->colid) {
                // broken question (no matrix or no col records)
                continue;
            }
            if (!isset($matrixinfos[$matrixcol->questionid])) {
                $matrixinfos[$matrixcol->questionid] = [];
            }
            if (!isset($matrixinfos[$matrixcol->questionid]['matrixid'])) {
                $matrixinfos[$matrixcol->questionid]['matrixid'] = $matrixcol->matrixid;
            }
            if (!isset($matrixinfos[$matrixcol->questionid]['cols'])) {
                $matrixinfos[$matrixcol->questionid]['cols'] = [];
            }
            $matrixinfos[$matrixcol->questionid]['cols'][] = $matrixcol->colid;
        }
        $matrixcols->close();

        // Collect info about attempt row order.
        $orderstepdatasql = stepdata_migration_utils::stepdata_sql($qinsql, '_order');
        // FIXME: Should probably be done in batches of 100.000
        $orderdatars = $DB->get_recordset_sql($orderstepdatasql, $qidparams);
        foreach ($orderdatars as $orderdata) {
            if (!$orderdata->questionid || !isset($matrixinfos[$orderdata->questionid])) {
                continue;
            }
            if (!isset($matrixinfos[$orderdata->questionid]['attemptroworder'])) {
                $matrixinfos[$orderdata->questionid]['attemptroworder'] = [];
            }
            $matrixinfos[$orderdata->questionid]['attemptroworder'][$orderdata->attemptid] = explode(',', $orderdata->value);
        }
        $orderdatars->close();
        return $matrixinfos;
    }

    public static function migrate_stepdata(array &$matrixinfos, array $questionids):void {
        global $DB;
        [$qinsql, $qidparams] = $DB->get_in_or_equal(array_keys($questionids));
        $cellstepdatasql = self::stepdata_sql($qinsql, 'cell%');
        // FIXME: Should probably be done in batches of 100.000

        $celldataupdatechunksize = 10000;
        $celldatars = $DB->get_recordset_sql($cellstepdatasql, $qidparams);
        $nrprocessedchunkcelldata = 0;
        $celldataids = [];
        $sqlnamecase = '';
        foreach ($celldatars as $celldata) {
            if (!$celldata->questionid || !isset($matrixinfos[$celldata->questionid])) {
                continue;
            }
            if (!isset($matrixinfos[$celldata->questionid]['attemptroworder'])) {
                continue;
            }
            if (!$celldata->attemptid || !isset($matrixinfos[$celldata->questionid]['attemptroworder'][$celldata->attemptid])) {
                continue;
            }
            try {
                $newname = stepdata_migration_utils::to_new_name(
                    $celldata->name,
                    $celldata->value,
                    $matrixinfos[$celldata->questionid]['attemptroworder'][$celldata->attemptid],
                    $matrixinfos[$celldata->questionid]['cols']
                );
            } catch (moodle_exception $e) {
                if ($e->errorcode == stepdata_migration_utils::ERRORCODE_BADDATA) {
                    // Step data and matrix data probably doesn't match anymore
                    continue;
                } else {
                    throw $e;
                }
            }
            $sqlnamecase .= ' WHEN id = '.$celldata->stepdataid . " THEN '".$newname."'";
            $celldataids[] = $celldata->stepdataid;
            $nrprocessedchunkcelldata++;
            if ($nrprocessedchunkcelldata == $celldataupdatechunksize) {
                // Build a big UPDATE query for each stepdata batch to speed up migration.
                [$insql, $celldataparams] = $DB->get_in_or_equal($celldataids);
                $updatesql = 'UPDATE {question_attempt_step_data}';
                $sqlnamecase = ' SET name = CASE' . $sqlnamecase . ' END';
                $updatesql .= $sqlnamecase . ", value = '1'";
                $updatesql .= ' WHERE id '.$insql;
                $DB->execute($updatesql, $celldataparams);

                $nrprocessedchunkcelldata = 0;
                unset($celldataids);
                $celldataids = [];
                $sqlnamecase = '';
            }
        }
        $celldatars->close();
    }
}
