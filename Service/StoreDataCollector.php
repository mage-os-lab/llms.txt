<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Service;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\LlmTxt\Data\SectionItemFactory;
use MageOS\LlmTxt\Data\StoreContext;
use MageOS\LlmTxt\Data\StoreContextFactory;
use MageOS\LlmTxt\Config\Config;

class StoreDataCollector
{
    public const MAX_ENTITY_COUNT = 10;

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Emulation $emulation,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly PageCollectionFactory $pageCollectionFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Config $config,
        private readonly SectionItemFactory $sectionItemFactory,
        private readonly StoreContextFactory $storeContextFactory,
    ) {}

    public function collect(int $storeId): StoreContext
    {
        $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        try {
            $store = $this->storeManager->getStore($storeId);

            $name = $this->config->getSiteName($storeId) ?: $store->getName();
            $description = $this->config->getSiteDescription($storeId) ?: null;
            $locale = $this->getStoreLocale($storeId);

            $categories = $this->collectCategories($storeId);
            $products = $this->collectProducts($storeId);
            $cmsPages = $this->collectCmsPages($storeId);

            $storeContext = $this->storeContextFactory->create()
                ->setStoreId($storeId)
                ->setName($name)
                ->setDescription($description)
                ->setUrl($store->getBaseUrl())
                ->setLocale($locale)
                ->setCategories($categories)
                ->setProducts($products)
                ->setCmsPages($cmsPages);
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }

        return $storeContext;
    }

    private function collectCategories(int $storeId): array
    {
        $categoryIds = $this->config->getCategoryIds($storeId);
        if (!$categoryIds) {
            return [];
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'url_key', 'meta_description'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter('is_active', 1)
            ->setStoreId($storeId)
            ->setOrder('position', 'ASC')
            ->setPageSize(self::MAX_ENTITY_COUNT);

        $categories = [];
        /** @var Category $category */
        foreach ($collection as $category) {
            $name = (string) $category->getName();
            $url = $category->getUrl();
            $metaDescription = (string) $category->getMetaDescription();

            $categories[] = $this->sectionItemFactory->create()
                ->setName($name)
                ->setUrl($url)
                ->setDescription($metaDescription ?: $name);
        }

        return $categories;
    }

    private function collectProducts(int $storeId): array
    {
        $productSkus = $this->config->getProductSkus($storeId);
        if (!$productSkus) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'url_key', 'meta_description', 'sku'])
            ->addAttributeToFilter('sku', ['in' => $productSkus])
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('visibility', ['in' => [2, 3, 4]])
            ->addStoreFilter($storeId)
            ->setStoreId($storeId)
            ->setOrder('created_at', 'DESC')
            ->setPageSize(self::MAX_ENTITY_COUNT);

        $products = [];
        /** @var Product $product */
        foreach ($collection as $product) {
            $name = (string) $product->getName();
            $metaDescription = (string) $product->getMetaDescription();
            $url = $product->getProductUrl();

            $products[] = $this->sectionItemFactory->create()
                ->setName($name)
                ->setUrl($url)
                ->setDescription($metaDescription ?: $name);
        }

        return $products;
    }

    private function collectCmsPages(int $storeId): array
    {
        $pageIdentifiers = $this->config->getCmsPageIdentifiers($storeId);
        if (!$pageIdentifiers) {
            return [];
        }

        $collection = $this->pageCollectionFactory->create();
        $collection->addFieldToSelect(['identifier', 'title', 'meta_description'])
            ->addFieldToFilter('identifier', ['in' => $pageIdentifiers])
            ->addFieldToFilter('is_active', 1)
            ->addStoreFilter($storeId)
            ->setOrder('creation_time', 'DESC')
            ->setPageSize(self::MAX_ENTITY_COUNT);

        $pages = [];
        /** @var Page $page */
        foreach ($collection as $page) {
            $identifier = (string) $page->getIdentifier();
            $title = (string) $page->getTitle();
            $url = $this->urlBuilder->getUrl(null, ['_direct' => $identifier]);
            $metaDescription = (string) $page->getMetaDescription();

            $pages[] = $this->sectionItemFactory->create()
                ->setName($title)
                ->setUrl($url)
                ->setDescription($metaDescription ?: $title);
        }

        return $pages;
    }

    private function getStoreLocale(int $storeId): string
    {
        return (string) $this->scopeConfig->getValue(
            'general/locale/code',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
