<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('qtype_matrix_show_non_kprime_gui', get_string('show_non_kprime_gui', 'qtype_matrix'),
                get_string('show_non_kprime_gui', 'qtype_matrix'), '0', '1', '0'));
}
