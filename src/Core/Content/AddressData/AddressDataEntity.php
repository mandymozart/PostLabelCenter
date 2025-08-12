<?php

namespace PostLabelCenter\Core\Content\AddressData;

use PostLabelCenter\Core\Content\BankData\BankDataEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Salutation\SalutationEntity;

class AddressDataEntity extends Entity
{
    use EntityIdTrait;

    protected string $displayName;
    protected bool $defaultAddress;
    protected string $salutationId;
    protected ?string $eoriNumber;
    protected string $email;
    protected ?string $company;
    protected ?string $department;
    protected string $firstName;
    protected string $lastName;
    protected string $street;
    protected string $city;
    protected string $zipcode;
    protected string $phoneNumber;
    protected string $addressType;
    protected string $countryId;
    protected ?string $bankDataId;
    protected string $salesChannelId;
    protected ?CountryEntity $country;
    protected ?BankDataEntity $bankData;
    protected ?SalesChannelEntity $salesChannel;
    protected ?SalutationEntity $salutation;

    /**
     * @var \DateTimeInterface
     */
    protected $createdAt;

    /**
     * @var \DateTimeInterface
     */
    protected $updatedAt;

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getZipcode(): string
    {
        return $this->zipcode;
    }

    /**
     * @param string $zipcode
     */
    public function setZipcode(string $zipcode): void
    {
        $this->zipcode = $zipcode;
    }

    /**
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getAddressType(): string
    {
        return $this->addressType;
    }

    /**
     * @param string $addressType
     */
    public function setAddressType(string $addressType): void
    {
        $this->addressType = $addressType;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeInterface $updatedAt
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getSalutationId(): string
    {
        return $this->salutationId;
    }

    /**
     * @param string $salutationId
     */
    public function setSalutationId(string $salutationId): void
    {
        $this->salutationId = $salutationId;
    }

    /**
     * @return bool
     */
    public function isDefaultAddress(): bool
    {
        return $this->defaultAddress;
    }

    /**
     * @param bool $defaultAddress
     */
    public function setDefaultAddress(bool $defaultAddress): void
    {
        $this->defaultAddress = $defaultAddress;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string|null $company
     */
    public function setCompany(?string $company): void
    {
        $this->company = $company;
    }

    /**
     * @return string|null
     */
    public function getDepartment(): ?string
    {
        return $this->department;
    }

    /**
     * @param string|null $department
     */
    public function setDepartment(?string $department): void
    {
        $this->department = $department;
    }

    /**
     * @return string
     */
    public function getCountryId(): string
    {
        return $this->countryId;
    }

    /**
     * @param string $countryId
     */
    public function setCountryId(string $countryId): void
    {
        $this->countryId = $countryId;
    }

    /**
     * @return string|null
     */
    public function getBankDataId(): ?string
    {
        return $this->bankDataId;
    }

    /**
     * @param string|null $bankDataId
     */
    public function setBankDataId(?string $bankDataId): void
    {
        $this->bankDataId = $bankDataId;
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
     * @return string|null
     */
    public function getEoriNumber(): ?string
    {
        return $this->eoriNumber;
    }

    /**
     * @param string|null $eoriNumber
     */
    public function setEoriNumber(?string $eoriNumber): void
    {
        $this->eoriNumber = $eoriNumber;
    }

    /**
     * @return CountryEntity|null
     */
    public function getCountry(): ?CountryEntity
    {
        return $this->country;
    }

    /**
     * @param CountryEntity|null $country
     */
    public function setCountry(?CountryEntity $country): void
    {
        $this->country = $country;
    }

    /**
     * @return BankDataEntity|null
     */
    public function getBankData(): ?BankDataEntity
    {
        return $this->bankData;
    }

    /**
     * @param BankDataEntity|null $bankData
     */
    public function setBankData(?BankDataEntity $bankData): void
    {
        $this->bankData = $bankData;
    }

    /**
     * @return SalesChannelEntity|null
     */
    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    /**
     * @param SalesChannelEntity|null $salesChannel
     */
    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    /**
     * @return SalutationEntity|null
     */
    public function getSalutation(): ?SalutationEntity
    {
        return $this->salutation;
    }

    /**
     * @param SalutationEntity|null $salutation
     */
    public function setSalutation(?SalutationEntity $salutation): void
    {
        $this->salutation = $salutation;
    }
}
