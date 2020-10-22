<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon MPFormConditions.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\MPFormConditions\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Oveleon\MPFormConditions\MPFormConditions;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(MPFormConditions::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['contao-mp_forms-conditions']),
        ];
    }
}
