<?php

/**
 * NewsPublisher
 *
 * Copyright 2011 Bob Ray
 *
 * @author Bob Ray <http://bobsguides.com>
 * @author Raymond Irving
 * 7/10/11
 *
 * NewsPublisher is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * NewsPublisher is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * NewsPublisher; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package newspublisher
 */
/**
 * MODx NewsPublisher Class
 *
 * @version Version 1.3.0-rc1
 *
 * @package  newspublisher
 *
 * The NewsPublisher snippet presents a form in the front end for
 * creating resources. Rich text editing is available for text fields
 * and rich text template variables.
 *
 * Refactored for OOP and Revolution by Bob Ray, January, 2011
 * The Newspublisher class contains all functions relating to NewsPublisher's
 * operation.
 */

class Newspublisher {

   /**
    * @var modx object Reference pointer to modx object
    */
    public $modx;
    /**
     * @var string current context key
     */
    public $context;
    /**
     * @var modResource The current resource
     */
    public $resource;
    /**
     * @var string Path to NewsPublisher Core
     */
    public $corePath;
    /**
     * @var string Path to NewsPublisher assets directory
     */
    public $assetsPath;
    /**
     * @var string URL to NewsPublisher Assets directory
     */
    public $assetsUrl;
    /**
     * @var int Max size of listbox TVs
     */
    public $listboxMax;
    /**
     * @var int Max size of multi-select listbox TVs
     */
    public $multipleListboxMax;
    /**
     * @var string prefix for placeholders
     */
    public $prefix;
    /**
     * @var int Max length for integer input fields
     */
    public $intMaxlength;
    /**
     * @var int Max length for text input fields
     */
    public $textMaxlength;
    /**
     * @var string NP language
     */
    public $language;
    /**
     * @var array scriptProperties array
     */
    public $props; // TODO: public or protected? (tinywidth, tinyheight, hoverhelp currently used by renders)
    /**
     * @var array Array of error messages
     */
    protected $errors;
    /**
     * @var int ID of the resource's parent
     */
    protected $parentId;
    /**
     * @var modResource The parent object
     */
    protected $parentObj;
    /**
     * @var boolean Indicates that we're editing an existing resource (ID of resource) 
     */
    protected $existing;
    /**
     * @var boolean Indicates a repost to self
     */
    protected $isPostBack;
    /**
     * @var boolean Use alias as title
     */
    protected $aliasTitle;
    /**
     * @var boolean Clear the cache after saving doc
     */
    protected $clearcache;
    /**
     * @var string Content of optional header chunk
     */
    protected $header;
    /**
     * @var string Content of optional footer chunk
     */
    protected $footer;
    /**
     * @var string Comma-separated list of words to remove
     */
    protected $badwords;
    /**
     * @var string Value for alias resource field
     */
    protected $alias;
    /**
     * @var array Array of Tpl chunk contents
     */
    protected $tpls;
    /**
     * @var string Comma-separated list or resource groups to
     * assign new docs to
     */
    protected $groups;
    /**
     * @var array Field names that are shown in the form
     */
    protected $show;
    /**
     * @var array Field names that are required
     */
    protected $required;
    /**
     * @var array Names of all modResource fields
     */
    protected $fieldNames;
    /**
     * @var array Holds all fields that are shown in the form and any other field with a default value. Keys are field names.
     */
    protected $fields;
    /**
     * @var array These fields cannot be modified or set as default. They are automatically set by NP
     */
    protected $protected = array('id', 'parent', 'editedby', 'editedon', 'context_key');
    /**
     * @var array Associative array of field types and the respective render class, used for knowing
     *  whether the field was already initialized during form rendering
     */
    protected $renderTypes = array();
    /**
     * @var array Array of field types to override with another type
     */
    protected $override = array();


    /** NewsPublisher constructor
     *
     * @access public
     * @param (reference object) $modx - modx object
     * @param (reference array) $props - scriptProperties array.
     */

    public function __construct(&$modx, &$props) {
        $this->modx =& $modx;
        $this->props =& $props;
        /* NP paths; Set the np. System Settings only for development */
        $this->corePath = $this->modx->getOption('np.core_path', null, MODX_CORE_PATH . 'components/newspublisher/');
        $this->assetsPath = $this->modx->getOption('np.assets_path', null, MODX_ASSETS_PATH . 'components/newspublisher/');
        $this->assetsUrl = $this->modx->getOption('np.assets_url', null, MODX_ASSETS_URL . 'components/newspublisher/');
        
        require_once $this->corePath . 'classes/npAbstractField.class.php';
        require_once $this->corePath . 'classes/npField.class.php';
        require_once $this->corePath . 'classes/npTV.class.php';
        require_once $this->corePath . 'classes/npFieldRender.class.php';
        spl_autoload_register(array($this, '_loadRender')); 
    }

    /** Sets Postback status
     *
     * @access public
     * @param $setting (bool) desired setting */
    public function setPostBack($setting) {
        $this->isPostBack = $setting;
    }

    /** gets Postback status. Used by snippet to determine
     * postback status.
     *
     * @access public
     * @return (bool) true if set, false if not
     */

    public function getPostBack() {
        return $this->isPostBack;
    }

    /** Initialize variables and placeholders.
     *  Uses $_POST on postback.
     *  Checks for an existing resource to edit in $_POST.
     *  Sets errors on failure.
     *
     *  @access public
     *  @param (string) $context - current context key
     */

        public function init($context) {
            $this->context = $context;
            $this->fieldNames = array_keys($this->modx->getFields('modResource'));
            
            $this->language = !empty($this->props['language'])
                    ? $this->props['language']
                    : $this->modx->getOption('cultureKey',null,$this->modx->getOption('manager_language',null,'en'));

            // TODO: mgr ever occurring?
            switch ($context) {
                case 'mgr':
                    break;
                case 'web':
                default:
                    $this->modx->lexicon->load($this->language . ':newspublisher:default');
                    break;
            }
            $this->modx->lexicon->load('core:resource');
            
            /* inject NP CSS file
            * Empty but sent parameter means use no CSS file at all */

            if ($this->props['cssfile'] === '0') { /* 0 sent, -- no css file */
                $css = false;
            } elseif (empty($this->props['cssfile'])) { /* nothing sent - use default */
                $css = $this->assetsUrl . 'css/newspublisher.css';
            } else {  /* set but not empty -- use it */
                $css = $this->assetsUrl . 'css/' . $this->props['cssfile'];
            }

            if ($css !== false) {
                $this->modx->regClientCSS($css);
            }

            $this->prefix =  empty($this->props['prefix']) ? 'np' : $this->props['prefix'];

            /* see if we're editing an existing doc */
            $this->existing = false;
            if (isset($_POST['np_existing']) && $_POST['np_existing'] == 'true') {
                $this->existing = is_numeric($_POST['np_doc_id'])
                        ? $_POST['np_doc_id'] : false;
            }

            /* see if it's a repost */
            $this->setPostback(isset($_POST['hidSubmit']) && $_POST['hidSubmit'] == 'true');

            $fieldTypes = $this->_parseOptionString($this->props['show']);
            $this->show = array_keys($fieldTypes);
            $this->required = array_filter(array_map('trim', explode(',', $this->props['required'])));

            if ($this->existing) {

                $this->resource = $this->modx->getObject('modResource', $this->existing);
                if ($this->resource) {
                    if (!$this->modx->hasPermission('view_document') || !$this->resource->checkPolicy('view') ) {
                        $this->setError($this->modx->lexicon('np_view_permission_denied'));
                        return;
                    }
                    
                } else {
                   $this->setError($this->modx->lexicon('np_no_resource') . $this->existing);
                   return;
                }

                $this->template = (isset($_POST['template']) && in_array('template', $this->show))?
                    $_POST['template'] :
                    $this->resource->get('template');
                    
                // for checking whether tv is attached to template in NpTV constructor....
                // TODO: not sure what would be the cleanest way to check for the template
                $this->resource->set('template', $this->template);
                
                /* need to forward this from $_POST so we know it's an existing doc */
                $stuff = '<input type="hidden" name="np_existing" value="true" />' . "\n" .
                '<input type="hidden" name="np_doc_id" value="' . $this->resource->get('id') . '" />';
                $this->modx->toPlaceholder('post_stuff',$stuff,$this->prefix);

            } else {
                
                /* new document */
                
                if (!$this->modx->hasPermission('new_document')) {
                    $this->setError($this->modx->lexicon('np_create_permission_denied'));
                }
                $this->resource = $this->modx->newObject('modResource');
                /* get folder id where we should store articles
                 else store under current document */
                $this->parentId = !empty($this->props['parentid']) ? intval($this->props['parentid']):$this->modx->resource->get('id');
                $this->parentObj = $this->modx->getObject('modResource',$this->parentId);
                if (! $this->parentObj) {
                    $this->setError('&amp;' .$this->modx->lexicon('np_no_parent'));
                    return;
                }

                $workingContext = $this->modx->getContext($this->context);
                $this->template = (integer) $workingContext->getOption('default_template', 1);
                // TODO: not sure what would be the cleanest way to check for the template (see also previous todo)
                $this->resource->set('template', $this->template);
                
                if (isset($this->props['defaults'])) {                    
                    $this->_parseDefaults($this->props['defaults']);
                }

                if (! empty($this->props['groups'])) {
                   $this->groups = $this->_setGroups($this->props['groups']);
                }
                
                $this->aliasTitle = $this->props['aliastitle']? true : false;
                $this->clearcache = $this->props['clearcache'] ? true: false;                
                $this->header = !empty($this->props['headertpl']) ? $this->modx->getChunk($this->props['headertpl']) : '';
                $this->footer = !empty($this->props['footertpl']) ? $this->modx->getChunk($this->props['footertpl']):'';

            } // end existing
            
            $override = isset($this->props['override'])? $this->_parseOptionString($this->props['override']) : array();
            $this->_initFormFields($fieldTypes, $override);

            if( !empty($this->props['badwords'])) {
                $this->badwords = str_replace(' ','', $this->props['badwords']);
                $this->badwords = "/".str_replace(',','|', $this->badwords)."/i";
            }

            $this->listboxMax = $this->props['listboxmax']? $this->props['listboxmax'] : 8;
            $this->multipleListboxMax = $this->props['multiplelistboxmax']? $this->props['multiplelistboxmax'] : 8;
            $this->intMaxlength = !empty($this->props['intmaxlength'])? $this->props['intmaxlength'] : 10; 
            $this->textMaxlength = !empty($this->props['textmaxlength'])? $this->props['textmaxlength'] : 60;
                        
        } /* end init */


    /* @param $fields (array) Associative array with field names as keys and (optionally) desired types as values
     * @param $override (array) Associative array of types to override with another type
     * creates form field objects and sets the correct value/type/caption, etc. */
    protected function _initFormFields($fields, $override=array()) {

        $class_key = ($this->isPostBack && key_exists('class_key', $fields))?
            $_POST['class_key'][0] :
            (isset($this->fields['class_key'])? $this->fields['class_key']->getValue() : 'modDocument');

        $captions = $this->_parseJSONProperty('captions');

        try {
            foreach ($fields as $name => $newType) {

                if (in_array($name, $this->protected)) {
                    throw new Exception($this->modx->lexicon('Editing this field is not allowed: ' . $name));
                }

                if (!isset($this->fields[$name]))
                    $this->fields[$name] = $this->_getField($this->resource, $name, $value);

                $field = $this->fields[$name];
                $type = $field->getType();

                /* content and introtext need special handling */
                switch ($name) {
                    case 'content':
                        switch ($class_key) {
                            case 'modDocument':
                                $field->setProperties(array(
                                    'rows'    => !empty($this->props['contentrows'])? $this->props['contentrows'] : '10',
                                    'columns' => !empty($this->props['contentcols'])? $this->props['contentcols'] : '60'
                                    ));
                                $type = $this->props['rtcontent'] ? 'richtext' : 'textarea';
                                break;
                            
                            case 'modWebLink':
                            case 'modSymLink':
                                $class_key = strtolower(substr($class_key, 3));
                                $field->setCaption($this->modx->lexicon($class_key));
                                $field->setHelp($this->modx->lexicon($class_key.'_help'));
                                $type = 'text';
                                break;

                            case 'modStaticResource':
                                $field->setCaption($this->modx->lexicon('static_resource'));
                                $type = 'file';
                                break;
                        }
                        break;

                    case 'introtext':
                        $field->setProperties(array(
                            'rows'    => !empty($this->props['summaryrows'])? $this->props['summaryrows'] : '10',
                            'columns' => !empty($this->props['summarycols'])? $this->props['summarycols'] : '60'
                            ));
                        $type = $this->props['rtsummary'] ? 'richtext' : 'textarea';
                        break;         
                }

                if ($newType) $type = $newType;
                if (isset($override[$type]))
                    $type = $override[$type];
                $field->setType($type);
                    
                if ($this->isPostBack) {
                    /* Read value from $_POST
                     *  TODO: duplicated code (althogh small piece, see _renderField) */
                    $class = $this->_getRenderClass($field->getType());
                    if (!class_exists($class) && !$this->_loadRender($class))
                        $class = 'npTextRender';

                    $field->setValue($class::getPostbackValue($field->name));

                } else {
                    if (!$field->validate())
                        foreach($field->errors as $msg) $this->setError($msg);
                }

                if (isset($captions[$name]))
                    $this->fields[$name]->setCaption($captions[$name]);
            }
            
        } catch (Exception $e) {
            $this->setError("There was a problem with '{$name}': ".$e->getMessage());
        }
    }
    

    /** Parses a string of default values which are set in new resources in the JSON format
     *  If the value is set to 'Parent' value of the corresponding field/TV is retrieved from the
     *  parent resource. Populates the $fields property with npField/npTV objects
     * @access protected
     * @param (string) $defaultString
     */

    protected function _parseDefaults($defaultString) {

        $fields = array();

        $defaults = $this->_parseJSONProperty('defaults');
        if ($defaults == null) return null;
        
        try {
            foreach ($defaults as $name => $value) {

                if (in_array($name, $this->protected)) {
                    $this->setError('Setting a default value for this field is not allowed: '.$name);
                    return null;
                }
                
                if ($value == 'Yes') $value = '1';
                elseif ($value == 'No') $value = '0';

                switch ($value) {
                    case 'Parent':
                        try {
                            $parentField = $this->_getField($this->parentObj, $name);
                            $value = $parentField->getValue();
                        } catch (Exception $e) {
                            $this->setError("An error occurred while retrieving the value for '{$name}' from the parent resource: ".$e->getMessage());
                            return null;
                        }
                        break;

                    case 'System Default':
                        // value (if there exists any) will be set in displayForm() or by the resource/create processor
                        continue 2;
                }
                if (!isset($this->fields[$name]))
                    $this->fields[$name] = $this->_getField($this->resource, $name, $value);
            }
        } catch (Exception $e) {
            $this->setError("An error occurred while parsing the default value for '{$name}': ".$e->getMessage());
            return null;
        }
    }

    /** return a specified tpl
     *
     * @access public
     * @param (string) tpl name
     *
     * @return (string) tpl content
     *  */

    public function getTpl($tpl) {
        if (!isset($this->tpls[$tpl])) {
            $this->tpls[$tpl] = !empty ($this->props[strtolower($tpl)])
                    ? $this->modx->getChunk($this->props[strtolower($tpl)])
                    : $this->modx->getChunk('np' . $tpl);

            if (empty($this->tpls[$tpl])) {
                $this->setError($this->modx->lexicon('np_no_tpl') . $tpl);

                switch ($tpl) {
                    case 'OuterTpl':
                        $this->tpls[$tpl] = '<div class="newspublisher">
                            <h2>[[%np_main_header]]</h2>
                            [[!+np.error_header:ifnotempty=`<h3>[[!+np.error_header]]</h3>`]]
                            [[!+np.errors_presubmit:ifnotempty=`[[!+np.errors_presubmit]]`]]
                            [[!+np.errors_submit:ifnotempty=`[[!+np.errors_submit]]`]]
                            [[!+np.errors:ifnotempty=`[[!+np.errors]]`]]</div>';
                        break;

                    case 'ErrorTpl':
                        $this->tpls[$tpl] = '<span class = "errormessage">[[+np.error]]</span>';
                        break;

                    case 'FieldErrorTpl':
                        $this->tpls[$tpl] = '<span class = "fielderrormessage">[[+np.error]]</span>';
                        break;
                }
            }
    
            /* set different placeholder prefix if requested */
            
            if ($this->prefix != 'np') {
                $this->tpls[$tpl] = str_replace('np.', $this->prefix . '.', $this->tpls[$tpl]);
            }
            
        }
        
        return $this->tpls[$tpl];
    }


    protected function _getField($resource, $name, $value=null) {
        $class = in_array($name, $this->fieldNames)? 'npField' : 'npTV';
        return new $class($name, $resource, $this->modx, $value);
    }

    
    protected function _getRenderClass($type) {
        if (isset($this->override[$type])) $type = $this->override[$type];
        return 'np' .str_replace('-', '', ucfirst($type)).'Render';
    }


    protected function _loadRender($class) {
        $file = $this->corePath.'classes/render/'.$class.'.class.php';
        if (!file_exists($file)) return false;
        include $file;
        return true;
    }


    /** Creates the HTML for the displayed form by calling _renderField() for each field.
     *
     * @access public
     * (name or ID) to include in the form
     *
     * @return (string) returns the finished form
     */
    public function displayForm() {

        if (! $this->resource)
            $this->setError($this->modx->lexicon('np_no_resource'));

        $formTpl = $this->getTpl('OuterTpl');
        $inner = '';

        if (!$this->errors) {
            foreach($this->show as $name) {
                $inner .= $this->_renderField($this->fields[$name]);
            }
        }

        $formTpl = str_replace('[[+npx.insert]]',$inner,$formTpl);
        
        // TODO: remove [[+npx.readonly]] and [[+npx.hidden]] in chunks?
        return $formTpl;
    }



    /** displays an individual field/TV
     * @access protected
     * @param $field (NpAbstractField) name of the field
     * @return (string) returns the HTML code for the field.
     */
    protected function _renderField($field) {

        /* Prevent rendering of MODx tags
         * TODO: only doing so for double brackets -> ok???? */
        $field->setValue(str_replace(array('[[',']]'), array('&#91;&#91;','&#93;&#93;'), $field->getValue()));
        $type = $field->getType();

        if (isset($this->renderTypes[$type])) { /* type already initialized */
            $class = $this->renderTypes[$type];
            $render = new $class($this, $field);
        } else {
            /* Render class of this type is called the first time -> initialize */
            $class = $this->_getRenderClass($type);
            if (!class_exists($class) && !$this->_loadRender($class)) {
                $class = 'npTextRender';
            }
            $render = new $class($this, $field);
            $render->init();
            $this->renderTypes[$type] = $class;
        }
        
        return $render->render();
    }
    

    /** creates a JSON string to send in the resource_groups field
     * for resource/update or resource/create processors.
     * If 'Parent' is supplied, the groups the parent resource belongs to are returned
     *
     * @access protected
     * @param string $resourceGroups - a comma-separated list of
     * resource groups names or IDs (or both mixed) to assign a
     * document to.
     *
     * @return (string) (JSON encoded array)
     */

    protected function _setGroups($resourceGroups) {
      
        $values = array();
        if ($resourceGroups == 'Parent') {

            $resourceGroups = (array) $this->parentObj->getMany('ResourceGroupResources');

            if (!empty($resourceGroups)) { /* parent belongs to at lease one resource group */
                /* build $resourceGroups string from parent's groups */
                $groupNumbers = array();
                foreach ($resourceGroups as $resourceGroup) {
                    $groupNumbers[] = $resourceGroup->get('document_group');
                }
                $resourceGroups = implode(',', $groupNumbers);
            } else { /* parent not in any groups */
                //$this->setError($this->modx->lexicon('np_no_parent_groups'));
                return '';
            }


        } /* end if 'parent' */

        $groups = explode(',', $resourceGroups);

        foreach ($groups as $group) {
            $group = trim($group);
            if (is_numeric($group)) {
                $groupObj = $this->modx->getObject('modResourceGroup', $group);
            } else {
                $groupObj = $this->modx->getObject('modResourceGroup', array('name' => $group));
            }
            if (! $groupObj) {
                $this->setError($this->modx->lexicon('np_no_resource_group') . $group);
                return null;
            }
            $values[] = array(
                'id' => $groupObj->get('id'),
                'name' => $groupObj->get('name'),
                'access' => '1',
                'menu' => '',
            );
        }
        //die('<pre>' . print_r($values,true));
        return $this->modx->toJSON($values);

    }

    /** allows strip slashes on an array
     * not used, but may have to be called if magic_quotes_gpc causes trouble
     * */
    /*protected function _stripslashes_deep($value) {
        $value = is_array($value) ?
                array_map('_stripslashes_deep', $value) :
                stripslashes($value);
        return $value;
    }*/

    /** Checks form fields before saving.
     *  Sets an error for the header and another for each
     *  missing required field.
     * */

    public function validate() {
        $errorTpl = $this->getTpl('FieldErrorTpl');
        $success = true;
        if ($this->required) {
            foreach ($this->required as $name) {
                $field = $this->fields[$name];
                $value = $field->getValue();
                if (empty($value)) {
                    $success = false;
                    /* set ph for field error msg */
                    $msg = $this->modx->lexicon('np_error_required');
                    $this->setFieldError($name, $msg);

                    /* set error for header */
                    $msg = $this->modx->lexicon('np_missing_field');
                    $msg = str_replace('[[+name]]', $name, $msg);
                    $msg = str_replace('[[+caption]]', $field->getCaption(), $msg);
                    $this->setError($msg);
                }
            }
        }

        foreach ($this->show as $name) {
            if (! $this->fields[$name]->validate()) {
                foreach ($this->fields[$name]->errors as $msg) {
                    $this->setError($msg);
                    $this->setFieldError($msg);
                }
                /* set fields to empty string */
                $this->modx->toPlaceholder($name, '', $this->prefix);
                $success = false;
            }
        }

        return $success;
    }


    /** Saves the resource to the database.
     *
     * @access public
     * @return - (int) returns the ID of the created or edited resource,
     * or empty string on error.
     * Used by snippet to forward the user.
     *
     */

    public function saveResource() {

        if (!$this->modx->hasPermission('allow_modx_tags')) {
            $allowedTags = '<p><br><a><i><em><b><strong><pre><table><th><td><tr><img><span><div><h1><h2><h3><h4><h5><font><ul><ol><li><dl><dt><dd>';
            foreach ($this->fields as $name => $field)
                if (!is_array($val = $field->getValue())) { /* leave checkboxes, etc. alone */
                    $field->setValue($this->modx->stripTags($val, $allowedTags));
                }
        }

        if (!empty($this->badwords)) {
            foreach ($this->fields as $name => $field) {
                $val = $field->getValue();
                if (!is_array($val)) {
                    $field->setValue(preg_replace($this->badwords, '[Filtered]', $val)); // remove badwords
                }
            }
        }

        $fields = $this->resource->toArray();

        if (!$this->existing) {
        // these fields aren't editable, but have to be set
        $fields = array_merge($fields, array(
            'editedon'    => '0',
            'editedby'    => '0',
            'parent'      => $this->parentId,
            'createdby'   => $this->modx->user->get('id'),
            'context_key' => $this->parentObj->get('context_key'),
            ));
        }

        /* Add fields and TVs to $fields for processor */
        /* processor handles all types */
        $hasTvs = false;
        foreach ($this->fields as $name => $field) {
            $fields[$field->getSaveName()] = $field->getValue();
            if (!$hasTvs && get_class($field) == 'NpTV')
                $fields['tvs'] = true;
        }

        /* *********************************************
         * Deal with bug in resource update processor. *
         * Set $fields to current TV value for all TVs *
         * This section can be removed when it's fixed *
         ********************************************* */
        if ($hasTvs && $this->existing) {
            $t_resourceTVs = $this->resource->getMany('TemplateVars');
            $t_resourceId = $this->resource->get('id');
            foreach ($t_resourceTVs as $t_tv) {
                if (!isset($fields[$t_tv->get('name')])) {
                    $t_tvId = $t_tv->get('id');
                    $t_value = $t_tv->getValue($t_resourceId);
                    $fields['tv' . $t_tvId] = $t_value;
                }
            }
            unset($t_resourceTVs,$t_resourceId,$t_tvId,$t_value);
        }

        
        if (!$this->existing) { /* new document */

            if (empty($fields['alias'])) { /* leave it alone if filled */
                if (!$this->aliasTitle) {
                    $suffix = !empty($this->props['aliasdatesuffix']) ? date($this->props['aliasdatesuffix']) : '-' . time();
                    if (!empty($this->props['aliasprefix'])) {
                        $alias = $this->props['aliasprefix'] . $suffix;
                    } else {
                        $alias = $suffix;
                    }
                } else { /* use pagetitle */
                    $alias = $this->modx->stripTags($this->fields['pagetitle']->getValue());
                    $alias = strtolower($alias);
                    $alias = preg_replace('/&.+?;/', '', $alias); // kill entities
                    $alias = preg_replace('/[^\.%a-z0-9 _-]/', '', $alias);
                    $alias = preg_replace('/\s+/', '-', $alias);
                    $alias = preg_replace('|-+|', '-', $alias);
                    $alias = trim($alias, '-');

                }
                $fields['alias'] = $alias;
            }

            $fields['content']  = $this->header . $fields['content'] . $this->footer;
        }

        /* set groups for new doc if param is set */
        if ((!empty($this->groups) && (!$this->existing))) {
            $fields['resource_groups'] = $this->groups;
        }
        
        /* one last error check before calling processor */
        if (!empty($this->errors)) {
            /* return without altering the DB */
            return '';
        }
        if ($this->props['clearcache']) {
            $fields['syncsite'] = true;
        }
        /* call the appropriate processor to save resource and TVs */
        if ($this->existing) {
            $response = $this->modx->runProcessor('resource/update', $fields);
        } else {
            $response = $this->modx->runProcessor('resource/create', $fields);
        }
        if ($response->isError()) {
            if ($response->hasFieldErrors()) {
                $fieldErrors = $response->getAllErrors();
                $errorMessage = implode("\n", $fieldErrors);
            } else {
                $errorMessage = 'An error occurred: ' . $response->getMessage();
            }
            $this->setError($errorMessage);
            return '';

        } else {
            $object = $response->getObject();

            $postId = $object['id'];
        }

        if (!$postId) {
            $this->setError('np_post_save_no_resource');
        }
        return $postId;

    } /* end saveResource() */

        /** Forward user to another page (default is edited page)
         *
         *  @access public
         *  @param (int) $postId - ID of page to forward to
         *  */

        public function forward($postId) {
            if (empty($postId)) {
                $postId = $this->existing? $this->existing : $this->resource->get('id');
            }
            /* clear cache on new resource */
            if (! $this->existing) {
               $cacheManager = $this->modx->getCacheManager();
               $cacheManager->clearCache(array (
                    "{$this->resource->context_key}/",
                ),
                array(
                    'objects' => array('modResource', 'modContext', 'modTemplateVarResource'),
                    'publishing' => true
                    )
                );
            }

            $_SESSION['np_resource_id'] = $this->resource->get('id');
            $goToUrl = $this->modx->makeUrl($postId);

            /* redirect to post id */

            /* ToDo: The next two lines can probably be removed once makeUrl() and sendRedirect() are updated */
            $controller = $this->modx->getOption('request_controller',null,'index.php');
            $goToUrl = $controller . '?id=' . $postId;

            $this->modx->sendRedirect($goToUrl);
        }


    protected function _parseOptionString($str) {
        $opts = explode(',', $str);
        foreach($opts as $opt) {
            list($k, $v) = array_map('trim', explode(':', $opt, 2));
            $out[$k] = $v;
        }
        return $out;
    }

    protected function _parseJSONProperty($name) {
        if (!isset($this->props[$name]))
            return '';
            
        $props = json_decode($this->props[$name], true);
        if ($props == null) {
            $this->setError("The '{$name}' property has a syntax error. It should be in a JSON format.");
            return null;
        }
        return $props;
    }

    /** Registers a new file browser
     * @param (string) $identifier - unique key (usually the field name)
     * @param (array) $msg - (optional) array of options, accepts all image/file TV input options
     * @return (string) - the full URL of the file browser
     */
    public function registerFileBrowser($identifier, $properties=array()) {
        $browserAction = $this->modx->getObject('modAction',array('namespace'  => 'newspublisher'));
        $url = $browserAction? $this->modx->getOption('manager_url',null,MODX_MANAGER_URL).'index.php?a='.$browserAction->get('id') : null;
        if (!$url)  $this->setError($this->modx->lexicon('np_no_action_found'));
        $url .= '&field='.$identifier;
        $_SESSION['newspublisher']['filebrowser'][$identifier] = $properties;
        return $url;
    }

    /** return any errors set in the class
     * @return (array) array of error strings
     */
    public function getErrors() {
        return $this->errors;
    }

    /** add error to error array
     * @param (string) $msg - error message
     */
    public function setError($msg) {
        $this->errors[] = $msg;
    }
    

    /** Sets placeholder for field error messages
     * @param (string) $fieldName - name of field
     * @param (string) $msg - lexicon error message string
     *
     */
    /* ToDo: Change [[+name]] to [[+npx.something]]?, or ditch it (or not)*/
    public function setFieldError($fieldName, $msg) {
        $msg = str_replace('[[+name]]', $fieldName, $msg);
        $msg = str_replace('[[+caption]]', $this->fields[$fieldName]->getCaption(), $msg);
        $msg = str_replace("[[+{$this->prefix}.error]]", $msg, $this->getTpl('FieldErrorTpl'));
        $ph = 'error_' . $fieldName;
        $this->modx->toPlaceholder($ph, $msg, $this->prefix);
    }

    
    public function my_debug($message, $clear = false) {
        global $modx;

        $chunk = $modx->getObject('modChunk', array('name'=>'debug'));
        if (! $chunk) {
            $chunk = $modx->newObject('modChunk', array('name'=>'debug'));
            $chunk->save();
            $chunk = $modx->getObject('modChunk', array('name'=>'debug'));
        }
        if ($clear) {
            $content = '';
        } else {
            $content = $chunk->getContent();
        }
        $content .= $message;
        $chunk->setContent($content);
        $chunk->save();
    }

} /* end class */


?>
