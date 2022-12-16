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

namespace qtype_matrix\local;
/**
 *
 */
class setting {
    const COMPONENT = 'qtype_matrix';

    public static function show_kprime_gui() {
        global $CFG;

        return !property_exists($CFG,
                'qtype_matrix_show_non_kprime_gui') || $CFG->qtype_matrix_show_non_kprime_gui !== '0';
    }

    public static function allow_dnd_ui() {
        return self::get('allow_dnd_ui');
    }

    public static function get($name) {
        return get_config(self::COMPONENT, $name);
    }

}
