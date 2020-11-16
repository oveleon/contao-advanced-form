<?php

/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoAdvancedForm;

use Contao\Form;
use Contao\FormFieldModel;
use Contao\FrontendUser;
use Contao\System;

class FormPage
{
    /**
     * Page switch form field model
     * @var FormFieldModel
     */
    protected $objPageSwitch;

    /**
     * Form field models
     * @var FormFieldModel[]
     */
    protected $objFormFields = [];

    /**
     * @var bool
     */
    protected $accessible = true;

    /**
     * @var bool
     */
    protected $callable = true;

    /**
     * Initialize the object
     */
    public function __construct($objPageSwitch=null)
    {
        if ($objPageSwitch === null)
        {
            return;
        }

        $this->objPageSwitch = $objPageSwitch;
    }

    /**
     * Return an object property
     *
     * @param string $strKey The property name
     *
     * @return mixed|null The property value or null
     */
    public function __get($strKey)
    {
        switch ($strKey)
        {
            case 'accessible':
                return $this->accessible;

            case 'alias':
                return $this->objPageSwitch->formPageAlias ?: 'start';

        }

        return null;
    }

    public function addField($objFormField)
    {
        $this->objFormFields[] = $objFormField;
    }

    public function getFields()
    {
        return $this->objFormFields;
    }

    protected function generateCondition($strCondition)
    {
        $strCondition = str_replace('in_array', '@in_array', $strCondition);
        $strCondition = preg_replace("/\\$([A-Za-z0-9_]+)/u", '$arrPost[\'$1\']', $strCondition);

        return 'return (' . $strCondition . ');';
    }

    public function isAccessible($manager)
    {
        if ($this->objPageSwitch === null)
        {
            return true;
        }

        if ($this->objPageSwitch->addCondition)
        {
            $condition = $this->generateCondition($this->objPageSwitch->condition);
            $submitted = $manager->getDataOfAllSteps()['submitted'];

            $callableCondition = function ($arrPost) use ($condition) {
                return eval($condition);
            };

            return $callableCondition($submitted);
        }

        return true;
    }
}