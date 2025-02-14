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

namespace Oveleon\ContaoAdvancedForm\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Oveleon\ContaoAdvancedForm\ContaoAdvancedForm;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoAdvancedForm::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['advanced-form']),
        ];
    }
}
