<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1682416519ShippingServiceCountry extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1682416519;
    }

    public function update(Connection $connection): void
    {
        if ($this->hasColumn('plc_shipping_services', 'country_id', $connection)) {
            $connection->executeStatement('ALTER TABLE `plc_shipping_services` DROP FOREIGN KEY `fk.plc_shipping_services.country_id`');
            $connection->executeStatement('ALTER TABLE `plc_shipping_services` DROP COLUMN `country_id`');
        }

        $connection->executeUpdate('
            CREATE TABLE IF NOT EXISTS `plc_shipping_service_country` (
              `shipping_service_id` BINARY(16) NOT NULL,
              `country_id` BINARY(16) NOT NULL,
              PRIMARY KEY (`shipping_service_id`, `country_id`),
              CONSTRAINT `fk.plc_shipping_service_country.shipping_service_id` FOREIGN KEY (`shipping_service_id`)
                REFERENCES `plc_shipping_services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.plc_shipping_service_country.country_id` FOREIGN KEY (`country_id`)
                REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function hasColumn(string $table, string $columnName, $connection): bool
    {
        return \in_array($columnName, array_column($connection->fetchAllAssociative(\sprintf('SHOW COLUMNS FROM `%s`', $table)), 'Field'), true);
    }
}
