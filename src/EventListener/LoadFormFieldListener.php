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
use Contao\Widget;
use Oveleon\ContaoAdvancedForm\Service\FormPage\FormPageManagerFactory;

#[AsHook('loadFormField')]
class LoadFormFieldListener
{
    public function __construct(
        private readonly FormPageManagerFactory $formPageManagerFactory,
    ) {
    }

    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        $manager = $this->formPageManagerFactory->getForForm($form);

        if ($manager->isStoredInData($widget->name))
        {
            $widget->value = $manager->fetchFromData($widget->name);
        }

        return $widget;
    }
}
