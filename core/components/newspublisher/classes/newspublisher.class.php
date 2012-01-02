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
    protected $modx;
    /**
     * @var string current context
     */
    protected $context;
    /**
     * @var modContext current working context
     */
    protected $workingContext;
    /**
     * @var array scriptProperties array
     */
    protected $props;
    /**
     * @var array Array of error messages
     */
    protected $errors;
    /**
     * @var modResource The current resource
     */
    protected $resource;
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
     * @var array Holds inital values of Fields/TVs which are used if a new resource is created
     */
    protected $defaults;
    /**
     * @var string Path to NewsPublisher Core
     */
    protected $corePath;
    /**
     * @var string Path to NewsPublisher assets directory
     */
    protected $assetsPath;
    /**
     * @var string URL to NewsPublisher Assets directory
     */
    protected $assetsUrl;
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
     * @var int Max size of listbox TVs
     */
    protected $listboxMax;
    /**
     * @var int Max size of multi-select listbox TVs
     */
    protected $multipleListboxMax;
    /**
     * @var string prefix for placeholders
     */
    protected $prefix;
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
     * @var int Max length for integer input fields
     */
    protected $intMaxlength;
    /**
     * @var int Max length for text input fields
     */
    protected $textMaxlength;
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
     * @var array These fields cannot be modified or set as default, they are set by NP
     */
    protected $protected = array('id', 'parent', 'editedby', 'editedon', 'context_key'); 


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
        require_once $this->corePath . 'classes/NpAbstractField.class.php';
        require_once $this->corePath . 'classes/NpField.class.php';
        require_once $this->corePath . 'classes/NpTV.class.php';
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
            $this->workingContext = $this->modx->getContext($this->context);
            $this->fieldNames = array_keys($this->modx->getFields('modResource'));
            
            $language = !empty($this->props['language'])
                    ? $this->props['language']
                    : $this->modx->getOption('cultureKey',null,$this->modx->getOption('manager_language',null,'en'));
            switch ($context) {
                case 'mgr':
                    break;
                case 'web':
                default:
                    $this->modx->lexicon->load($language . ':newspublisher:default');
                    break;
            }
                       /* inject NP CSS file */
           /* Empty but sent parameter means use no CSS file at all */

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

            if ($this->isPostBack) {
                /* Don't use arrays for HTML select/radio fields with a single element.
                 * The nested arrays cause problems when saving fields */
                foreach($_POST as $k => $v) {
                    if (is_array($v) && count($v)==1) $_POST[$k] = reset($v);
                }
            }

            $this->show = array_map('trim', explode(',', $this->props['show']));
            $this->required = array_filter(array_map('trim', explode(',', $this->props['required'])));
            $this->fields = array();

            if ($this->existing) {

                $this->resource = $this->modx->getObject('modResource', $this->existing);
                if ($this->resource) {

                    if (!$this->modx->hasPermission('view_document') || !$this->resource->checkPolicy('view') ) {
                        $this->setError($this->modx->lexicon('np_view_permission_denied'));
                    }
                    
                } else {
                   $this->setError($this->modx->lexicon('np_no_resource') . $this->existing);
                   return;
                }

                $this->template = isset($_POST['template']) ? $_POST['template'] : $this->resource->get('template');
                
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

                /* these fields need to be set in new docs  */
                $this->template = (integer) $this->workingContext->getOption('default_template', 0);
                
                // for checking whether tv is attached to template in NpTV constructor....
                // TODO: not sure what would be the cleanest way to check for the template
                $this->resource->set('template', $this->template);
                
                /* get the default values (if set) */
                if (isset($this->props['defaults'])) {
                    $this->fields = $this->_parseDefaults($this->props['defaults']);
                }
                    
                $this->aliasTitle = $this->props['aliastitle']? true : false;
                $this->clearcache = isset($_POST['clearcache'])? $_POST['clearcache'] : $this->props['clearcache'] ? true: false;

                if (! empty($this->props['groups'])) {
                   $this->groups = $this->_setGroups($this->props['groups']);
                }
                 
                $this->header = !empty($this->props['headertpl']) ? $this->modx->getChunk($this->props['headertpl']) : '';
                $this->footer = !empty($this->props['footertpl']) ? $this->modx->getChunk($this->props['footertpl']):'';

            }

             if( !empty($this->props['badwords'])) {
                 $this->badwords = str_replace(' ','', $this->props['badwords']);
                 $this->badwords = "/".str_replace(',','|', $this->badwords)."/i";
             }

           $this->modx->lexicon->load('core:resource');

           if($this->props['initdatepicker']) {
                $this->modx->regClientCSS($this->assetsUrl . 'datepicker/css/datepicker.css');
                $this->modx->regClientStartupHTMLBlock('<script type=text/javascript src="' . $this->assetsUrl . 'datepicker/js/datepicker.packed.js">{"lang":"' . $language . '"}</script>');
           }

           $this->listboxMax = $this->props['listboxmax']? $this->props['listboxmax'] : 8;
           $this->multipleListboxMax = $this->props['multiplelistboxmax']? $this->props['multiplelistboxmax'] : 8;

           $this->intMaxlength = !empty($this->props['intmaxlength'])? $this->props['intmaxlength'] : 10;
           $this->textMaxlength = !empty($this->props['textmaxlength'])? $this->props['textmaxlength'] : 60;

           if (false) { /* do rich text stuff */
               //$ph = ! empty($this->props['rtcontent']) ? 'MODX_RichTextWidget':'content';
               $ph = !empty($this->props['rtcontent'])
                       ? 'modx-richtext' : 'np-content';
               $this->modx->toPlaceholder('rt_content_1', $ph, $this->prefix);
               $ph = !empty($this->props['rtcontent'])
                       ? 'modx-richtext' : 'np-content';
               $this->modx->toPlaceholder('rt_content_2', $ph, $this->prefix);


               /* set rich text summary field */

               $ph = !empty($this->props['rtsummary'])
                       ? 'modx-richtext' : 'np-introtext';
               $this->modx->toPlaceholder('rt_summary_1', $ph, $this->prefix);
               $ph = !empty($this->props['rtsummary'])
                       ? 'modx-richtext' : 'np-introtext';
               $this->modx->toPlaceholder('rt_summary_2', $ph, $this->prefix);
           }

            unset($ph);
           if ($this->props['initrte']) {
                /* set rich text content placeholders and includes necessary js files */
               $tinyPath = $this->modx->getOption('core_path').'components/tinymce/';
               $this->modx->regClientStartupScript($this->modx->getOption('manager_url').'assets/ext3/adapter/ext/ext-base.js');
               $this->modx->regClientStartupScript($this->modx->getOption('manager_url').'assets/ext3/ext-all.js');
               $this->modx->regClientStartupScript($this->modx->getOption('manager_url').'assets/modext/core/modx.js');


               $whichEditor = $this->modx->getOption('which_editor',null,'');

               $plugin=$this->modx->getObject('modPlugin',array('name'=>$whichEditor));
               if ($whichEditor == 'TinyMCE' ) {
                   //$tinyUrl = $this->modx->getOption('assets_url').'components/tinymcefe/';
                    $tinyUrl = $this->modx->getOption('assets_url').'components/tinymce/';
                   /* OnRichTextEditorInit */

                   $tinyproperties=$plugin->getProperties();
                   require_once $tinyPath.'tinymce.class.php';
                   $tiny = new TinyMCE($this->modx,$tinyproperties,$tinyUrl);
                   // if (isset($this->props['forfrontend']) || $this->modx->isFrontend()) {
                   if (isset($this->props['forfrontend']) || $this->modx->context->get('key') != 'mgr') {
                       $tinyproperties['language'] = $this->modx->getOption('fe_editor_lang',array(),$language);
                       $tinyproperties['frontend'] = true;
                       unset($def);
                   }
                   $tinyproperties['cleanup'] = true; /* prevents "bogus" bug */
                   $tinyproperties['width'] = empty ($this->props['tinywidth'] )? '95%' : $this->props['tinywidth'];
                   $tinyproperties['height'] = empty ($this->props['tinyheight'])? '400px' : $this->props['tinyheight'];

                   //$tinyproperties['tiny.custom_buttons1'] = 'image';
                   //$tinyproperties['tiny.custom_buttons2'] = '';

                   $tiny->setProperties($tinyproperties);

                   $html = $tiny->initialize();

                   $this->modx->regClientStartupScript($tiny->config['assetsUrl'].'jscripts/tiny_mce/langs/'.$tiny->properties['language'].'.js');
                   $this->modx->regClientStartupScript($tiny->config['assetsUrl'].'tiny.browser.js');

                   $this->modx->regClientStartupHTMLBlock('<script type="text/javascript">
                       Ext.onReady(function() {
                       MODx.loadRTE();
                       '.$js.'
                       });
                   </script>');

               } /* end if ($whichEditor == 'TinyMCE') */

           } /* end if ($richtext) */

        } /* end init */


    /** Parses a string of default values which are set in new resources in the form 'field_or_tv:value'
     *  If the value is set to 'Parent' value of the corresponding field/TV is retrieved from the
     *  parent resource
     * @access protected
     * @param (string) $defaultString
     * @return (array) returns an associative array of fields/TVs and their values
     */

    protected function _parseDefaults($defaultString) {

        $defaults = array();
        /* Split the string by commas if they are not escaped. See also
         * http://stackoverflow.com/questions/6243778/split-string-by-delimiter-but-not-if-it-is-escaped/  */
        $fields = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $defaultString);
        
        foreach ($fields as $i => $field_str) {

            list($name, $value) = array_map('trim', explode(':', $field_str, 2));

            if (in_array($name, $this->protected)) {
                $this->setError('Setting a default value for this field is not allowed: '.$name);
                return null;
            }

            // remove eventual backslashes before commas
            $value = str_replace('\\,', ',', $value);
            
            if ($value == 'Yes') $value = '1';
            else if ($value == 'No') $value = '0';

            switch ($value) {
                case 'Parent':
                    try {
                        $parentField = $this->_getField($this->parentObj, $name);
                        $value = $parentField->getValue();
                    } catch (Exception $e) {
                        $this->setError("An error occurred while retrieving the value for {$name} from the parent resource: ".$e->getMessage());
                        return null;
                    }
                    break;

                case 'System Default':
                    // value (if there exists any) will be set in displayForm() or by the resource/create processor
                    continue 2;
            }
            try {
                $defaults[$name]  = $this->_getField($this->resource, $name, $value);
            } catch (Exception $e) {
                $this->setError("An error occurred while parsing the default value for {$name}: ".$e->getMessage());
                return null;
            }
        }

        return $defaults;

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


    /**
     *
     * @access protected
     * @param (string) $name
     * 
     * @return (string) 
     */
    protected function _getField($resource, $name, $value=null) {
        $class = in_array($name, $this->fieldNames)? 'NpField' : 'NpTV';
        $field = new $class($name, $resource, $this->modx, $value);
        return $field;
    }


    /** Creates the HTML for the displayed form by calling _displayField() for each field.
     *
     * @access public
     * (name or ID) to include in the form
     *
     * @return (string) returns the finished form
     */
    public function displayForm() {

        $inner = '';

        if (! $this->resource) {
            $this->setError($this->modx->lexicon('np_no_resource'));
            return $this->getTpl('OuterTpl');
        }
        
        try {
            foreach($this->show as $fieldName) {
                $fieldName = trim($fieldName);

                if (in_array($fieldName, $this->protected)) {
                    throw new Exception($this->modx->lexicon('Editing this field is not allowed: ' . $fieldName));
                }
                
                if (!isset($this->fields[$fieldName])) {

                    $value = null;
                    
                    if (!$this->existing) {
                    /* these fields can have a default value that needs only to be retrieved if they are displayed in the form
                     * otherwise they are set by the resource/create processor
                     * TODO: store them all in $this->defaults during init()?? */
                        switch ($fieldName) {
                            case 'template':  $value = $this->template; break;
                            case 'richtext':  $value = (integer) $this->workingContext->getOption('richtext_default', 1); break;
                            case 'published': $value = (integer) $this->workingContext->getOption('publish_default', 0); break;
                            case 'cacheable': $value = (integer) $this->workingContext->getOption('cache_default', 1); break;
                            case 'searchable':$value = (integer) $this->workingContext->getOption('search_default', 1); break;
                        }
                    }
                    
                    $this->fields[$fieldName] = $this->_getField($this->resource, $fieldName, $value);                    
                }
                $inner .= $this->_displayField($this->fields[$fieldName]);
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }

        $formTpl = str_replace('[[+npx.insert]]',$inner,$this->getTpl('OuterTpl'));
        return $formTpl;
    }



    /** displays an individual field/TV
     * @access protected
     * @param $field (NpAbstractField) name of the field
     * @return (string) returns the HTML code for the field.
     */

    protected function _displayField($field) {
        
        /* Get the field value */
        if ($this->isPostBack) {
             $value = $_POST[$field->name];

        } else {
            $value = $field->getValue();
            if (strstr($value, '[[') && ! $this->modx->hasPermission('allow_modx_tags')) {
                $this->setError($this->modx->lexicon('np_no_modx_tags'));
                return null;
            }
            // TODO: not sure if this is good. Or should checking for modx tags go to NpAbstractField::validate(), too?
            if (!$field->validate()) {
                foreach($field->errors as $msg) $this->setError($msg);
            }
        }
        /* Prevent rendering of MODx tags */
        $value = str_replace(array('[',']'), array('&#91;','&#93;'), $value);
        
        $replace = array(
            '[[+npx.help]]' => $this->props['hoverhelp'] ? $field->getHelp() : '',
            '[[+npx.caption]]' => $field->getCaption(),
            '[[+npx.fieldName]]' => $field->name
            );
        $inner = '';
        $props = $field->getProperties();
        $type = $field->getType();

        /* content and introtext need special handling */
        switch ($field->name) {
            case 'content':
                /* adjust content field type according to class_key */
                if ($this->existing) $class_key = $this->resource->get('class_key');
                else $class_key = isset($_POST['class_key']) ? $_POST['class_key'] : (isset($this->fields['class_key'])? $this->fields['class_key']->getValue() : 'modDocument');

                switch ($class_key) {
                    case 'modDocument':
                        $props['rows']    = !empty($this->props['contentrows'])? $this->props['contentrows'] : '10';
                        $props['columns'] = !empty($this->props['contentcols'])? $this->props['contentcols'] : '60';
                        $type = $this->props['rtcontent'] ? 'richtext' : 'textarea';
                        break;
                    
                    case 'modWebLink':
                    case 'modSymLink':
                        $class_key = strtolower(substr($class_key, 3));
                        $replace['[[+npx.caption]]'] = $this->modx->lexicon($class_key);
                        $replace['[[+npx.help]]'] = $this->modx->lexicon($class_key.'_help');
                        $type = 'text';
                        break;

                    case 'modStaticResource':
                        $replace['[[+npx.caption]]'] = $this->modx->lexicon('static_resource');
                        $type = 'file';
                }
                break;

            case 'introtext':
                $props['rows']    = ! empty($this->props['summaryrows'])? $this->props['summaryrows'] : '10';
                $props['columns'] = ! empty($this->props['summarycols'])? $this->props['summarycols'] : '60';
                $type = $this->props['rtsummary'] ? 'richtext' : 'textarea';
                break;
        }

        /* Call the appropriate rendering function */
        $function = str_replace('-', '', '_display'.$type);
        if (!method_exists($this, $function)) {
            $function = '_displaytext';
        }
        $inner .= $this->$function($field->name, $value, $props);

        return $this->strReplaceAssoc($replace, $inner);
    }
    

    /** Uses an associative array for string replacement
     *
     * @param $replace - (array) associative array of keys and values
     * @param &$subject - (string) string to do replacements in
     * @return (string) - modified subject */

    public function strReplaceAssoc(array $replace, $subject) {
       return str_replace(array_keys($replace), array_values($replace), $subject);
    }
    

    /** Produces the HTML code for date fields/TVs
     * Splits time string into date and time and sets
     * placeholders for each of them
     *
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $timeString - (string) date-time string in the format "2011-04-01 13:20:01"
     * @param $options - (array) Associative array of options. Accepts 'disabledDates', 'disabledDays', 'minDateValue' and 'maxDateValue' (in the format used for the corresponding TV input options)
     * @return (string) - date field/TV HTML code */
    
    protected function _displaydate($name, $timeString, $options = array()) {

        if (! $this->props['initdatepicker']) {
            $msg = $this->modx->lexicon('np_no_datepicker');
            $this->setError($msg . $name);
            $this->setFieldError($name, $msg);
        }
        
        if (! $this->isPostBack) {
            $s = substr($timeString,11,5);
            $this->modx->toPlaceholder($name . '_time' , $s, $this->prefix);

            /* format date string according to np_date_format lexicon entry
             * (see http://www.frequency-decoder.com/2009/09/09/unobtrusive-date-picker-widget-v5
             * for details)
             */
            if ($timeString) {
              $format = $this->modx->lexicon('np_date_format');
              $format = str_replace( array('-','sp','dt','sl','ds','cc'),
                                     array( '', ' ', '.', '/', '-', ','), $format);
              $timestamp = mktime(0, 0, 0, substr($timeString,5,2), substr($timeString,8,2), substr($timeString,0,4));
              $s = date($format, $timestamp);
            } else {
              $s = '';
            }
            $this->modx->toPlaceholder($name, $s, $this->prefix);
          }
          
          /* Set disabled dates */
          
          $disabled = '';
          if ($options['disabledDates']) {
              $disabled .= 'disabledDates:{';
              foreach (explode(',', $options['disabledDates']) as $d) {
                  $disabled .= '"';
                  $d = str_replace('-', '', $d);
                  $d = str_replace('.', '*', $d);
                  if (! (strpos($d, '^') === false)) {
                      $d = str_replace('^',  str_repeat('*', 9 - strlen($d)), $d);
                  }
                  $disabled .= $d . '":1,';
              }
              $disabled .= '},';
          }
          if ($options['disabledDays']) {
              $disabled .= 'disabledDays:[';
              $days = explode(',', $options['disabledDays']);
              for ($day = 1; $day <= 7; $day++) {
                  $disabled .= (in_array($day, $days) ? 1 : 0) . ',';
              }
              $disabled .= '],';
          }
          if ($options['minDateValue']) {
              $disabled .= 'rangeLow:"' . str_replace('-', '', $options['minDateValue']) . '",';
          }
          if ($options['maxDateValue']) {
              $disabled .= 'rangeHigh:"' . str_replace('-', '', $options['maxDateValue']) . '",';
          }
          
          $PHs = array('[[+npx.disabledDates]]' => $disabled);

        return $this->strReplaceAssoc($PHs, $this->getTpl('DateTpl'));
    }

    
    /** Produces the HTML code for text fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $text - (string) the text content
     * @return (string) - field/TV HTML code */

    protected function _displaytext($name, $text) {
        $PHs = array('[[+npx.maxlength]]' => $this->textMaxlength);
        $this->modx->toPlaceholder($name, $text, $this->prefix);
        return $this->strReplaceAssoc($PHs, $this->getTpl('TextTpl'));
    }


    /** Produces the HTML code for number input fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $text - (string) the number
     * @return (string) - field/TV HTML code */

    protected function _displaynumber($name, $number) {
        $PHs = array('[[+npx.maxlength]]' => $this->intMaxlength);
        $this->modx->toPlaceholder($name, $number, $this->prefix);
        return $this->strReplaceAssoc($PHs, $this->getTpl('IntTpl'));
    }

    protected function _displayfile($name, $path, $options) {
        return $this->_displayFileInput($name, $path, $options, 'FileTpl');
    }

    protected function _displayimage($name, $path, $options) {
        return $this->_displayFileInput($name, $path, $options, 'ImageTpl');
    }

    /** Produces the HTML code for file/image TVs
     * 
     * @access protected
     * @param $name - (string) name of the TV
     * @param $path - (string) the file path
     * @param $options - (array) Associative array of options. Accepts all file/image TV input options.
     *       Possible options: all (processed) TV input options (Revo versions below 2.20), respectively the media source.
     *       'wctx' doesn't seem to have an effect (?)
     * @return (string) - HTML code */

    protected function _displayFileInput($name, $path, $options, $tplName) {
        
        /* Prepare input properties.
         * code adapted from core/model/modx/processors/element/tv/renders/mgr/input/file.php
         * and (...)/image.php */
         
        $this->modx->getService('fileHandler','modFileHandler', '', array('context' => $this->context));
        $options['wctx'] = $this->context; // not sure if this is important, doesn't seem to have an effect

        // not using modMediaSource::getOpenTo since we don't have the object, but using the same code
        $openTo = empty($path)? '' : dirname($path).'/';

        if (isset($options['source'])) {
            if (!is_numeric($options['source']))
                $this->setError($options['source']);
                
        } else {/* MODx versions < 2.20 */

            $workingContext = $this->modx->getContext($this->resource->get('context_key'));
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

            if (empty($options['basePath'])) {
                $options['basePath'] = $this->modx->fileHandler->getBasePath();
                $options['basePath'] = str_replace($replaceKeys,$replaceValues,$options['basePath']);
                $options['basePathRelative'] = $workingContext->getOption('filemanager_path_relative',true) ? 1 : 0;
            } else {
                $options['basePath'] = str_replace($replaceKeys,$replaceValues,$options['basePath']);
                $options['basePathRelative'] = !isset($options['basePathRelative']) || in_array($options['basePathRelative'],array('true',1,'1'));
            }
            if (empty($options['baseUrl'])) {
                $options['baseUrl'] = $this->modx->fileHandler->getBaseUrl();
                $options['baseUrl'] = str_replace($replaceKeys,$replaceValues,$options['baseUrl']);
                $options['baseUrlRelative'] = $workingContext->getOption('filemanager_url_relative',true) ? 1 : 0;
            } else {
                $options['baseUrl'] = str_replace($replaceKeys,$replaceValues,$options['baseUrl']);
                $options['baseUrlRelative'] = !isset($options['baseUrlRelative']) || in_array($options['baseUrlRelative'],array('true',1,'1'));
            }
            $modxBasePath = $this->modx->getOption('base_path',null,MODX_BASE_PATH);
            if ($options['basePathRelative'] && $modxBasePath != '/') {
                $options['basePath'] = ltrim(str_replace($modxBasePath,'',$options['basePath']),'/');
            }
            $modxBaseUrl = $this->modx->getOption('base_url',null,MODX_BASE_URL);
            if ($options['baseUrlRelative'] && $modxBaseUrl != '/') {
                $options['baseUrl'] = ltrim(str_replace($modxBaseUrl,'',$options['baseUrl']),'/');
            }

            if (!empty($options['baseUrl']) && !empty($value)) {
                $relativeValue = $options['baseUrl'].ltrim($value,'/');
            } else {
                $relativeValue = $value;
            }
            
            $options['openTo'] = $openTo;
        }


        /* Add placehoders for launching the file browser and generating the preview thumbnail */
        
        $browserAction = $this->modx->getObject('modAction',array('namespace'  => 'newspublisher'));
        $browserUrl = $browserAction ? $this->modx->getOption('manager_url',null,MODX_MANAGER_URL).'index.php?a='.$browserAction->get('id') : null;

        if ($browserUrl) {

            $_SESSION['newspublisher']['filebrowser'][$name] = $options;

            $phpthumbUrl = $this->modx->getOption('connectors_url',null,MODX_CONNECTORS_URL) . 'system/phpthumb.php?';
            foreach ($options as $key => $value) {
                $phpthumbUrl .= "&{$key}={$value}";
            }
            
            $browserUrl .= '&field=' . $name;

            $PHs = array(
                '[[+npx.phpthumbBaseUrl]]' => $phpthumbUrl,
                '[[+npx.launchBrowser]]'   => "var popup=window.open('{$browserUrl}', 'select file', 'width=' + Math.min(screen.availWidth,1000) + ',height=' + Math.min(screen.availHeight*0.9,700) + 'resizable=no,status=no,location=no,toolbar=no');popup.focus();browserPathInput=getElementById('np-{$name}');return false;"
            );
            
            $this->modx->toPlaceholder($name, $path, $this->prefix);
            return $this->strReplaceAssoc($PHs, $this->getTpl($tplName));

        } else {
            
            $this->setError($this->modx->lexicon('np_no_action_found'));
            return null;
        }
    }
    
    
    /** Produces the HTML code for boolean (checkbox) fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $checked - (bool) Is the Checkbox activated?
     * @return (string) - field/TV HTML code */

    protected function _displayboolean($name, $checked) {
        $PHs = array('[[+npx.checked]]' => $checked? 'checked="checked"' : '');
        return $this->strReplaceAssoc($PHs, $this->getTpl('boolTpl'));
    }

    protected function _displaycheckbox($name, $checked, $options) {
        return $this->_displaylist($name, $checked, $options, 'checkbox');
    }

    protected function _displaylistbox($name, $selected, $options) {
        return $this->_displaylist($name, $selected, $options, 'listbox');
    }
    
    protected function _displaylistboxmultiple($name, $selected, $options) {
        return $this->_displaylist($name, $selected, $options, 'listbox-multiple');
    }

    protected function _displayoption($name, $selected, $options) {
        return $this->_displaylist($name, $selected, $options, 'radio');
    }

    /** Produces the HTML code for resource list fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $selected - (string|array) 
     * @param $options - (array) Options: 
     * @return (string) - field/TV HTML code */

    protected function _displayresourcelist($name, $selected, $options) {

        /* code adapted from core/model/modx/processors/element/tv/renders/mgr/input/resourcelist.php */

        if (!empty($options['parents']) || $options['parents'] === '0') {
            $parents = explode(',',$options['parents']);
        } elseif (!empty($options['elements'])) {
            $parents = $options['elements'];
        } else {
            return '';
        }
        
        $options['depth'] = !empty($options['depth']) ? $options['depth'] : 10;
        if (empty($parents) || (empty($parents[0]) && $parents[0] !== '0')) { $parents = array($this->modx->getOption('site_start',null,1)); }

        $parentList = array();
        foreach ($parents as $parent) {
            $parent = $this->modx->getObject('modResource',$parent);
            if ($parent) $parentList[] = $parent;
        }

        /* get all children */
        $ids = array();
        foreach ($parentList as $parent) {
            if ($options['includeParent'] != 'false') $ids[] = $parent->get('id');
            $children = $this->modx->getChildIds($parent->get('id'),$options['depth'],array(
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
            if (!empty($options['where'])) {
                $options['where'] = $this->modx->fromJSON($options['where']);
                $c->where($options['where']);
            }
            $c->sortby('Parent.menuindex,modResource.menuindex','ASC');
            if (!empty($options['limit'])) {
                $c->limit($options['limit']);
            }
            $resources = $this->modx->getCollection('modResource',$c);
        }

        /* iterate */
        $options['elements'] = array();
        foreach ($resources as $resource) {
            $id = $resource->get('id');
            $options['elements'][$id] = $resource->get('pagetitle'); //.' ('.$resource->get('id').')',
        }

        /* If the list is empty do not require selecting something */
        if (!$options) $options['allowBlank'] = 'true';
        
        $this->_displaylist($name, $selected, $options, 'listbox');
    }

    /** Produces the HTML code for list fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $selected - (string|array) Selected element (or array of selected elements)
     * @param $options - (array) required: $options['elements'] has to contain an associative array of list entries
     *    in the form array('value' => 'text to display'). 'subtype' is the list type (checkbox, listbox, radio, ...)
     *    Other options are the TV input options 'allowBlank' and 'showBlank'
     * @return (string) - field/TV HTML code */

    protected function _displaylist($name, $selected, $options, $type, $showNone = false) {

        /* listbox/dropdown values cannot be deleted in the frontend. For this case adding an empty option.
         * Doing the same if there are no listoptions and allowBlank is false. Otherwise there would be an unresolvable field error */
        if ($options['showNone'] == 'true' ||  ($type == 'listbox' || $type =='dropdown') && ($options['allowBlank'] == 'true' || !$options['elements'])) {
            if ($showNone) $options['elements'] = array('' => '-') + $options['elements'];
        }
        
        $postfix = ($type == 'checkbox' || $type=='listbox-multiple' || $type=='listbox')? '[]' : '';
        
        $PHs = array('[[+npx.name]]' => $name . $postfix);

        if($type == 'listbox' || $type == 'listbox-multiple' || $type == 'dropdown') {
            $formTpl = $this->getTpl('ListOuterTpl');
            $PHs['[[+npx.multiple]]'] = ($type == 'listbox-multiple')? ' multiple="multiple" ': '';
            $count = count($options['elements']);
            if ($type == 'dropdown') {
                $max = 1;
            } else {
                $max = ($type == 'listbox')? $this->listboxMax : $this->multipleListboxMax;
            }
            $PHs['[[+npx.size]]'] = ($count <= $max)? $count : $max;
        } else {
            $formTpl = $this->getTpl('OptionOuterTpl');
        }

        $PHs['[[+npx.hidden]]'] = ($type == 'checkbox')? '<input type="hidden" name="' . $name . '[]" value="" />' : '';
        $PHs['[[+npx.class]]'] = $type;

        /* Do outer TPl replacements */
        $formTpl = $this->strReplaceAssoc($PHs,$formTpl);

        /* new replace array for options */
        $inner = '';
        $PHs = array('[[+npx.name]]' => $name . $postfix);

    
        /* Set HTML code to use for selected options */
        $selectedCode = ($type == 'radio' || $type == 'checkbox')? 'checked="checked"' : 'selected="selected"';

        if ($type == 'listbox' || $type == 'listbox-multiple' || $type == 'dropdown') {
            $optionTpl = $this->getTpl('ListOptionTpl');
        } else {
            $optionTpl = $this->getTpl('OptionTpl');
            $PHs['[[+npx.class]]'] = $PHs['[[+npx.type]]'] = $type;
        }

        /* loop through options and set selections */
        if (!is_array($selected)) $selected = array($selected);
        $idx = 1;
        foreach ($options['elements'] as $value => $text) {
            $PHs['[[+npx.name]]'] = $name . $postfix;
            $PHs['[[+npx.value]]'] = $value;
            $PHs['[[+npx.idx]]'] = $idx;
            $PHs['[[+npx.selected]]'] = in_array($value, $selected) ? $selectedCode : '';
            $PHs['[[+npx.text]]'] = $text;
            $inner .= $this->strReplaceAssoc($PHs,$optionTpl);
            $idx++;
        }

        return str_replace('[[+npx.options]]',$inner, $formTpl);
    }


    /** Produces the HTML code for textarea fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $content - (string) text/HTML content of the textarea
     * @param $options - (array) Options: subtype (richtext or textarea), rows, columns
     * @return (string) - field/TV HTML code */

    protected function _displaytextarea($name, $content, $options) {
        $PHs = array(
            '[[+npx.rows]]' => $optinos['rows'],
            '[[+npx.cols]]' => $options['columns']
            );
        $PHs['[[+npx.class]]'] = 'np-'.$name;

        $this->modx->toPlaceholder($name, $content, $this->prefix);
        return $this->strReplaceAssoc($PHs, $this->getTpl('TextareaTpl'));
    }



    protected function _displayrichtext($name, $content, $options) {
        $PHs = array(
            '[[+npx.rows]]' => $optinos['rows'],
            '[[+npx.cols]]' => $options['columns']
            );

        if($this->props['initrte']) {
            $PHs['[[+npx.class]]'] = 'modx-richtext';
        } else {
            $msg = $this->modx->lexicon('np_no_rte');
            $this->setError($msg . $field);
            $this->setFieldError($field, $msg);
            $PHs['[[+npx.class]]'] = 'np-'.$name;
        }

        $this->modx->toPlaceholder($name, $content, $this->prefix);
        return $this->strReplaceAssoc($PHs, $this->getTpl('TextareaTpl'));
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
            foreach ($_POST as $k => $v)
                if (!is_array($v)) { /* leave checkboxes, etc. alone */
                    $_POST[$k] = $this->modx->stripTags($v, $allowedTags);
                }
        }

        if (!empty($this->badwords)) {
            foreach ($_POST as $field => $val) {
                if (!is_array($val)) {
                    $_POST[$field] = preg_replace($this->badwords, '[Filtered]', $val); // remove badwords
                }
            }
        }

        $fields = $this->resource->toArray();
        // these fields aren't editable, but have to be set
        $fields = array_merge($fields, array(
            'editedon'    => '0',
            'editedby'    => '0',
            'parent'      => $this->parentId,
            'createdby'   => $this->modx->user->get('id'),
            'context_key' => $this->parentObj->get('context_key'),
            ));

        /* Add fields and TVs to $fields for processor */
        /* processor handles all types */
        $hasTvs = false;
        foreach ($this->fields as $name => $field) {
            if (isset($_POST[$name])) {
                $type = $field->getType();
                if (is_array($_POST[$name]) && $type=='checkbox') {
                    /* get rid of phantom checkbox */
                    unset($_POST[$name][0]);
                }
                $fields[$name] = $_POST[$name];
                if ($type == 'date') {
                    $fields[$name] .= ' ' . $_POST[$name . '_time'];
                }
            } else {
                $fields[$field->getSaveName()] = $field->getValue();
            }
            if (!$hasTvs && get_class($field) == 'NpTV') $fields['tvs'] = true;
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
                    $alias = $this->modx->stripTags($_POST['pagetitle']);
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

            /* clean post array */
            $_POST = array();
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
        if ($resourceGroups == 'parent' || $resourceGroups == 'Parent') {

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


    /** Checks form fields before saving.
     *  Sets an error for the header and another for each
     *  missing required field.
     * */

    public function validate() {
        $errorTpl = $this->getTpl('FieldErrorTpl');
        $success = true;
        if ($this->required) {

            foreach ($this->required as $field) {
                if (empty($_POST[$field])) {
                    $success = false;
                    /* set ph for field error msg */
                    $msg = $this->modx->lexicon('np_error_required');
                    $this->setFieldError($field, $msg);

                    /* set error for header */
                    $msg = $this->modx->lexicon('np_missing_field');
                    $msg = str_replace('[[+name]]', $field, $msg);
                    $msg = str_replace('[[+caption]]', $this->fields[$field]->getCaption(), $msg);
                    $this->setError($msg);
                }
            }
        }

        foreach ($this->show as $name) {
            if (!$this->fields[$name]->validate($_POST[$name])) {
                foreach ($this->fields[$name]->errors as $msg) {
                    $this->setError($msg);
                    $this->setFieldError($msg);
                }
                $_POST[$field] = '';
                /* set fields to empty string */
                $this->modx->toPlaceholder($field, '', $this->prefix);
                $success = false;
            }
        }

        return $success;
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
