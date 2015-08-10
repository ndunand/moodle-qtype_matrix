<?php

/**
 * 
 */
class config
{

    const COMPONENT = 'qtype_matrix';

    static function get($name)
    {
        return get_config(self::COMPONENT, $name);
    }

    static function show_kprime_gui()
    {
        global $CFG;

        return !property_exists($CFG, 'qtype_matrix_show_non_kprime_gui') || $CFG->qtype_matrix_show_non_kprime_gui !== '0';
    }

    static function allow_dnd_ui()
    {
        return self::get('allow_dnd_ui');
    }

}
