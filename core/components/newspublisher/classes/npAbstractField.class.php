<?php


abstract class NpAbstractField {
    public $modx;
    public $resource;
    public $name;
    public $errors = array();
    protected $_type;
    protected $_value;
    protected $_properties = array();
    protected $_elements;
    protected $_caption;
    protected $_help;

    public function __construct($name, &$resource, &$modx, $value=null) {
        $this->name = $name;
        $this->resource =& $resource;
        $this->modx =& $modx;
        if ($value!=null) $this->_value = $value;
    }

    public function setValue($value) {
        $this->_value = $value;
    }
    
    abstract public function getValue();

    public function setCaption($caption) {
        $this->_caption = $caption;
    }
    
    abstract public function getCaption();

    public function setHelp($message) {
        $this->_help = $message;
    }
    
    abstract public function getHelp();

    public function setType($type) {
        $this->_type = $type;
    }
    
    abstract public function getType();

    public function setProperties($properties) {
        $this->_properties = array_merge($this->_properties, $properties);
    }
    
    public function getProperties() {
        return $this->_properties;
    }

    public function setElements($elements) {
        $this->_elements = $elements;
    }
    
    public abstract function getElements();
    
    public function validate() {
        $value = $this->getValue();
        if (is_array($value)) $value = implode('', $value);
        if (false && strstr($value, '[[') && ! $this->modx->hasPermission('allow_modx_tags')) {
            $this->errors = array($this->modx->lexicon('np_no_modx_tags'));
            return false;
        }
        return true;
    }
    
    abstract public function getSaveName();
}


?>
