<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\OrderLabels;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(OrderLabelsEntity $entity)
 * @method void               set(string $key, OrderLabelsEntity $entity)
 * @method OrderLabelsEntity[]    getIterator()
 * @method OrderLabelsEntity[]    getElements()
 * @method OrderLabelsEntity|null get(string $key)
 * @method OrderLabelsEntity|null first()
 * @method OrderLabelsEntity|null last()
 */
class OrderLabelsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderLabelsEntity::class;
    }

    public function getApiAlias(): string
    {
        return 'plc_order_labels_collection';
    }
}