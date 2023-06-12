<?php

namespace Oveleon\ContaoAdvancedForm\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use Oveleon\ContaoAdvancedForm\FormPageManager;

/**
 * @Hook("prepareFormData")
 */
class PrepareFormDataListener
{
    public function __invoke(array &$submittedData, array &$labels, array $fields, Form $form): void
    {
        $manager = FormPageManager::getInstance($form);

        if (!$manager->isValidFormFieldCombination())
        {
            return;
        }

        $manager->storeData($submittedData, $labels, isset($_SESSION['FILES']) ? (array) $_SESSION['FILES'] : array());

        // Submit form
        if ($manager->isLastStep() && $_POST['pageSwitch'] === 'continue')
        {
            $data = $manager->getDataOfAllSteps();

            $submittedData     = $data['submitted'];
            $labels            = $data['labels'];
            $_SESSION['FILES'] = $data['files'];

            $_POST = $submittedData;

            $_SESSION['FORM_DATA'] = $submittedData;

            $manager->resetData();
            return;
        }
        else
        {
            $_SESSION['FORM_DATA'] = array();
        }

        $manager->redirectToStep($manager, $manager->getNextStep());
    }
}
