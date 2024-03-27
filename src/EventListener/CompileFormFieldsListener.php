<?php

namespace Oveleon\ContaoAdvancedForm\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use Contao\Input;
use Oveleon\ContaoAdvancedForm\FormHandler;
use Oveleon\ContaoAdvancedForm\FormPageManager;

/**
 * @Hook("compileFormFields")
 */
class CompileFormFieldsListener
{
    /**
     * @var array<FormHandler>
     */
    private array $handlers = [];

    /**
     * @throws \JsonException
     */
    public function __invoke(array $fields, string $formId, Form $form): array
    {
        if (0 === count($fields))
        {
            return $fields;
        }

        $manager = FormPageManager::getInstance($form);

        if (!isset($this->handlers[$formId]))
        {
            $this->handlers[$formId] = new FormHandler($form, $fields, $manager);
        }

        // Don't try to render multi page form if no valid combination
        if (!$manager->isValidFormFieldCombination())
        {
            return $manager->getFieldsWithoutPageBreaks();
        }

        if (isset($_POST['pageSwitch']) && $_POST['pageSwitch'] === 'back')
        {
            $manager->storeData($_POST, [], (array) ($_SESSION['FILES'] ?? []));
            $manager->redirectToStep($manager, $manager->getPreviousStep());
        }

        if (!$manager->isFirstStep() && !$_POST)
        {
            $vResult = $manager->validateSteps('start', $manager->getPreviousStep());

            if ($vResult !== true)
            {
                $manager->setPreviousStepsWereInvalid();
                $manager->redirectToStep($manager, $vResult);
            }
        }

        if ($manager->getPreviousStepsWereInvalid())
        {
            Input::setPost('FORM_SUBMIT', $manager->getFormId());
            $manager->resetPreviousStepsWereInvalid();
        }

        return $manager->getFieldsForStep($manager->getCurrentStep());
    }
}
