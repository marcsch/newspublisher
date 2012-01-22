<?php


class npListboxmultipleRender extends npAbstractListRender {

    public function process() {

        $elements = $this->field->getElements();

        $count = count($elements);
        $this->setPlaceholder('size', ($count <= $this->newspublisher->multipleListboxMax)? $count : $this->newspublisher->multipleListboxMax);
        $this->setPlaceholder('multiple', ' multiple="multiple" ');
        
        /* HTML listbox values cannot be deleted. Therefore adding an empty option if empty values are allowed.
         * Doing the same if there are no listoptions and allowBlank is false. Otherwise there would be an unresolvable field error
         * TODO: unselecting possible, although not easy (all browsers?) */
        if ($this->properties['allowBlank'] == 'true' || !$elements)
            $this->properties['showNone'] = 'true';

        $this->_processListInner($elements, 'listbox', '[]', 'ListOptionTpl', 'selected="selected"');
    }


    public function getTemplate() {
        return 'ListOuterTpl';
    }
}


?>
