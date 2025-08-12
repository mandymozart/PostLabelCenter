<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\ReturnReasons;

use PostLabelCenter\Core\Content\ReturnReasons\Translated\ReturnReasonsTranslatedDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ReturnReasonsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plc_return_reasons';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('technical_name', 'technicalName'))->addFlags(new ApiAware()),
            (new TranslatedField('name'))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(ReturnReasonsTranslatedDefinition::class, 'plc_return_reasons_id'))->addFlags(new ApiAware(), new Required()),
            new CreatedAtField(),
            new UpdatedAtField()
        ]);
    }

    public function getEntityClass(): string
    {
        return ReturnReasonsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ReturnReasonsCollection::class;
    }
}