<?php

namespace PostLabelCenter\Core\Content\OrderLabels;

use DateTimeInterface;
use PostLabelCenter\Core\Content\BankData\BankDataEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class OrderLabelsEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;
    protected string $documentId;
    protected string $atTrackingNumber;
    protected string $intTrackingNumber;
    protected bool $downloaded;
    protected bool $shippingDocuments;
    protected string $orderId;

    protected ?DateTimeInterface $createdAt;
    protected ?DateTimeInterface $updatedAt;

    /**
     * @return string
     */
    public function getAtTrackingNumber(): string
    {
        return $this->atTrackingNumber;
    }

    /**
     * @param string $atTrackingNumber
     */
    public function setAtTrackingNumber(string $atTrackingNumber): void
    {
        $this->atTrackingNumber = $atTrackingNumber;
    }

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
     * @return string
     */
    public function getIntTrackingNumber(): string
    {
        return $this->intTrackingNumber;
    }

    /**
     * @param string $intTrackingNumber
     */
    public function setIntTrackingNumber(string $intTrackingNumber): void
    {
        $this->intTrackingNumber = $intTrackingNumber;
    }

    /**
     * @return bool
     */
    public function isDownloaded(): bool
    {
        return $this->downloaded;
    }

    /**
     * @param bool $downloaded
     */
    public function setDownloaded(bool $downloaded): void
    {
        $this->downloaded = $downloaded;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return bool
     */
    public function isShippingDocuments(): bool
    {
        return $this->shippingDocuments;
    }

    /**
     * @param bool $shippingDocuments
     */
    public function setShippingDocuments(bool $shippingDocuments): void
    {
        $this->shippingDocuments = $shippingDocuments;
    }
}