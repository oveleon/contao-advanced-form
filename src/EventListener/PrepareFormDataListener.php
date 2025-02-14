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
use Oveleon\ContaoAdvancedForm\Service\FormPage\FormPageManagerFactory;

#[AsHook('prepareFormData')]
class PrepareFormDataListener
{
    public function __construct(
        private readonly FormPageManagerFactory $formPageManager,
    ) {
    }

    public function __invoke(array &$submittedData, array &$labels, array $fields, Form $form): void
    {
        $manager = $this->formPageManager->getForForm($form);

        if (!$manager->isValidFormFieldCombination())
        {
            return;
        }

        $manager->storeData($submittedData);

        // Submit form
        if ($manager->isLastStep() && $_POST['pageSwitch'] === 'continue')
        {
            $data = $manager->getDataOfAllSteps();

            $submittedData = $data['submitted'];
            $labels = $data['labels'];
            $_SESSION['FILES'] = $data['files'];

            $_POST = $submittedData;

            $_SESSION['FORM_DATA'] = $submittedData;

            $manager->resetData();

            return;
        }
        $_SESSION['FORM_DATA'] = [];

        $manager->redirectToStep($manager, $manager->getNextStep());
    }
}
