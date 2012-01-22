<?php


class npBooleanRender extends npFieldRender {

    public function process() {
        $this->setPlaceholder('checked', $this->field->getValue()? 'checked="checked"' : '');
    }

    public function getTemplate() {
        return 'BoolTpl';
    }
}

?>
