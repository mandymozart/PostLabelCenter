<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\DailyStatements;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(DailyStatementsEntity $entity)
 * @method void               set(string $key, DailyStatementsEntity $entity)
 * @method DailyStatementsEntity[]    getIterator()
 * @method DailyStatementsEntity[]    getElements()
 * @method DailyStatementsEntity|null get(string $key)
 * @method DailyStatementsEntity|null first()
 * @method DailyStatementsEntity|null last()
 */
class DailyStatementsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return DailyStatementsEntity::class;
    }

    public function getApiAlias(): string
    {
        return 'plc_order_labels_collection';
    }
}