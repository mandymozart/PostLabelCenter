<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642506041ShippingServices extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1682416491;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `plc_shipping_services` (
            `id` BINARY(16) NOT NULL,
            `display_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `shipping_product` JSON,
            `feature_list` JSON,
            `country_id` BINARY(16) NOT NULL,
            `sales_channel_id` BINARY(16) NOT NULL,
            `customs_information` JSON,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3),
            PRIMARY KEY (`id`),
            CONSTRAINT `fk.plc_shipping_services.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                REFERENCES `sales_channel` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT `fk.plc_shipping_services.country_id` FOREIGN KEY (`country_id`)
                REFERENCES `country` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
