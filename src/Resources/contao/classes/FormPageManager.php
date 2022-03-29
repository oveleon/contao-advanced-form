<?php

/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoAdvancedForm;

use Contao\Form;
use Contao\FormCaptcha;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Input;
use Contao\Session;
use Contao\System;
use Contao\Widget;
use Haste\Util\Url;

class FormPageManager
{
    /**
     * Instances
     * @var FormPageManager
     */
    protected static $arrInstances = array();

    /**
     * Form model
     * @var FormModel
     */
    protected $objForm;

    /**
     * Form field models
     * @var FormFieldModel[]
     */
    protected $arrFormFields;

    /**
     * @var FormPage[]
     */
    protected $arrFormPages;

    /**
     * @var array
     */
    protected $arrFormPageMapper;

    /**
     * True if the manager can handle this form
     *
     * @var bool
     */
    private $isValid = true;

    /**
     * Initialize the object
     *
     * @param Form $form
     */
    protected function __construct($form)
    {
        $this->objForm = $form->getModel();

        if ($this->objForm === null)
        {
            $this->isValid = false;
            return;
        }

        $this->loadFormFieldModels();

        if (count($this->arrFormFields) === 0)
        {
            $this->isValid = false;
            return;
        }

        $formPage = new FormPage(null);
        $this->arrFormPageMapper = array('start');

        foreach ($this->arrFormFields as $objFormField)
        {
            $formPage->addField($objFormField);

            if ($this->isPageBreak($objFormField))
            {
                $this->arrFormPages[] = $formPage;

                $formPage = new FormPage($objFormField);
                $this->arrFormPageMapper[] = $objFormField->formPageAlias ?: strval(count($this->arrFormPageMapper));
            }

            if ($objFormField->type === 'submit')
            {
                $this->isValid = false;
            }
        }

        $this->arrFormPages[] = $formPage;
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final public function __clone()
    {
    }

    /**
     * Get one object instance per table
     *
     * @param Form $form       The form object
     *
     * @return FormPageManager The object instance
     */
    public static function getInstance($form)
    {
        if (!isset(static::$arrInstances[$form->id]))
        {
            static::$arrInstances[$form->id] = new static($form);
        }

        return static::$arrInstances[$form->id];
    }

    /**
     * Loads the form field models (calling the compileFormFields hook).
     */
    protected function loadFormFieldModels()
    {
        $objFormFields = FormFieldModel::findPublishedByPid($this->objForm->id);
        $arrFormFields = [];

        if ($objFormFields !== null)
        {
            $arrFormFields = $objFormFields->getModels();
        }

        $form = $this->createDummyForm();

        // HOOK: compile form fields
        if (isset($GLOBALS['TL_HOOKS']['compileFormFields']) && \is_array($GLOBALS['TL_HOOKS']['compileFormFields']))
        {
            foreach ($GLOBALS['TL_HOOKS']['compileFormFields'] as $callback)
            {
                // Do not call ourselves recursively
                if ($callback[0] === 'Oveleon\ContaoAdvancedForm\AdvancedForm')
                {
                    continue;
                }

                $objCallback = System::importStatic($callback[0]);
                $arrFormFields = $objCallback->{$callback[1]}($arrFormFields, $this->getFormId(), $form);
            }
        }

        $this->arrFormFields = $arrFormFields;
    }

    /**
     * Gets the form generator form id.
     *
     * @return string
     */
    public function getFormId()
    {
        return ($this->objForm->formID !== '') ? 'auto_' . $this->objForm->formID : 'auto_form_' . $this->objForm->id;
    }

    /**
     * Check whether a form field is of type page switch.
     *
     * @param FormFieldModel $objFormField
     *
     * @return bool
     */
    public function isPageBreak(FormFieldModel $objFormField)
    {
        return $objFormField->type == 'pageSwitch';
    }

    /**
     * Checks if the combination is valid.
     *
     * @return bool
     */
    public function isValidFormFieldCombination()
    {
        return $this->isValid;
    }

    /**
     * Get the fields without the page breaks.
     *
     * @return FormFieldModel[]
     */
    public function getFieldsWithoutPageBreaks()
    {
        $arrFormFields = $this->arrFormFields;

        foreach ($arrFormFields as $k => $objFormField)
        {
            if ($objFormField->type === 'pageSwitch')
            {
                unset($arrFormFields[$k]);
            }
        }

        return $arrFormFields;
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
        $stepParam = $this->getStepParam();

        if ($step === '')
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
        return isset($this->arrFormPages[array_search($step, $this->arrFormPageMapper)]);
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
        return $this->arrFormPages[array_search($step, $this->arrFormPageMapper)];
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

        if (($index = array_search($currentAlias, $this->arrFormPageMapper)) !== false)
        {
            for ($index++ ;$index < count($this->arrFormPageMapper); $index++)
            {
                if (!$this->arrFormPages[$index]->isAccessible($this))
                {
                    continue;
                }

                return $this->arrFormPages[$index]->alias;
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

        if (($index = array_search($currentAlias, $this->arrFormPageMapper)) !== false)
        {
            for ($index-- ;$index >= 0; $index--)
            {
                if (!$this->arrFormPages[$index]->isAccessible($this))
                {
                    continue;
                }

                return $this->arrFormPages[$index]->alias;
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
        return $this->arrFormPages[(count($this->arrFormPages) - 1)]->alias;
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
        $currentIndex = array_search($this->getCurrentStep(), $this->arrFormPageMapper);
        $targetIndex  = (count($this->arrFormPageMapper) - 2);

        if ($currentIndex >= $targetIndex)
        {
            return true;
        }

        for (++$currentIndex; $currentIndex <= $targetIndex; $currentIndex++)
        {
            if ($this->arrFormPages[$currentIndex]->isAccessible($this))
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

        foreach ((array) $_SESSION['FORMSTORAGE'][$this->objForm->id] as $stepData)
        {
            $arrSubmitted = array_merge($arrSubmitted, (array) $stepData['submitted']);
            $arrLabels    = array_merge($arrLabels, (array) $stepData['labels']);
            $arrFiles     = array_merge($arrFiles, (array) $stepData['files']);
        }

        return array
        (
            'submitted' => $arrSubmitted,
            'labels'    => $arrLabels,
            'files'     => $arrFiles
        );
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
            $stepTo = $this->arrFormPageMapper[(count($this->arrFormPageMapper) - 1)];
        }

        foreach ($this->arrFormPageMapper as $step)
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
        if (isset($GLOBALS['TL_HOOKS']['loadFormField']) && \is_array($GLOBALS['TL_HOOKS']['loadFormField']))
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
        $form->form = $this->objForm->id;

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
            $session = Session::getInstance();
            $captcha = $session->get('captcha_' . $objWidget->id);

            return isset($_POST[$captcha['key']]);
        }

        return isset($_POST[$objWidget->name]);
    }
}