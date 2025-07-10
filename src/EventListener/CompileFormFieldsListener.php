<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Advanced Form.
 *
 * @package     contao-advanced-form
 * @license     proprietary
 * @author      Fabian Ekert          <https://github.com/eki89>
 * @author      Daniele Sciannimanica <https://github.com/doishub>
 * @author      Sebastian Zoglowek    <https://github.com/zoglo>
 * @copyright   Oveleon               <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoAdvancedForm\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Input;
use Oveleon\ContaoAdvancedForm\Service\FormPage\FormPageManagerFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('compileFormFields')]
class CompileFormFieldsListener
{
    public function __construct(
        private readonly FormPageManagerFactory $formPageManagerFactory,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(array $fields, string $formId, Form $form): array
    {
        if ($fields === [])
        {
            return $fields;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request)
        {
            return $fields;
        }

        $manager = $this->formPageManagerFactory->getForForm($form);

        // Can't test this
        /*if (!isset($this->handlers[$formId]))
        {
            $this->handlers[$formId] = new FormHandler($form, $fields, $manager);
        }*/

        // Don't try to render multipage forms if no valid combinations exist
        if (!$manager->isValidFormFieldCombination())
        {
            return $manager->getFieldsWithoutPageBreaks();
        }

        if ($request->get('pageSwitch') === 'back')
        {
            $manager->storeData();
            $manager->redirectToStep($manager, $manager->getPreviousStep());
        }

        if (!$manager->isFirstStep() && [] === $request->request->all())
        {
            $valid = $manager->validateSteps('start', $manager->getPreviousStep());

            if ($valid !== true)
            {
                $manager->setPreviousStepsWereInvalid();
                $manager->redirectToStep($manager, $valid);
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
