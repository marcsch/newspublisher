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
     * @var object current modContext
     */
    protected $workingContext;
    /**
     * @var array scriptProperties array
     */
    protected $props;
    /**
     * @var array Array of all TVs
     */
    protected $allTvs;
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

                /* these fields need to be set in new docs
                 * TODO: not sure where the default system settings should be set. They are only needed if a field is displayed,
                 * otherwise they will be set by the resource/create processor  */
                $this->defaults = array(
                    'editedon'    => '0',
                    'editedby'    => '0',
                    'parent'      => $this->parentId,
                    'createdby'   => $this->modx->user->get('id'),
                    'context_key' => $this->parentObj->get('context_key'),
                    'template'    => (integer) $this->workingContext->getOption('default_template', 0),
                    'hidemenu'    => (integer) $this->workingContext->getOption('hidemenu_default', 0),
                    'richtext'    => (integer) $this->workingContext->getOption('richtext_default', 1),
                    'published'   => (integer) $this->workingContext->getOption('publish_default', 0),
                    'cacheable'   => (integer) $this->workingContext->getOption('cache_default', 1),
                    'searchable'  => (integer) $this->workingContext->getOption('search_default', 1)
                );
                
                /* get the default values (if set) */
                if (isset($this->props['defaults'])) 
                    $this->defaults = array_merge($this->defaults, $this->_parseDefaults($this->props['defaults']));

                $this->template = $this->defaults['template'];

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
        
        foreach (explode(',', $defaultString) as $i => $field_str) {

            list($key, $value) = array_map('trim', explode(':', $field_str, 2));
            
            if ($value == 'Yes') $value = '1';
            else if ($value == 'No') $value = '0';

            switch ($value) {
                case 'parent':
                case 'Parent':
                    //$value = $this->parentObj->get($field);
                    break;

                case 'System Default':
                    // the default value (if there exists any) will be set by the resource/create processor
                    continue 2;
            }

            $defaults[$key] = $value;
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


    /** Creates the HTML for the displayed form by concatenating
     * the necessary Tpls and calling _displayTv() for any TVs.
     *
     * @access public
     * @param (string) $show - comma-separated list of fields and TVs
     * (name or ID) to include in the form
     *
     * @return (string) returns the finished form
     */
    public function displayForm($show) {

        $fields = explode(',',$show);
        $inner = '';

        if (! $this->resource) {
            $this->setError($this->modx->lexicon('np_no_resource'));
            return $this->getTpl('OuterTpl');
        }

        /* get the resource field names */
        $resourceFieldNames = array_keys($this->modx->getFields('modResource'));

        foreach($fields as $field) {
            $field = trim($field);
            
            if (in_array($field,$resourceFieldNames)) {
                /* regular resource field */
                $inner .= $this->_displayField($field);
              
            } else {
                /* see if it's a TV */
                $retVal = $this->_displayTv($field);
                if ($retVal) {
                    $inner .= "\n" . $retVal;
                }
            }
        }
        $formTpl = str_replace('[[+npx.insert]]',$inner,$this->getTpl('OuterTpl'));
        //die ('<pre>' . print_r($formTpl,true));
        // die ('$_POST<br /><pre>' . print_r($_POST,true));
        return $formTpl;
    } /* end displayForm */



    /** displays an individual field
     * @access protected
     * @param $field (string) name of the field
     * @return (string) returns the HTML code for the field.
     */

    protected function _displayField($field) {

        /* Get the field value */
        if ($this->isPostBack) {
             $value = $_POST[$field];

        } else {
                        
            if (isset($this->defaults[$field])) {
                $value = $this->defaults[$field];
                
            } else {
                $value = $this->resource->get($field);
                if (strstr($value, '[[') && ! $this->modx->hasPermission('allow_modx_tags')) {
                    $this->setError($this->modx->lexicon('np_no_modx_tags'));
                    return null;
                }
            }
        }
        $value = str_replace(array('[',']'),array('&#91;','&#93;'),$value);
        $this->modx->toPlaceholder($field, $value, $this->prefix);

        $replace = array();
        $inner = '';
        $replace['[[+npx.help]]'] = $this->props['hoverhelp'] ? '[[%resource_' . $field . '_help:notags]]' : '';
        $replace['[[+npx.caption]]'] = '[[%resource_' . $field . ']]';
        $fieldType = $this->resource->_fieldMeta[$field]['phptype'];

        if ($field == 'id') {
            $replace['[[+npx.readonly]]'] = 'readonly="readonly"';
        } elseif ($this->props['readonly']) {
            $readOnlyArray = explode(',', $this->props['readonly']);
            if (in_array($field, $readOnlyArray)) {
                $replace['[[+npx.readonly]]'] = 'readonly="readonly"';
            }
            unset($readOnlyArray);
        }

        $replace['[[+npx.fieldName]]'] = $field ;


        /* do content and introtext fields */
        switch ($field) {
            case 'content':
                /* adjust content field type according to class_key */
                if ($this->existing) $class_key = $this->resource->get('class_key');
                else $class_key = isset($_POST['class_key']) ? $_POST['class_key'] : (isset($this->defaults['class_key'])? $this->defaults['class_key'] : 'modDocument');

                switch ($class_key) {
                    case 'modDocument':
                        $rows =  ! empty($this->props['contentrows'])? $this->props['contentrows'] : '10';
                        $cols =  ! empty($this->props['contentcols'])? $this->props['contentcols'] : '60';
                        $inner .= $this->_displayTextarea($field, $this->props['rtcontent'], 'np-content', $rows, $cols);
                        break;
                    
                    case 'modWebLink':
                    case 'modSymLink':
                        $class_key = strtolower(substr($class_key, 3));
                        $replace['[[+npx.caption]]'] = $this->modx->lexicon($class_key);
                        $replace['[[+npx.help]]'] = $this->modx->lexicon($class_key.'_help');
                        $inner .= $this->_displaySimple($field, 'TextTpl', $this->textMaxlength);
                        break;

                    case 'modStaticResource':
                        $replace['[[+npx.caption]]'] = $this->modx->lexicon('staticresource');
                        $inner .= $this->_displayFileInput($field, 'fileTpl');
                }
                break;

            case 'introtext':
                $rows =  ! empty($this->props['summaryrows'])? $this->props['summaryrows'] : '10';
                $cols =  ! empty($this->props['summarycols'])? $this->props['summarycols'] : '60';
                $inner .= $this->_displayTextarea($field, $this->props['rtsummary'], 'np-introtext', $rows, $cols);
                break;

            case 'template':
                $options = array();
                $templates = $this->modx->getCollection('modTemplate');
                foreach ($templates as $template) {
                    if ($template->checkPolicy('list')) {
                        $options[$template->get('id')] = $template->get('templatename');
                    }
                }
                $inner .= $this->_displayList($field, 'listbox', $options, $value);
                break;

            case 'class_key':
                $options = array();
                $classes = array('modDocument' => 'document', 'modSymLink' => 'symlink', 'modWebLink' => 'weblink', 'modStaticResource' => 'static_resource');
                foreach ($classes as $k => $v) $options[$k] = $this->modx->lexicon($v);
                $inner .= $this->_displayList($field, 'listbox', $options, $this->resource->get('class_key'));
                break;
                
            case 'content_dispo':
                $options = array();
                $dispo = array('inline', 'attachment');
                foreach ($dispo as $k => $v) $options[$k] = $this->modx->lexicon($v);
                $inner .= $this->_displayList($field, 'listbox', $options, $value);
                break;

            case 'uri_override': /* correct schema errors */
            case 'hidemenu':
                $fieldType = 'boolean';
                
            default:
                switch($fieldType) {
                    case 'string':
                    default:
                        $inner .= $this->_displaySimple($field, 'TextTpl', $this->textMaxlength);
                        break;

                    case 'boolean':
                        $inner .= $this->_displayBoolean($field, $value);
                        break;

                    case 'integer':
                        $inner .= $this->_displaySimple($field, 'IntTpl', $this->intMaxlength);
                        break;

                    case 'timestamp':
                        $inner .= $this->_displayDateInput($field, $value);
                        break;
                }
        }

        $inner = $this->strReplaceAssoc($replace, $inner);
        
        return $inner;
    }
    

    /** displays an individual TV
     *
     * @access protected
     * @param $tvNameOrId (string) name or ID of TV to process.
     *
     * @return (string) returns the HTML code for the TV.
     */

    protected function _displayTv($tvNameOrId) {

        if (is_numeric($tvNameOrId)) {
           $tvObj = $this->modx->getObject('modTemplateVar',$tvNameOrId);
        } else {
           $tvObj = $this->modx->getObject('modTemplateVar',array('name' => $tvNameOrId));
        }
        if (empty($tvObj)) {
            $this->setError($this->modx->lexicon('np_no_tv') . $tvNameOrId);
            return null;
        } else {
            /* make sure requested TV is attached to this template*/
            $tvId = $tvObj->get('id');
            $found = $this->modx->getCount('modTemplateVarTemplate', array('templateid' => $this->template, 'tmplvarid' => $tvId));
            if (! $found) {
                $this->setError($this->modx->lexicon('np_not_our_tv') . ' Template: ' . $this->template . '  ----    TV: ' . $tvNameOrId);
                return null;
            } else {
                $this->allTvs[] = $tvObj;
            }
        }


    /* we have a TV to show */
    /* Build TV template dynamically based on type */

        $formTpl = '';
        $tv = $tvObj;

        $fields = $tv->toArray();
        $name = $fields['name'];

        $params = $tv->get('input_properties');
        /* use TV's name as caption if caption is empty */
        $caption = empty($fields['caption'])? $name : $fields['caption'];

        /* Build TV input code dynamically based on type */
        $tvType = $tv->get('type');
        $tvType = $tvType == 'option'? 'radio' : $tvType;

        /* set TV to current value or default if not postBack */
        if ($this->isPostBack ) {
            $value = $_POST[$name];
            
        } else {
            if (isset($this->defaults[$name])) {
                $value = $this->defaults[$name];
                
            } else {
                $value = '';
                if ($this->existing) {
                    $value = $tv->getValue($this->existing);
                }
                /* empty value gets default_text for both new and existing docs */
                if (empty($value)) {
                    $value = $fields['default_text'];
                }
                if (strstr($value, '[[') && ! $this->modx->hasPermission('allow_modx_tags')) {
                    $this->setError($this->modx->lexicon('np_no_modx_tags'));
                    return null;
                }
            }
            if (stristr($value,'@EVAL') || stristr($_POST[$name.'_time'], '@eval')) {
                $this->setError($this->modx->lexicon('np_no_evals'). $name);
                return null;
            }
        }

        $value = str_replace(array('[',']'),array('&#91;','&#93;'),$value);
        $this->modx->toPlaceholder($name, $value, $this->prefix);

        $replace = array();
        $replace['[[+npx.help]]'] = $this->props['hoverhelp'] ? $fields['description'] :'';
        $replace['[[+npx.caption]]'] = $caption;
        $replace['[[+npx.fieldName]]'] = $name;

        switch ($tvType) {
            case 'date':
                $formTpl .= $this->_displayDateInput($name, $value, $params);
                break;

            default:
            case 'text':
            case 'textbox':
            case 'email';
                $formTpl .= $this->_displaySimple($name, 'TextTpl', $this->textMaxlength);
                break;

            case 'number':
                $formTpl .= $this->_displaySimple($name, 'IntTpl', $this->intMaxlength);
                break;

            case 'textarea':
            case 'textareamini':
                $formTpl .= $this->_displayTextarea($name, false, $tvType);
                break;

            case 'richtext':
                $formTpl .= $this->_displayTextarea($name, true, 'textarea');

                break;


            case 'radio':
            case 'checkbox':
            case 'listbox':
            case 'listbox-multiple':
            case 'dropdown':
            
                /* handle @ binding TVs */
                if (preg_match('/^@/',$fields['elements'])) {
                    $fields['elements'] = $tv->processBindings($fields['elements']);
                }
                $elements = explode('||',$fields['elements']);

                /* parse options */
                $options = array();
                foreach ($elements as $option) {
                    $text = strtok($option,'=');
                    $option = strtok('=');
                    $option = $option? $option : $text;
                    $options[$option] = $text;
                }

                /* selected entries */
                $selected = is_array($value)? $value : explode('||', $value);

                /* render HTML */
                $formTpl .= $this->_displayList($name, $tvType, $options, $selected,
                                                $params['allowBlank']=='true' && ($tvType=='listbox' || $tvType=='dropdown'));
                break;

            case 'resourcelist':

                /* code adapted from core/model/modx/processors/element/tv/renders/mgr/input/resourcelist.php */

                $parents = $tv->get('elements');
                $bindingsResult = $tv->processBindings($tv->get('elements'), $this->modx->resource->get('id'));
                $parents = $tv->parseInputOptions($bindingsResult);
                $parents = !empty($params['parents']) || $params['parents'] === '0' ? explode(',',$params['parents']) : $parents;
                $params['depth'] = !empty($params['depth']) ? $params['depth'] : 10;
                if (empty($parents) || (empty($parents[0]) && $parents[0] !== '0')) { $parents = array($this->modx->getOption('site_start',null,1)); }

                $parentList = array();
                foreach ($parents as $parent) {
                    $parent = $this->modx->getObject('modResource',$parent);
                    if ($parent) $parentList[] = $parent;
                }

                /* get all children */
                $ids = array();
                foreach ($parentList as $parent) {
                    if ($params['includeParent'] != 'false') $ids[] = $parent->get('id');
                    $children = $this->modx->getChildIds($parent->get('id'),$params['depth'],array(
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
                    if (!empty($params['where'])) {
                        $params['where'] = $this->modx->fromJSON($params['where']);
                        $c->where($params['where']);
                    }
                    $c->sortby('Parent.menuindex,modResource.menuindex','ASC');
                    if (!empty($params['limit'])) {
                        $c->limit($params['limit']);
                    }
                    $resources = $this->modx->getCollection('modResource',$c);
                }

                /* iterate */
                $options = array();
                foreach ($resources as $resource) {
                    $id = $resource->get('id');
                    $options[$id] = $resource->get('pagetitle'); //.' ('.$resource->get('id').')',
                    if ($id == $value) $selected[] = $id;
                }

                /* If the list is empty do not require selecting something */
                if (!$options) $params['allowBlank'] = 'true';
                $formTpl .= $this->_displayList($name, 'listbox', $options, $selected, $params['showNone']!='false');
                break;

                
            case 'image':
            case 'file':
                            
                /* code adapted from core/model/modx/processors/element/tv/renders/mgr/input/file.php
                 * and (...)/image.php */

                $this->modx->getService('fileHandler','modFileHandler', '', array('context' => $this->context));
                $params['wctx'] = $this->context; // not sure if this is important, doesn't seem to have an effect
                $openTo = '';

                if (method_exists($tv, 'getSource')) { /* MODx version is 2.20 or higher */
                    
                    $source = $tv->getSource($this->context);
                    if (!$source) {
                        $this->setError($this->modx->lexicon('np_no_media_source') . $name);
                        return null;
                    }
                    if (!$source->getWorkingContext()) {
                        $this->setError($this->modx->lexicon('np_source_wctx_error') . $name);
                    }
                    $source->initialize();
                    $params['source'] = $source->get('id');
                    
                    if (!$source->checkPolicy('view')) {
                        $this->setError($this->modx->lexicon('np_media_source_access_denied') . $name);
                        return null;
                    }

                    if (!empty($value)) {
                        $openTo = $source->getOpenTo($value,$params);
                    }
                    $tv->set('relativeValue',$value);

                } else { /* MODx versions below 2.20 */

                    $this->modx->fileHandler->context =& $this->workingContext;


                    /* get base path based on either TV param or filemanager_path */
                    $replacePaths = array(
                        '[[++base_path]]' => $this->workingContext->getOption('base_path',null,MODX_BASE_PATH),
                        '[[++core_path]]' => $this->workingContext->getOption('core_path',null,MODX_CORE_PATH),
                        '[[++manager_path]]' => $this->workingContext->getOption('manager_path',null,MODX_MANAGER_PATH),
                        '[[++assets_path]]' => $this->workingContext->getOption('assets_path',null,MODX_ASSETS_PATH),
                        '[[++base_url]]' => $this->workingContext->getOption('base_url',null,MODX_BASE_URL),
                        '[[++manager_url]]' => $this->workingContext->getOption('manager_url',null,MODX_MANAGER_URL),
                        '[[++assets_url]]' => $this->workingContext->getOption('assets_url',null,MODX_ASSETS_URL),
                    );
                    $replaceKeys = array_keys($replacePaths);
                    $replaceValues = array_values($replacePaths);

                    if (empty($params['basePath'])) {
                        $params['basePath'] = $this->modx->fileHandler->getBasePath();
                        $params['basePath'] = str_replace($replaceKeys,$replaceValues,$params['basePath']);
                        $params['basePathRelative'] = $this->workingContext->getOption('filemanager_path_relative',true) ? 1 : 0;
                    } else {
                        $params['basePath'] = str_replace($replaceKeys,$replaceValues,$params['basePath']);
                        $params['basePathRelative'] = !isset($params['basePathRelative']) || in_array($params['basePathRelative'],array('true',1,'1'));
                    }
                    if (empty($params['baseUrl'])) {
                        $params['baseUrl'] = $this->modx->fileHandler->getBaseUrl();
                        $params['baseUrl'] = str_replace($replaceKeys,$replaceValues,$params['baseUrl']);
                        $params['baseUrlRelative'] = $this->workingContext->getOption('filemanager_url_relative',true) ? 1 : 0;
                    } else {
                        $params['baseUrl'] = str_replace($replaceKeys,$replaceValues,$params['baseUrl']);
                        $params['baseUrlRelative'] = !isset($params['baseUrlRelative']) || in_array($params['baseUrlRelative'],array('true',1,'1'));
                    }
                    $modxBasePath = $this->modx->getOption('base_path',null,MODX_BASE_PATH);
                    if ($params['basePathRelative'] && $modxBasePath != '/') {
                        $params['basePath'] = ltrim(str_replace($modxBasePath,'',$params['basePath']),'/');
                    }
                    $modxBaseUrl = $this->modx->getOption('base_url',null,MODX_BASE_URL);
                    if ($params['baseUrlRelative'] && $modxBaseUrl != '/') {
                        $params['baseUrl'] = ltrim(str_replace($modxBaseUrl,'',$params['baseUrl']),'/');
                    }

                    if (!empty($params['baseUrl']) && !empty($value)) {
                        $relativeValue = $params['baseUrl'].ltrim($value,'/');
                    } else {
                        $relativeValue = $value;
                    }
                    if (!empty($value) && strpos($value,'/') !== false) {
                        $openTo = pathinfo($value,PATHINFO_DIRNAME);
                        $openTo = rtrim($openTo,'/').'/';
                    }
                }

                $formTpl .= $this->_displayFileInput($name, $tvType.'Tpl', $params, $openTo);
                break;
                
        }  /* end switch */
        
        $formTpl = $this->strReplaceAssoc($replace, $formTpl);

        /* Add TV to required fields if blank values are not allowed */
        if ($params['allowBlank'] == 'false') $this->props['required'] .= ',' . $name;
        
        return $formTpl;
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
     * @param $timestring - (string) date-time string in the format "2011-04-01 13:20:01"
     * @ param $options - (array) Associative array of options. Accepts 'disabledDates', 'disabledDays', 'minDateValue' and 'maxDateValue' (in the format used for the corresponding TV input options)
     * @return (string) - date field/TV HTML code */
    
    protected function _displayDateInput($name, $timeString, $options = array()) {

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
    /** Produces the HTML code for simple text fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $tplName - (string) name of the template chunk that should be used
     * @param $maxLength - (int) Max length for the input field (in characters)
     * @return (string) - field/TV HTML code */

    protected function _displaySimple($name, $tplName, $maxLength = 10) {
        $PHs = array('[[+npx.maxlength]]' => $maxLength);
        return $this->strReplaceAssoc($PHs, $this->getTpl($tplName));
    }


    /** Produces the HTML code for file/image TVs
     * 
     * @access protected
     * @param $name - (string) name of the TV
     * @param $tplName - (string) name of the template chunk that should be used
     * @param $sourceOptions - (array) Associative array of options. Accepts all file/image TV input options.
     *       Possible options: all (processed) TV input options (Revo versions below 2.20), respectively the media source.
     *       'wctx' doesn't seem to have an effect (?)
     * @param $openTo - (string) Path for the directory to open to
     * @return (string) - HTML code */

    protected function _displayFileInput($name, $tplName, $sourceOptions = array(), $openTo = '') {

        $browserAction = $this->modx->getObject('modAction',array('namespace'  => 'newspublisher'));
        $browserUrl = $browserAction ? $this->modx->getOption('manager_url',null,MODX_MANAGER_URL).'index.php?a='.$browserAction->get('id') : null;

        if ($browserUrl) {

            $phpthumbUrl = $this->modx->getOption('connectors_url',null,MODX_CONNECTORS_URL) . 'system/phpthumb.php?';
            foreach ($sourceOptions as $key => $value) {
                $phpthumbUrl .= "&{$key}={$value}";
            }

            $browserUrl .= '&field=' . $name;
            $sourceOptions['openTo'] = $openTo;
            $_SESSION['newspublisher']['filebrowser'][$name] = $sourceOptions;

             $PHs = array(
                '[[+npx.phpthumbBaseUrl]]' => $phpthumbUrl,
                '[[+npx.launchBrowser]]'   => "var popup=window.open('{$browserUrl}', 'select file', 'width=' + Math.min(screen.availWidth,1000) + ',height=' + Math.min(screen.availHeight*0.9,700) + 'resizable=no,status=no,location=no,toolbar=no');popup.focus();browserPathInput=getElementById('np-{$name}');return false;"
            );
            
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
     * @param $checked - (bool) Is the Checkbox activated?  (ignored on postback)
     * @return (string) - field/TV HTML code */

    protected function _displayBoolean($name, $checked) {
        if ($this->isPostBack) {
            $checked = $_POST[$name];
        }
        $PHs = array('[[+npx.checked]]' => $checked? 'checked="checked"' : '');
        
        return $this->strReplaceAssoc($PHs, $this->getTpl('boolTpl'));
    }

    
    /** Produces the HTML code for list fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $options - (array) associative array of list entries in the form array('value' => 'text to display').
     * @param $selected - (mixed) List entry or array of (mutiple) list entries ($options values) that are currently selected
     *                            (this option is ignored on postback)
     * @param $showNone - (bool) If true, the first option will be 'empty' (represented by a '-')
     * @return (string) - field/TV HTML code */

    protected function _displayList($name, $type, $options, $selected = null, $showNone = false) {

        if ($showNone) $options = array('' => '-') + $options;

        $postfix = ($type == 'checkbox' || $type=='listbox-multiple' || $type=='listbox')? '[]' : '';
        
        $PHs = array('[[+npx.name]]' => $name . $postfix);

        if($type == 'listbox' || $type == 'listbox-multiple' || $type == 'dropdown') {
            $formTpl = $this->getTpl('ListOuterTpl');
            $PHs['[[+npx.multiple]]'] = ($type == 'listbox-multiple')? ' multiple="multiple" ': '';
            $count = count($options);
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

        // if postback -> use selection from $_POST
        if ($this->isPostBack) $selected = $_POST[$name];
        if (!is_array($selected)) $selected = array($selected);

        /* Set HTML code to use for selected options */
        $selectedCode = ($type == 'radio' || $type == 'checkbox')? 'checked="checked"' : 'selected="selected"';

        if ($type == 'listbox' || $type =='listbox-multiple' || $type == 'dropdown') {
            $optionTpl = $this->getTpl('ListOptionTpl');
        } else {
            $optionTpl = $this->getTpl('OptionTpl');
            $PHs['[[+npx.class]]'] = $PHs['[[+npx.type]]'] = $type;
        }

        /* loop through options and set selections */
        $idx = 1;
        foreach ($options as $value => $text) {
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
     * @param $RichText - (bool) Is this a Richtext field?
     * @param $noRTE_class - (string) class name for non-Richtext textareas
     * @param $rows - (int) number of rows in the textarea
     * @param $columns - (int) width (number of columns) of the textarea
     * @return (string) - field/TV HTML code */

    protected function _displayTextarea($name, $RichText, $noRTE_class, $rows = 20, $columns = 60) {
        $PHs = array(
            '[[+npx.rows]]' => $rows,
            '[[+npx.cols]]' => $columns
            );

        if ($RichText) {
            if($this->props['initrte']) {
                $PHs['[[+npx.class]]'] = 'modx-richtext';
            } else {
                $msg = $this->modx->lexicon('np_no_rte');
                $this->setError($msg . $field);
                $this->setFieldError($field, $msg);
                $PHs['[[+npx.class]]'] = $noRTE_class;
            }
        } else {
            $PHs['[[+npx.class]]'] = $noRTE_class;
        }
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

        /* correct timestamp resource fields */
        foreach ($_POST as $field => $val) {
            if ($this->resource->_fieldMeta[$field]['phptype'] == 'timestamp') {
                if (empty($_POST[$field])) {
                    unset($_POST[$field]);
                } else {
                    $_POST[$field] = $val . ' ' . $_POST[$field . '_time'];
                }
            }
        }
        
        $fields = $this->existing
            ? array_merge($this->resource->toArray(), $_POST)
            : array_merge($this->defaults, $_POST);
                     
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

        /* Add TVs to $fields for processor */
        /* e.g. $fields[tv13] = $_POST['MyTv5'] */
        /* processor handles all types */

        if (!empty($this->allTvs)) {
            /* *********************************************
             * Deal with bug in resource update processor. *
             * Set $fields to current TV value for all TVs *
             * This section can be removed when it's fixed *
             ********************************************* */
            if ($this->existing) {
                $t_resourceTVs = $this->resource->getMany('TemplateVars');
                $t_resourceId = $this->resource->get('id');
                foreach ($t_resourceTVs as $t_tv) {
                    $t_tvId = $t_tv->get('id');
                    $t_value = $t_tv->getValue($t_resourceId);
                    $fields['tv' . $t_tvId] = $t_value;
                }
                unset($t_resourceTVs,$t_resourceId,$t_tvId,$t_value);
            }



            /* ****************************************** */
            $fields['tvs'] = true;
            foreach ($this->allTvs as $tv) {
                $name = $tv->get('name');
                
                if ($tv->get('type') == 'date') {
                    $fields['tv' . $tv->get('id')] = $_POST[$name] . ' ' . $_POST[$name . '_time'];
                } else {
                    if (is_array($_POST[$name])) {
                        /* get rid of phantom checkbox */
                        if ($tv->get('type')=='checkbox') {
                            unset($_POST[$name][0]);
                        }
                    }
                    $fields['tv' . $tv->get('id')] = $_POST[$name];
                }
            }
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
        $fields = explode(',', $this->props['required']);
        if (!empty($fields)) {

            foreach ($fields as $field) {
                if (empty($_POST[$field])) {
                    $success = false;
                    /* set ph for field error msg */
                    $msg = $this->modx->lexicon('np_error_required');
                    $this->setFieldError($field, $msg);
                    /*$msg = str_replace('[[+name]]', $field, $msg);
                    $msg = str_replace("[[+{$this->prefix}.error]]", $msg, $errorTpl);
                    $ph = 'error_' . $field;
                    $this->modx->toPlaceholder($ph, $msg, $this->prefix);*/

                    /* set error for header */
                    $msg = $this->modx->lexicon('np_missing_field');
                    $msg = str_replace('[[+name]]', $field, $msg);
                    $this->setError($msg);

                }
            }
        }

        $fields = explode(',', $this->props['show']);
        foreach ($fields as $field) {
            $field = trim($field);
        }

        foreach ($fields as $field) {
            $value = $_POST[$field];
            if (is_array($value)) $value = implode($value, '');
            if (stristr($value, '@EVAL')) {
                $this->setError($this->modx->lexicon('np_no_evals_input'));
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
