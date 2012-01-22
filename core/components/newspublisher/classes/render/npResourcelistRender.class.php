<?php


class npResourcelistRender extends npListboxRender {

    public function process() {

        /* code adapted from core/model/modx/processors/element/tv/renders/mgr/input/resourcelist.php */

        $elements = $this->field->getElements();

        if (!empty($this->config['parents']) || $this->config['parents'] === '0') {
            $parents = explode(',',$this->properties['parents']);
        } elseif (!empty($elements)) {
            $parents = $elements;
        } else {
            $parents = array($this->modx->resource->get('id'));
        }
        
        $this->config['depth'] = !empty($this->config['depth']) ? $this->config['depth'] : 10;
        if (empty($parents) || (empty($parents[0]) && $parents[0] !== '0')) { $parents = array($this->modx->getOption('site_start',null,1)); }

        $parentList = array();
        foreach ($parents as $parent) {
            $parent = $this->modx->getObject('modResource',$parent);
            if ($parent) $parentList[] = $parent;
        }

        /* get all children */
        $ids = array();
        foreach ($parentList as $parent) {
            if ($this->config['includeParent'] != 'false') $ids[] = $parent->get('id');
            $children = $this->modx->getChildIds($parent->get('id'),$this->config['depth'],array(
                'context' => $parent->get('context_key'),
            ));
            $ids = array_merge($ids,$children);
        }
        $ids = array_unique($ids);

        if (empty($ids)) {
            $resources = array();

        } else {

            /* get resources */
            $c = $this->modx->newQuery('modResource');
            $c->leftJoin('modResource','Parent');
            if (!empty($ids)) {
                $c->where(array('modResource.id:IN' => $ids));
            }
            if (!empty($this->config['where'])) {
                $this->config['where'] = $this->modx->fromJSON($this->config['where']);
                $c->where($this->config['where']);
            }
            $c->sortby('Parent.menuindex,modResource.menuindex','ASC');
            if (!empty($this->config['limit'])) {
                $c->limit($this->config['limit']);
            }
            $resources = $this->modx->getCollection('modResource',$c);
        }

        /* iterate */
        $elements = array();
        foreach ($resources as $resource) {
            $id = $resource->get('id');
            $elements[$id] = $resource->get('pagetitle'); //.' ('.$resource->get('id').')',
        }

        /* If the list is empty do not require selecting something */
        if (!$this->config) $this->config['allowBlank'] = 'true';

        $this->field->setElements($elements);
        parent::process();
    }
}


?>
