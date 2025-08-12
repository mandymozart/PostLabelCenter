<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642506039AddressData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642506039;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `plc_address_data` (
            `id` BINARY(16) NOT NULL,
            `display_name` VARCHAR(150) COLLATE utf8mb4_unicode_ci NULL,
            `email` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
            `default_address` TINYINT(1) NOT NULL DEFAULT 0,
            `eori_number` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL,
            `salutation_id` BINARY(16) NOT NULL,
            `company` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
            `department` VARCHAR(150) COLLATE utf8mb4_unicode_ci NULL,
            `first_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
            `last_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
            `street` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `city` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `zipcode` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `country_id` BINARY(16) NOT NULL,
            `phone_number` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `address_type` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `bank_data_id` BINARY(16) NULL,
            `sales_channel_id` BINARY(16) NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3),
            PRIMARY KEY (`id`),
            CONSTRAINT `fk.plc_address_data.salutation_id` FOREIGN KEY (`salutation_id`)
                REFERENCES `salutation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk.plc_address_data.country_id` FOREIGN KEY (`country_id`)
                REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk.plc_address_data.bank_data_id` FOREIGN KEY (`bank_data_id`)
                REFERENCES `plc_bank_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk.plc_address_data.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
