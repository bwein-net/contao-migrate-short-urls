<?php

declare(strict_types=1);

/*
 * This file is part of mgration of short URLs to URL rewrites for Contao Open Source CMS.
 *
 * (c) bwein.net
 *
 * @license MIT
 */

namespace Bwein\MigrateShortUrls\Migration;

use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Terminal42\UrlRewriteBundle\EventListener\RewriteContainerListener;

class ShortUrlsMigration implements MigrationInterface
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var RewriteContainerListener
     */
    private $rewriteContainerListener;

    public function __construct(Connection $connection, RewriteContainerListener $rewriteContainerListener)
    {
        $this->connection = $connection;
        $this->rewriteContainerListener = $rewriteContainerListener;
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (null === $schemaManager || !$schemaManager->tablesExist(['tl_url_rewrite', 'tl_short_urls'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_short_urls');

        if (!isset($columns['migrated'])) {
            $this->connection->executeQuery(
                'ALTER TABLE tl_short_urls ADD migrated BOOLEAN NOT NULL DEFAULT 0 AFTER disable'
            );
        }

        $query = 'SELECT true FROM tl_short_urls WHERE migrated = 0 LIMIT 1';

        return (bool) $this->connection->executeQuery($query)->fetchOne();
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function run(): MigrationResult
    {
        $domains = $this->getPageDomains();
        $conversionDateTime = date('Y-m-d H:i');

        $statement = $this->connection->executeQuery('SELECT * FROM tl_short_urls WHERE migrated = 0');

        while (false !== ($row = $statement->fetchAssociative())) {
            $statementInsert = $this->connection->prepare(
                "
                INSERT INTO tl_url_rewrite (
                    name,
                    type,
                    priority,
                    comment,
                    inactive,
                    requestHosts,
                    requestPath,
                    requestRequirements,
                    requestCondition,
                    responseCode,
                    responseUri,
                    tstamp
                ) VALUES
                (
                    :name,
                    'basic',
                    0,
                    :comment,
                    :inactive,
                    :requestHosts,
                    :requestPath,
                    NULL,
                    '',
                    :responseCode,
                    :responseUri,
                    :tstamp
                 )"
            );

            $statementInsert->executeStatement(
                [
                    ':name' => $row['name'],
                    ':comment' => sprintf('Short url ID %s [%s]', $row['id'], $conversionDateTime),
                    ':inactive' => (int) $row['disable'],
                    ':requestHosts' => !empty($row['domain']) && isset($domains[$row['domain']]) ? serialize([$domains[$row['domain']]]) : null,
                    ':requestPath' => $row['name'],
                    ':responseCode' => 'temporary' === $row['redirect'] ? 302 : 301,
                    ':responseUri' => $row['target'],
                    ':tstamp' => (int) $row['tstamp'],
                ]
            );

            $statementMigrate = $this->connection->prepare(
                'UPDATE tl_short_urls SET migrated = 1 WHERE id = :id'
            );
            $statementMigrate->executeStatement([':id' => $row['id']]);
        }

        $this->rewriteContainerListener->onRecordsModified();

        return new MigrationResult(true, $this->getName().' successful');
    }

    public function getName(): string
    {
        return 'Short URLs Migration';
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    private function getPageDomains(): array
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (null === $schemaManager || !$schemaManager->tablesExist(['tl_page'])) {
            return [];
        }
        $statement = $this->connection->executeQuery(
            "SELECT id, dns
                FROM tl_page
                WHERE type = 'root'
                  AND dns != ''"
        );

        return array_column($statement->fetchAllAssociative(), 'dns', 'id');
    }
}
