<?php
/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

// Frontend form fields
$GLOBALS['TL_FFL']['pageSwitch'] = 'Oveleon\ContaoAdvancedForm\FormPageSwitch';

// Hooks
$GLOBALS['TL_HOOKS']['compileFormFields'][]   = ['Oveleon\ContaoAdvancedForm\AdvancedForm', 'compileFormFields'];
$GLOBALS['TL_HOOKS']['loadFormField'][]       = ['Oveleon\ContaoAdvancedForm\AdvancedForm', 'loadValuesFromSession'];
$GLOBALS['TL_HOOKS']['prepareFormData'][]     = ['Oveleon\ContaoAdvancedForm\AdvancedForm', 'prepareFormData'];