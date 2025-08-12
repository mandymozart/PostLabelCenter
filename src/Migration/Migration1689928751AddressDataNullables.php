<?php declare(strict_types=1);

namespace PostLabelCenter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1689928751AddressDataNullables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1689928751;
    }

    public function update(Connection $connection): void
    {
            $connection->executeStatement('ALTER TABLE `plc_address_data` MODIFY `default_address` TINYINT(1) NULL DEFAULT 0');
            $connection->executeStatement('ALTER TABLE `plc_address_data` MODIFY `eori_number` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL');
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
