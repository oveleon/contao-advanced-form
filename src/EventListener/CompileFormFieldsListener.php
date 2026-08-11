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

namespace Oveleon\ContaoAdvancedForm\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Input;
use Oveleon\ContaoAdvancedForm\Service\FormPage\FormPageManager;
use Oveleon\ContaoAdvancedForm\Service\FormPage\FormPageManagerFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('compileFormFields')]
readonly class CompileFormFieldsListener
{
    public function __construct(
        private FormPageManagerFactory $formPageManagerFactory,
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(array $fields, string $formId, Form $form): array
    {
        if ($fields === []) {
            return $fields;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return $fields;
        }

        $manager = $this->formPageManagerFactory->getForForm($form);

        // Don't try to render multipage forms if no valid combinations exist
        if (!$manager->isValidFormFieldCombination()) {
            return $manager->getFieldsWithoutPageBreaks();
        }

        if ($request->get('pageSwitch') === 'back') {
            // Contao must not validate anything when the visitor wants to go back, but what has already been
            // entered on the current step is stored anyway, so it is still there when they return to it.
            $manager->storeData($this->getSubmittedData($manager, $request));
            $manager->redirectToStep($manager, $manager->getPreviousStep());
        }

        if (!$manager->isFirstStep() && $request->request->all() === []) {
            $valid = $manager->validateSteps('start', $manager->getPreviousStep());

            if ($valid !== true) {
                $manager->setPreviousStepsWereInvalid();
                $manager->redirectToStep($manager, $valid);
            }
        }

        if ($manager->getPreviousStepsWereInvalid()) {
            Input::setPost('FORM_SUBMIT', $manager->getFormId());
            $manager->resetPreviousStepsWereInvalid();
        }

        return $manager->getFieldsForStep($manager->getCurrentStep());
    }

    /**
     * Returns the values of the current step from the request, limited to the fields of that step.
     *
     * The request is not stored as it is: it also carries FORM_SUBMIT, REQUEST_TOKEN and the page switch itself,
     * which would end up in the submitted data of the form.
     */
    private function getSubmittedData(FormPageManager $manager, Request $request): array
    {
        $step = $manager->getCurrentStep();

        if (!$manager->hasStep($step)) {
            return [];
        }

        $submitted = $request->request->all();
        $data = [];

        foreach ($manager->getFieldsForStep($step) as $formField) {
            $name = (string) $formField->name;

            // Fieldsets, explanations and the page switch itself have no name of their own
            if ($name === '' || !\array_key_exists($name, $submitted)) {
                continue;
            }

            $data[$name] = $submitted[$name];
        }

        return $data;
    }
}
