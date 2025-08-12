<?php

namespace PostLabelCenter\Core\Content\ReturnReasons;

use PostLabelCenter\Core\Content\ReturnReasons\Translated\ReturnReasonsTranslatedCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ReturnReasonsEntity extends Entity
{
    use EntityIdTrait;

    protected $technicalName;

    /**
     * @var \DateTimeInterface
     */
    protected $createdAt;

    /**
     * @var \DateTimeInterface
     */
    protected $updatedAt;

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
