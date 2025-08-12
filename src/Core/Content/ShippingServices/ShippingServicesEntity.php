<?php

namespace PostLabelCenter\Core\Content\ShippingServices;

use DateTimeInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Country\CountryCollection;

class ShippingServicesEntity extends Entity
{
    use EntityIdTrait;

    protected string $displayName;
    protected string $shippingProduct;
    protected string $featureList;
    protected string $customsInformation;
    protected string $salesChannelId;
    protected ?CountryCollection $countries = null;

    protected ?DateTimeInterface $createdAt;
    protected ?DateTimeInterface $updatedAt;

    /**
     * @return string
     */
    public function getShippingProduct(): string
    {
        return $this->shippingProduct;
    }

    /**
     * @param string $shippingProduct
     */
    public function setShippingProduct(string $shippingProduct): void
    {
        $this->shippingProduct = $shippingProduct;
    }

    /**
     * @return string
     */
    public function getFeatureList(): string
    {
        return $this->featureList;
    }

    /**
     * @param string $featureList
     */
    public function setFeatureList(string $featureList): void
    {
        $this->featureList = $featureList;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
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
     * @return string
     */
    public function getCustomsInformation(): string
    {
        return $this->customsInformation;
    }

    /**
     * @param string $customsInformation
     */
    public function setCustomsInformation(string $customsInformation): void
    {
        $this->customsInformation = $customsInformation;
    }

    /**
     * @return CountryCollection|null
     */
    public function getCountries(): ?CountryCollection
    {
        return $this->countries;
    }

    /**
     * @param CountryCollection|null $countries
     */
    public function setCountries(?CountryCollection $countries): void
    {
        $this->countries = $countries;
    }
}
