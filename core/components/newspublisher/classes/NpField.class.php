<?php


class NpField extends NpAbstractField {
    protected $type;

    public function getValue() {
        return $this->value == null ? $this->resource->get($this->name) : $this->value;
    }

    public function getCaption() {
        return '[[%resource_' . $this->name . ']]';
    }
    
    public function getHelp() {
        return '[[%resource_' . $this->name . '_help:notags]]';
    }

    public function getType() {
        if (isset($this->type)) return $this->type;
        
        $nameMap = array(
            'introtext' => 'textarea',
            'content'   => 'textarea',
            'template'  => 'listbox',
            'class_key'  => 'listbox',
            'content_dispo' => 'listbox',
            'uri_override' => 'boolean',
            'hidemenu'  => 'boolean'
            );
            
        $typeMap = array(
            'string' => 'text',
            '' => 'text',
            'timestamp' => 'date',
            'integer' => 'number'
            );        
        if (isset($nameMap[$this->name]))
            return $this->type = $nameMap[$this->name];
        
        $this->type = $this->resource->_fieldMeta[$this->name]['phptype'];
        return $this->type = isset($typeMap[$this->type]) ? $typeMap[$this->type] : $this->type;
    }


    public function getProperties() {

        if (isset($this->properties)) return $this->properties;
        
        $this->properties['elements'] = array();
        switch ($this->name) {
            case 'template':
                $templates = $this->modx->getCollection('modTemplate');
                foreach ($templates as $template) {
                    if ($template->checkPolicy('list')) {
                        $this->properties['elements'][$template->get('id')] = $template->get('templatename');
                    }
                }
                break;

            case 'class_key':
                $classes = array('modDocument' => 'document', 'modSymLink' => 'symlink', 'modWebLink' => 'weblink', 'modStaticResource' => 'static_resource');
                foreach ($classes as $k => $v) $this->properties['elements'][$k] = $this->modx->lexicon($v);
                break;
                
            case 'content_dispo':
                $dispo = array('inline', 'attachment');
                foreach ($dispo as $k => $v)
                    $this->properties['elements'][$k] = $this->modx->lexicon($v);
                break;
        }

        return $this->properties;
    }
    
    
    public function getSaveName() {
        return $this->name;
    }


    public function validate($value=null) {
        return true;
    }
    
}

?>
