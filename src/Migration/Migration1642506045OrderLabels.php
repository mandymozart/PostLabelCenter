<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642506045OrderLabels extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642506045;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `plc_order_labels` (
            `id` BINARY(16) NOT NULL,
            `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `document_id` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `at_tracking_number` JSON,
            `int_tracking_number` JSON,
            `downloaded` TINYINT(1) NULL DEFAULT 0,
            `shipping_documents` TINYINT(1) NULL DEFAULT 0,
            `order_id` BINARY(16) NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3),
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
