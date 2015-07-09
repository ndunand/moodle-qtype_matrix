<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // mod_ND : BEGIN
    $settings->add(new admin_setting_configcheckbox('qtype_matrix/show_non_kprime_gui',
            get_string('show_non_kprime_gui', 'qtype_matrix'), get_string('show_non_kprime_gui', 'qtype_matrix'), '0',
            '1', '0'));
    $settings->add(new admin_setting_configcheckbox('qtype_matrix/allow_dnd_ui',
            get_string('allow_dnd_ui', 'qtype_matrix'), get_string('allow_dnd_ui_descr', 'qtype_matrix'), '0',
            '1', '0'));
    // mod_ND : END
}
