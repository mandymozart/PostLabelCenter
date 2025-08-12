<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\OrderReturnData;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(OrderReturnDataEntity $entity)
 * @method void               set(string $key, OrderReturnDataEntity $entity)
 * @method OrderReturnDataEntity[]    getIterator()
 * @method OrderReturnDataEntity[]    getElements()
 * @method OrderReturnDataEntity|null get(string $key)
 * @method OrderReturnDataEntity|null first()
 * @method OrderReturnDataEntity|null last()
 */
class OrderReturnDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderReturnDataEntity::class;
    }

    public function getApiAlias(): string
    {
        return 'plc_order_return_data_collection';
    }
}