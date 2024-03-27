<?php

/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 *
 * @author Ingolf Steinhardt <info@e-spin.de>
 */

declare(strict_types=1);

namespace Oveleon\ContaoAdvancedForm;

use Contao\Form;
use Contao\FormFieldModel;
use Contao\FormFieldsetStart;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class FormHandler
{
    private FormPageManager $formManager;

    /**
     * @param Form                  $form
     * @param array<FormFieldModel> $fields
     * @param FormPageManager       $manager
     *
     * @throws \JsonException
     */
    public function __construct(Form $form, array $fields, FormPageManager $manager)
    {
        $this->formManager = $manager;

        $conditions = false;

        foreach ($fields as $field)
        {
            if ('fieldsetStart' === $field->type && $field->isConditionalFormField)
            {
                $conditions = true;
                break;
            }
        }

        if ($conditions)
        {
            // Add CSS class for current form.
            $formAttributes    = StringUtil::deserialize($form->attributes, true);
            $formAttributes[1] = \trim(($formAttributes[1] ?? '') . ' cff');

            // Add data of previous steps as JSON at form tag.
            if (!empty($previousData = $this->getPreviousDataFromAdvForms()))
            {
                $formAttributes[1] .= '" data-cff-previous="' .
                                      StringUtil::specialcharsAttribute(
                                          \json_encode($previousData, JSON_THROW_ON_ERROR)
                                      );
            }

            $form->attributes = $formAttributes;
        }
    }

    /**
     * Retrieve data from previous steps.
     *
     * @return array
     */
    private function getPreviousDataFromAdvForms(): array
    {
        return $this->formManager->getDataOfAllSteps()['submitted'];
    }
}
