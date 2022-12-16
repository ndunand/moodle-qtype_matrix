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

/**
 * Helper class to build the form.
 */
class matrix_form_builder implements ArrayAccess {

    private $_form = null;

    public function __construct($form) {
        $this->_form = $form;
    }

    public function create_text($name, $label = '') {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->createElement('text', $name, $label);
    }

    public function create_htmlpopup($name, $label = '') {
        static $popcount = 0;
        $popcount++;
        $id = "htmlpopup$popcount";
        $result = [];
        $result[] = $this->create_static(
            '<a class="pbutton input-group-addon" href="#" onclick="mtrx_popup(\'' . $id . '\');return false;" >...</a>'
        );
        $result[] = $this->create_static('<div id="' . $id . '" class="popup">');
        $result[] = $this->create_static('<div>');
        $result[] = $this->create_static(
            '<a class="pbutton close" href="#" onclick="mtrx_popup(\'' . $id . '\');return false;" >&nbsp;&nbsp;&nbsp;</a>'
        );
        $result[] = $this->create_static('<span class="title">');
        $result[] = $this->create_static($label);
        $result[] = $this->create_static('</span>');
        $result[] = $this->create_htmleditor($name);
        $result[] = $this->create_static('</div>');
        $result[] = $this->create_static('</div>');
        return $result;
    }

    public function create_static($html) {
        $name = $this->create_name();
        return $this->_form->createElement('static', $name, null, $html);
    }

    public function create_name() {
        static $count = 0;
        return '__j' . $count++;
    }

    public function create_htmleditor($name, $label = '') {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->createElement('editor', $name, $label);
    }

    public function create_hidden($name, $value = null) {
        return $this->_form->createElement('hidden', $name, $value);
    }

    public function create_group($name = null, $label = null, $elements = null, $separator = '', $appendname = true) {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->createElement('group', $name, $label, $elements, $separator, $appendname);
    }

    public function create_header($name, $label = '') {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->createElement('header', $name, $label);
    }

    public function create_submit($name, $label = '', $attributes = null) {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->createElement('submit', $name, $label, $attributes);
    }

    public function add_javascript($js) {
        $this[] = $element = $this->create_javascript($js);
        return $element;
    }

    public function create_javascript($js) {
        $html = '<script type="text/javascript">';
        $html .= $js;
        $html .= '</script>';
        $name = $this->create_name();
        return $this->_form->createElement('static', $name, null, $html);
    }

    public function add_static($html) {
        return $this->_form->addElement('static', null, null, $html);
    }

    public function add_text($name, $label = '') {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->addElement('text', $name, $label);
    }

    public function add_htmleditor($name, $label = '') {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->addElement('htmleditor', $name, $label);
    }

    public function add_hidden($name, $value = null) {
        return $this->_form->addElement('hidden', $name, $value);
    }

    public function add_group($name = null, $label = null, $elements = null, $separator = '', $appendname = true) {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->addElement('group', $name, $label, $elements, $separator, $appendname);
    }

    public function add_header($name, $label = '') {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->addElement('header', $name, $label);
    }

    public function add_selectyesno($name, $label = '') {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        $result = $this->_form->addElement('advcheckbox', $name, $label);
        return $result;
    }

    public function add_select($name, $label = '', $options = null) {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->addElement('select', $name, $label, $options);
    }

    public function add_submit($name, $label = '') {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = qtype_matrix::get_string($shortname);
        }
        return $this->_form->addElement('submit', $name, $label);
    }

    public function add_help_button($elementname,
        $identifier = null,
        $component = 'qtype_matrix',
        $linktext = '',
        $suppresscheck = false) {
        if (is_null($identifier)) {
            $identifier = $elementname;
        }
        $this->_form->addHelpButton($elementname, $identifier, $component, $linktext, $suppresscheck);
    }

    public function add_element($element) {
        return $this->_form->addElement($element);
    }

    public function set_default($name, $value) {
        $this->_form->setDefault($name, $value);
    }

    public function element_exists($name) {
        return $this->_form->elementExists($name);
    }

    public function insert_element_before($element, $beforename) {
        return $this->_form->insertElementBefore($element, $beforename);
    }

    public function disabled_if($elementname, $dependenton, $condition = 'notchecked', $value = '1') {
        $this->_form->disabledIf($elementname, $dependenton, $condition, $value);
    }

    public function register_no_submit_button($name) {
        $this->_form->registerNoSubmitButton($name);
    }

    // Implement ArrayAccess.

    public function offsetExists($offset) {
        return $this->_form->elementExists($offset);
    }

    public function offsetGet($offset) {
        return $this->_form->getElement($offset);
    }

    public function offsetSet($offset, $value) {
        $this->_form->addElement($value);
    }

    public function offsetUnset($offset) {
        $this->_form->removeElement($offset);
    }
}
