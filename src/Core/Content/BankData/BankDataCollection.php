<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\BankData;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(BankDataEntity $entity)
 * @method void               set(string $key, BankDataEntity $entity)
 * @method BankDataEntity[]    getIterator()
 * @method BankDataEntity[]    getElements()
 * @method BankDataEntity|null get(string $key)
 * @method BankDataEntity|null first()
 * @method BankDataEntity|null last()
 */
class BankDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return BankDataEntity::class;
    }

    public function getApiAlias(): string
    {
        return 'plc_bank_data_collection';
    }
}
