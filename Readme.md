# Migration of short URLs to URL rewrites for Contao Open Source CMS

Since the extension [fritzmg/contao-short-urls](https://packagist.org/packages/fritzmg/contao-short-urls) has been
abandoned, you should use [terminal42/contao-url-rewrite](https://packagist.org/packages/terminal42/contao-url-rewrite).
This bundle provides a migration for short URLs to URL rewrites.

## Installation

Install the bundle via Composer:

```
composer require bwein-net/contao-migrate-short-urls
```

## Run the migration

After the installation you can run the migration via console `contao:migrate` or you open the contao install tool.

> Attention: You should not run the database migration which removes the auxiliary column `migrated` from `tl_short_urls`, because the migration of this extension will run again!

## Uninstall extensions

After running the migration you can savely and should uninstall both extensions:

```
composer remove bwein-net/contao-migrate-short-urls
composer remove fritzmg/contao-short-urls
```
