<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642506038BankData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642506038;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `plc_bank_data` (
            `id` BINARY(16) NOT NULL,
            `display_name`  VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `account_holder`  VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `bic`  VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `iban`  VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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