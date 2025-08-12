<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\OrderLabels;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderLabelsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plc_order_labels';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name'))->addFlags(new Required(), new ApiAware()),
            (new StringField('document_id', 'documentId'))->addFlags(new Required(), new ApiAware()),
            (new LongTextField('at_tracking_number', 'atTrackingNumber'))->addFlags(new ApiAware()),
            (new LongTextField('int_tracking_number', 'intTrackingNumber'))->addFlags(new ApiAware()),
            (new BoolField('downloaded', 'downloaded'))->addFlags(new ApiAware()),
            (new BoolField('shipping_documents', 'shippingDocuments'))->addFlags(new ApiAware()),
            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required(), new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField()
        ]);
    }

    public function getEntityClass(): string
    {
        return OrderLabelsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return OrderLabelsCollection::class;
    }
}
