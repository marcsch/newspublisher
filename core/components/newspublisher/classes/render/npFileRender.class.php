<?php


class npFileRender extends npFieldRender {

    /* Prepare input properties.
     * code adapted from core/model/modx/processors/element/tv/renders/mgr/input/file.php
     * and (...)/image.php */
     
    protected function _prepareProperties() {
         
        $this->modx->getService('fileHandler','modFileHandler', '', array('context' => $this->newspublisher->context));
        $this->properties['wctx'] = $this->newspublisher->context; // not sure if this is important, doesn't seem to have an effect

        // not using modMediaSource::getOpenTo since we don't have the object, but using the same code
        $openTo = empty($path)? '' : dirname($path).'/';

        // TODO: version_compare is complicated, but npTV::getSource could theoretically return null even in MODx >= 2.20
        // better way?
        
        if (version_compare($this->modx->version['full_version'],'2.2.0-rc1','<')) { 
            $source = $this->field->getSource();
            if (!$source) {
                $this->newspublisher->setError($this->modx->lexicon('np_no_media_source') . $this->field->name);
                
            } elseif (!$source->getWorkingContext()) {
                $this->newspublisher->setError($this->modx->lexicon('np_source_wctx_error') . $this->field->name);

            } else {
                $source->initialize();
                if (!$source->checkPolicy('view'))
                    $this->newspublisher->setError($this->modx->lexicon('np_media_source_access_denied') . $this->field->name);
            }
            
            $this->properties['source'] = (integer) $source->get('id');
            
        } else {/* MODx versions < 2.20 */

            $workingContext = $this->modx->getContext($this->newspublisher->resource->get('context_key'));
            $this->modx->fileHandler->context =& $workingContext;

            /* get base path based on either TV param or filemanager_path */
            $replacePaths = array(
                '[[++base_path]]' => $workingContext->getOption('base_path',null,MODX_BASE_PATH),
                '[[++core_path]]' => $workingContext->getOption('core_path',null,MODX_CORE_PATH),
                '[[++manager_path]]' => $workingContext->getOption('manager_path',null,MODX_MANAGER_PATH),
                '[[++assets_path]]' => $workingContext->getOption('assets_path',null,MODX_ASSETS_PATH),
                '[[++base_url]]' => $workingContext->getOption('base_url',null,MODX_BASE_URL),
                '[[++manager_url]]' => $workingContext->getOption('manager_url',null,MODX_MANAGER_URL),
                '[[++assets_url]]' => $workingContext->getOption('assets_url',null,MODX_ASSETS_URL),
            );
            $replaceKeys = array_keys($replacePaths);
            $replaceValues = array_values($replacePaths);

            if (empty($this->properties['basePath'])) {
                $this->properties['basePath'] = $this->modx->fileHandler->getBasePath();
                $this->properties['basePath'] = str_replace($replaceKeys,$replaceValues,$this->properties['basePath']);
                $this->properties['basePathRelative'] = $workingContext->getOption('filemanager_path_relative',true) ? 1 : 0;
            } else {
                $this->properties['basePath'] = str_replace($replaceKeys,$replaceValues,$this->properties['basePath']);
                $this->properties['basePathRelative'] = !isset($this->properties['basePathRelative']) || in_array($this->properties['basePathRelative'],array('true',1,'1'));
            }
            if (empty($this->properties['baseUrl'])) {
                $this->properties['baseUrl'] = $this->modx->fileHandler->getBaseUrl();
                $this->properties['baseUrl'] = str_replace($replaceKeys,$replaceValues,$this->properties['baseUrl']);
                $this->properties['baseUrlRelative'] = $workingContext->getOption('filemanager_url_relative',true) ? 1 : 0;
            } else {
                $this->properties['baseUrl'] = str_replace($replaceKeys,$replaceValues,$this->properties['baseUrl']);
                $this->properties['baseUrlRelative'] = !isset($this->properties['baseUrlRelative']) || in_array($this->properties['baseUrlRelative'],array('true',1,'1'));
            }
            $modxBasePath = $this->modx->getOption('base_path',null,MODX_BASE_PATH);
            if ($this->properties['basePathRelative'] && $modxBasePath != '/') {
                $this->properties['basePath'] = ltrim(str_replace($modxBasePath,'',$this->properties['basePath']),'/');
            }
            $modxBaseUrl = $this->modx->getOption('base_url',null,MODX_BASE_URL);
            if ($this->properties['baseUrlRelative'] && $modxBaseUrl != '/') {
                $this->properties['baseUrl'] = ltrim(str_replace($modxBaseUrl,'',$this->properties['baseUrl']),'/');
            }

            // TODO used?
            if (!empty($this->properties['baseUrl']) && !empty($value)) {
                $relativeValue = $this->properties['baseUrl'].ltrim($value,'/');
            } else {
                $relativeValue = $value;
            }
            
            $this->properties['openTo'] = $openTo;
        }

    }


    public function process() {
        
        $this->modx->toPlaceholder($this->field->name, $this->field->getValue(), $this->newspublisher->prefix);

        $this->_prepareProperties();

        $browserUrl = $this->newspublisher->registerFileBrowser($this->field->name, $this->properties);
        if (!$browserUrl) return;        

        $phpthumbUrl = $this->modx->getOption('connectors_url',null,MODX_CONNECTORS_URL) . 'system/phpthumb.php?';
        foreach ($this->properties as $key => $value) {
            $phpthumbUrl .= "&{$key}={$value}";
        }

        $this->setPlaceholder('phpthumbBaseUrl', $phpthumbUrl);
        $this->setPlaceholder('launchBrowser', "var popup=window.open('{$browserUrl}', 'select file', 'width=' + Math.min(screen.availWidth,1000) + ',height=' + Math.min(screen.availHeight*0.9,700) + 'resizable=no,status=no,location=no,toolbar=no');popup.focus();browserPathInput=getElementById('np-{$this->field->name}');return false;");
    }

    public function getTemplate() {
        return 'FileTpl';
    }
}


?>
