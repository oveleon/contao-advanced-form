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
use Contao\Form;
use Symfony\Component\HttpFoundation\RequestStack;

class FormPageManagerFactory
{
    private array $instances = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlParser $urlParser,
    ) {
    }

    public function getForForm(Form $form): FormPageManager
    {
        return $this->instances[$form->id] ?? $this->instances[$form->id] = new FormPageManager(
            $form,
            $this->requestStack,
            $this->urlParser,
        );
    }
}
