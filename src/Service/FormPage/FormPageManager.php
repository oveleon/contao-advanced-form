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

namespace Oveleon\ContaoAdvancedForm\Service\FormPage;

use Codefog\HasteBundle\UrlParser;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Form;
use Contao\FormCaptcha;
use Contao\FormFieldModel;
use Contao\Input;
use Contao\Model;
use Contao\System;
use Contao\Widget;
use Oveleon\ContaoAdvancedForm\EventListener\CompileFormFieldsListener;
use Oveleon\ContaoAdvancedForm\Storage\FormStorage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FormPageManager
{
    protected array $formFields;

    protected array $formPages;

    protected array $formPageMapper;

    protected Model|null $form;

    private readonly SessionInterface $session;

    private readonly FormStorage $storage;

    private bool $isValid = true;

    private array $files = [];

    public function __construct(
        Form $form,
        private readonly RequestStack $requestStack,
        private readonly UrlParser $urlParser,
        private readonly string $projectDir,
    ) {
        if (null === ($this->form = $form->getModel())) {
            $this->isValid = false;

            return;
        }

        $this->session = $this->requestStack->getSession();
        $this->storage = new FormStorage((string) $this->form->id, $this->projectDir, $this->requestStack);

        $this->loadFormFieldModels();

        if ($this->formFields === []) {
            $this->isValid = false;

            return;
        }

        $formPage = new FormPage();
        $this->formPageMapper = ['start'];

        foreach ($this->formFields as $objFormField) {
            $formPage->addField($objFormField);

            if ($this->isPageBreak($objFormField)) {
                $this->formPages[] = $formPage;

                $formPage = new FormPage($objFormField);
                $this->formPageMapper[] = $objFormField->formPageAlias ?: (string) (\count($this->formPageMapper));
            }

            if ($objFormField->type === 'submit') {
                $this->isValid = false;
            }
        }

        $this->formPages[] = $formPage;
    }

    public function isFirstStep(): bool
    {
        return $this->getCurrentStep() === 'start';
    }

    public function getFormId(): string
    {
        return $this->form->formID !== '' ? 'auto_' . $this->form->formID : 'auto_form_' . $this->form->id;
    }

    public function isPageBreak(FormFieldModel $objFormField): bool
    {
        return $objFormField->type === 'pageSwitch';
    }

    public function isValidFormFieldCombination(): bool
    {
        return $this->isValid;
    }

    public function getFieldsWithoutPageBreaks(): array
    {
        $formFields = $this->formFields;

        foreach ($formFields as $k => $objFormField) {
            if ($objFormField->type === 'pageSwitch') {
                unset($formFields[$k]);
            }
        }

        return $formFields;
    }

    public function getUrlForStep(string $step): string
    {
        $uri = urldecode($this->requestStack->getCurrentRequest()->getUri());

        $stepParam = $this->getStepParam();

        if ($step === '') {
            return $this->urlParser->removeQueryString([$stepParam], $uri);
        }

        return $this->urlParser->addQueryString($stepParam . '=' . $step, $uri);
    }

    public function hasStep(int|string $step): bool
    {
        return isset($this->formPages[array_search($step, $this->formPageMapper, true)]);
    }

    /**
     * Get the fields for a given step.
     *
     * @return FormFieldModel[]
     * @throws \InvalidArgumentException
     */
    public function getFieldsForStep(string $step): array
    {
        if (!$this->hasStep($step)) {
            throw new \InvalidArgumentException('Step "' . $step . '" is not available!');
        }

        $formPage = $this->getFormPageForStep($step);

        return $formPage->getFields();
    }

    /**
     * Gets alias of the current step.
     */
    public function getCurrentStep(): string
    {
        $alias = Input::get($this->getStepParam());

        if (empty($alias)) {
            return 'start';
        }

        return $alias;
    }

    public function getNextStep(): int|string|null
    {
        $currentAlias = $this->getCurrentStep();
        $index = array_search($currentAlias, $this->formPageMapper, true);

        if ($index === false) {
            return null;
        }

        $steps = \count($this->formPageMapper);

        while (++$index < $steps) {
            if ($this->formPages[$index]->isAccessible($this)) {
                return $this->formPageMapper[$index];
            }
        }

        return null;
    }

    public function getPreviousStep(): string|null
    {
        $currentAlias = $this->getCurrentStep();
        $index = array_search($currentAlias, $this->formPageMapper, true);

        if ($index === false) {
            return null;
        }

        while (--$index >= 0) {
            if ($this->formPages[$index]->isAccessible($this)) {
                return $this->formPages[$index]->alias;
            }
        }

        return null;
    }

    public function getLastStep(): string|null
    {
        return $this->formPages[\count($this->formPages) - 1]?->alias;
    }

    /**
     * Check if current step is the last.
     */
    public function isLastStep(): bool
    {
        $currentIndex = array_search($this->getCurrentStep(), $this->formPageMapper, true);
        $targetIndex = \count($this->formPageMapper) - 2;

        if ($currentIndex >= $targetIndex) {
            return true;
        }

        for (++$currentIndex; $currentIndex <= $targetIndex; ++$currentIndex) {
            if ($this->formPages[$currentIndex]->isAccessible($this)) {
                return false;
            }
        }

        return true;
    }

    public function storeData(array $data = [], array $labels = []): void
    {
        $this->storage->saveStep(
            $this->getCurrentStep(),
            $data,
            $labels,
            $this->files,
        );
    }

    public function setUploadedFiles(array $files): void
    {
        $this->files = $files;
    }

    public function getDataOfStep(string $step): array
    {
        return $this->storage->getByStep($step);
    }

    public function getDataOfAllSteps(): array
    {
        return $this->storage->get($this->formFields);
    }

    public function resetData(): void
    {
        $this->storage->reset();
    }

    public function setPreviousStepsWereInvalid(): void
    {
        $this->storage->invalidatePreviousSteps();
    }

    public function getPreviousStepsWereInvalid(): bool
    {
        return $this->storage->previousStepsWereInvalid();
    }

    public function resetPreviousStepsWereInvalid(): void
    {
        $this->storage->resetInvalidation();
    }

    /**
     * Check if there is data stored for a certain field name.
     *
     * @param string|null $step Current step if null
     * @param string   $key
     */
    public function isStoredInData($fieldName, string|null $step = null, $key = 'submitted'): bool
    {
        $step ??= $this->getCurrentStep();

        return isset($this->getDataOfStep($step)[$key])
            && \array_key_exists($fieldName, $this->getDataOfStep($step)[$key]);
    }

    /**
     * Retrieve the value stored for a certain field name.
     *
     * @param string|null $step Current step if null
     * @param string   $key
     */
    public function fetchFromData(mixed $fieldName, string|null $step = null, string $key = 'submitted')
    {
        $step ??= $this->getCurrentStep();

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
    public function validateSteps(string $stepFrom = 'start', $stepTo = null): bool|string
    {
        if ($stepTo === null) {
            $stepTo = $this->formPageMapper[\count($this->formPageMapper) - 1];
        }

        foreach ($this->formPageMapper as $step) {
            if (!$this->getFormPageForStep($step)->isAccessible($this)) {
                continue;
            }

            if ($this->validateStep($step) === false) {
                return $step;
            }

            if ($step === $stepTo) {
                break;
            }
        }

        return true;
    }

    public function validateStep($step): bool
    {
        $formFields = $this->getFieldsForStep($step);

        foreach ($formFields as $formField) {
            if ($this->validateField($formField, $step) === false) {
                return false;
            }
        }

        return true;
    }

    public function validateField(FormFieldModel $formField, int|string|null $step): bool
    {
        $class = $GLOBALS['TL_FFL'][$formField->type];

        if (!class_exists($class)) {
            return true;
        }

        /** @var Widget $objWidget */
        $objWidget = new $class($formField->row());
        $objWidget->required = $formField->mandatory;
        $objWidget->decodeEntities = true;

        // Needed for the hook
        $form = $this->createDummyForm();

        // HOOK: load form field callback
        if (isset($GLOBALS['TL_HOOKS']['loadFormField']) && \is_array($GLOBALS['TL_HOOKS']['loadFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadFormField'] as $callback) {
                $objCallback = System::importStatic($callback[0]);
                $objWidget = $objCallback->{$callback[1]}($objWidget, $this->getFormId(), $this->form->row(), $form);
            }
        }

        $fakeValidation = false;

        if (!$this->checkWidgetSubmittedInCurrentStep($objWidget)) {
            // Handle regular fields
            if ($this->isStoredInData($objWidget->name, $step)) {
                Input::setPost($formField->name, $this->fetchFromData($objWidget->name, $step));
            }
            else {
                Input::setPost($formField->name, '');
            }

            // Handle files
            // ToDo: Check files (Most likely BC related and not needed anymore?)
            /*if ($this->isStoredInData($objWidget->name, $step, 'files')) {
                // $files = $this->requestStack->getCurrentRequest()->files->all();

                // $_FILES[$objWidget->name] = $this->fetchFromData($objWidget->name, $step, 'files');
            }*/

            $fakeValidation = true;
        }

        $objWidget->validate();

        // HOOK: validate form field callback
        if (isset($GLOBALS['TL_HOOKS']['validateFormField']) && \is_array($GLOBALS['TL_HOOKS']['validateFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['validateFormField'] as $callback) {
                $objCallback = System::importStatic($callback[0]);
                $objWidget = $objCallback->{$callback[1]}($objWidget, $this->getFormId(), $this->form->row(), $form);
            }
        }

        // Reset fake validation
        if ($fakeValidation) {
            Input::setPost($formField->name, null);
        }

        // ToDo: Check files (Most likely BC related and not needed anymore?)
        /*if ($objWidget instanceof \uploadable && isset($_SESSION['FILES'][$objWidget->name]))
        {
            $_FILES[$objWidget->name] = $_SESSION['FILES'][$objWidget->name];
        }*/

        return !$objWidget->hasErrors();
    }

    public function getStepParam(): string
    {
        return $this->form->stepParam ?: 'step';
    }

    public function redirectToStep(self $manager, int|string|null $step): void
    {
        throw new RedirectResponseException($manager->getUrlForStep((string) $step));
    }

    protected function loadFormFieldModels(): void
    {
        $objFormFields = FormFieldModel::findPublishedByPid($this->form->id);
        $formFields = [];

        if ($objFormFields !== null) {
            $formFields = $objFormFields->getModels();
        }

        $form = $this->createDummyForm();

        // HOOK: compile form fields
        if (isset($GLOBALS['TL_HOOKS']['compileFormFields']) && \is_array($GLOBALS['TL_HOOKS']['compileFormFields'])) {
            foreach ($GLOBALS['TL_HOOKS']['compileFormFields'] as $callback) {
                // Do not call ourselves recursively
                if ($callback[0] === CompileFormFieldsListener::class) {
                    continue;
                }

                $objCallback = System::importStatic($callback[0]);
                $formFields = $objCallback->{$callback[1]}($formFields, $this->getFormId(), $form);
            }
        }

        $this->formFields = $formFields;
    }

    protected function getFormPageForStep(int|string $step): FormPage
    {
        return $this->formPages[array_search($step, $this->formPageMapper, true)];
    }

    protected function createDummyForm(): Form
    {
        $id = $this->form->id;

        return new class($id) extends Form {
            public function __construct($id)
            {
                $this->id = $id;
                $this->headline = null;
                $this->typePrefix = null;
                $this->cssID = null;
                $this->strColumn = 'main';
            }
        };
    }

    private function checkWidgetSubmittedInCurrentStep(Widget $objWidget): bool
    {
        // Special handling for captcha field
        if ($objWidget instanceof FormCaptcha) {
            // ToDo Test
            $captcha = $this->session->get('captcha_' . $objWidget->id);

            return isset($_POST[$captcha['key']]);
        }

        return isset($_POST[$objWidget->name]);
    }
}
