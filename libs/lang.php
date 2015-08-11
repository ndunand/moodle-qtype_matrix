<?php

/**
 *  Helper class for strings
 */
class lang
{

    const COMPONENT = 'qtype_matrix';

    public static function get($identifier, $a = null)
    {
        return get_string($identifier, self::COMPONENT, $a);
    }

    public static function use_dnd_ui()
    {
        return self::get('use_dnd_ui');
    }

    public static function shuffle_answers()
    {
        return self::get('shuffleanswers');
    }

    public static function must_define_1_by_1()
    {
        return self::get('mustdefine1by1');
    }

    public static function multiple_allowed()
    {
        return self::get('multipleallowed');
    }

    public static function grade_method()
    {
        return self::get('grademethod');
    }

    public static function col_description()
    {
        return self::get('cols_description');
    }

    public static function row_feedback()
    {
        return self::get('rows_feedback');
    }

    public static function row_long()
    {
        return self::get('rows_description');
    }

    public static function true_()
    {
        return self::get('true');
    }

    public static function false_()
    {
        return self::get('false');
    }

    public static function one_answer_per_row()
    {
        return self::get('oneanswerperrow');
    }

}
