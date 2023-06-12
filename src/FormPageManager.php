<?php

/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoAdvancedForm;

use Contao\Controller;
use Contao\Form;
use Contao\FormCaptcha;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Input;
use Contao\System;
use Contao\Widget;
use Haste\Util\Url;
use Oveleon\ContaoAdvancedForm\EventListener\CompileFormFieldsListener;

class FormPageManager
{
    protected static array $arrInstances = [];
    protected array $formFields;
    protected array $formPages;
    protected array $formPageMapper;

    protected $objForm;

    private bool $isValid = true;

    protected function __construct(Form $form)
    {
        if (null === ($this->objForm = $form->getModel()))
        {
            $this->isValid = false;
            return;
        }

        $this->loadFormFieldModels();

        if (0 === count($this->formFields))
        {
            $this->isValid = false;
            return;
        }

        $formPage = new FormPage(null);
        $this->formPageMapper = ['start'];

        foreach ($this->formFields as $objFormField)
        {
            $formPage->addField($objFormField);

            if ($this->isPageBreak($objFormField))
            {
                $this->formPages[] = $formPage;

                $formPage = new FormPage($objFormField);
                $this->formPageMapper[] = $objFormField->formPageAlias ?: strval(count($this->formPageMapper));
            }

            if ('submit' === $objFormField->type)
            {
                $this->isValid = false;
            }
        }

        $this->formPages[] = $formPage;
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final public function __clone()
    {
    }

    /**
     * Get one object instance per table
     */
    public static function getInstance(Form $form): FormPageManager
    {
        if (!isset(self::$arrInstances[$form->id]))
        {
            self::$arrInstances[$form->id] = new static($form);
        }

        return self::$arrInstances[$form->id];
    }

    /**
     * Loads the form field models (calling the compileFormFields hook).
     */
    protected function loadFormFieldModels(): void
    {
        $objFormFields = FormFieldModel::findPublishedByPid($this->objForm->id);
        $formFields = [];

        if ($objFormFields !== null)
        {
            $formFields = $objFormFields->getModels();
        }

        $form = $this->createDummyForm();

        // HOOK: compile form fields
        if (isset($GLOBALS['TL_HOOKS']['compileFormFields']) && \is_array($GLOBALS['TL_HOOKS']['compileFormFields']))
        {
            foreach ($GLOBALS['TL_HOOKS']['compileFormFields'] as $callback)
            {
                // Do not call ourselves recursively
                if ($callback[0] === CompileFormFieldsListener::class)
                {
                    continue;
                }

                $objCallback = System::importStatic($callback[0]);
                $formFields = $objCallback->{$callback[1]}($formFields, $this->getFormId(), $form);
            }
        }

        $this->formFields = $formFields;
    }

    /**
     * Gets the form generator form id.
     */
    public function getFormId(): string
    {
        return ($this->objForm->formID !== '') ? 'auto_' . $this->objForm->formID : 'auto_form_' . $this->objForm->id;
    }

    /**
     * Check whether a form field is of type page switch.
     */
    public function isPageBreak(FormFieldModel $objFormField): bool
    {
        return 'pageSwitch' === $objFormField->type;
    }

    /**
     * Checks if the combination is valid.
     */
    public function isValidFormFieldCombination(): bool
    {
        return $this->isValid;
    }

    /**
     * Get the fields without the page breaks.
     */
    public function getFieldsWithoutPageBreaks(): array
    {
        $formFields = $this->formFields;

        foreach ($formFields as $k => $objFormField)
        {
            if ('pageSwitch' === $objFormField->type)
            {
                unset($formFields[$k]);
            }
        }

        return $formFields;
    }

    /**
     * Generates an url for the step.
     *
     * @param string $step
     *
     * @return mixed
     */
    public function getUrlForStep($step)
    {
        //return


        $stepParam = $this->getStepParam();

        if ('' === $step)
        {
            $url = Url::removeQueryString([$stepParam]);
        }
        else
        {
            $url = Url::addQueryString($stepParam . '=' . $step);
        }

        return $url;
    }

    /**
     * Check if a given step is available
     *
     * @param int $step
     *
     * @return boolean
     */
    public function hasStep($step)
    {
        return isset($this->formPages[array_search($step, $this->formPageMapper)]);
    }

    /**
     * Get the form page for a given step.
     *
     * @param string $step
     *
     * @return FormPage
     */
    protected function getFormPageForStep($step)
    {
        return $this->formPages[array_search($step, $this->formPageMapper)];
    }

    /**
     * Get the fields for a given step.
     *
     * @param string $step
     *
     * @return FormFieldModel[]
     *
     * @throws \InvalidArgumentException
     */
    public function getFieldsForStep($step)
    {
        if (!$this->hasStep($step))
        {
            throw new \InvalidArgumentException('Step "' . $step . '" is not available!');
        }

        return $this->getFormPageForStep($step)->getFields();
    }

    /**
     * Gets alias of the current step.
     *
     * @return string
     */
    public function getCurrentStep()
    {
        $alias = Input::get($this->getStepParam());

        if (empty($alias))
        {
            $alias = 'start';
        }

        return $alias;
    }

    /**
     * Gets alias of the next step.
     *
     * @return int
     */
    public function getNextStep()
    {
        $currentAlias = $this->getCurrentStep();

        if (($index = array_search($currentAlias, $this->formPageMapper)) !== false)
        {
            for ($index++ ;$index < count($this->formPageMapper); $index++)
            {
                if (!$this->formPages[$index]->isAccessible($this))
                {
                    continue;
                }

                return $this->formPageMapper[$index];
            }
        }
    }

    /**
     * Gets alias of previous step.
     *
     * @return string
     */
    public function getPreviousStep()
    {
        $currentAlias = $this->getCurrentStep();

        if (($index = array_search($currentAlias, $this->formPageMapper)) !== false)
        {
            for ($index-- ;$index >= 0; $index--)
            {
                if (!$this->formPages[$index]->isAccessible($this))
                {
                    continue;
                }

                return $this->formPages[$index]->alias;
            }
        }
    }

    /**
     * Gets alias of the last step.
     *
     * @return string
     */
    public function getLastStep()
    {
        return $this->formPages[(count($this->formPages) - 1)]->alias;
    }

    /**
     * Check if current step is the first.
     *
     * @return bool
     */
    public function isFirstStep()
    {
        if ($this->getCurrentStep() === 'start')
        {
            return true;
        }

        return false;
    }

    /**
     * Check if current step is the last.
     *
     * @return bool
     */
    public function isLastStep()
    {
        $currentIndex = array_search($this->getCurrentStep(), $this->formPageMapper);
        $targetIndex  = (count($this->formPageMapper) - 2);

        if ($currentIndex >= $targetIndex)
        {
            return true;
        }

        for (++$currentIndex; $currentIndex <= $targetIndex; $currentIndex++)
        {
            if ($this->formPages[$currentIndex]->isAccessible($this))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Store data.
     *
     * @param array $submitted
     * @param array $labels
     * @param array $files
     */
    public function storeData(array $submitted, array $labels, array $files)
    {
        // Make sure files are moved to our own tmp directory so they are
        // kept across php processes
        foreach ($files as $k => $file)
        {
            // If the user marked the form field to upload the file into
            // a certain directory, this check will return false and thus
            // we won't move anything.
            if (is_uploaded_file($file['tmp_name']))
            {
                $target = sprintf('%s/system/tmp/mp_forms_%s.%s',
                    TL_ROOT,
                    basename($file['tmp_name']),
                    $this->guessFileExtension($file)
                );
                move_uploaded_file($file['tmp_name'], $target);
                $files[$k]['tmp_name'] = $target;
            }
        }

        $_SESSION['FORMSTORAGE'][$this->objForm->id][$this->getCurrentStep()] = array
        (
            'submitted' => $submitted,
            'labels'    => $labels,
            'files'     => $files,
        );
    }

    /**
     * Get data of given step.
     *
     * @param int $step
     *
     * @return array
     */
    public function getDataOfStep($step)
    {
        if (!isset($_SESSION['FORMSTORAGE'][$this->objForm->id][$step]))
        {
            return [];
        }

        return (array) $_SESSION['FORMSTORAGE'][$this->objForm->id][$step];
    }

    /**
     * Get data of all steps as array.
     *
     * @return array
     */
    public function getDataOfAllSteps()
    {
        $arrSubmitted = [];
        $arrLabels    = [];
        $arrFiles     = [];

        $formFields = [];

        if (!!$this->formFields)
        {
            foreach ($this->formFields as $field)
            {
                $formFields[] = $field->name;
            }

            $formFields = array_fill_keys($formFields, '');
        }

        foreach ((array) $_SESSION['FORMSTORAGE'][$this->objForm->id] as $stepData)
        {
            $arrSubmitted = array_merge($arrSubmitted, (array) $stepData['submitted']);
            $arrLabels    = array_merge($arrLabels, (array) $stepData['labels']);
            $arrFiles     = array_merge($arrFiles, (array) $stepData['files']);
        }

        return [
            'fieldset'          => $formFields,
            'submitted'         => $arrSubmitted,
            'fieldsetSubmitted' => array_merge($formFields, $arrSubmitted),
            'labels'            => $arrLabels,
            'files'             => $arrFiles
        ];
    }

    /**
     * Reset all data.
     */
    public function resetData()
    {
        unset($_SESSION['FORMSTORAGE'][$this->objForm->id]);
    }

    /**
     * Stores if some previous step was invalid into the session.
     */
    public function setPreviousStepsWereInvalid()
    {
        $_SESSION['FORMSTORAGE_PSWI'][$this->objForm->id] = true;
    }

    /**
     * Checks if some previous step was invalid from the session.
     *
     * @return bool
     */
    public function getPreviousStepsWereInvalid()
    {
        if (!isset($_SESSION['FORMSTORAGE_PSWI'][$this->objForm->id]))
        {
            return false;
        }

        return $_SESSION['FORMSTORAGE_PSWI'][$this->objForm->id] === true;
    }

    /**
     * Resets the session for the previous step check.
     */
    public function resetPreviousStepsWereInvalid()
    {
        unset($_SESSION['FORMSTORAGE_PSWI'][$this->objForm->id]);
    }

    /**
     * Check if there is data stored for a certain field name.
     *
     * @param          $fieldName
     * @param null|int $step Current step if null
     * @param string   $key
     *
     * @return bool
     */
    public function isStoredInData($fieldName, $step=null, $key='submitted')
    {
        $step = $step === null ? $this->getCurrentStep() : $step;

        return isset($this->getDataOfStep($step)[$key])
            && array_key_exists($fieldName, $this->getDataOfStep($step)[$key]);
    }

    /**
     * Retrieve the value stored for a certain field name.
     *
     * @param          $fieldName
     * @param null|int $step Current step if null
     * @param string   $key
     *
     * @return mixed
     */
    public function fetchFromData($fieldName, $step=null, $key='submitted')
    {
        $step = null === $step ? $this->getCurrentStep() : $step;

        return $this->getDataOfStep($step)[$key][$fieldName];
    }

    /**
     * Validates all steps, optionally accepting custom from -> to ranges to validate only a subset of steps.
     *
     * @param string $stepFrom
     * @param string $stepTo
     *
     * @return true|string True if all steps valid, otherwise the step that failed validation
     */
    public function validateSteps($stepFrom='start', $stepTo=null)
    {
        if ($stepTo === null)
        {
            $stepTo = $this->formPageMapper[(count($this->formPageMapper) - 1)];
        }

        foreach ($this->formPageMapper as $step)
        {
            if (!$this->getFormPageForStep($step)->isAccessible($this))
            {
                continue;
            }

            if ($this->validateStep($step) === false)
            {
                return $step;
            }

            if ($step === $stepTo)
            {
                break;
            }
        }

        return true;
    }

    /**
     * Validates a step.
     *
     * @param $step
     *
     * @return bool
     */
    public function validateStep($step)
    {
        $formFields = $this->getFieldsForStep($step);

        foreach ($formFields as $formField)
        {
            if ($this->validateField($formField, $step) === false)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a field.
     *
     * @param FormFieldModel $formField
     * @param int             $step
     *
     * @return bool
     */
    public function validateField(FormFieldModel $formField, $step)
    {
        $class = $GLOBALS['TL_FFL'][$formField->type];

        if (!class_exists($class))
        {
            return true;
        }

        /** @var Widget $objWidget */
        $objWidget = new $class($formField->row());
        $objWidget->required = $formField->mandatory ? true : false;
        $objWidget->decodeEntities = true;

        // Needed for the hook
        $form = $this->createDummyForm();

        // HOOK: load form field callback
        if (isset($GLOBALS['TL_HOOKS']['loadFormField']) && is_array($GLOBALS['TL_HOOKS']['loadFormField']))
        {
            foreach ($GLOBALS['TL_HOOKS']['loadFormField'] as $callback)
            {
                $objCallback = System::importStatic($callback[0]);
                $objWidget = $objCallback->{$callback[1]}($objWidget, $this->getFormId(), $this->objForm->row(), $form);
            }
        }

        // Validation (needs to set POST values because the widget class searches
        // only in POST values :-(
        // This should only happen if value is not currently submitted and if
        // the value is neither submitted in POST nor in the session, we have
        // to default it to an empty string so the widget validates for mandatory
        // fields
        $fakeValidation = false;

        if (!$this->checkWidgetSubmittedInCurrentStep($objWidget)) {

            // Handle regular fields
            if ($this->isStoredInData($objWidget->name, $step))
            {
                Input::setPost($formField->name, $this->fetchFromData($objWidget->name, $step));
            }
            else
            {
                Input::setPost($formField->name, '');
            }

            // Handle files
            if ($this->isStoredInData($objWidget->name, $step, 'files'))
            {
                $_FILES[$objWidget->name] = $this->fetchFromData($objWidget->name, $step, 'files');
            }

            $fakeValidation = true;
        }

        $objWidget->validate();

        // HOOK: validate form field callback
        if (isset($GLOBALS['TL_HOOKS']['validateFormField']) && \is_array($GLOBALS['TL_HOOKS']['validateFormField']))
        {
            foreach ($GLOBALS['TL_HOOKS']['validateFormField'] as $callback)
            {
                $objCallback = System::importStatic($callback[0]);
                $objWidget = $objCallback->{$callback[1]}($objWidget, $this->getFormId(), $this->objForm->row(), $form);
            }
        }

        // Reset fake validation
        if ($fakeValidation)
        {
            Input::setPost($formField->name, null);
        }

        // Special hack for upload fields because they delete $_FILES and thus
        // multiple validation calls will fail - sigh
        if ($objWidget instanceof \uploadable && isset($_SESSION['FILES'][$objWidget->name]))
        {
            $_FILES[$objWidget->name] = $_SESSION['FILES'][$objWidget->name];
        }

        return !$objWidget->hasErrors();
    }

    /**
     * Creates a dummy form instance that is needed for the hooks.
     *
     * @return Form
     */
    protected function createDummyForm()
    {
        $form = new \stdClass();
        $form->form =       $this->objForm->id;
        $form->headline =   null;
        $form->typePrefix = null;
        $form->cssID =      null;

        return new Form($form);
    }

    private function guessFileExtension(array $file)
    {
        $extension = 'unknown';

        if (!isset($file['type']))
        {
            return $extension;
        }

        foreach ($GLOBALS['TL_MIME'] as $ext => $data)
        {
            if ($data[0] === $file['type'])
            {
                $extension = $ext;
                break;
            }
        }

        return $extension;
    }

    /**
     * Gets the step GET param.
     *
     * @return string
     */
    public function getStepParam()
    {
        return $this->objForm->stepParam ?: 'step';
    }

    /**
     * Checks if a widget was submitted in current step handling some exceptions.
     *
     * @return bool
     */
    private function checkWidgetSubmittedInCurrentStep(Widget $objWidget)
    {
        // Special handling for captcha field
        if ($objWidget instanceof FormCaptcha)
        {
            $objSession = System::getContainer()->get('session');
            $captcha = $objSession->get('captcha_' . $objWidget->id);

            return isset($_POST[$captcha['key']]);
        }

        return isset($_POST[$objWidget->name]);
    }

    /**
     * Redirect to step.
     *
     * @param FormPageManager $manager
     * @param string          $step
     */
    public function redirectToStep(FormPageManager $manager, $step)
    {
        // ToDo: RedirectResponseException
        Controller::redirect($manager->getUrlForStep($step));
    }
}
