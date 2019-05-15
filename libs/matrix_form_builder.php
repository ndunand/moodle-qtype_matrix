<?php

/**
 * Helper class to build the form.
 */
class matrix_form_builder implements ArrayAccess
{

    private $_form = null;
    
    function __construct($form) {
       $this->_form = $form;
   }

    public function create_name()
    {
        static $count = 0;
        return '__j' . $count++;
    }

    public function create_javascript($js)
    {
        $html = '<script type="text/javascript">';
        $html .= $js;
        $html .= '</script>';
        $name = $this->create_name();
        return $this->_form->createElement('static', $name, null, $html);
    }

    public function create_static($html)
    {
        $name = $this->create_name();
        return $this->_form->createElement('static', $name, null, $html);
    }

    public function create_text($name, $label = '')
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->createElement('text', $name, $label);
    }

    public function create_htmleditor($name, $label = '')
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->createElement('editor', $name, $label);
    }

    public function create_htmlpopup($name, $label = '')
    {
        static $pop_count = 0;
        $pop_count++;
        $id = "htmlpopup$pop_count";

        $result = array();
        $result[] = $this->create_static('<a class="pbutton input-group-addon" href="#" onclick="mtrx_popup(\'' . $id . '\');return false;" >...</a>');
        $result[] = $this->create_static('<div id="' . $id . '" class="popup">');
        $result[] = $this->create_static('<div>');
        $result[] = $this->create_static('<a class="pbutton close" href="#" onclick="mtrx_popup(\'' . $id . '\');return false;" >&nbsp;&nbsp;&nbsp;</a>');
        $result[] = $this->create_static('<span class="title">');
        $result[] = $this->create_static($label);
        $result[] = $this->create_static('</span>');
        $result[] = $this->create_htmleditor($name);
        $result[] = $this->create_static('</div>');
        $result[] = $this->create_static('</div>');
        return $result;
    }

    public function create_hidden($name, $value = null)
    {
        return $this->_form->createElement('hidden', $name, $value);
    }

    public function create_group($name = null, $label = null, $elements = null, $separator = '', $appendName = true)
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->createElement('group', $name, $label, $elements, $separator, $appendName);
    }

    public function create_header($name, $label = '')
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->createElement('header', $name, $label);
    }

    public function create_submit($name, $label = '', $attributes = null)
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->createElement('submit', $name, $label, $attributes);
    }

    public function add_javascript($js)
    {
        $this[] = $element = $this->create_javascript($js);
        return $element;
    }

    public function add_static($html)
    {
        return $this->_form->addElement('static', null, null, $html);
    }

    public function add_text($name, $label = '')
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->addElement('text', $name, $label);
    }

    public function add_htmleditor($name, $label = '')
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->addElement('htmleditor', $name, $label);
    }

    public function add_hidden($name, $value = null)
    {
        return $this->_form->addElement('hidden', $name, $value);
    }

    public function add_group($name = null, $label = null, $elements = null, $separator = '', $appendName = true)
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->addElement('group', $name, $label, $elements, $separator, $appendName);
    }

    public function add_header($name, $label = '')
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->addElement('header', $name, $label);
    }

    public function add_selectyesno($name, $label = '')
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        $result = $this->_form->addElement('advcheckbox', $name, $label);
        return $result;
    }

    public function add_select($name, $label = '', $options = null)
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->addElement('select', $name, $label, $options);
    }

    public function add_submit($name, $label = '')
    {
        if ($label === '') {
            $short_name = explode('[', $name);
            $short_name = reset($short_name);
            $label = qtype_matrix::get_string($short_name);
        }
        return $this->_form->addElement('submit', $name, $label);
    }

    public function add_help_button($elementname, $identifier = null, $component = 'qtype_matrix', $linktext = '', $suppresscheck = false)
    {
        if (is_null($identifier)) {
            $identifier = $elementname;
        }
        $this->_form->addHelpButton($elementname, $identifier, $component, $linktext, $suppresscheck);
    }

    public function add_element($element)
    {
        return $this->_form->addElement($element);
    }

    public function set_default($name, $value)
    {
        $this->_form->setDefault($name, $value);
    }

    public function element_exists($name)
    {
        return $this->_form->elementExists($name);
    }

    public function insert_element_before($element, $before_name)
    {
        return $this->_form->insertElementBefore($element, $before_name);
    }

    public function disabled_if($elementName, $dependentOn, $condition = 'notchecked', $value = '1')
    {
        $this->_form->disabledIf($elementName, $dependentOn, $condition, $value);
    }

    public function register_no_submit_button($name)
    {
        $this->_form->registerNoSubmitButton($name);
    }

    // implement ArrayAccess
    
    public function offsetExists($offset)
    {
        return $this->_form->elementExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->_form->getElement($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->_form->addElement($value);
    }

    public function offsetUnset($offset)
    {
        $this->_form->removeElement($offset);
    }

}