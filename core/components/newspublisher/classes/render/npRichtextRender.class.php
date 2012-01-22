<?php

class npRichtextRender extends npTextareaRender {

    public function init() {
        $editor = isset($this->newspublisher->props['whichEditor'])?
            $this->newspublisher->props['whichEditor'] : 
            $this->modx->getOption('whichEditor',null,'TinyMCE');
        
        if ($editor == 'TinyMCE') {
            // TinyMCE needs modExt JS
            $this->modx->regClientStartupScript($this->modx->getOption('manager_url').'assets/ext3/adapter/ext/ext-base.js');
            $this->modx->regClientStartupScript($this->modx->getOption('manager_url').'assets/ext3/ext-all.js');
            $this->modx->regClientStartupScript($this->modx->getOption('manager_url').'assets/modext/core/modx.js');

           $this->modx->regClientStartupHTMLBlock('
                <script type="text/javascript">
                   Ext.onReady(function() { MODx.loadRTE(); });
                </script>');
            }

        $out = $this->modx->invokeEvent('OnRichTextEditorInit', array(
            'editor'   => $editor,
            'forfrontend' => true,
            'resource'  => $this->newspublisher->resource,
            //'elements' => ?? TODO: not possible to know id of elements, in init(), but not used by TinyMCE anyway
            'language' => $this->newspublisher->language,
            'width'  => empty ($this->newspublisher->props['tinywidth'] )? '95%' : '95%',
            'height' => empty ($this->newspublisher->props['tinyheight'])? '400px' : '400px'
            ));
    }

    public function process() {
        parent::process();
        $this->setPlaceholder('class', 'modx-richtext');

    }
}


?>
