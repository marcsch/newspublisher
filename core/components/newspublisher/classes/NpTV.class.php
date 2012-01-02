<?php


class NpTV extends NpAbstractField {
    protected $tvObj;
    protected $elements;
    
    public function __construct($name, $resource, $modx, $value=null) {
        parent::__construct($name, $resource, $modx, $value);
        
        $this->tvObj = $modx->getObject('modTemplateVar', array('name' => $name));
        if (!isset($this->tvObj)) throw new Exception($this->modx->lexicon('np_no_tv') . $name);
        $template = $resource->get('template');
        $found = $modx->getCount('modTemplateVarTemplate', array('templateid' => $template, 'tmplvarid' => $this->tvObj->get('id')));
        if (! $found) throw new Exception($this->modx->lexicon('np_not_our_tv') . ' Template: ' . $template . '  ----    TV: ' . $name);

        $this->elements = $this->tvObj->get('elements');
    }

    public function getValue() {

        if ($this->value != null) return $this->value;

        $this->value = $this->tvObj->getValue($this->resource->get('id'));

        /* empty value gets default_text for both new and existing docs */
        if (empty($this->value)) {
            $this->value = $this->tvObj->get('default_text');
        }

        // list types
        if (!empty($this->elements)) $this->value = explode('||', $this->value);

        return $this->value;
    }

    public function getType() {
        if (isset($this->type)) return $this->type;
        
        return $this->type = $this->tvObj->get('type');
    }

    public function getCaption() {
        $caption =  $this->tvObj->get('caption');
        if (empty($caption)) $caption = $this->name;
        return $caption;
    }


    public function getHelp() {
        return $this->tvObj->get('description');
    }


    public function getProperties() {
        
        if (isset($this->properties)) return $this->properties;
        
        $this->properties = $this->tvObj->get('input_properties');

        if (!empty($this->elements)) {
            /* handle @ binding TVs */
            $elements = $this->tvObj->get('elements');
            if (preg_match('/^\s*@/',$elements)) {
                $elements = $this->tvObj->processBindings($elements, $this->resource->get('id'));
            }
            $elements = explode('||',$elements);

            /* parse options */
            $this->properties['elements'] = array();
            foreach ($elements as $option) {
                $text = strtok($option,'=');
                $option = strtok('=');
                $option = $option? $option : $text;
                $this->properties['elements'][$option] = $text;
            }
        }
        
         /* in MODx versions >= 2.20 get the media source associated to this tv and context
          * currently only used for file/image TVs, but can be set for all TVs
          * TODO: always retrieve? */
          
        if (method_exists($this->tvObj, 'getSource')) {
            
            $source = $this->tvObj->getSource($this->resource->get('context_key'));
            if (!$source) {
                $this->properties['source'] = $this->modx->lexicon('np_no_media_source') . $this->name;
                
            } else if (!$source->getWorkingContext()) {
                $this->properties['source'] = $this->modx->lexicon('np_source_wctx_error') . $this->name;

            } else {
                $source->initialize();
                if (!$source->checkPolicy('view')) {
                    $this->properties['source'] = $this->modx->lexicon('np_media_source_access_denied') . $this->name;
                } else {
                    $this->properties['source'] = (integer) $source->get('id');
                }
            }
        }


        return $this->properties;
    }

    public function getSaveName() {
        return 'tv' . $this->tvObj->get('id');
    }


    public function validate($value=null) {
        $value = $value == null ? $this->getValue() : $value;
            /* Check for @EVAL */
        if (is_array($value)) $value = implode('', $value);
        if (stristr($value,'@EVAL')) {
            $this->errors = array($this->modx->lexicon("The TV '{$this->getCaption()}' has an @EVAL binding. Using @EVAL in the frontend is not allowed"));
            return false;
        }
        return true;
    }
}

?>
