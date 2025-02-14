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

use Contao\FormFieldModel;
use Contao\FrontendUser;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class FormPage
{
    protected array $objFormFields = [];

    protected bool $accessible = true;

    protected bool $callable = true;

    public function __construct(
        protected FormFieldModel|null $pageSwitch = null,
    ) {
    }

    public function __get(string $strKey): mixed
    {
        return match ($strKey)
        {
            'accessible' => $this->accessible,
            'alias' => $this->pageSwitch->formPageAlias ?? 'start',
            default => null,
        };
    }

    public function addField(FormFieldModel $objFormField): void
    {
        $this->objFormFields[] = $objFormField;
    }

    public function getFields(): array
    {
        return $this->objFormFields;
    }

    public function isAccessible(FormPageManager $manager): mixed
    {
        $accessible = !$this->pageSwitch?->formPageAccessible || $this->evaluateExpression($manager);

        if (!$accessible)
        {
            return false;
        }

        $container = System::getContainer();
        $feUserLoggedIn = $container->get('contao.security.token_checker')->hasFrontendUser();

        if ($this->pageSwitch?->guests && $feUserLoggedIn)
        {
            return false;
        }

        if (!$this->pageSwitch?->protected)
        {
            return true;
        }

        if (
            !$feUserLoggedIn
            || [] === ($groups = StringUtil::deserialize($this->pageSwitch?->groups, true))
        ) {
            return false;
        }

        /** @var FrontendUser $user */
        $user = $container->get('security.helper')?->getUser();

        return array_intersect($groups, $user->groups) !== [];
    }

    private function evaluateExpression(FormPageManager $manager): bool
    {
        $condition = $this->generateCondition((string) $this->pageSwitch->condition);
        $submitted = $manager->getDataOfAllSteps()['fieldsetSubmitted'];

        // Create EL and register native php functions
        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('floatval'));
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('strval'));
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('intval'));
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('in_array'));
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('str_contains'));

        // Evaluate condition
        return $expressionLanguage->evaluate($condition, $submitted);
    }

    private function generateCondition(string $condition): string
    {
        $strCondition = preg_replace('/\$([A-Za-z0-9_]+)/u', '$1', $condition);

        return html_entity_decode((string) $strCondition);
    }
}
