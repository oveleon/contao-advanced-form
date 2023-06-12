<?php
/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][]  = 'addCondition';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][]  = 'protected';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['pageSwitch']      = '{type_legend},type,formPageAlias,label,slabel,blabel;{image_legend:hide},imageSubmit;{condition_legend},addCondition,protected,guests;{expert_legend:hide},class,accesskey,tabindex;{template_legend:hide},customTpl;{invisible_legend:hide},invisible';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['addCondition'] = 'condition';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['protected']    = 'groups';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['formPageAlias'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength'=>255, 'tl_class'=>'w50 clr'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['blabel'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength'=>255, 'tl_class'=>'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['addCondition'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange'=>true, 'tl_class'=>'clr'],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['condition'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['mandatory' => true, 'decodeEntities' => true, 'style' => 'height:40px', 'tl_class' => 'clr'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['protected'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange'=>true, 'tl_class'=>'clr'],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['groups'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_member_group.name',
    'eval' => ['mandatory'=>true, 'multiple'=>true],
    'sql' => "blob NULL",
    'relation' => ['type'=>'hasMany', 'load'=>'lazy']
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['guests'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class'=>'w50'],
    'sql' => "char(1) NOT NULL default ''"
];
