<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\BankData;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class BankDataDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plc_bank_data';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('display_name', 'displayName')),
            (new StringField('account_holder', 'accountHolder')),
            (new StringField('bic', 'bic')),
            (new StringField('iban', 'iban')),
            new CreatedAtField(),
            new UpdatedAtField()
        ]);
    }

    public function getEntityClass(): string
    {
        return BankDataEntity::class;
    }

    public function getCollectionClass(): string
    {
        return BankDataCollection::class;
    }
}