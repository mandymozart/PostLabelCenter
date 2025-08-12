<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642506047DailyStatements extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642506047;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `plc_daily_statements` (
            `id` BINARY(16) NOT NULL,
            `document_id` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `plc_date_added` DATETIME(3),
            `plc_created_on` DATETIME(3),
            `sales_channel_id` BINARY(16) NOT NULL,
            `pdf_data` JSON NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3),
            PRIMARY KEY (`id`),
            CONSTRAINT `fk.plc_daily_statements.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}