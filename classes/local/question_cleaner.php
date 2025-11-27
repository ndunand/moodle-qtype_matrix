<?php
/**
 * Author: Daniel Poggenpohl
 * Date: 27.11.2025
 */

namespace qtype_matrix\local;

use qtype_matrix\local\qtype_matrix_grading;

use \stdClass;

class question_cleaner {

    const DEFAULT_MULTIPLE = true;

    const DEFAULT_SHUFFLEANSWERS = true;

    const DEFAULT_USEDNDUI = false;

    public static function clean_data($questiondata, bool $useoptions = false) {
        $datasource = $questiondata;
        if ($useoptions) {
            $questiondata->options ??= new stdClass();
            $datasource = $questiondata->options;
        }

        if (
            !isset($datasource->grademethod)
            || !is_string($datasource->grademethod)
            || !in_array($datasource->grademethod, qtype_matrix_grading::VALID_GRADINGS)
        ) {
            $datasource->grademethod = qtype_matrix_grading::default_grading()->get_name();
        }
        $datasource->multiple = (bool) ($datasource->multiple ?? self::DEFAULT_MULTIPLE);
        $datasource->shuffleanswers = (bool) ($datasource->shuffleanswers ?? self::DEFAULT_SHUFFLEANSWERS);
        $datasource->usedndui = (bool) ($datasource->usedndui ?? self::DEFAULT_USEDNDUI);
        $datasource->rows ??= [];
        $datasource->cols ??= [];
        $datasource->weights ??= [[]];
        return $questiondata;
    }

}
