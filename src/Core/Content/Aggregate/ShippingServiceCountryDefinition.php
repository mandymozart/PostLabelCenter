<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\Aggregate;

use PostLabelCenter\Core\Content\ShippingServices\ShippingServicesDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\System\Country\CountryDefinition;

class ShippingServiceCountryDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'plc_shipping_service_country';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('shipping_service_id', 'shippingServiceId', ShippingServicesDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('country_id', 'countryId', CountryDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('shippingService', 'shipping_service_id', ShippingServicesDefinition::class, 'id', false),
            new ManyToOneAssociationField('countries', 'country_id', CountryDefinition::class, 'id', false),
        ]);
    }
}
