<?php declare(strict_types=1);

namespace PostLabelCenter\Core\Content\ReturnReasons\Translated;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(ReturnReasonsTranslatedEntity $entity)
 * @method void               set(string $key, ReturnReasonsTranslatedEntity $entity)
 * @method ReturnReasonsTranslatedEntity[]    getIterator()
 * @method ReturnReasonsTranslatedEntity[]    getElements()
 * @method ReturnReasonsTranslatedEntity|null get(string $key)
 * @method ReturnReasonsTranslatedEntity|null first()
 * @method ReturnReasonsTranslatedEntity|null last()
 */
class ReturnReasonsTranslatedCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ReturnReasonsTranslatedEntity::class;
    }

    public function getLanguageIds(): array
    {
        return $this->fmap(function (ReturnReasonsTranslatedEntity $reasonsTranslatedEntity) {
            return $reasonsTranslatedEntity->getLanguageId();
        });
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(function (ReturnReasonsTranslatedEntity $reasonsTranslatedEntity) use ($id) {
            return $reasonsTranslatedEntity->getLanguageId() === $id;
        });
    }

    public function getApiAlias(): string
    {
        return 'plc_return_reasons_translation_collection';
    }
}
