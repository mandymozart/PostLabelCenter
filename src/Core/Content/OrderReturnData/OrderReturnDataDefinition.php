<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\OrderReturnData;

use PostLabelCenter\Core\Content\ReturnReasons\ReturnReasonsDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderReturnDataDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plc_order_return_data';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('return_note', 'returnNote'))->addFlags(new Required(), new ApiAware()),
            (new LongTextField('line_items', 'lineItems'))->addFlags(new Required(), new ApiAware()),
            (new StringField('document_id', 'documentId'))->addFlags(new Required(), new ApiAware()),
            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new FkField('return_reason_id', 'returnReasonId', ReturnReasonsDefinition::class))->addFlags(new Required(), new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField(),

            (new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('returnReason', 'return_reason_id', ReturnReasonsDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);
    }

    public function getEntityClass(): string
    {
        return OrderReturnDataEntity::class;
    }

    public function getCollectionClass(): string
    {
        return OrderReturnDataCollection::class;
    }
}