<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642506050OrderReturnData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642506050;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `plc_order_return_data` (
            `id` BINARY(16) NOT NULL,
            `return_note` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `line_items` JSON NULL,
            `order_id` BINARY(16) NOT NULL,
            `return_reason_id` BINARY(16) NOT NULL,
            `document_id` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3),
            PRIMARY KEY (`id`),
            CONSTRAINT `fk.plc_return_reasons.return_reason_id` FOREIGN KEY (`return_reason_id`)
                REFERENCES `plc_return_reasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
