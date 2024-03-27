<?php

/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoAdvancedForm;

use Contao\Controller;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\Input;
use Contao\Widget;

// @Todo - Remove this class completely
class AdvancedForm
{
    /**
     * @var array<FormHandler>
     */
    private array $handlers = [];

    /**
     * Adjust form fields to given page.
     *
     * @param FormFieldModel[] $arrFields
     * @param string $formId
     * @param Form $form
     * @return FormFieldModel[]
     * @throws \JsonException
     */
    public function compileFormFields($arrFields, $formId, Form $form)
    {
        // Skip empty form fields array
        if (count($arrFields) === 0)
        {
            return $arrFields;
        }

        $manager = FormPageManager::getInstance($form);

        if (!isset($this->handlers[$formId]))
        {
            $this->handlers[$formId] = new FormHandler($form, $arrFields, $manager);
        }

        // Don't try to render multi page form if no valid combination
        if (!$manager->isValidFormFieldCombination())
        {
            return $manager->getFieldsWithoutPageBreaks();
        }

        if (isset($_POST['pageSwitch']) && $_POST['pageSwitch'] === 'back')
        {
            $manager->storeData($_POST, [], (array) ($_SESSION['FILES'] ?? []));
            $this->redirectToStep($manager, $manager->getPreviousStep());
        }

        if (!$manager->isFirstStep() && !$_POST)
        {
            $vResult = $manager->validateSteps('start', $manager->getPreviousStep());

            if ($vResult !== true)
            {
                $manager->setPreviousStepsWereInvalid();
                $this->redirectToStep($manager, $vResult);
            }
        }

        if ($manager->getPreviousStepsWereInvalid())
        {
            Input::setPost('FORM_SUBMIT', $manager->getFormId());
            $manager->resetPreviousStepsWereInvalid();
        }

        return $manager->getFieldsForStep($manager->getCurrentStep());
    }

    /**
     * Loads the values from the session and adds it as default value to the widget.
     *
     * @param Widget $widget
     * @param string $formId
     * @param array  $formData
     * @param        $form
     *
     * @return Widget
     */
    public function loadValuesFromSession(Widget $widget, $formId, $formData, $form)
    {
        $manager = FormPageManager::getInstance($form);

        if ($manager->isStoredInData($widget->name))
        {
            $widget->value = $manager->fetchFromData($widget->name);
        }

        return $widget;
    }

    /**
     * Store the submitted data into the session and redirect to the next step.
     *
     * @param array $arrSubmitted
     * @param array $arrLabels
     * @param array $arrFields
     * @param Form  $form
     */
    public function prepareFormData(&$arrSubmitted, &$arrLabels, $arrFields, $form)
    {
        $manager = FormPageManager::getInstance($form);

        if (!$manager->isValidFormFieldCombination())
        {
            return;
        }

        $manager->storeData($arrSubmitted, $arrLabels, isset($_SESSION['FILES']) ? (array) $_SESSION['FILES'] : array());

        // Submit form
        if ($manager->isLastStep() && $_POST['pageSwitch'] === 'continue')
        {
            $data = $manager->getDataOfAllSteps();

            $arrSubmitted      = $data['submitted'];
            $arrLabels         = $data['labels'];
            $_SESSION['FILES'] = $data['files'];

            $_POST = $arrSubmitted;

            $_SESSION['FORM_DATA'] = $arrSubmitted;

            $manager->resetData();
            return;
        }
        else
        {
            $_SESSION['FORM_DATA'] = array();
        }

        $this->redirectToStep($manager, $manager->getNextStep());
    }

    /**
     * Redirect to step.
     *
     * @param FormPageManager $manager
     * @param string          $step
     */
    private function redirectToStep(FormPageManager $manager, $step)
    {
        Controller::redirect($manager->getUrlForStep($step));
    }
}
