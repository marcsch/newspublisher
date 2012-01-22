<?php

class npTextareaRender extends npFieldRender {

    public function process() {
        $this->properties = array_merge(array('rows' => 20, 'columns' => 60), $this->properties);
        $this->setPlaceholder('rows', $this->properties['rows']);
        $this->setPlaceholder('cols', $this->properties['columns']);

        $this->modx->toPlaceholder($this->field->name, $this->field->getValue(), $this->newspublisher->prefix);
    }

    public function getTemplate() {
        return 'TextareaTpl';
    }
}

?>
