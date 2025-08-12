<?php

namespace PostLabelCenter\Core\Content\ReturnReasons\Translated;

use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class ReturnReasonsTranslatedEntity extends TranslationEntity
{
    /**
     * @var string|null
     */
    protected $name;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
