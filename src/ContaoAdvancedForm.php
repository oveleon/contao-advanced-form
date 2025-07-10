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

namespace Oveleon\ContaoAdvancedForm;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoAdvancedForm extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
