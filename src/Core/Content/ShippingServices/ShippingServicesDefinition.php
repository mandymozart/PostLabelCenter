<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\ShippingServices;

use PostLabelCenter\Core\Content\Aggregate\ShippingServiceCountryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class ShippingServicesDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plc_shipping_services';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('display_name', 'displayName'))->addFlags(new Required(), new ApiAware()),
            (new LongTextField('shipping_product', 'shippingProduct'))->addFlags(new Required(), new ApiAware()),
            (new LongTextField('feature_list', 'featureList'))->addFlags(new Required(), new ApiAware()),
            (new LongTextField('customs_information', 'customsInformation'))->addFlags(new ApiAware()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required(), new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField(),

            (new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false))->addFlags(new ApiAware()),
            new ManyToManyAssociationField('countries', CountryDefinition::class, ShippingServiceCountryDefinition::class, 'shipping_service_id', 'country_id')
        ]);
    }

    public function getEntityClass(): string
    {
        return ShippingServicesEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ShippingServicesCollection::class;
    }
}