<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Model;

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
use Magento\Store\Model\StoreManagerInterface;
use MageOS\LlmTxt\Model\Data\SectionItemFactory;
use MageOS\LlmTxt\Model\Data\StoreContext;
use MageOS\LlmTxt\Model\Data\StoreContextFactory;

class StoreDataCollector
{
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
            $storeLocale = (string) $this->scopeConfig->getValue('general/locale/code');

            $categories = $this->collectCategories($storeId);
            $products = $this->collectProducts($storeId);
            $cmsPages = $this->collectCmsPages($storeId);

            $storeContext = $this->storeContextFactory->create()
                ->setStoreId($storeId)
                ->setName($store->getName())
                ->setUrl($store->getBaseUrl())
                ->setLocale($storeLocale)
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
        $collection->addAttributeToSelect(['name', 'url_key', 'description'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->setOrder('position', 'ASC');

        $categories = [];
        /** @var Category $category */
        foreach ($collection as $category) {
            $categories[] = $this->sectionItemFactory->create()
                ->setName((string) $category->getName())
                ->setUrl($category->getUrl())
                ->setDescription(strip_tags((string) $category->getDescription()));
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
        $collection->addAttributeToSelect(['name', 'url_key', 'short_description', 'sku'])
            ->addAttributeToFilter('sku', ['in' => $productSkus])
            ->setOrder('created_at', 'DESC');

        $products = [];
        /** @var Product $product */
        foreach ($collection as $product) {
            $products[] = $this->sectionItemFactory->create()
                ->setName((string) $product->getName())
                ->setUrl($product->getProductUrl())
                ->setDescription(strip_tags((string) $product->getShortDescription()));
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
            ->addStoreFilter($storeId)
            ->setOrder('creation_time', 'DESC');

        $pages = [];
        /** @var Page $page */
        foreach ($collection as $page) {
            $identifier = (string) $page->getIdentifier();

            $pages[] = $this->sectionItemFactory->create()
                ->setName((string) $page->getTitle())
                ->setUrl($this->urlBuilder->getUrl(null, ['_direct' => $identifier]))
                ->setDescription((string) $page->getMetaDescription() ?: (string) $page->getTitle());
        }

        return $pages;
    }
}
