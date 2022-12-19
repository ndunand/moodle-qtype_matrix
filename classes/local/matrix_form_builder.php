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
 * Helper class to build the form.
 */
class matrix_form_builder implements \ArrayAccess {

    private $_form = null;

    public function __construct(\MoodleQuickForm $form) {
        $this->_form = $form;
    }

    public function create_text(string $name, string $label = ''): object {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->createElement('text', $name, $label);
    }

    public function create_htmlpopup(string $name, string $label = ''): array {
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

    public function create_static(string $html): object {
        $name = $this->create_name();
        return $this->_form->createElement('static', $name, null, $html);
    }

    public function create_name(): string {
        static $count = 0;
        return '__j' . $count++;
    }

    public function create_htmleditor(string $name, string $label = ''): object {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->createElement('editor', $name, $label);
    }

    public function create_hidden(string $name, $value = null): object {
        return $this->_form->createElement('hidden', $name, $value);
    }

    public function create_group(?string $name = null,
        ?string $label = null,
        array $elements = [],
        string $separator = '',
        bool $appendname = true): object {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->createElement('group', $name, $label, $elements, $separator, $appendname);
    }

    public function create_header(string $name, string $label = ''): object {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->createElement('header', $name, $label);
    }

    public function create_submit(string $name, string $label = '', array $attributes = []): object {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->createElement('submit', $name, $label, $attributes);
    }

    public function add_javascript(string $js): object {
        $element = $this->create_javascript($js);
        $this[] = $element; // Unsure if arrayaccess should be really used like this, seems to be hacky.
        return $element;
    }

    public function create_javascript(string $js): object {
        $html = '<script type="text/javascript">';
        $html .= $js;
        $html .= '</script>';
        $name = $this->create_name();
        return $this->_form->createElement('static', $name, null, $html);
    }

    public function add_static(string $html): \HTML_QuickForm_element {
        return $this->_form->addElement('static', null, null, $html);
    }

    public function add_text(string $name, string $label = ''): \HTML_QuickForm_element {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->addElement('text', $name, $label);
    }

    public function add_htmleditor(string $name, string $label = ''): \HTML_QuickForm_element {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->addElement('htmleditor', $name, $label);
    }

    public function add_hidden(string $name, $value = null): \HTML_QuickForm_element {
        return $this->_form->addElement('hidden', $name, $value);
    }

    public function add_group(?string $name = null,
        ?string $label = null,
        array $elements = [],
        string $separator = '',
        bool $appendname = true): \HTML_QuickForm_element {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->addElement('group', $name, $label, $elements, $separator, $appendname);
    }

    public function add_header(string $name, string $label = ''): \HTML_QuickForm_element {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->addElement('header', $name, $label);
    }

    public function add_selectyesno(string $name, string $label = ''): \HTML_QuickForm_element {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        $result = $this->_form->addElement('advcheckbox', $name, $label);
        return $result;
    }

    public function add_select(string $name, string $label = '', array $options = []): \HTML_QuickForm_element {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->addElement('select', $name, $label, $options);
    }

    public function add_submit(string $name, string $label = ''): \HTML_QuickForm_element {
        if ($label === '') {
            $shortname = explode('[', $name);
            $shortname = reset($shortname);
            $label = lang::get($shortname);
        }
        return $this->_form->addElement('submit', $name, $label);
    }

    public function add_help_button(string $elementname,
        ?string $identifier = null,
        string $component = 'qtype_matrix',
        string $linktext = '',
        bool $suppresscheck = false): void {
        if (is_null($identifier)) {
            $identifier = $elementname;
        }
        $this->_form->addHelpButton($elementname, $identifier, $component, $linktext, $suppresscheck);
    }

    public function set_default(string $name, $value): void {
        $this->_form->setDefault($name, $value);
    }

    public function element_exists(string $name): bool {
        return $this->_form->elementExists($name);
    }

    public function insert_element_before($element, $beforename): object {
        return $this->_form->insertElementBefore($element, $beforename);
    }

    public function disabled_if($elementname, $dependenton, string $condition = 'notchecked', $value = '1'): void {
        $this->_form->disabledIf($elementname, $dependenton, $condition, $value);
    }

    public function register_no_submit_button(string $name): void {
        $this->_form->registerNoSubmitButton($name);
    }

    // Implement ArrayAccess.

    public function offsetExists($offset): bool {
        return $this->_form->elementExists($offset);
    }

    /**
     * Cant type this function returns object/mixed ?
     *
     * @param $offset
     * @return mixed|object
     */
    public function offsetGet($offset) {
        return $this->_form->getElement($offset);
    }

    public function offsetSet($offset, $value): void {
        $this->_form->addElement($value);
    }

    public function offsetUnset($offset): void {
        $this->_form->removeElement($offset);
    }
}
