<?php

namespace PostLabelCenter\Core\Content\DailyStatements;

use DateTimeInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class DailyStatementsEntity extends Entity
{
    use EntityIdTrait;

    protected string $documentId;
    protected DateTimeInterface $plcDateAdded;
    protected DateTimeInterface $plcCreatedOn;
    protected string $salesChannelId;
    protected SalesChannelEntity $salesChannel;
    protected string $pdfData;

    /**
     * @var DateTimeInterface
     */
    protected $createdAt;

    /**
     * @var DateTimeInterface
     */
    protected $updatedAt;

    /**
     * @return string
     */
    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    /**
     * @param string $documentId
     */
    public function setDocumentId(string $documentId): void
    {
        $this->documentId = $documentId;
    }

    /**
     * @return DateTimeInterface
     */
    public function getPlcDateAdded(): DateTimeInterface
    {
        return $this->plcDateAdded;
    }

    /**
     * @param DateTimeInterface $plcDateAdded
     */
    public function setPlcDateAdded(DateTimeInterface $plcDateAdded): void
    {
        $this->plcDateAdded = $plcDateAdded;
    }

    /**
     * @return DateTimeInterface
     */
    public function getPlcCreatedOn(): DateTimeInterface
    {
        return $this->plcCreatedOn;
    }

    /**
     * @param DateTimeInterface $plcCreatedOn
     */
    public function setPlcCreatedOn(DateTimeInterface $plcCreatedOn): void
    {
        $this->plcCreatedOn = $plcCreatedOn;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeInterface $createdAt
     */
    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTimeInterface
     */
    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTimeInterface $updatedAt
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getPdfData(): string
    {
        return $this->pdfData;
    }

    /**
     * @param string $pdfData
     */
    public function setPdfData(string $pdfData): void
    {
        $this->pdfData = $pdfData;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     */
    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return SalesChannelEntity
     */
    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    /**
     * @param SalesChannelEntity $salesChannel
     */
    public function setSalesChannel(SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }
}
