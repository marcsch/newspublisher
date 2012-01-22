<?php


class npField extends npAbstractField {

    public function getValue() {
        if ($this->_value != null) return $this->_value;
        $this->_value = $this->resource->get($this->name);

        if ($this->resource->isNew()) {
            switch ($this->name) {
                case 'template':  $this->_value = (integer) $this->_getSystemDefault('default_template', 1); break;
                case 'richtext':  $this->_value = (integer) $this->_getSystemDefault('richtext_default', 1); break;
                case 'published': $this->_value = (integer) $this->_getSystemDefault('publish_default', 0); break;
                case 'cacheable': $this->_value = (integer) $this->_getSystemDefault('cache_default', 1); break;
                case 'searchable':$this->_value = (integer) $this->_getSystemDefault('search_default', 1); break;
            }
        }
        
        return $this->_value;
    }

    protected function _getSystemDefault($setting, $fallback) {
        $workingContext = $this->modx->getContext($this->resource->get('context_key'));
        return $workingContext->getOption($setting, $fallback);
    }

    public function getCaption() {
        return isset($this->_caption)? $this->_caption : '[[%resource_' . $this->name . ']]';
    }
    
    public function getHelp() {
        return isset($this->_help)? $this->_help : '[[%resource_' . $this->name . '_help:notags]]';
    }

    public function getType() {
        if (isset($this->_type)) return $this->_type;
        
        $nameMap = array(
            'introtext' => 'textarea',
            'content'   => 'textarea',
            'template'  => 'listbox',
            'class_key'  => 'listbox',
            'content_dispo' => 'listbox',
            'uri_override' => 'boolean',
            'hidemenu'  => 'boolean'
            );
        if (isset($nameMap[$this->name]))
            return $this->_type = $nameMap[$this->name];
            
        $this->_type = $this->resource->_fieldMeta[$this->name]['phptype'];
        $typeMap = array(
            'string'    => 'text',
            ''          => 'text',
            'timestamp' => 'date',
            'integer'   => 'number',
            );
        
        return $this->_type = isset($typeMap[$this->_type]) ? $typeMap[$this->_type] : $this->_type;
    }


    public function getElements() {
        if (isset($this->_elements)) return $this->_elements;
        
        $this->_elements = array();
        switch ($this->name) {
            case 'template':
                $templates = $this->modx->getCollection('modTemplate');
                foreach ($templates as $template) {
                    if ($template->checkPolicy('list')) {
                        $this->_elements[$template->get('id')] = $template->get('templatename');
                    }
                }
                break;

            case 'class_key':
                $classes = array('modDocument' => 'document', 'modSymLink' => 'symlink', 'modWebLink' => 'weblink', 'modStaticResource' => 'static_resource');
                foreach ($classes as $k => $v) $this->_elements[$k] = $this->modx->lexicon($v);
                break;
                
            case 'content_dispo':
                $dispo = array('inline', 'attachment');
                foreach ($dispo as $k => $v)
                    $this->_elements[$k] = $this->modx->lexicon($v);
                break;
        }
        return $this->_elements;
    }
    
    public function getSaveName() {
        return $this->name;
    }

    public function getSource() {
        return null;
    }

    public function validate($value=null) {
        return true;
    }
    
}

?>
