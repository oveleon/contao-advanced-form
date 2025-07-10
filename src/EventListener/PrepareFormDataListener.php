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

use Codefog\HasteBundle\FileUploadNormalizer;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Oveleon\ContaoAdvancedForm\Service\FormPage\FormPageManagerFactory;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('prepareFormData')]
class PrepareFormDataListener
{
    public function __construct(
        private readonly FormPageManagerFactory $formPageManager,
        private readonly FileUploadNormalizer $fileUploadNormalizer,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(array &$submittedData, array &$labels, array $fields, Form $form, array &$files): void
    {
        $manager = $this->formPageManager->getForForm($form);

        if (!$manager->isValidFormFieldCombination())
        {
            return;
        }

        if ($files !== [])
        {
            $manager->setUploadedFiles($this->fileUploadNormalizer->normalize($files));
        }

        $manager->storeData($submittedData, $labels);

        // Submit form
        if ($manager->isLastStep() && $this->requestStack->getCurrentRequest()?->get('pageSwitch') === 'continue')
        {
            $data = $manager->getDataOfAllSteps();

            $submittedData = $data['submitted'];
            $labels = $data['labels'];
            $files = $data['files'];

            $manager->resetData();

            return;
        }

        $manager->redirectToStep($manager, $manager->getNextStep());
    }
}
