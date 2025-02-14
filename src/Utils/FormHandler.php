<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Advanced Form.
 *
 * @package     contao-advanced-form
 * @license     AGPL-3.0
 * @author      Fabian Ekert          <https://github.com/eki89>
 * @author      Daniele Sciannimanica <https://github.com/doishub>
 * @author      Sebastian Zoglowek    <https://github.com/zoglo>
 * @copyright   Oveleon               <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoAdvancedForm\Utils;

use Contao\Form;
use Contao\FormFieldModel;
use Contao\StringUtil;
use Oveleon\ContaoAdvancedForm\Service\FormPage\FormPageManager;

class FormHandler
{
    /**
     * @param array<FormFieldModel> $fields
     *
     * @throws \JsonException
     */
    public function __construct(
        Form $form,
        array $fields,
        private readonly FormPageManager $formManager,
    ) {
        $conditions = false;

        foreach ($fields as $field)
        {
            if ($field->type === 'fieldsetStart' && $field->isConditionalFormField)
            {
                $conditions = true;
                break;
            }
        }

        if ($conditions)
        {
            // Add CSS class for current form.
            $formAttributes = StringUtil::deserialize($form->attributes, true);
            $formAttributes[1] = trim(($formAttributes[1] ?? '') . ' cff');

            // Add data of previous steps as JSON at form tag.
            if (($previousData = $this->getPreviousDataFromAdvForms()) !== [])
            {
                $formAttributes[1] .= '" data-cff-previous="' .
                                      StringUtil::specialcharsAttribute(
                                          json_encode($previousData, JSON_THROW_ON_ERROR),
                                      );
            }

            $form->attributes = $formAttributes;
        }
    }

    /**
     * Retrieve data from previous steps.
     */
    private function getPreviousDataFromAdvForms(): array
    {
        return $this->formManager->getDataOfAllSteps()['submitted'];
    }
}
