<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642506037ReturnReasons extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642506037;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `plc_return_reasons` (
            `id` BINARY(16) NOT NULL,
            `technical_name`  VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3),
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `plc_return_reasons_translation` (
            `plc_return_reasons_id` BINARY(16) NOT NULL,
            `language_id` BINARY(16) NOT NULL,
            `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3),
            PRIMARY KEY (`plc_return_reasons_id`, `language_id`),
            CONSTRAINT `fk.plc_return_reasons_translation.language_id` FOREIGN KEY (`language_id`)
                REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk.plc_return_reasons_translation.return_reason_id` FOREIGN KEY (`plc_return_reasons_id`)
                REFERENCES `plc_return_reasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
