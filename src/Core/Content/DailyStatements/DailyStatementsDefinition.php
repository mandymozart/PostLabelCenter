<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\DailyStatements;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
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
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class DailyStatementsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plc_daily_statements';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('document_id', 'documentId'))->addFlags(new ApiAware()),
            (new DateTimeField('plc_date_added', 'plcDateAdded'))->addFlags(new ApiAware()),
            (new DateTimeField('plc_created_on', 'plcCreatedOn'))->addFlags(new ApiAware()),
            (new LongTextField('pdf_data', 'pdfData'))->addFlags(new Required(), new ApiAware()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField(),

            (new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);
    }

    public function getEntityClass(): string
    {
        return DailyStatementsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return DailyStatementsCollection::class;
    }
}