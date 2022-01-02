<?php

declare(strict_types=1);

/*
 * This file is part of mgration of short URLs to URL rewrites for Contao Open Source CMS.
 *
 * (c) bwein.net
 *
 * @license MIT
 */

namespace Bwein\MigrateShortUrls\ContaoManager;

use Bwein\MigrateShortUrls\BweinMigrateShortUrlsBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\UrlRewriteBundle\Terminal42UrlRewriteBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(BweinMigrateShortUrlsBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class, Terminal42UrlRewriteBundle::class, 'short_urls']),
        ];
    }
}
