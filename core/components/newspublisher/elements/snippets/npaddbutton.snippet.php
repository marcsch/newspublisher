<?php
/*
 * The NpAddButton snippet presents a button in the front end for
 * adding new resources. Clicking on the button launches NewsPublisher.
 *
 * @property np_id (int) - ID of newspublisher page
 * @property caption -  (optional -- not actually a parameter) -
 *      Caption for button.
 * @property language (optional) - Language to use for error messages.
 * @property debug (optional) - Displays the button on all pages with
 *      either the caption, or a message explaining why it
 *      would not be shown.
 *
 * ADDITIONALLY, any property valid for the Newspublisher snippet
 *      can be supplied here. It will override the values of properties
 *      with the same name defined in the Newspublisher call on the np_id page.
 *      Example: [[!NpAddButton?np_id=`5` &parent=`3` &template=`MyTemplate`]]
 *        This will add the new resource to the parent with id=5
 *        using 'MyTemplate' as template.
 */


$language = $modx->getOption('language', $scriptProperties, null);
$language = $language ? $language . ':' : '';
$modx->lexicon->load($language . 'newspublisher:button');

$debug = $modx->getOption('debug', $scriptProperties, false);

$caption = $defaultCaption = !empty($caption) ? $caption : $modx->lexicon('np_add');
$np_id = $modx->getOption('np_id', $scriptProperties, '');

if (empty($np_id)) $caption = $modx->lexicon('np_no_np_id');

/* check permissions on current page */
if (!$modx->hasPermission('edit_document')) {
    $caption = $modx->lexicon('np_no_edit_document_permission');
}

if (!$modx->hasPermission('save_document')) {
    $caption = $modx->lexicon('np_no_context_save_document_permission');
}

if (!$modx->resource->checkPolicy('save')) {
    $caption = $modx->lexicon('np_no_resource_save_document_permission');
}

if ($caption != $defaultCaption && !$debug) return '';

/* Create and return the form */
$output = '<form action="' . $modx->makeUrl($np_id) . '" method="post" class="np_add_button_form">'
        . "\n" . '<input type = "hidden" name="'.$prop.'" value="'.$val.'" />'
        . "\n" . '<input type="submit" class = "np_add_button" name="submit" value="' . $caption . '"/>';

$exclude = array('np_id', 'debug', 'caption', 'language');

foreach ($scriptProperties as $prop => $val) {
    if (! in_array($prop, $exclude)) {
        $output .= "\n" . '<input type = "hidden" name="'.$prop.'" value="'.$val.'" />';
    }
}
$output .= '</form>';

return $output;