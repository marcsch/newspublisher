<?php


class npTV extends npAbstractField {
    protected $tvObj;
    
    public function __construct($name, $resource, $modx, $value=null) {
        parent::__construct($name, $resource, $modx, $value);

        $this->tvObj = $modx->getObject('modTemplateVar', array('name' => $name));
        if (!isset($this->tvObj)) throw new Exception($this->modx->lexicon('np_no_tv') . $name);
        $template = $resource->get('template');
        $found = $modx->getCount('modTemplateVarTemplate', array('templateid' => $template, 'tmplvarid' => $this->tvObj->get('id')));
        if (! $found) throw new Exception($this->modx->lexicon('np_not_our_tv') . ' Template: ' . $template . '  ----    TV: ' . $name);

        // Used in setValue/getValue. Converted into an array as soon as getElements is called
        $this->_elements = $this->tvObj->get('elements');
    }

    public function setValue($value) {
        $this->_value = $value;
        if (!empty($this->_elements) && !is_array($this->_value))
            $this->_value = explode('||', $this->_value);
    }

    public function getValue() {
        if ($this->_value != null) return $this->_value;

        $this->_value = $this->tvObj->getValue($this->resource->get('id'));

        /* empty value gets default_text for both new and existing docs */
        if (empty($this->_value)) {
            $this->_value = $this->tvObj->get('default_text');
        }

        // list types
        if (!empty($this->_elements)) $this->_value = explode('||', $this->_value);
        return $this->_value;
    }


    public function getType() {
        if (isset($this->_type)) return $this->_type;
        return $this->_type = $this->tvObj->get('type');
    }


    public function getCaption() {
        if (isset($this->_caption)) return $this->_caption;
        $caption =  $this->tvObj->get('caption');
        if (empty($caption)) $caption = $this->name;
        return $caption;
    }


    public function getHelp() {
        return isset($this->_help)? $this->_help : $this->tvObj->get('description');
    }

    public function getElements() {
        if (is_array($this->_elements)) return $this->_elements;

        $elements = array();
        if (!empty($this->_elements)) {
            /* handle @ binding TVs */
            if (preg_match('/^\s*@/', $this->_elements)) {
                $this->_elements = $this->tvObj->processBindings($this->_elements, $this->resource->get('id'));
            }
            $this->_elements = explode('||',$this->_elements);

            /* parse options */
            foreach ($this->_elements as $option) {
                $text = strtok($option,'=');
                $option = strtok('=');
                $option = $option? $option : $text;
                $elements[$option] = $text;
            }
        }
        return $this->_elements = $elements;
    }

    public function getProperties() {
        if (isset($this->_properties)) return $this->_properties;
        return $this->_properties = $this->tvObj->get('input_properties');
    }

    public function getSource() {
         /* In MODx versions <= 2.20, null is returned */
        return method_exists($this->tvObj, 'getSource')? $this->tvObj->getSource($this->resource->get('context_key')) : null;
    }


    public function getSaveName() {
        return 'tv' . $this->tvObj->get('id');
    }


    public function validate() {
         // TODO: calling implode twice is inefficient
        $val = $this->getValue();
        if (is_array($val)) $val = implode('', $val);
        $result = parent::validate($val);
        if (stristr($val, '@EVAL')) {
            $this->errors[] = $this->modx->lexicon("The TV '{$this->getCaption()}' has an @EVAL binding. Using @EVAL in the frontend is not allowed");
            $result = false;
        }
        return $result;
    }
}

?>
