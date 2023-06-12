<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoAdvancedForm;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoAdvancedForm extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
