<?php

class npTextRender extends npFieldRender {

    public function process() {
        $this->setPlaceholder('maxlength', $this->newspublisher->textMaxlength);
        $this->modx->toPlaceholder($this->field->name, $this->field->getValue(), $this->newspublisher->prefix);
    }

    public function getTemplate() {
        return 'TextTpl';
    }
}

?>
