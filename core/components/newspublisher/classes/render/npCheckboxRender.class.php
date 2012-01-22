<?php


class npCheckboxRender extends npAbstractListRender {

    public function process() {
        $this->_processListInner($this->field->getElements(), 'checkbox', '[]', 'OptionTpl', 'checked="checked"');
    }

    public function getTemplate() {
        return 'OptionOuterTpl';
    }
}


?>
