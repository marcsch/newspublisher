<?php


class npNumberRender extends npFieldRender {

    public function process() {
        $this->setPlaceholder('maxlength', $this->newspublisher->intMaxlength);
        $this->modx->toPlaceholder($this->field->name, $this->field->getValue(), $this->newspublisher->prefix);
    }

    public function getTemplate() {
        return 'IntTpl';
    }
}


?>
