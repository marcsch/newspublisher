<?php


class npOptionRender extends npAbstractListRender {

    public function process() {
        $this->_processListInner($this->field->getElements(), 'radio', '', 'OptionTpl', 'checked="checked"');
    }

    public function getTemplate() {
        return 'OptionOuterTpl';
    }
}


?>
