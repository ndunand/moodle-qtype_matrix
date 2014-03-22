<?php

$GLOBALS['_HTML_QuickForm_default_renderer'] =& new MatrixFormRenderer();

class MatrixFormRenderer extends MoodleQuickForm_Renderer{

//
//    function renderElement(&$element, $required, $error){
//        $this->MoodleQuickForm_Renderer_renderElement($element, $required, $error);
//    }
//
//
//    function MoodleQuickForm_Renderer_renderElement(&$element, $required, $error){
//        //manipulate id of all elements before rendering
//        if (!is_null($element->getAttribute('id'))) {
//            $id = $element->getAttribute('id');
//        } else {
//            $id = $element->getName();
//        }
//        //strip qf_ prefix and replace '[' with '_' and strip ']'
//        $id = preg_replace(array('/^qf_|\]/', '/\[/'), array('', '_'), $id);
//        if (strpos($id, 'id_') !== 0){
//            $element->updateAttributes(array('id'=>'id_'.$id));
//        }
//
//        //adding stuff to place holders in template
//        if (method_exists($element, 'getElementTemplateType')){
//            $html = $this->_elementTemplates[$element->getElementTemplateType()];
//        }else{
//            $html = $this->_elementTemplates['default'];
//        }
//        if ($this->_showAdvanced){
//            $advclass = ' advanced';
//        } else {
//            $advclass = ' advanced hide';
//        }
//        if (isset($this->_advancedElements[$element->getName()])){
//            $html =str_replace(' {advanced}', $advclass, $html);
//        } else {
//            $html =str_replace(' {advanced}', '', $html);
//        }
//        if (isset($this->_advancedElements[$element->getName()])||$element->getName() == 'mform_showadvanced'){
//            $html =str_replace('{advancedimg}', $this->_advancedHTML, $html);
//        } else {
//            $html =str_replace('{advancedimg}', '', $html);
//        }
//        $html =str_replace('{type}', 'f'.$element->getType(), $html);
//        $html =str_replace('{name}', $element->getName(), $html);
//        if (method_exists($element, 'getHelpButton')){
//            $html = str_replace('{help}', $element->getHelpButton(), $html);
//        }else{
//            $html = str_replace('{help}', '', $html);
//
//        }
//        if (!isset($this->_templates[$element->getName()])) {
//            $this->_templates[$element->getName()] = $html;
//        }else{
//            ;
//        }
//
//        $this->HTML_QuickForm_Renderer_Tableless_renderElement($element, $required, $error);
//    }
//
//    function HTML_QuickForm_Renderer_Tableless_renderElement(&$element, $required, $error)
//    {
//        // if the element name indicates the end of a fieldset, close the fieldset
//        if (   in_array($element->getName(), $this->_stopFieldsetElements)
//            && $this->_fieldsetsOpen > 0
//           ) {
//            $this->_html .= $this->_closeFieldsetTemplate;
//            $this->_fieldsetsOpen--;
//        }
//        // if no fieldset was opened, we need to open a hidden one here to get
//        // XHTML validity
//        if ($this->_fieldsetsOpen === 0) {
//            $this->_html .= $this->_openHiddenFieldsetTemplate;
//            $this->_fieldsetsOpen++;
//        }
//        if (!$this->_inGroup) {
//            $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error);
//            // the following lines (until the "elseif") were changed / added
//            // compared to the default renderer
//            $element_html = $element->toHtml();
//            if (!is_null($element->getAttribute('id'))) {
//                $id = $element->getAttribute('id');
//            } else {
//                $id = $element->getName();
//            }
//            if (!empty($id) and !$element->isFrozen() and !is_a($element, 'MoodleQuickForm_group') and !is_a($element, 'HTML_QuickForm_static')) { // moodle hack
//                $html = str_replace('<label', '<label for="' . $id . '"', $html);
//                $element_html = preg_replace('#name="' . $id . '#',
//                                             'id="' . $id . '" name="' . $id . '',
//                                             $element_html,
//                                             1);
//            }
//            $this->_html .= str_replace('{element}', $element_html, $html);
//        } elseif (!empty($this->_groupElementTemplate)) {
////PATCH START
//            //$html = str_replace('{label}', $element->getLabel(), $this->_groupElementTemplate);
//
//            $template = $this->_groupElementTemplate;
//            // allow it to be overridden with an element template.
//            if (isset($this->_templates[$element->getName()])) {
//                $template = $this->_templates[$element->getName()];
//                $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error);
//            } else {
//                $html = str_replace('{label}', $element->getLabel(), $template);
//            }
////PATCH END
//            if ($required) {
//                $html = str_replace('<!-- BEGIN required -->', '', $html);
//                $html = str_replace('<!-- END required -->', '', $html);
//            } else {
//                $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/i", '', $html);
//            }
//            $this->_groupElements[] = str_replace('{element}', $element->toHtml(), $html);
//
//        } else {
//            $this->_groupElements[] = $element->toHtml();
//        }
//    } // end func renderElement
}