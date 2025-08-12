<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\ShippingServices;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(ShippingServicesEntity $entity)
 * @method void               set(string $key, ShippingServicesEntity $entity)
 * @method ShippingServicesEntity[]    getIterator()
 * @method ShippingServicesEntity[]    getElements()
 * @method ShippingServicesEntity|null get(string $key)
 * @method ShippingServicesEntity|null first()
 * @method ShippingServicesEntity|null last()
 */
class ShippingServicesCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ShippingServicesEntity::class;
    }

    public function getApiAlias(): string
    {
        return 'plc_shipping_services_collection';
    }
}