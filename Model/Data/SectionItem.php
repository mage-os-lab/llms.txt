<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Model\Data;

use Magento\Framework\Model\AbstractExtensibleModel;

class SectionItem extends AbstractExtensibleModel
{
    public const KEY_NAME = 'name';
    public const KEY_URL = 'url';
    public const KEY_DESCRIPTION = 'description';

    public function getName(): ?string
    {
        return $this->getData(self::KEY_NAME);
    }

    public function setName(?string $name): self
    {
        return $this->setData(self::KEY_NAME, $name);
    }

    public function getUrl(): ?string
    {
        return $this->getData(self::KEY_URL);
    }

    public function setUrl(?string $url): self
    {
        return $this->setData(self::KEY_URL, $url);
    }

    public function getDescription(): ?string
    {
        return $this->getData(self::KEY_DESCRIPTION);
    }

    public function setDescription(?string $description): self
    {
        return $this->setData(self::KEY_DESCRIPTION, $description);
    }

    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    public function setExtensionAttributes(SectionItemExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
