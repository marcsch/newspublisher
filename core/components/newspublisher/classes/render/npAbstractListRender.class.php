<?php


abstract class npAbstractListRender extends npFieldRender {

    protected function _processListInner($elements, $type, $postfix, $elementTpl, $selectedCode) {
        
        if ($this->properties['showNone'] && $this->properties['showNone'] !== 'false')
            $elements = array('' => '-') + $elements;

        $selected = $this->field->getValue();
        if (!is_array($selected)) // make sure we have an array
            $selected = array($selected);
        $elementTpl = $this->newspublisher->getTpl($elementTpl);
        
        /* loop through options and set selections */
        $inner = '';
        $idx = 1;
        $this->setPlaceholder('name', $this->field->name . $postfix);
        // setting all 'global' placeholders here as well since the 'options' placeholder may not be parsed first
        $replace = $this->placeholders;
        $replace['[[+npx.class]]'] = $replace['[[+npx.type]]'] = $type;
        foreach ($elements as $value => $text) {
            $replace['[[+npx.idx]]'] = $idx;
            $replace['[[+npx.value]]'] = $value;
            $replace['[[+npx.selected]]'] = in_array($value, $selected) ? $selectedCode : '';
            $replace['[[+npx.text]]'] = $text;
            $inner .= str_replace(array_keys($replace), array_values($replace), $elementTpl);
            $idx++;
        }
        
        $this->setPlaceholder('options', $inner);
    }
}

?>
