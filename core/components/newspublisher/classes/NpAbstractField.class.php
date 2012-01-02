<?php


abstract class NpAbstractField {

    protected $modx;
    protected $value;
    protected $properties;
    public $resource;
    public $name;
    public $errors;


    public function __construct($name, $resource, $modx, $value=null) {
        $this->name = $name;
        $this->resource = $resource;
        $this->modx = $modx;
        if ($value!=null) $this->value = $value;
    }

    public function setValue($value) {
        $this->value = $value;
    }
    
    abstract public function getValue();
    abstract public function getCaption();
    abstract public function getHelp();    
    abstract public function getType();
    abstract public function getProperties();
    abstract public function validate($value=null);    
}


?>
