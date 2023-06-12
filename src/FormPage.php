<?php

/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoAdvancedForm;

use Contao\FormFieldModel;
use Contao\FrontendUser;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

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
                return $this->objPageSwitch->formPageAlias ?? 'start';

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
        $strCondition = preg_replace("/\\$([A-Za-z0-9_]+)/u", '$1', $strCondition);
        $strCondition = html_entity_decode($strCondition);

        return $strCondition;
    }

    public function isAccessible($manager)
    {
        if ($this->objPageSwitch === null)
        {
            return true;
        }

        $accessible = true;

        if ($this->objPageSwitch->addCondition)
        {
            $condition = $this->generateCondition($this->objPageSwitch->condition);
            $submitted = $manager->getDataOfAllSteps()['fieldsetSubmitted'];

            // Create EL and register native php functions
            $expressionLanguage = new ExpressionLanguage();
            $expressionLanguage->addFunction(ExpressionFunction::fromPhp('floatval'));
            $expressionLanguage->addFunction(ExpressionFunction::fromPhp('strval'));
            $expressionLanguage->addFunction(ExpressionFunction::fromPhp('intval'));
            $expressionLanguage->addFunction(ExpressionFunction::fromPhp('in_array'));
            $expressionLanguage->addFunction(ExpressionFunction::fromPhp('str_contains'));

            // Evaluate condition
            $accessible = $expressionLanguage->evaluate($condition, $submitted);
        }

        if ($accessible)
        {
            $blnFeUserLoggedIn = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
            $user = System::importStatic(FrontendUser::class, 'User');

            if ($this->objPageSwitch->guests && $blnFeUserLoggedIn)
            {
                $accessible = false;
            }

            if ($this->objPageSwitch->protected)
            {
                $groups = StringUtil::deserialize($this->objPageSwitch->groups);

                if ($blnFeUserLoggedIn && !(empty($groups) || !\is_array($groups) || !count(array_intersect($groups, $user->groups))))
                {
                    $accessible = true;
                }
                else
                {
                    $accessible = false;
                }
            }
        }

        return $accessible;
    }
}
