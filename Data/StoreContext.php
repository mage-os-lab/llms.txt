<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Data;

use Magento\Framework\DataObject;

class StoreContext extends DataObject
{
    public const KEY_STORE_ID = 'store_id';
    public const KEY_NAME = 'name';
    public const KEY_DESCRIPTION = 'description';
    public const KEY_URL = 'url';
    public const KEY_LOCALE = 'locale';
    public const KEY_CATEGORIES = 'categories';
    public const KEY_PRODUCTS = 'products';
    public const KEY_CMS_PAGES = 'cms_pages';

    public function getStoreId(): ?int
    {
        return $this->getData(self::KEY_STORE_ID);
    }

    public function setStoreId(?int $storeId): self
    {
        return $this->setData(self::KEY_STORE_ID, $storeId);
    }

    public function getName(): ?string
    {
        return $this->getData(self::KEY_NAME);
    }

    public function setName(?string $name): self
    {
        return $this->setData(self::KEY_NAME, $name);
    }

    public function getDescription(): ?string
    {
        return $this->getData(self::KEY_DESCRIPTION);
    }

    public function setDescription(?string $description): self
    {
        return $this->setData(self::KEY_DESCRIPTION, $description);
    }

    public function getUrl(): ?string
    {
        return $this->getData(self::KEY_URL);
    }

    public function setUrl(?string $url): self
    {
        return $this->setData(self::KEY_URL, $url);
    }

    public function getLocale(): ?string
    {
        return $this->getData(self::KEY_LOCALE);
    }

    public function setLocale(?string $locale): self
    {
        return $this->setData(self::KEY_LOCALE, $locale);
    }

    /**
     * @return SectionItem[]|null
     */
    public function getCategories(): ?array
    {
        return $this->getData(self::KEY_CATEGORIES);
    }

    /**
     * @param SectionItem[]|null $categories
     */
    public function setCategories(?array $categories): self
    {
        return $this->setData(self::KEY_CATEGORIES, $categories);
    }

    /**
     * @return SectionItem[]|null
     */
    public function getProducts(): ?array
    {
        return $this->getData(self::KEY_PRODUCTS);
    }

    /**
     * @param SectionItem[]|null $products
     */
    public function setProducts(?array $products): self
    {
        return $this->setData(self::KEY_PRODUCTS, $products);
    }

    /**
     * @return SectionItem[]|null
     */
    public function getCmsPages(): ?array
    {
        return $this->getData(self::KEY_CMS_PAGES);
    }

    /**
     * @param SectionItem[]|null $cmsPages
     */
    public function setCmsPages(?array $cmsPages): self
    {
        return $this->setData(self::KEY_CMS_PAGES, $cmsPages);
    }
}
