<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\ReturnReasons;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(ReturnReasonsEntity $entity)
 * @method void               set(string $key, ReturnReasonsEntity $entity)
 * @method ReturnReasonsEntity[]    getIterator()
 * @method ReturnReasonsEntity[]    getElements()
 * @method ReturnReasonsEntity|null get(string $key)
 * @method ReturnReasonsEntity|null first()
 * @method ReturnReasonsEntity|null last()
 */
class ReturnReasonsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ReturnReasonsEntity::class;
    }

    public function getApiAlias(): string
    {
        return 'plc_return_reasons_collection';
    }
}
