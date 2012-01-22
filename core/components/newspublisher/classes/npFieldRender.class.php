<?php


abstract class NpFieldRender {
    public $field;
    public $newspublisher;
    public $modx;
    public $placeholders = array();
    protected $properties;

    function __construct($newspublisher, $field) {
        $this->newspublisher =& $newspublisher;
        $this->modx =& $newspublisher->modx;
        $this->field = $field;
        $this->placeholders = array(
            '[[+npx.help]]' => $newspublisher->props['hoverhelp']? $field->getHelp() : '',
            '[[+npx.caption]]' => $field->getCaption(),
            '[[+npx.fieldName]]' => $field->name,
            '[[+npx.class]]' => $field->getType(),
            );
        $this->properties = $field->getProperties();
    }

    public function init() {}

    public function setPlaceholder($k, $v, $prefix='npx') {
        if ($prefix) $prefix .= '.';
        $this->placeholders['[[+'.$prefix.$k.']]'] = $v;
    }

    public function render() {
        $this->process();
        $template = $this->newspublisher->getTpl($this->getTemplate());
        return str_replace(array_keys($this->placeholders), array_values($this->placeholders), $template);
    }

    abstract public function process();
    
    abstract public function getTemplate();

    public static function getPostbackValue($fieldName) {
        $value = $_POST[$fieldName];
        if (is_array($value) && count($value)==1)
            $value = reset($value);
        unset($_POST[$fieldName]);
        return $value;
    }
}


?>
