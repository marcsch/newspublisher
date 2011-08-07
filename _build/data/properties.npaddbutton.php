<?php

 /**
 * Default properties for the NpEditThisButton snippet
 * @author Bob Ray
 * 1/15/11
 *
 * @package newspublisher
 * @subpackage build
 */

$properties = array(
    array(
        'name' => 'debug',
        'desc' => 'np_debug_desc',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => '',
        'lexicon' => 'newspublisher:button',
    ),
    array(
        'name' => 'language',
        'desc' => 'np_language_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'newspublisher:button',
    ),
    array(
        'name' => 'np_id',
        'desc' => 'np_id_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'newspublisher:button',
    )
);

return $properties;
