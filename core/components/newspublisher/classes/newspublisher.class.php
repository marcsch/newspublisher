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
 * @version Version 1.2.0-rc2
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
     * @var array Array of names of fields/TVs to show in the form
     */
    protected $fieldsToShow;
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
     * @var array Keys are fields which (can) have a default system setting in MODx.
     *  The values are the names of the system setting.
     */
    protected $systemDefaultNames =  array( 
                    'published'  => 'publish_default',
                    'hidemenu'   => 'hidemenu_default',
                    'cacheable'  => 'cache_default',
                    'searchable' => 'search_default',
                    'richtext'   => 'richtext_default',
                    'template'   => 'default_template'
                  );
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


    /** gets the resource ID or null if it is a new resource
     *
     * @access public
     * @return (int) resource ID
     */

    public function getResourceId() {
        return $this->existing;
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


            /* Array of fields to show in form */
            $this->fieldsToShow = explode(',', $this->props['show']);
            
            /* see if it's a repost */
            $this->setPostback(isset($_POST['hidSubmit']) && $_POST['hidSubmit'] == 'true');

            $stuff = ''; // POST stuff (placeholders)
	  
            if($this->existing) {

                $this->resource = $this->modx->getObject('modResource', $this->existing);
                if ($this->resource) {

                    if (!$this->modx->hasPermission('view_document') || !$this->resource->checkPolicy('view') ) {
                        $this->setError($this->modx->lexicon('np_view_permission_denied'));
                    }
                    if ($this->isPostBack) {
                        /* str_replace to prevent rendering of placeholders */
                         $fs = array();
                         foreach($_POST as $k=>$v) {
                             $fs[$k] = str_replace(array('[',']'),array('&#91;','&#93;'),$v);
                         }
                        $this->modx->toPlaceholders($fs,$this->prefix);


                    } else {
                        $ph = $this->resource->toArray();
                        $tags = false;
                        foreach($ph as $k=>$v) {
                            if (strstr($v, '[[')) {
                                $tags = true;
                            }
                            if ($tags && ! $this->modx->hasPermission('allow_modx_tags')) {
                                $this->setError($this->modx->lexicon('np_no_modx_tags'));
                                return;
                            }
                            $fs[$k] = str_replace(array('[',']'),array('&#91;','&#93;'),$v);
                        }
                        $ph = $fs;
                        $this->modx->toPlaceholders($ph,$this->prefix);
                        unset($ph);
                    }
                } else {
                   $this->setError($this->modx->lexicon('np_no_resource') . $this->existing);
                   return;

                }
                /* need to forward this from $_POST so we know it's an existing doc */
                $stuff = '<input type="hidden" name="np_existing" value="true" />' . "\n" .
                '<input type="hidden" name="np_doc_id" value="' . $this->resource->get('id') . '" />';

            } else {
              
                /* new document */
                if (!$this->modx->hasPermission('new_document')) {
                    $this->setError($this->modx->lexicon('np_create_permission_denied'));
                }
                $this->resource = $this->modx->newObject('modResource');
                
                /* get folder id and resource object where we should store articles
                 * else store under current document */
                // allow 'parent' parameter to be used as well as synonym for 'parentid'
                if (!empty($this->props['parentid'])) $this->props['parent'] = $this->props['parentid'];
                $parentId = !empty($this->props['parent']) ? intval($this->props['parent']):$this->modx->resource->get('id');
                $this->parentObj = $this->modx->getObject('modResource',$parentId);
                if (! $this->parentObj) {
                    $this->setError('&amp;' .$this->modx->lexicon('np_no_parent'));
                    return $retVal;
                }

                /* str_replace to prevent rendering of placeholders */
                $fs = array();
                foreach($_POST as $k=>$v) {
                    $fs[$k] = str_replace(array('[',']'),array('&#91;','&#93;'),$v);
                }
                $this->modx->toPlaceholders($fs,$this->prefix);

                /* Set initial values for fields  */
                $validFields = $this->modx->getFields('modResource');
                $fieldValues = array_intersect_key(array_merge($this->props, $_POST),  $validFields);

                if (!isset ($fieldValues['parent'])) $fieldValues['parent'] = $parentId;
                $fieldValues['createdby'] = $this->modx->user->get('id');
                $fieldValues['context_key'] = $this->parentObj->get('context_key');

                foreach($fieldValues as $field => $value) {
                      $value = isset($_POST[$field]) ? $_POST[$field] : $this->_setDefault($field, $value);
                      $this->resource->set($field, $value);
                      
                      /* Make sure the value appears in $_POST */
                      if (!in_array($field, $this->fieldsToShow)) {
                          $stuff .= '<input type="hidden" name="' . $field . '" value="' . $value . '" />'. "\n";
                      }

                }

                  /* Get resource groups (JSON encoded array) if 'groups' parameter was set */
                if (! empty($this->props['groups'])) {
                    $this->groups = $this->_setGroups($this->props['groups']);
                }

                $this->header = !empty($this->props['headertpl']) ? $this->modx->getChunk($this->props['headertpl']) : '';
                $this->footer = !empty($this->props['footertpl']) ? $this->modx->getChunk($this->props['footertpl']):'';
                $this->aliasTitle = $this->props['aliastitle']? true : false;
                $this->clearcache = isset($_POST['clearcache'])? $_POST['clearcache'] : $this->props['clearcache'] ? true: false;
                $this->intMaxlength = !empty($this->props['intmaxlength'])? $this->props['intmaxlength'] : 10;
                $this->textMaxlength = !empty($this->props['textmaxlength'])? $this->props['textmaxlength'] : 60;

            } /* end new document */
            

            $this->modx->toPlaceholder('post_stuff',$stuff,$this->prefix);

            if ($this->isPostBack) {
                /* str_replace to prevent rendering of placeholders */
                 $fs = array();
                 foreach($_POST as $k=>$v) {
                     $fs[$k] = str_replace(array('[',']'),array('&#91;','&#93;'),$v);
                 }
                $this->modx->toPlaceholders($fs,$this->prefix);


            } else {
                $ph = $this->resource->toArray();
                $tags = false;
                foreach($ph as $k=>$v) {
                    if (strstr($v, '[[')) {
                        $tags = true;
                    }
                    if ($tags && ! $this->modx->hasPermission('allow_modx_tags')) {
                        $this->setError($this->modx->lexicon('np_no_modx_tags'));
                        return;
                    }
                    $fs[$k] = str_replace(array('[',']'),array('&#91;','&#93;'),$v);
                }
                $ph = $fs;
                $this->modx->toPlaceholders($ph,$this->prefix);
                unset($ph);
            }
            
            if( !empty($this->props['badwords'])) {
                 $this->badwords = str_replace(' ','', $this->props['badwords']);
                 $this->badwords = "/".str_replace(',','|', $this->badwords)."/i";
             }

           $this->modx->lexicon->load('core:resource');
           if($this->props['initdatepicker']) {
                $this->modx->regClientCSS($this->assetsUrl . 'datepicker/css/datepicker.css');
                $this->modx->sjscripts[] = '<script type=text/javascript src="' . $this->assetsUrl . 'datepicker/js/datepicker.packed.js">{"lang":"' . $language . '"}</script>';
           }

           $this->listboxMax = $this->props['listboxmax']? $this->props['listboxmax'] : 8;
           $this->MultipleListboxMax = $this->props['multiplelistboxmax']? $this->props['multiplelistboxmax'] : 8;


           $ph = ! empty($this->props['contentrows'])? $this->props['contentrows'] : '10';
           $this->modx->toPlaceholder('contentrows',$ph,$this->prefix);

           $ph = ! empty($this->props['contentcols'])? $this->props['contentcols'] : '60';
           $this->modx->toPlaceholder('contentcols',$ph, $this->prefix);

           $ph = ! empty($this->props['summaryrows'])? $this->props['summaryrows'] : '10';
           $this->modx->toPlaceholder('summaryrows',$ph, $this->prefix);

           $ph = ! empty($this->props['summarycols'])? $this->props['summarycols'] : '60';
           $this->modx->toPlaceholder('summarycols',$ph, $this->prefix);

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

    /** Sets default values for any resource field (if sent).
     *
     * @access protected
     *
     * @param (string) $field - name of resource field
     * @param (string) $value  - value of resource field (or null if there is none)
     *
     * @return (mixed) returns boolean option, JSON string for
     * groups, and null on failure
     */

    protected function _setDefault($field, $value) {

        $retVal = null;
        $value = (string) $value; // convert booleans
        $value = $value == 'Yes'? '1': $value;
        $value = $value == 'No' ? '0' :$value;

        switch ($value) {

            case 'parent':
            case 'Parent':
                $retVal = $this->parentObj->get($field);
                break;

            case 'System Default':
                $retVal = $this->systemDefaultNames[$field];
                if ($retVal) {
                    $retVal = $this->modx->getOption($retVal);
                }
                if (!isset($retVal)) { // either not in systemDefaultNames or setting is not present
                    //$this->setError($this->modx->lexicon('np_unknown_field') . $field);
                    $this->setError($this->modx->lexicon('np_no_system_setting') . $field);
                    return;
                }
                break;
                
            default:            
                $retVal = ($field == 'template') ? $this->_getTemplate($value) : $value;

        }
        
        if ($retVal === null) {
            $this->setError($this->modx->lexicon('np_illegal_value') . $field . ': ' . $value . $this->modx->lexicon('np_no_permission') );
        }
        return $retVal;
    }

    /** Sets the array of Tpl strings use to create the form.
     *  Attempts to get chunks of names are send as parameters,
     *  used defaults if not.
     *
     *  @access public
     *
     *  @return (bool) true on success, false if a non-empty tpl property
     *  is send and it fails to find the named chunk.
     */

    public function getTpls() {
            $this->tpls = array();

            /* this is the outer Tpl for the whole page */
        $this->tpls['outerTpl'] = !empty ($this->props['outertpl'])? $this->modx->getChunk($this->props['outertpl']): $this->modx->getChunk('npOuterTpl');
        $this->tpls['textTpl'] = ! empty ($this->props['texttpl'])? $this->modx->getChunk($this->props['texttpl']) : $this->modx->getChunk('npTextTpl');
        $this->tpls['intTpl'] = ! empty ($this->props['inttpl'])? $this->modx->getChunk($this->props['inttpl']) : $this->modx->getChunk('npIntTpl');
        $this->tpls['dateTpl'] = ! empty ($this->props['datetpl'])? $this->modx->getChunk($this->props['datetpl']) : $this->modx->getChunk('npDateTpl');
        $this->tpls['boolTpl'] = ! empty ($this->props['booltpl'])? $this->modx->getChunk($this->props['booltpl']) : $this->modx->getChunk('npBoolTpl');
        $this->tpls['textareaTpl'] = ! empty ($this->props['textareatvtpl'])? $this->modx->getChunk($this->props['textareatvtpl']) : $this->modx->getChunk('npTextareaTpl');
        $this->tpls['imageTpl'] = ! empty ($this->props['imagetpl'])? $this->modx->getChunk($this->props['imagetpl']) : $this->modx->getChunk('npImageTpl');
        $this->tpls['fileTpl'] = ! empty ($this->props['filetpl'])? $this->modx->getChunk($this->props['filetpl']) : $this->modx->getChunk('npFileTpl');
        $this->tpls['optionOuterTpl'] = ! empty ($this->props['optionoutertpl'])? $this->modx->getChunk($this->props['optionoutertpl']) : $this->modx->getChunk('npOptionOuterTpl');
        $this->tpls['listOuterTpl'] = ! empty ($this->props['listoutertpl'])? $this->modx->getChunk($this->props['listoutertpl']) : $this->modx->getChunk('npListOuterTpl');
        $this->tpls['optionTpl'] = ! empty ($this->props['optiontpl'])? $this->modx->getChunk($this->props['optiontpl']) : $this->modx->getChunk('npOptionTpl');
        $this->tpls['listOptionTpl'] = ! empty ($this->props['listoptiontpl'])? $this->modx->getChunk($this->props['listoptiontpl']) : $this->modx->getChunk('npListOptionTpl');
        $this->tpls['errorTpl'] = ! empty ($this->props['errortpl'])? $this->modx->getChunk($this->props['errortpl']) : $this->modx->getChunk('npErrorTpl');
        $this->tpls['fieldErrorTpl'] = ! empty ($this->props['fielderrortpl'])? $this->modx->getChunk($this->props['fielderrortpl']) : $this->modx->getChunk('npFieldErrorTpl');


        /* make sure we have all of them */
        $success = true;
        foreach($this->tpls as $tpl=>$val) {
            if (empty($val)) {
                $this->setError($this->modx->lexicon('np_no_tpl') . $tpl);
                $success = false;
            }
        }
        /* Set these templates for sure so we can show any errors */

        if (empty($this->tpls['outerTpl'])) {
            $this->tpls['outerTpl'] = '<div class="newspublisher">
        <h2>[[%np_main_header]]</h2>
        [[!+np.error_header:ifnotempty=`<h3>[[!+np.error_header]]</h3>`]]
        [[!+np.errors_presubmit:ifnotempty=`[[!+np.errors_presubmit]]`]]
        [[!+np.errors_submit:ifnotempty=`[[!+np.errors_submit]]`]]
        [[!+np.errors:ifnotempty=`[[!+np.errors]]`]]</div>';
        }

        if (empty($this->tpls['errorTpl'])) {
            $this->tpls['errorTpl'] = '<span class = "errormessage">[[+np.error]]</span>';
        }

        if (empty($this->tpls['fieldErrorTpl'])) {
            $this->tpls['fieldErrorTpl'] = '<span class = "fielderrormessage">[[+np.error]]</span>';
        }

        /* set different placeholder prefix if requested */

        if ($this->prefix != 'np') {
            $this->tpls = str_replace('np.', $this->prefix . '.', $this->tpls);
        }

        return $success;
    }
/** return a specified tpl
 *
 * @access public
 * @param (string) tpl name
 *
 * @return (string) tpl content
 *  */

    public function getTpl($name) {
        return $this->tpls[$name];
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
    public function displayForm() {

        $fields = $this->fieldsToShow;
        $inner = '';

        if (! $this->resource) {
            $this->setError($this->modx->lexicon('np_no_resource'));
            return $this->tpls['outerTpl'];
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
        $formTpl = str_replace('[[+npx.insert]]',$inner,$this->tpls['outerTpl']);
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
      
        $replace = array();
        $replace['[[+npx.help]]'] = $this->props['hoverhelp'] ? '[[%resource_' . $field . '_help:notags]]' : '';
        $replace['[[+npx.caption]]'] = '[[%resource_' . $field . ']]';
        $fieldType = $this->resource->_fieldMeta[$field]['phptype'];
        if ($field == 'hidemenu') {  /* correct schema error */
            $fieldType = 'boolean';
        }

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
                $type = $this->resource->get('class_key');
                if ($type == 'modSymLink' || $type == 'modWebLink') {
                    $replace['[[+npx.caption]]'] = '[[%weblink]]';
                    $replace['[[+npx.help]]'] = '[[%weblink_help]]';
                    $inner .= $this->_processSimple($field, $replace, 'textTpl');
                } else {
                    $inner .= $this->_processTextarea($field, $replace, $this->props['rtcontent'], 'np-content');
                }
                break;

            case 'introtext':
                $inner .= $this->_processTextarea($field, $replace, $this->props['rtsummary'], 'np-introtext');
                break;

            case 'template':
                $options = array();
                $templates = $this->modx->getCollection('modTemplate');
                foreach ($templates as $template) {
                    if ($template->checkPolicy('list')) {
                        $options[$template->get('templatename')] = $template->get('id');
                    }
                }
                $inner .= $this->_processList($field, $replace, 'dropdown', $options, array($this->resource->get('template')), true);
                break;

            case 'contentType':
                $options = array();
                if (!isset($contentTypes)) $contentTypes = $this->modx->getCollection('modContentType');
                foreach ($contentTypes as $type) {
                    $options[$type->get('name')] = $type->get('mime_type');
                  }
                $inner .= $this->_processList($field, $replace, 'dropdown', $options);
                break;
                
            case 'class_key':
                $options = array();
                $classes = array('modDocument', 'modSymLink', 'modWebLink', 'modStaticResource');
                foreach ($classes as $key) $options[$key] = $key;
                $inner .= $this->_processList($field, $replace, 'dropdown', $options);
                break;
                
            case 'content_dispo':
                $options = array();
                $dispo = array('inline', 'attachment');
                foreach ($dispo as $key) $options[$this->modx->lexicon($key)] = $key;
                $inner .= $this->_processList($field, $replace, 'dropdown', $options);
                break;

            case 'uri_override':
                $fieldType = 'boolean';
                
            default:
                switch($fieldType) {
                    case 'string':
                    default:
                        $inner .= $this->_processSimple($field, $replace, 'textTpl');
                        break;

                    case 'boolean':
                        $inner .= $this->_processBoolean($field, $replace, $this->resource->get($field));
                        break;

                    case 'integer':
                        $inner .= $this->_processSimple($field, $replace, 'intTpl');
                        break;

                    case 'timestamp':
                        $inner .= $this->_processDate($field, $replace, $this->resource->get($field));
                        break;
                }
        }
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
            $template = $this->resource->get('template');
            $found = $this->modx->getCount('modTemplateVarTemplate', array('templateid' => $template, 'tmplvarid' => $tvId));
            if (! $found) {
                $this->setError($this->modx->lexicon('np_not_our_tv') . ' Template: ' . $template . '  ----    TV: ' . $tvNameOrId);
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
        if (! $this->isPostBack ) {
            $ph = '';
            if ($this->existing) {
                $ph = $tv->getValue($this->existing);
            }
            /* empty value gets default_text for both new and existing docs */
            if (empty($ph)) {
                $ph = $fields['default_text'];
            }
            if (stristr($ph,'@EVAL') || stristr($_POST[$name],'@EVAL') || stristr($_POST[$name.'_time'], '@eval')) {
                $this->setError($this->modx->lexicon('np_no_evals'). $tv->get('name'));
                return null;
                
            } else {

                $this->modx->toPlaceholder($name, $ph, $this->prefix );
            }
        }

        $replace = array();
        $replace['[[+npx.help]]'] = $this->props['hoverhelp'] ? $fields['description'] :'';
        $replace['[[+npx.caption]]'] = $caption;
        $replace['[[+npx.fieldName]]'] = $name;

        switch ($tvType) {
            case 'date':
                $formTpl .= $this->_processDate($name, $replace,  $tv->getValue($this->existing), $params);
                break;

            default:
            case 'text':
            case 'textbox':
            case 'email';
                $formTpl .= $this->_processSimple($name, $replace, 'textTpl');
                break;

            case 'number':
                $formTpl .= $this->_processSimple($name, $replace, 'intTpl');
                break;

            case 'textarea':
            case 'textareamini':
                $formTpl .= $this->_processTextarea($name, $replace, false, $tvType);
                break;

            case 'richtext':
                $formTpl .= $this->_processTextarea($name, $replace, true, 'textarea');

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
                    $options[$text] = $option;
                }

                /* selected entries */
                $selected = explode('||',$tv->getValue($this->existing));

                /* render HTML */
                $formTpl .= $this->_processList($name, $replace, $tvType, $options, $selected, $params['allowBlank']=='true');
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
                    $options[$resource->get('pagetitle')] = $id; //.' ('.$resource->get('id').')',
                    if ($id == $tv->getValue($this->existing)) $selected[] = $id;
                }

                /* If the list is empty do not require selecting something */
                if (!$options) $params['allowBlank'] = 'true';
                $formTpl .= $this->_processList($name, $replace, 'dropdown', $options, $selected, $params['showNone']=='true' || $params['allowBlank']=='true');
                break;
            case 'image':
            case 'file':
                            
                /* code adapted from core/model/modx/processors/element/tv/renders/mgr/input/file.php
                 * and (...)/image.php */

                $this->modx->getService('fileHandler','modFileHandler', '', array('context' => $this->context));

                $workingContext = $this->modx->getContext($this->context);
                $params['wctx'] = $this->context;
                $this->modx->fileHandler->context =& $workingContext;

                $value = $tv->getValue($this->existing);

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

                if (empty($params['basePath'])) {
                    $params['basePath'] = $this->modx->fileHandler->getBasePath();
                    $params['basePath'] = str_replace($replaceKeys,$replaceValues,$params['basePath']);
                    $params['basePathRelative'] = $workingContext->getOption('filemanager_path_relative',true) ? 1 : 0;
                } else {
                    $params['basePath'] = str_replace($replaceKeys,$replaceValues,$params['basePath']);
                    $params['basePathRelative'] = !isset($params['basePathRelative']) || in_array($params['basePathRelative'],array('true',1,'1'));
                }
                if (empty($params['baseUrl'])) {
                    $params['baseUrl'] = $this->modx->fileHandler->getBaseUrl();
                    $params['baseUrl'] = str_replace($replaceKeys,$replaceValues,$params['baseUrl']);
                    $params['baseUrlRelative'] = $workingContext->getOption('filemanager_url_relative',true) ? 1 : 0;
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
                    $dir = pathinfo($value,PATHINFO_DIRNAME);
                    $dir = rtrim($dir,'/').'/';
                    $params['openTo'] = $dir;
                }

                $formTpl .= $this->_processFile($name, $replace, $tvType.'Tpl', $tvType=='image', $params);
                break;
                
        }  /* end switch */
        
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
     * @param $PHs - (array) associative array of placeholders and their values to be inserted 
     * @param $timestring - (string) date-time string in the format "2011-04-01 13:20:01"
     * @ param $options - (array) Associative array of options. Accepts 'disabledDates', 'disabledDays', 'minDateValue' and 'maxDateValue' (in the format used for the corresponding TV input options)
     * @return (string) - date field/TV HTML code */
    
    protected function _processDate($name, $PHs, $timeString, $options = array()) {

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
          
          $PHs['[[+npx.disabledDates]]'] = $disabled;

        return $this->strReplaceAssoc($PHs, $this->tpls['dateTpl']);
    }
    /** Produces the HTML code for simple text fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $PHs - (array) associative array of placeholders and their values to be inserted
     * @param $tplName - (string) name of the template chunk that should be used
     * @return (string) - field/TV HTML code */

    protected function _processSimple($name, $PHs, $tplName) {
        $PHs['[[+npx.maxlength]]'] = $this->textMaxlength;
        return $this->strReplaceAssoc($PHs, $this->tpls[$tplName]);
    }


    /** Produces the HTML code for file/image TVs
     * 
     * @access protected
     * @param $name - (string) name of the TV
     * @param $PHs - (array) associative array of placeholders and their values to be inserted 
     * @param $tplName - (string) name of the template chunk that should be used
     * @param $showPreview - (bool) If true, a npx.phpthumbBaseUrl placeholder will be set for producing a preview thumbnail. The &src, &w and &h attributes should be appended from within the template chunk
     * @ param $options - (array) Associative array of options. Accepts all file/image TV input options
     * @return (string) - HTML code */

    protected function _processFile($name, $PHs, $tplName, $showPreview = true, $options = array()) {

        $browserAction = $this->modx->getObject('modAction',array('namespace'  => 'newspublisher'));
        $url = $browserAction ? $this->modx->getOption('manager_url',null,MODX_MANAGER_URL).'index.php?a='.$browserAction->get('id') : null;
        
        if ($showPreview) {
            $PHs['[[+npx.phpthumbBaseUrl]]'] = $this->modx->getOption('connectors_url',null,MODX_CONNECTORS_URL) . "system/phpthumb.php?basePath=" .$options['basePath']. "&basePathRelative=" .$options['basePathRelative']. "&baseUrl=" .$options['baseUrl']. "&baseUrlRelative=" .$options['baseUrlRelative']. "&baseUrlPrependCheckSlash=" . $options['baseUrlPrependCheckSlash'];
        }

        foreach ($options as $opt => $val) $url .= '&' . $opt . '=' . $val;

        /* Javascript for launching file browser */
        $PHs['[[+npx.launchBrowser]]'] = "var popup=window.open('" . $url . "', 'select file', 'width=' + Math.min(screen.availWidth,1000) + ',height=' + Math.min(screen.availHeight*0.9,700) + 'resizable=no,status=no,location=no,toolbar=no');popup.focus();browserPathInput=getElementById('np-" . $name . "');return false;";
        
        return $this->strReplaceAssoc($PHs, $this->tpls[$tplName]);
    }
    
    /** Produces the HTML code for boolean (checkbox) fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $PHs - (array) associative array of placeholders and their values to be inserted
     * @param $checked - (bool) Is the Checkbox activated?  (ignored on postback)
     * @return (string) - field/TV HTML code */

    protected function _processBoolean($name, $PHs, $checked) {
        if ($this->isPostBack) {
            $checked = $_POST[$name];
        }
        $PHs ['[[+npx.checked]]'] = $checked? 'checked="checked"' : '';
        
        return $this->strReplaceAssoc($PHs, $this->tpls['boolTpl']);
    }

    
    /** Produces the HTML code for list fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $PHs - (array) associative array of placeholders and their values to be inserted
     * @param $options - (array) associative array of list entries in the form array('displayed text' => 'value'), e.g. array('resource with ID 8' =>)
     * @param $selected - (array) Array of list entries ($options values) that are currently selected (ignored on postback)
     * @param $showNone - (bool) If true, the first option will be 'empty' (only a dash)
     * @return (string) - field/TV HTML code */

    protected function _processList($name, $PHs, $type, $options, $selected = null, $showNone = false) {

        // if blank selections are allowed, add a blank ('-') field as first option
        // TODO: should 'listbox' and 'radio' have one or not????? Should there always be an empty option,
        // irrespective of the allowBlank TV input option????
        if ($showNone && $type == 'dropdown') $options = array_merge(array('-' => ''), $options);

        $postfix = ($type == 'checkbox' || $type=='listbox-multiple' || $type=='listbox')? '[]' : '';
        $PHs['[[+npx.name]]'] = $name . $postfix;

        if($type == 'listbox' || $type == 'listbox-multiple' || $type == 'dropdown') {
            $formTpl = $this->tpls['listOuterTpl'];
            $PHs['[[+npx.multiple]]'] = ($type == 'listbox-multiple')? ' multiple="multiple" ': '';
            $count = count($options);
            if ($type == 'dropdown') {
                $max = 1;
            } else {
                $max = ($type == 'listbox')? $this->listboxMax : $this->multipleListboxMax;
            }
            $PHs['[[+npx.size]]'] = ($count <= $max)? $count : $max;
        } else {
            $formTpl = $this->tpls['optionOuterTpl'];
        }

        $PHs['[[+npx.hidden]]'] = ($type == 'checkbox')? '<input type="hidden" name="' . $name . '[]" value="" />' : '';
        $PHs['[[+npx.class]]'] = 'np-tv-' . $type;

        /* Do outer TPl replacements */
        $formTpl = $this->strReplaceAssoc($PHs,$formTpl);

        /* new replace array for options */
        $inner = '';
        $PHs = array();
        $PHs['[[+npx.name]]'] = $name . $postfix;

        // if postback -> use selection from $_POST
        if ($this->isPostBack) $selected = $_POST[$name];
        if (!is_array($selected)) $selected = array($selected);

        /* Set HTML code to use for selected options */
        $selectedCode = ($type == 'radio' || $type == 'checkbox')? 'checked="checked"' : 'selected="selected"';

        if ($type == 'listbox' || $type =='listbox-multiple' || $type == 'dropdown') {
            $optionTpl = $this->tpls['listOptionTpl'];
        } else {
            $optionTpl = $this->tpls['optionTpl'];
            $PHs['[[+npx.class]]'] = $PHs['[[+npx.type]]'] = $type;
        }

        /* loop through options and set selections */
        foreach ($options as $text => $value) {
            $PHs['[[+npx.name]]'] = $name . $postfix;
            $PHs['[[+npx.value]]'] = $value;
            $PHs['[[+npx.selected]]'] = in_array($value, $selected) ? $selectedCode : '';
            $PHs['[[+npx.text]]'] = $text;
            $inner .= $this->strReplaceAssoc($PHs,$optionTpl);
        }

        return str_replace('[[+npx.options]]',$inner, $formTpl);
    }


    /** Produces the HTML code for textarea fields/TVs
     * 
     * @access protected
     * @param $name - (string) name of the field/TV
     * @param $PHs - (array) associative array of placeholders and their values to be inserted
     * @param $RichText - (bool) Is this a Richtext field?
     * @param $noRTE_class - (string) class name for non-Richtext textareas
     * @param $rows - (int) number of rows in the textarea
     * @param $columns - (int) width (number of columns) of the textarea
     * @return (string) - field/TV HTML code */

    protected function _processTextarea($name, $PHs, $RichText, $noRTE_class, $rows = 20, $columnns = 60) {
        $PHs['[[+npx.rows]]'] = $rows;
        $PHs['[[+npx.cols]]'] = $columns;

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
        return $this->strReplaceAssoc($PHs, $this->tpls['textareaTpl']);
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
        $oldFields = $this->resource->toArray();

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
        $fields = array_merge($oldFields, $_POST);
        if (!$this->existing) { /* new document */

            /* ToDo: Move this to init()? */
            /* set alias name of document used to store articles */
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
            /* set fields for new object */

            /* set editedon and editedby for existing docs */
            $fields['editedon'] = '0';
            $fields['editedby'] = '0';

            /* these *might* be in the $_POST array. Set them if not */
            $fields['content']  = $this->header . $fields['content'] . $this->footer;

        }

        /* Add TVs to $fields for processor */
        /* e.g. $fields[tv13] = $_POST['MyTv5'] */
        /* processor handles all types */

        if (!empty($this->allTvs)) {
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


        /** Deletes the current resource
         *
         *  @access public
         */

    public function deleteResource() {
      
        $response = $this->modx->runProcessor('resource/delete', array('id' => $this->resource->get('id')));

        if ($response->isError()) {
                $this->setError($this->modx->lexicon('np_error_occured') . $response->getMessage());
        }
    }

    /** creates a JSON string to send in the resource_groups field
     * for resource/update or resource/create processors.
     * Accepts a 'parent' value in analogy to _setDefaults, which
     * only accepts Resource fields
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

    /** Gets template ID of resource
     * @param (string) $template - a template name or ID
     * @return (int) returns the template ID
     */
    protected function _getTemplate($template) {

        if (is_numeric($template)) { /* user sent a number */
            $t = $this->modx->getObject('modTemplate', $template);
            /* make sure it exists */
            if (! $t) {
                $this->setError($this->modx->lexicon('np_no_template_id') . $this->props['template']);
            }
        } else { /* user sent a template name */
            $t = $this->modx->getObject('modTemplate', array('templatename' => $this->props['template']));
            if (!$t) {
                $this->setError($this->modx->lexicon('np_no_template_name') . $this->props['template']);
            }
        }
        $template = $t ? $t->get('id') : $this->modx->getOption('default_template');
        unset($t);

        return $template;
    }

    /** Checks form fields before saving.
     *  Sets an error for the header and another for each
     *  missing required field.
     * */

    public function validate() {
        $errorTpl = $this->tpls['fieldErrorTpl'];
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
            if (stristr($_POST[$field], '@EVAL')) {
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
        $msg = str_replace("[[+{$this->prefix}.error]]", $msg, $this->tpls['fieldErrorTpl']);
        $ph = 'error_' . $fieldName;
        $this->modx->toPlaceholder($ph, $msg, $this->prefix);
    }


} /* end class */


?>
