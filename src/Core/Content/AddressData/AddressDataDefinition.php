<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\AddressData;

use PostLabelCenter\Core\Content\BankData\BankDataDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\Salutation\SalutationDefinition;

class AddressDataDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plc_address_data';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('display_name', 'displayName'))->addFlags(new Required(), new ApiAware()),
            (new BoolField('default_address', 'defaultAddress'))->addFlags(new ApiAware()),
            (new StringField('eori_number', 'eoriNumber'))->addFlags(new ApiAware()),
            (new StringField('email', 'email'))->addFlags(new Required(), new ApiAware()),
            (new FkField('salutation_id', 'salutationId', SalutationDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new StringField('company', 'company'))->addFlags(new ApiAware()),
            (new StringField('department', 'department'))->addFlags(new ApiAware()),
            (new StringField('first_name', 'firstName'))->addFlags(new Required(), new ApiAware()),
            (new StringField('last_name', 'lastName'))->addFlags(new Required(), new ApiAware()),
            (new StringField('street', 'street'))->addFlags(new Required(), new ApiAware()),
            (new StringField('city', 'city'))->addFlags(new Required(), new ApiAware()),
            (new StringField('zipcode', 'zipcode'))->addFlags(new Required(), new ApiAware()),
            (new FkField('country_id', 'countryId', CountryDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new StringField('phone_number', 'phoneNumber'))->addFlags(new Required(), new ApiAware()),
            (new StringField('address_type', 'addressType'))->addFlags(new Required(), new ApiAware()),
            (new FkField('bank_data_id', 'bankDataId', BankDataDefinition::class))->addFlags(new ApiAware()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required(), new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField(),

            (new ManyToOneAssociationField('country', 'country_id', CountryDefinition::class, 'id', true))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('salutation', 'salutation_id', SalutationDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('bankData', 'bank_data_id', BankDataDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);
    }

    public function getEntityClass(): string
    {
        return AddressDataEntity::class;
    }

    public function getCollectionClass(): string
    {
        return AddressDataCollection::class;
    }
}