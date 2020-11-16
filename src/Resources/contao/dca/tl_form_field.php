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

$GLOBALS['TL_DCA']['tl_form_field']['fields']['formPageAlias'] = array
(
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50 clr'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['blabel'] = array
(
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['addCondition'] = array
(
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['condition'] = array
(
    'exclude'                 => true,
    'inputType'               => 'textarea',
    'eval'                    => array('mandatory' => true, 'decodeEntities' => true, 'style' => 'height:40px', 'tl_class' => 'clr'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['protected'] = array
(
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['groups'] = array
(
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'foreignKey'              => 'tl_member_group.name',
    'eval'                    => array('mandatory'=>true, 'multiple'=>true),
    'sql'                     => "blob NULL",
    'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_form_field']['fields']['guests'] = array
(
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);
