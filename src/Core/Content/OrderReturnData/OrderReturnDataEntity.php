<?php

namespace PostLabelCenter\Core\Content\OrderReturnData;

use DateTimeInterface;
use PostLabelCenter\Core\Content\ReturnReasons\ReturnReasonsEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class OrderReturnDataEntity extends Entity
{
    use EntityIdTrait;

    protected string $returnNote;
    protected string $lineItems;
    protected string $orderId;
    protected string $returnReasonId;
    protected string $documentId;
    protected ?OrderEntity $order;
    protected ?ReturnReasonsEntity $returnReason;

    protected ?DateTimeInterface $createdAt;
    protected ?DateTimeInterface $updatedAt;

    /**
     * @return string
     */
    public function getReturnNote(): string
    {
        return $this->returnNote;
    }

    /**
     * @param string $returnNote
     */
    public function setReturnNote(string $returnNote): void
    {
        $this->returnNote = $returnNote;
    }

    /**
     * @return string
     */
    public function getLineItems(): string
    {
        return $this->lineItems;
    }

    /**
     * @param string $lineItems
     */
    public function setLineItems(string $lineItems): void
    {
        $this->lineItems = $lineItems;
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
     * @return string
     */
    public function getReturnReasonId(): string
    {
        return $this->returnReasonId;
    }

    /**
     * @param string $returnReasonId
     */
    public function setReturnReasonId(string $returnReasonId): void
    {
        $this->returnReasonId = $returnReasonId;
    }

    /**
     * @return OrderEntity|null
     */
    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    /**
     * @param OrderEntity|null $order
     */
    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }

    /**
     * @return ReturnReasonsEntity|null
     */
    public function getReturnReason(): ?ReturnReasonsEntity
    {
        return $this->returnReason;
    }

    /**
     * @param ReturnReasonsEntity|null $returnReason
     */
    public function setReturnReason(?ReturnReasonsEntity $returnReason): void
    {
        $this->returnReason = $returnReason;
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
}