<?php

namespace Oveleon\ContaoAdvancedForm\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use Contao\Widget;
use Oveleon\ContaoAdvancedForm\FormPageManager;

/**
 * @Hook("loadFormField")
 */
class LoadFormFieldListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        $manager = FormPageManager::getInstance($form);

        if ($manager->isStoredInData($widget->name))
        {
            $widget->value = $manager->fetchFromData($widget->name);
        }

        return $widget;
    }
}
