<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\ReturnReasons\Translated;

use PostLabelCenter\Core\Content\ReturnReasons\ReturnReasonsDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ReturnReasonsTranslatedDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'plc_return_reasons_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
        ]);
    }

    protected function getParentDefinitionClass(): string
    {
        return ReturnReasonsDefinition::class;
    }

    public function getEntityClass(): string
    {
        return ReturnReasonsTranslatedEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ReturnReasonsTranslatedCollection::class;
    }
}