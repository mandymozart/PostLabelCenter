<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\AddressData;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(AddressDataEntity $entity)
 * @method void               set(string $key, AddressDataEntity $entity)
 * @method AddressDataEntity[]    getIterator()
 * @method AddressDataEntity[]    getElements()
 * @method AddressDataEntity|null get(string $key)
 * @method AddressDataEntity|null first()
 * @method AddressDataEntity|null last()
 */
class AddressDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AddressDataEntity::class;
    }

    public function getApiAlias(): string
    {
        return 'plc_address_data_collection';
    }
}