<?php

namespace PostLabelCenter\Core\Content\ReturnReasons;

use DateTimeInterface;
use PostLabelCenter\Core\Content\ReturnReasons\Translated\ReturnReasonsTranslatedCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ReturnReasonsEntity extends Entity
{
    use EntityIdTrait;

    protected string $technicalName;
    protected ?DateTimeInterface $createdAt;
    protected ?DateTimeInterface $updatedAt;

    /**
     * @var ReturnReasonsTranslatedCollection|null
     */
    protected $translations;

    /**
     * @return ReturnReasonsTranslatedCollection|null
     */
    public function getTranslations(): ?ReturnReasonsTranslatedCollection
    {
        return $this->translations;
    }

    /**
     * @param ReturnReasonsTranslatedCollection|null $translations
     */
    public function setTranslations(?ReturnReasonsTranslatedCollection $translations): void
    {
        $this->translations = $translations;
    }

    /**
     * @return mixed
     */
    public function getTechnicalName()
    {
        return $this->technicalName;
    }

    /**
     * @param mixed $technicalName
     */
    public function setTechnicalName($technicalName): void
    {
        $this->technicalName = $technicalName;
    }
}
